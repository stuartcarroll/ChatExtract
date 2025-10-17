<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\WhisperTranscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TranscribeMediaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Media $media
    ) {
        $this->onQueue('transcriptions');
    }

    /**
     * Execute the job.
     */
    public function handle(WhisperTranscriptionService $whisper): void
    {
        // Only transcribe audio files
        if ($this->media->type !== 'audio') {
            return;
        }

        // Skip if already transcribed
        if ($this->media->transcription) {
            return;
        }

        // Mark as requested
        $this->media->update(['transcription_requested' => true]);

        // Transcribe
        $result = $whisper->transcribe($this->media->file_path);

        if ($result['transcription']) {
            // Save transcription
            $this->media->update([
                'transcription' => $result['transcription'],
                'transcribed_at' => now(),
            ]);

            Log::info('Media transcribed successfully', [
                'media_id' => $this->media->id,
                'filename' => $this->media->filename
            ]);
        } else {
            Log::error('Failed to transcribe media', [
                'media_id' => $this->media->id,
                'filename' => $this->media->filename,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Transcription job failed', [
            'media_id' => $this->media->id,
            'error' => $exception->getMessage()
        ]);
    }
}
