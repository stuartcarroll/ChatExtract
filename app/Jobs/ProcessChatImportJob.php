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

    public $timeout = 3600; // 1 hour
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importProgressId,
        public string $filePath,
        public string $chatName,
        public ?string $chatDescription,
        public int $userId,
        public ?string $extractPath = null
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

        try {
            $progress->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Parse the chat file
            $messages = $parser->parseFile($this->filePath);

            if (empty($messages)) {
                throw new \Exception('No messages found in the file');
            }

            $progress->update(['total_messages' => count($messages)]);

            // Create the chat
            $chat = Chat::create([
                'name' => $this->chatName,
                'description' => $this->chatDescription,
                'user_id' => $this->userId,
            ]);

            $chat->users()->attach($this->userId);

            $progress->update(['chat_id' => $chat->id]);

            // Import messages in chunks to avoid memory issues
            $this->importMessagesInChunks($chat, $messages, $progress);

            // Process media files from ZIP if they exist
            if ($this->extractPath && file_exists($this->extractPath)) {
                $this->processMediaFiles($chat, $this->extractPath, $progress);
            }

            $progress->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Cleanup temporary extraction directory
            if ($this->extractPath && file_exists($this->extractPath)) {
                $this->deleteDirectory($this->extractPath);
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
            if ($this->extractPath && file_exists($this->extractPath)) {
                $this->deleteDirectory($this->extractPath);
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
