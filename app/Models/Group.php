<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Group extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'description', 'created_by'];

    /**
     * Get the user who created this group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users in this group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('added_by')->withTimestamps();
    }

    /**
     * Get all chat access grants for this group.
     */
    public function chatAccess(): MorphMany
    {
        return $this->morphMany(ChatAccess::class, 'accessable');
    }

    /**
     * Get all tag access grants for this group.
     */
    public function tagAccess(): MorphMany
    {
        return $this->morphMany(TagAccess::class, 'accessable');
    }
}
