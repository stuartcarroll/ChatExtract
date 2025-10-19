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
     *
     * @var list<string>
     */
    protected $guarded = ['is_admin', 'email_verified_at'];

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
        $allChatIds = $this->accessibleChatIds();
        return Chat::whereIn('id', $allChatIds);
    }

    /**
     * Get IDs of all chats the user can access.
     */
    public function accessibleChatIds()
    {
        // Get owned chat IDs
        $ownedIds = $this->ownedChats()->pluck('id');

        // Get chats with direct user access via chat_access table
        $directAccessIds = \App\Models\ChatAccess::where('accessable_type', self::class)
            ->where('accessable_id', $this->id)
            ->pluck('chat_id');

        // Group access not currently supported
        // $userGroupIds = \App\Models\GroupUser::where('user_id', $this->id)->pluck('group_id');
        $groupAccessIds = collect();
        // if ($userGroupIds->isNotEmpty()) {
        //     $groupAccessIds = \App\Models\ChatAccess::where('accessable_type', \App\Models\Group::class)
        //         ->whereIn('accessable_id', $userGroupIds)
        //         ->pluck('chat_id');
        // }

        // Legacy: Get shared chat IDs from old chat_user pivot table
        $legacySharedIds = $this->chats()->pluck('id');

        // Merge and get unique IDs
        return $ownedIds
            ->merge($directAccessIds)
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
