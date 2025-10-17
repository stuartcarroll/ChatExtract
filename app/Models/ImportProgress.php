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
        'status',
        'total_messages',
        'processed_messages',
        'total_media',
        'processed_media',
        'images_count',
        'videos_count',
        'audio_count',
        'error_message',
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
}
