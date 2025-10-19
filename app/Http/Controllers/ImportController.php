<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Participant;
use App\Models\Message;
use App\Models\ImportProgress;
use App\Services\WhatsAppParserService;
use App\Jobs\DetectStoryJob;
use App\Jobs\ProcessChatImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    protected WhatsAppParserService $parser;

    public function __construct(WhatsAppParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Show the import form.
     */
    public function create()
    {
        return view('chats.import');
    }

    /**
     * Handle the file upload and queue import job.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'chat_file' => 'required|file|mimes:txt,zip|max:10485760', // Max 10GB (10240MB)
            'chat_name' => 'required|string|max:255',
            'chat_description' => 'nullable|string|max:1000',
        ]);

        try {
            // Get the uploaded file
            $file = $request->file('chat_file');
            // Sanitize filename to prevent path traversal
            $filename = basename($file->getClientOriginalName());
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $isZip = $file->getClientOriginalExtension() === 'zip';

            // Store file quickly - just save to disk, don't extract yet
            $storedPath = $file->store('imports', 'local');
            $fullPath = storage_path('app/' . $storedPath);

            // Create import progress record immediately
            $progress = ImportProgress::create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'file_path' => $storedPath,
                'is_zip' => $isZip,
                'status' => 'uploading', // New status to show upload complete
            ]);

            $progress->addLog("File uploaded: {$filename} (" . round($file->getSize() / 1024 / 1024, 2) . " MB)");

            // Dispatch the job - it will handle extraction and parsing
            ProcessChatImportJob::dispatch(
                $progress->id,
                $fullPath,
                $request->chat_name,
                $request->chat_description,
                auth()->id(),
                $isZip // Pass flag instead of extractPath
            );

            // Immediately redirect to progress page
            return redirect()->route('import.progress', $progress)
                ->with('success', 'File uploaded! Processing will begin shortly...');

        } catch (\Exception $e) {
            return back()->withErrors(['chat_file' => 'Error starting import: ' . $e->getMessage()]);
        }
    }

    /**
     * Show import progress page.
     */
    public function progress(ImportProgress $progress)
    {
        // Authorize the user
        if ($progress->user_id !== auth()->id()) {
            abort(403);
        }

        return view('import.progress', compact('progress'));
    }

    /**
     * Get import progress as JSON (for AJAX polling).
     */
    public function progressStatus(ImportProgress $progress)
    {
        // Authorize the user
        if ($progress->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'status' => $progress->status,
            'total_messages' => $progress->total_messages,
            'processed_messages' => $progress->processed_messages,
            'total_media' => $progress->total_media,
            'processed_media' => $progress->processed_media,
            'images_count' => $progress->images_count,
            'videos_count' => $progress->videos_count,
            'audio_count' => $progress->audio_count,
            'progress_percentage' => $progress->progress_percentage,
            'media_progress_percentage' => $progress->media_progress_percentage,
            'error_message' => $progress->error_message,
            'processing_log' => $progress->processing_log,
            'chat_id' => $progress->chat_id,
        ]);
    }

    /**
     * Show dashboard with all import jobs.
     */
    public function dashboard()
    {
        $imports = auth()->user()
            ->importProgress()
            ->with('chat')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('chats.import-dashboard', compact('imports'));
    }

    /**
     * Get all imports status as JSON (for dashboard polling).
     */
    public function dashboardStatus()
    {
        $imports = auth()->user()
            ->importProgress()
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get([
                'id',
                'filename',
                'status',
                'total_messages',
                'processed_messages',
                'total_media',
                'processed_media',
                'images_count',
                'videos_count',
                'audio_count',
                'error_message',
                'chat_id',
                'created_at',
                'started_at',
                'completed_at'
            ])
            ->map(function ($import) {
                $import->progress_percentage = $import->progress_percentage;
                $import->media_progress_percentage = $import->media_progress_percentage;
                return $import;
            });

        return response()->json($imports);
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Process and store media files from ZIP archive.
     */
    protected function processMediaFiles(Chat $chat, string $mediaPath): void
    {
        // Create permanent storage directory for this chat's media
        $chatMediaDir = "chat_{$chat->id}";
        Storage::disk('media')->makeDirectory($chatMediaDir);

        // Get all media files (excluding .txt files)
        $mediaFiles = glob($mediaPath . '/*');

        foreach ($mediaFiles as $filePath) {
            if (is_file($filePath) && !str_ends_with($filePath, '.txt')) {
                $filename = basename($filePath);
                $mimeType = mime_content_type($filePath);
                $fileSize = filesize($filePath);

                // Determine media type
                $type = $this->getMediaType($mimeType);

                // Copy file to permanent storage
                $destinationPath = $chatMediaDir . '/' . $filename;
                Storage::disk('media')->put(
                    $destinationPath,
                    file_get_contents($filePath)
                );

                // Store the path relative to storage/app/public for asset() access
                $publicPath = 'media/' . $destinationPath;

                // Find the message that references this media file
                $message = Message::where('chat_id', $chat->id)
                    ->where('content', 'LIKE', '%' . $filename . '%')
                    ->first();

                if ($message) {
                    // Create media record
                    \App\Models\Media::create([
                        'message_id' => $message->id,
                        'type' => $type,
                        'filename' => $filename,
                        'file_path' => $publicPath,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                    ]);
                }
            }
        }
    }

    /**
     * Determine media type from MIME type.
     */
    protected function getMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * Import messages into the database.
     */
    protected function importMessages(Chat $chat, array $messages, ?string $mediaPath = null): int
    {
        $participantCache = [];
        $importedCount = 0;

        foreach ($messages as $messageData) {
            try {
                // Get or create participant
                $participantName = $messageData['participant'];

                if (!isset($participantCache[$participantName])) {
                    $participant = Participant::firstOrCreate(
                        [
                            'chat_id' => $chat->id,
                            'name' => $participantName,
                        ],
                        [
                            'phone_number' => null,
                        ]
                    );
                    $participantCache[$participantName] = $participant->id;
                } else {
                    $participantId = $participantCache[$participantName];
                }

                // Create message hash for deduplication
                $messageHash = $this->createMessageHash(
                    $chat->id,
                    $messageData['timestamp'],
                    $participantName,
                    $messageData['content']
                );

                // Check if message already exists
                if (Message::where('message_hash', $messageHash)->exists()) {
                    continue; // Skip duplicate
                }

                // Create the message
                $message = Message::create([
                    'chat_id' => $chat->id,
                    'participant_id' => $participantCache[$participantName] ?? null,
                    'content' => $messageData['content'],
                    'sent_at' => $messageData['timestamp'],
                    'message_hash' => $messageHash,
                    'is_system_message' => $messageData['is_system_message'],
                    'is_story' => false, // Will be determined by job
                    'story_confidence' => null,
                ]);

                $importedCount++;

                // Handle media attachments
                if ($messageData['has_media'] && $messageData['media_filename']) {
                    // Note: This is a placeholder. Actual media files need to be uploaded separately
                    // and matched by filename
                    $this->handleMediaAttachment($message, $messageData);
                }

                // Queue story detection for non-system messages
                if (!$messageData['is_system_message']) {
                    DetectStoryJob::dispatch($message)->onQueue('story-detection');
                }
            } catch (\Exception $e) {
                // Log error but continue with other messages
                \Log::error('Error importing message: ' . $e->getMessage(), [
                    'message' => $messageData,
                ]);
            }
        }

        return $importedCount;
    }

    /**
     * Create a unique hash for message deduplication.
     */
    protected function createMessageHash(int $chatId, $timestamp, string $participant, string $content): string
    {
        $data = $chatId . '|' . $timestamp->format('Y-m-d H:i:s') . '|' . $participant . '|' . $content;
        return hash('sha256', $data);
    }

    /**
     * Handle media attachment for a message.
     */
    protected function handleMediaAttachment(Message $message, array $messageData): void
    {
        // This is a placeholder for media handling
        // In a real implementation, you would:
        // 1. Look for the media file in an uploads directory
        // 2. Store it in the media disk
        // 3. Create a Media record

        // Example implementation (requires media files to be uploaded):
        /*
        $mediaType = $messageData['media_type'];
        $filename = $messageData['media_filename'];

        $media = Media::create([
            'message_id' => $message->id,
            'type' => $mediaType,
            'filename' => $filename,
            'file_path' => 'media/' . $filename,
            'file_size' => null,
            'mime_type' => null,
        ]);
        */
    }

    /**
     * Upload media files for a chat.
     */
    public function uploadMedia(Request $request, Chat $chat)
    {
        // Authorize the user
        $this->authorize('update', $chat);

        // Validate the request
        $request->validate([
            'media_files' => 'required|array',
            'media_files.*' => 'file|max:10485760', // Max 10GB per file
        ]);

        $uploadedCount = 0;

        foreach ($request->file('media_files') as $file) {
            try {
                $filename = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                // Determine media type from mime type
                $mediaType = $this->getMediaTypeFromMime($mimeType);

                // Store the file
                $path = $file->store('media', 'media');

                // Find the message with this media reference
                $message = Message::where('chat_id', $chat->id)
                    ->where('content', 'like', "%{$filename}%")
                    ->whereDoesntHave('media')
                    ->first();

                if ($message) {
                    $message->media()->create([
                        'type' => $mediaType,
                        'filename' => $filename,
                        'file_path' => $path,
                        'file_size' => $size,
                        'mime_type' => $mimeType,
                    ]);

                    $uploadedCount++;
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading media file: ' . $e->getMessage(), [
                    'filename' => $filename ?? 'unknown',
                ]);
            }
        }

        return back()->with('success', "Successfully uploaded {$uploadedCount} media files.");
    }

    /**
     * Get media type from MIME type.
     */
    protected function getMediaTypeFromMime(string $mimeType): string
    {
        if (Str::startsWith($mimeType, 'image/')) {
            return 'image';
        } elseif (Str::startsWith($mimeType, 'video/')) {
            return 'video';
        } elseif (Str::startsWith($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * Retry a failed import.
     */
    public function retry(ImportProgress $progress)
    {
        // Authorize the user
        if ($progress->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if import can be retried
        if (!$progress->canRetry()) {
            return back()->withErrors(['error' => 'This import cannot be retried. File may be missing or import is not in failed state.']);
        }

        try {
            // Get chat info from failed import or use defaults
            $chatName = $progress->chat->name ?? $progress->filename;
            $chatDescription = $progress->chat->description ?? null;

            // Reset the progress
            $progress->resetForRetry();

            // Re-dispatch the job with stored file path
            ProcessChatImportJob::dispatch(
                $progress->id,
                storage_path('app/' . $progress->file_path),
                $chatName,
                $chatDescription,
                auth()->id(),
                $progress->is_zip
            );

            return redirect()->route('import.progress', $progress)
                ->with('success', 'Import retry started!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error retrying import: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel an in-progress import.
     */
    public function cancel(ImportProgress $progress)
    {
        // Authorize the user
        if ($progress->user_id !== auth()->id()) {
            abort(403);
        }

        // Can only cancel imports that are in progress
        $cancellableStatuses = ['uploading', 'processing', 'parsing', 'extracting', 'creating_chat', 'importing_messages', 'processing_media'];

        if (!in_array($progress->status, $cancellableStatuses)) {
            return back()->withErrors(['error' => 'This import cannot be cancelled. It may have already completed or failed.']);
        }

        try {
            // Update status to cancelled
            $progress->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            $progress->addLog("Import cancelled by user at " . now()->format('Y-m-d H:i:s'));

            // Note: The actual job may still be running in the queue.
            // The job should check the status and exit gracefully if cancelled.

            return redirect()->route('import.dashboard')
                ->with('success', 'Import has been cancelled.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error cancelling import: ' . $e->getMessage()]);
        }
    }
}
