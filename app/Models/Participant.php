<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'name',
        'phone_number',
        'transcription_consent',
        'transcription_consent_given_at',
        'transcription_consent_given_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transcription_consent' => 'boolean',
            'transcription_consent_given_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if this participant has given consent for transcription.
     * CRITICAL PRIVACY CHECK - defaults to false if not explicitly set.
     *
     * @return bool
     */
    public function hasTranscriptionConsent(): bool
    {
        return $this->transcription_consent === true;
    }

    /**
     * Get the user who granted transcription consent.
     */
    public function consentGrantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transcription_consent_given_by');
    }

    /**
     * Get the chat the participant belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the messages sent by the participant.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
