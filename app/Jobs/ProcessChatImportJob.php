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

        $extractPath = null;

        try {
            $progress->addLog("Starting import process");
            $progress->update(['started_at' => now()]);

            // STAGE 1: Extract ZIP if needed
            if ($this->isZip) {
                $progress->update(['status' => 'extracting']);
                $progress->addLog("Extracting ZIP file: {$this->filePath}");

                $extractPath = storage_path('app/temp/' . Str::uuid());
                mkdir($extractPath, 0755, true);

                $zip = new \ZipArchive();
                $zipStatus = $zip->open($this->filePath);

                if ($zipStatus === true) {
                    $fileCount = $zip->numFiles;
                    $progress->addLog("ZIP opened successfully, contains {$fileCount} files");

                    $zip->extractTo($extractPath);
                    $zip->close();
                    $progress->addLog("ZIP extracted to temporary directory");

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
            $progress->update(['status' => 'parsing']);
            $progress->addLog("Parsing WhatsApp chat file");

            $messages = $parser->parseFile($txtFilePath);

            if (empty($messages)) {
                throw new \Exception('No messages found in the file');
            }

            $messageCount = count($messages);
            $progress->update(['total_messages' => $messageCount]);
            $progress->addLog("Parsed {$messageCount} messages from chat");

            // STAGE 3: Create chat
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
            $progress->addLog("Importing messages in chunks of 500");
            $this->importMessagesInChunks($chat, $messages, $progress);
            $progress->addLog("All messages imported successfully");

            // STAGE 5: Process media files from ZIP if they exist
            if ($extractPath && file_exists($extractPath)) {
                $progress->update(['status' => 'processing_media']);
                $progress->addLog("Processing media files from ZIP archive");
                $this->processMediaFiles($chat, $extractPath, $progress);
                $progress->addLog("Media processing completed");
            }

            $progress->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $progress->addLog("Import completed successfully!");

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

        foreach ($chunks as $chunk) {
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
        $progress->update(['total_media' => count(array_filter($mediaFiles, 'is_file'))]);

        foreach ($mediaFiles as $filePath) {
            if (is_file($filePath) && !str_ends_with($filePath, '.txt')) {
                try {
                    $filename = basename($filePath);
                    $mimeType = mime_content_type($filePath);
                    $fileSize = filesize($filePath);

                    $type = $this->getMediaType($mimeType);

                    // Copy file to permanent storage
                    $destinationPath = $chatMediaDir . '/' . $filename;
                    Storage::disk('media')->put(
                        $destinationPath,
                        file_get_contents($filePath)
                    );

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

                } catch (\Exception $e) {
                    Log::error('Error processing media file', [
                        'file' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
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
