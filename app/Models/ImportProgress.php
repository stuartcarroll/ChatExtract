<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportProgress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'import_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'chat_id',
        'filename',
        'file_path',
        'is_zip',
        'status',
        'upload_id',
        'total_chunks',
        'uploaded_chunks',
        'total_messages',
        'processed_messages',
        'total_media',
        'processed_media',
        'images_count',
        'videos_count',
        'audio_count',
        'error_message',
        'processing_log',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_zip' => 'boolean',
            'total_messages' => 'integer',
            'processed_messages' => 'integer',
            'total_media' => 'integer',
            'processed_media' => 'integer',
            'images_count' => 'integer',
            'videos_count' => 'integer',
            'audio_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who owns this import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chat this import created.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_messages === 0) {
            return 0;
        }

        return (int) (($this->processed_messages / $this->total_messages) * 100);
    }

    /**
     * Get media progress percentage.
     */
    public function getMediaProgressPercentageAttribute(): int
    {
        if ($this->total_media === 0) {
            return 100;
        }

        return (int) (($this->processed_media / $this->total_media) * 100);
    }

    /**
     * Add a log entry to the processing log.
     */
    public function addLog(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";

        $this->update([
            'processing_log' => ($this->processing_log ?? '') . $logEntry
        ]);
    }

    /**
     * Check if this import can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed'
            && $this->file_path
            && file_exists(storage_path('app/' . $this->file_path));
    }

    /**
     * Reset this import for retry.
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status' => 'pending',
            'total_messages' => 0,
            'processed_messages' => 0,
            'total_media' => 0,
            'processed_media' => 0,
            'images_count' => 0,
            'videos_count' => 0,
            'audio_count' => 0,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'processing_log' => ($this->processing_log ?? '') . "\n--- RETRY STARTED AT " . now()->format('Y-m-d H:i:s') . " ---\n",
        ]);
    }
}
