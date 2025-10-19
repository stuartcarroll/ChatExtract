<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatAccess extends Model
{
    protected $table = 'chat_access';

    protected $fillable = [
        'chat_id',
        'accessable_type',
        'accessable_id',
        'permission',
        'granted_by',
    ];

    protected $guarded = ['granted_by'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the chat this access grant belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the entity (user or group) that has access.
     */
    public function accessable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who granted this access.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
