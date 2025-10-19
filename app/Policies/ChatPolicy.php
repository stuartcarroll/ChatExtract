<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChatPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Chat $chat): bool
    {
        // User can view if they own the chat
        if ($chat->user_id === $user->id) {
            return true;
        }

        // Check if user has direct access via chat_access table
        $hasDirectAccess = $chat->access()
            ->where('accessable_type', \App\Models\User::class)
            ->where('accessable_id', $user->id)
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        // Group access not currently supported
        // $userGroupIds = \App\Models\GroupUser::where('user_id', $user->id)->pluck('group_id');
        // if ($userGroupIds->isNotEmpty()) {
        //     $hasGroupAccess = $chat->access()
        //         ->where('accessable_type', \App\Models\Group::class)
        //         ->whereIn('accessable_id', $userGroupIds)
        //         ->exists();
        //
        //     if ($hasGroupAccess) {
        //         return true;
        //     }
        // }

        // Legacy: Check old chat_user pivot table
        return $chat->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Chat $chat): bool
    {
        // Only the owner can update the chat
        return $chat->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Chat $chat): bool
    {
        // Only the owner can delete the chat
        return $chat->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Chat $chat): bool
    {
        return $chat->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Chat $chat): bool
    {
        return $chat->user_id === $user->id;
    }
}
