<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Message extends Model
{
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'participant_id',
        'content',
        'sent_at',
        'message_hash',
        'is_system_message',
        'is_story',
        'story_confidence',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'is_system_message' => 'boolean',
            'is_story' => 'boolean',
            'story_confidence' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Include audio transcriptions in searchable content
        $transcriptions = $this->media()
            ->where('type', 'audio')
            ->whereNotNull('transcription')
            ->pluck('transcription')
            ->join(' ');

        return [
            'id' => $this->id,
            'content' => trim($this->content . ' ' . $transcriptions),
            'sent_at' => $this->sent_at->timestamp,
            'is_story' => $this->is_story,
            'is_system_message' => $this->is_system_message,
            'chat_id' => $this->chat_id,
            'participant_name' => $this->participant?->name,
        ];
    }

    /**
     * Get the chat the message belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the participant who sent the message.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the media attachments for the message.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Get the tags associated with the message.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
