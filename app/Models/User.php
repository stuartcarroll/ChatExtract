<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin ?? false;
    }

    /**
     * Get the chats owned by the user.
     */
    public function ownedChats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get the chats the user has access to.
     */
    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class);
    }

    /**
     * Get the tags created by the user.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the import progress records for the user.
     */
    public function importProgress(): HasMany
    {
        return $this->hasMany(ImportProgress::class);
    }

    /**
     * Get all chats the user has access to (owned + shared).
     */
    public function accessibleChats()
    {
        // Get owned chat IDs
        $ownedIds = $this->ownedChats()->pluck('id');

        // Get shared chat IDs
        $sharedIds = $this->chats()->pluck('id');

        // Merge and get unique IDs
        $allChatIds = $ownedIds->merge($sharedIds)->unique();

        // Return query builder for all accessible chats
        return Chat::whereIn('id', $allChatIds);
    }
}
