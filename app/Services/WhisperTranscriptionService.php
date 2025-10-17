<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WhisperTranscriptionService
{
    protected ?string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/audio/transcriptions';
    protected string $model = 'whisper-1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Transcribe an audio file using OpenAI Whisper API.
     *
     * @param string $filePath Path to the audio file in storage
     * @return array{transcription: string|null, error: string|null}
     */
    public function transcribe(string $filePath): array
    {
        try {
            // Check if API key is configured
            if (empty($this->apiKey)) {
                return [
                    'transcription' => null,
                    'error' => 'OpenAI API key not configured'
                ];
            }

            // Strip media/ prefix if present since the disk root already points to media
            $filePath = str_replace('media/', '', $filePath);
            $fullPath = Storage::disk('media')->path($filePath);

            if (!file_exists($fullPath)) {
                return [
                    'transcription' => null,
                    'error' => 'File not found: ' . $filePath
                ];
            }

            // Convert .opus to .mp3 if needed
            $convertedPath = null;
            $fileToUpload = $fullPath;
            
            if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'opus') {
                $convertedPath = $this->convertOpusToMp3($fullPath);
                if ($convertedPath === null) {
                    return [
                        'transcription' => null,
                        'error' => 'Failed to convert .opus file to .mp3'
                    ];
                }
                $fileToUpload = $convertedPath;
            }

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file',
                file_get_contents($fileToUpload),
                basename($fileToUpload)
            )->post($this->apiUrl, [
                'model' => $this->model,
                'language' => 'en',
                'response_format' => 'json',
            ]);

            // Clean up converted file
            if ($convertedPath && file_exists($convertedPath)) {
                unlink($convertedPath);
            }

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'transcription' => $data['text'] ?? null,
                    'error' => null
                ];
            }

            // Log the error
            Log::error('Whisper API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'transcription' => null,
                'error' => 'API error: ' . $response->status()
            ];

        } catch (\Exception $e) {
            // Clean up converted file if it exists
            if (isset($convertedPath) && $convertedPath && file_exists($convertedPath)) {
                unlink($convertedPath);
            }

            Log::error('Whisper transcription exception', [
                'message' => $e->getMessage(),
                'file' => $filePath
            ]);

            return [
                'transcription' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert .opus file to .mp3 using FFmpeg.
     *
     * @param string $opusPath Path to the .opus file
     * @return string|null Path to the converted .mp3 file, or null on failure
     */
    protected function convertOpusToMp3(string $opusPath): ?string
    {
        try {
            // Create temp file for mp3
            $mp3Path = sys_get_temp_dir() . '/' . uniqid('whisper_') . '.mp3';

            // Convert using FFmpeg
            $command = sprintf(
                'ffmpeg -i %s -codec:a libmp3lame -qscale:a 2 %s 2>&1',
                escapeshellarg($opusPath),
                escapeshellarg($mp3Path)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($mp3Path)) {
                Log::error('FFmpeg conversion failed', [
                    'command' => $command,
                    'output' => implode("\n", $output),
                    'return_code' => $returnCode
                ]);
                return null;
            }

            return $mp3Path;

        } catch (\Exception $e) {
            Log::error('FFmpeg conversion exception', [
                'message' => $e->getMessage(),
                'file' => $opusPath
            ]);
            return null;
        }
    }

    /**
     * Check if the service is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
