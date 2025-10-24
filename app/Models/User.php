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
     * The attributes that are not mass assignable.
     * SECURITY FIX: Guard critical fields to prevent privilege escalation.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'role',  // SECURITY FIX: Prevent privilege escalation via mass assignment
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_otp_secret',
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
            'two_factor_confirmed_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a chat user.
     */
    public function isChatUser(): bool
    {
        return $this->role === 'chat_user';
    }

    /**
     * Check if user is view-only.
     */
    public function isViewOnly(): bool
    {
        return $this->role === 'view_only';
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
     * Get the import progress records for the user.
     */
    public function importProgress(): HasMany
    {
        return $this->hasMany(ImportProgress::class);
    }

    /**
     * Get the groups this user belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withPivot('added_by')->withTimestamps();
    }

    /**
     * Get the groups created by this user.
     */
    public function createdGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    /**
     * Get all tag access grants for this user.
     */
    public function tagAccess()
    {
        return $this->morphMany(TagAccess::class, 'accessable');
    }

    /**
     * Get IDs of all tags the user can access.
     * Returns a collection of tag IDs from direct access and group access.
     */
    public function accessibleTagIds()
    {
        // Direct tag access
        $directAccess = $this->tagAccess()->pluck('tag_id');

        // Tag access via groups
        $userGroupIds = $this->groups()->pluck('groups.id');
        $groupAccess = collect();
        if ($userGroupIds->isNotEmpty()) {
            $groupAccess = TagAccess::where('accessable_type', Group::class)
                ->whereIn('accessable_id', $userGroupIds)
                ->pluck('tag_id');
        }

        return $directAccess->merge($groupAccess)->unique();
    }

    /**
     * Get all chats the user has access to (owned + shared).
     */
    public function accessibleChats()
    {
        $allChatIds = $this->accessibleChatIds();
        return Chat::whereIn('id', $allChatIds);
    }

    /**
     * Get IDs of all chats the user can access.
     */
    public function accessibleChatIds()
    {
        // Admin: all chats
        if ($this->isAdmin()) {
            return Chat::pluck('id');
        }

        // View-only: ONLY chats with messages tagged with accessible tags
        if ($this->isViewOnly()) {
            $accessibleTagIds = $this->accessibleTagIds();
            if ($accessibleTagIds->isEmpty()) {
                return collect();
            }

            return Message::whereHas('tags', function($q) use ($accessibleTagIds) {
                $q->whereIn('tags.id', $accessibleTagIds);
            })->distinct()->pluck('chat_id');
        }

        // Chat User: owned + granted access
        $ownedIds = $this->ownedChats()->pluck('id');

        $directAccessIds = ChatAccess::where('accessable_type', self::class)
            ->where('accessable_id', $this->id)
            ->pluck('chat_id');

        $userGroupIds = $this->groups()->pluck('groups.id');
        $groupAccessIds = collect();
        if ($userGroupIds->isNotEmpty()) {
            $groupAccessIds = ChatAccess::where('accessable_type', Group::class)
                ->whereIn('accessable_id', $userGroupIds)
                ->pluck('chat_id');
        }

        $legacySharedIds = $this->chats()->pluck('id');

        return $ownedIds->merge($directAccessIds)
            ->merge($groupAccessIds)
            ->merge($legacySharedIds)
            ->unique();
    }

    /**
     * Check if two-factor authentication is enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate recovery codes for two-factor authentication.
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }

        $this->two_factor_recovery_codes = encrypt(json_encode($codes));
        $this->save();

        return $codes;
    }

    /**
     * Get decrypted recovery codes.
     */
    public function getRecoveryCodes(): array
    {
        if (!$this->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(decrypt($this->two_factor_recovery_codes), true);
    }

    /**
     * Use a recovery code.
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();

        $index = array_search(strtoupper($code), $codes);

        if ($index === false) {
            return false;
        }

        // Remove the used code
        unset($codes[$index]);
        $codes = array_values($codes);

        // Save remaining codes
        if (empty($codes)) {
            $this->two_factor_recovery_codes = null;
        } else {
            $this->two_factor_recovery_codes = encrypt(json_encode($codes));
        }

        $this->save();

        return true;
    }
}
