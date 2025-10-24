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
     *
     * Handles all three roles automatically:
     * - Admin: can view all chats
     * - Chat user: can view owned + granted access chats
     * - View-only: can only view chats with messages tagged with accessible tags
     */
    public function view(User $user, Chat $chat): bool
    {
        // Use the accessibleChatIds method which handles all roles
        $accessibleChatIds = $user->accessibleChatIds();
        return $accessibleChatIds->contains($chat->id);
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
