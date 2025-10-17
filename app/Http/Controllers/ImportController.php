<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Participant;
use App\Models\Message;
use App\Services\WhatsAppParserService;
use App\Jobs\DetectStoryJob;
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
     * Handle the file upload and parsing.
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
            DB::beginTransaction();

            // Get the uploaded file
            $file = $request->file('chat_file');
            $filePath = $file->getRealPath();

            // Handle ZIP files
            if ($file->getClientOriginalExtension() === 'zip') {
                $extractPath = storage_path('app/temp/' . Str::uuid());
                mkdir($extractPath, 0755, true);

                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    $zip->extractTo($extractPath);
                    $zip->close();

                    // Find the .txt file in the extracted content
                    $files = glob($extractPath . '/*.txt');
                    if (empty($files)) {
                        throw new \Exception('No .txt file found in ZIP archive');
                    }

                    $filePath = $files[0];

                    // Store media files directory for later processing
                    $mediaPath = $extractPath;
                } else {
                    throw new \Exception('Failed to extract ZIP file');
                }
            }

            // Parse the chat file
            $messages = $this->parser->parseFile($filePath);

            if (empty($messages)) {
                return back()->withErrors(['chat_file' => 'No messages found in the file. Please check the file format.']);
            }

            // Create the chat
            $chat = Chat::create([
                'name' => $request->chat_name,
                'description' => $request->chat_description,
                'user_id' => auth()->id(),
            ]);

            // Give the owner access to the chat
            $chat->users()->attach(auth()->id());

            // Import messages and handle media files if present
            $importedCount = $this->importMessages($chat, $messages, $mediaPath ?? null);

            // Process media files from ZIP if they exist
            if (isset($mediaPath) && file_exists($mediaPath)) {
                $this->processMediaFiles($chat, $mediaPath);
            }

            DB::commit();

            // Cleanup temporary extraction directory if it was a ZIP
            if (isset($extractPath) && file_exists($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            return redirect()->route('chats.show', $chat)
                ->with('success', "Successfully imported {$importedCount} messages.");
        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup on error
            if (isset($extractPath) && file_exists($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            return back()->withErrors(['chat_file' => 'Error importing chat: ' . $e->getMessage()]);
        }
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
        $chatMediaDir = "media/chat_{$chat->id}";
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
                        'file_path' => $destinationPath,
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
}
