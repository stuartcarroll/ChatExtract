<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TagAccess extends Model
{
    /**
     * The database table name.
     *
     * @var string
     */
    protected $table = 'tag_access';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['tag_id', 'accessable_type', 'accessable_id', 'granted_by'];

    /**
     * Get the tag this access grant belongs to.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
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
