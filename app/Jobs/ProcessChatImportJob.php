<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\ImportProgress;
use App\Models\Message;
use App\Models\Participant;
use App\Services\WhatsAppParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessChatImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hours for very large imports
    public $tries = 3; // Retry up to 3 times on failure
    public $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importProgressId,
        public string $filePath,
        public string $chatName,
        public ?string $chatDescription,
        public int $userId,
        public bool $isZip = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppParserService $parser): void
    {
        $progress = ImportProgress::find($this->importProgressId);

        if (!$progress) {
            Log::error('Import progress not found', ['id' => $this->importProgressId]);
            return;
        }

        // Check if import was cancelled before we even started
        if ($progress->status === 'cancelled') {
            Log::info('Import was cancelled before processing started', ['id' => $this->importProgressId]);
            return;
        }

        $extractPath = null;

        try {
            $progress->addLog("Starting import process");
            $progress->update(['started_at' => now()]);

            // Check if file exists
            if (!file_exists($this->filePath)) {
                throw new \Exception("Import file not found at: {$this->filePath}. The file may have been deleted or moved.");
            }

            // STAGE 1: Extract ZIP if needed
            if ($this->isZip) {
                $progress->update(['status' => 'extracting']);
                $progress->addLog("Starting ZIP extraction: {$this->filePath}");

                $fileSize = filesize($this->filePath);
                $progress->addLog("ZIP file size: " . round($fileSize / 1024 / 1024, 2) . " MB");

                $extractPath = storage_path('app/temp/' . Str::uuid());
                mkdir($extractPath, 0755, true);
                $progress->addLog("Created extraction directory: " . basename($extractPath));

                $zip = new \ZipArchive();
                $zipStatus = $zip->open($this->filePath);

                if ($zipStatus === true) {
                    $fileCount = $zip->numFiles;
                    $progress->addLog("ZIP opened successfully, contains {$fileCount} files");
                    $progress->addLog("Extracting {$fileCount} files...");

                    $zip->extractTo($extractPath);
                    $zip->close();
                    $progress->addLog("ZIP extraction completed successfully");

                    // Find the .txt file in the extracted content
                    $files = glob($extractPath . '/*.txt');
                    if (empty($files)) {
                        throw new \Exception('No .txt file found in ZIP archive');
                    }

                    $txtFilePath = $files[0];
                    $progress->addLog("Found chat file: " . basename($txtFilePath));
                } else {
                    // Get zip error message
                    $zipErrors = [
                        \ZipArchive::ER_EXISTS => 'File already exists',
                        \ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                        \ZipArchive::ER_INVAL => 'Invalid argument',
                        \ZipArchive::ER_MEMORY => 'Malloc failure',
                        \ZipArchive::ER_NOENT => 'No such file',
                        \ZipArchive::ER_NOZIP => 'Not a zip archive',
                        \ZipArchive::ER_OPEN => 'Can\'t open file',
                        \ZipArchive::ER_READ => 'Read error',
                        \ZipArchive::ER_SEEK => 'Seek error',
                    ];

                    $errorMsg = $zipErrors[$zipStatus] ?? 'Unknown error';
                    Log::error('ZIP extraction failed', [
                        'file' => $this->filePath,
                        'error_code' => $zipStatus,
                        'error_msg' => $errorMsg,
                        'file_exists' => file_exists($this->filePath),
                        'file_size' => file_exists($this->filePath) ? filesize($this->filePath) : 0,
                        'is_readable' => file_exists($this->filePath) ? is_readable($this->filePath) : false,
                    ]);
                    throw new \Exception("Failed to extract ZIP file: $errorMsg (code: $zipStatus)");
                }
            } else {
                $txtFilePath = $this->filePath;
            }

            // STAGE 2: Parse messages
            $progress->refresh();
            if ($progress->status === 'cancelled') {
                $progress->addLog("Import cancelled during extraction");
                return;
            }

            $progress->update(['status' => 'parsing']);
            $progress->addLog("Starting WhatsApp chat file parsing");
            $progress->addLog("Reading file: " . basename($txtFilePath));

            $fileSize = filesize($txtFilePath);
            $progress->addLog("Chat file size: " . round($fileSize / 1024, 2) . " KB");

            if ($fileSize > 1024 * 1024) { // > 1MB
                $progress->addLog("Large file detected - this may take 1-2 minutes...");
            }

            $parseStartTime = microtime(true);
            $messages = $parser->parseFile($txtFilePath);
            $parseEndTime = microtime(true);
            $parseDuration = round($parseEndTime - $parseStartTime, 2);

            if (empty($messages)) {
                throw new \Exception('No messages found in the file');
            }

            $messageCount = count($messages);
            $progress->update(['total_messages' => $messageCount]);
            $progress->addLog("Successfully parsed {$messageCount} messages in {$parseDuration} seconds");
            $progress->addLog("Average: " . round($messageCount / $parseDuration, 0) . " messages/second");
            $progress->addLog("Detecting participants and media references...");

            // STAGE 3: Create chat
            $progress->refresh();
            if ($progress->status === 'cancelled') {
                $progress->addLog("Import cancelled during parsing");
                return;
            }

            $progress->update(['status' => 'creating_chat']);
            $progress->addLog("Creating chat: {$this->chatName}");

            $chat = Chat::create([
                'name' => $this->chatName,
                'description' => $this->chatDescription,
                'user_id' => $this->userId,
            ]);

            $chat->users()->attach($this->userId);
            $progress->update(['chat_id' => $chat->id]);
            $progress->addLog("Chat created with ID: {$chat->id}");

            // STAGE 4: Import messages in chunks
            $progress->update(['status' => 'importing_messages']);
            $progress->addLog("Starting message import to database");
            $estimatedImportTime = ceil($messageCount / 500) * 2; // Rough estimate: 2 seconds per 500 messages
            $progress->addLog("Importing {$messageCount} messages in chunks of 500...");
            $progress->addLog("Estimated import time: ~{$estimatedImportTime} seconds");

            $importStartTime = microtime(true);
            $this->importMessagesInChunks($chat, $messages, $progress);
            $importEndTime = microtime(true);
            $importDuration = round($importEndTime - $importStartTime, 2);

            $progress->addLog("All {$messageCount} messages imported in {$importDuration} seconds");
            $progress->addLog("Import rate: " . round($messageCount / $importDuration, 0) . " messages/second");

            // STAGE 5: Process media files from ZIP if they exist
            if ($extractPath && file_exists($extractPath)) {
                $progress->update(['status' => 'processing_media']);

                // Count media files
                $mediaFiles = glob($extractPath . '/*');
                $mediaCount = count(array_filter($mediaFiles, fn($f) => is_file($f) && !str_ends_with($f, '.txt')));

                $progress->addLog("Found {$mediaCount} media files in ZIP archive");

                if ($mediaCount > 0) {
                    $estimatedMediaTime = ceil($mediaCount / 10); // Rough estimate: 10 files per second
                    $progress->addLog("Estimated processing time: ~{$estimatedMediaTime} seconds");
                    $progress->addLog("Processing media files (copying and matching with messages)...");

                    $mediaStartTime = microtime(true);
                    $this->processMediaFiles($chat, $extractPath, $progress);
                    $mediaEndTime = microtime(true);
                    $mediaDuration = round($mediaEndTime - $mediaStartTime, 2);

                    $progress->addLog("Media processing completed in {$mediaDuration} seconds");
                    $progress->addLog("Imported {$progress->processed_media} / {$mediaCount} files");
                    $progress->addLog("Breakdown: {$progress->images_count} images, {$progress->videos_count} videos, {$progress->audio_count} audio files");

                    if ($progress->processed_media < $mediaCount) {
                        $unmatched = $mediaCount - $progress->processed_media;
                        $progress->addLog("Note: {$unmatched} media files could not be matched to messages");
                    }
                }
            }

            $progress->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $progress->addLog("Import completed successfully!");

            // Dispatch throttled indexing job for background processing
            // Note: IndexMessagesJob is not yet implemented
            // $progress->addLog("Scheduling background indexing (50 messages per 10 seconds)");
            // IndexMessagesJob::dispatch($chat->id, 50, 0)->onQueue('indexing');

            // Cleanup temporary files
            if ($extractPath && file_exists($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            // Cleanup stored import file
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

        } catch (\Exception $e) {
            Log::error('Import failed', [
                'progress_id' => $this->importProgressId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $progress->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Cleanup on error
            if ($extractPath && file_exists($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

            throw $e;
        }
    }

    /**
     * Import messages in chunks to avoid memory issues.
     */
    protected function importMessagesInChunks(Chat $chat, array $messages, ImportProgress $progress): void
    {
        $participantCache = [];
        $chunkSize = 500;
        $chunks = array_chunk($messages, $chunkSize);
        $totalChunks = count($chunks);

        foreach ($chunks as $chunkIndex => $chunk) {
            // Log progress every 5 chunks
            if (($chunkIndex + 1) % 5 === 0 || $chunkIndex === 0 || $chunkIndex === $totalChunks - 1) {
                $progress->addLog("Processing message chunk " . ($chunkIndex + 1) . "/{$totalChunks} ({$progress->processed_messages}/{$progress->total_messages} messages)");
            }

            DB::transaction(function () use ($chat, $chunk, $progress, &$participantCache) {
                foreach ($chunk as $messageData) {
                    try {
                        // Get or create participant
                        $participantName = $messageData['participant'];

                        if (!isset($participantCache[$participantName])) {
                            $participant = Participant::firstOrCreate(
                                [
                                    'chat_id' => $chat->id,
                                    'name' => $participantName,
                                ],
                                ['phone_number' => null]
                            );
                            $participantCache[$participantName] = $participant->id;
                        }

                        // Create message hash for deduplication
                        $messageHash = $this->createMessageHash(
                            $chat->id,
                            $messageData['timestamp'],
                            $participantName,
                            $messageData['content']
                        );

                        // Skip if message already exists
                        if (Message::where('message_hash', $messageHash)->exists()) {
                            continue;
                        }

                        // Create the message
                        Message::create([
                            'chat_id' => $chat->id,
                            'participant_id' => $participantCache[$participantName],
                            'content' => $messageData['content'],
                            'sent_at' => $messageData['timestamp'],
                            'message_hash' => $messageHash,
                            'is_system_message' => $messageData['is_system_message'],
                            'is_story' => false,
                            'story_confidence' => null,
                        ]);

                        // Update progress
                        $progress->increment('processed_messages');

                    } catch (\Exception $e) {
                        Log::error('Error importing message', [
                            'message' => $messageData,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        }
    }

    /**
     * Process and store media files from ZIP archive.
     */
    protected function processMediaFiles(Chat $chat, string $mediaPath, ImportProgress $progress): void
    {
        $chatMediaDir = "chat_{$chat->id}";
        Storage::disk('media')->makeDirectory($chatMediaDir);

        $mediaFiles = glob($mediaPath . '/*');
        $totalMediaFiles = count(array_filter($mediaFiles, 'is_file'));
        $progress->update(['total_media' => $totalMediaFiles]);
        $processedCount = 0;
        $logInterval = max(1, ceil($totalMediaFiles / 10)); // Log every 10% or at least every file if < 10 files

        foreach ($mediaFiles as $filePath) {
            if (is_file($filePath) && !str_ends_with($filePath, '.txt')) {
                try {
                    $filename = basename($filePath);
                    $mimeType = mime_content_type($filePath);
                    $fileSize = filesize($filePath);

                    $type = $this->getMediaType($mimeType);

                    // Copy file to permanent storage using streaming to avoid memory issues
                    $destinationPath = $chatMediaDir . '/' . $filename;
                    $stream = fopen($filePath, 'r');
                    Storage::disk('media')->put($destinationPath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    $publicPath = 'media/' . $destinationPath;

                    // Find the message that references this media file
                    $message = Message::where('chat_id', $chat->id)
                        ->where('content', 'LIKE', '%' . $filename . '%')
                        ->first();

                    if ($message) {
                        \App\Models\Media::create([
                            'message_id' => $message->id,
                            'type' => $type,
                            'filename' => $filename,
                            'file_path' => $publicPath,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                        ]);

                        // Update type-specific counts
                        if ($type === 'image') {
                            $progress->increment('images_count');
                        } elseif ($type === 'video') {
                            $progress->increment('videos_count');
                        } elseif ($type === 'audio') {
                            $progress->increment('audio_count');
                        }
                    }

                    $progress->increment('processed_media');
                    $processedCount++;

                    // Log progress at intervals
                    if ($processedCount % $logInterval === 0 || $processedCount === $totalMediaFiles) {
                        $percentage = round(($processedCount / $totalMediaFiles) * 100, 1);
                        $progress->addLog("Media progress: {$processedCount}/{$totalMediaFiles} files ({$percentage}%)");
                    }

                } catch (\Exception $e) {
                    Log::error('Error processing media file', [
                        'file' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
                    $progress->increment('processed_media'); // Count failed files too
                    $processedCount++;
                }
            }
        }
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
        }
        return 'document';
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
}
