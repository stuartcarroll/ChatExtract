<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Display a listing of the user's chats.
     */
    public function index()
    {
        $user = auth()->user();

        // Get owned chats
        // Optimized query to get all accessible chats
        $chats = Chat::where('user_id', $user->id)
            ->orWhereHas('access', function($q) use ($user) {
                // Only user access is currently supported (no group access)
                $q->where('accessable_type', \App\Models\User::class)
                  ->where('accessable_id', $user->id);
            })
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        return view('chats.index', compact('chats'));
    }

    /**
     * Show the form for creating a new chat.
     */
    public function create()
    {
        return redirect()->route('import.create');
    }

    /**
     * Display the specified chat.
     */
    public function show(Chat $chat)
    {
        // Authorize the user
        $this->authorize('view', $chat);

        // Check if a specific message is requested via URL fragment
        $highlightMessageId = request()->get('message');
        $page = request()->get('page', 1);

        // If a message ID is provided and we're on page 1, calculate the correct page
        if ($highlightMessageId && $page == 1) {
            $messagePosition = $chat->messages()
                ->where('id', '<=', $highlightMessageId)
                ->count();

            if ($messagePosition > 0) {
                $page = ceil($messagePosition / 100);
            }
        }

        // Load messages with participants and media
        $messages = $chat->messages()
            ->with(['participant', 'media', 'tags'])
            ->orderBy('sent_at', 'asc')
            ->paginate(100, ['*'], 'page', $page);

        // Get chat statistics
        $statistics = [
            'total_messages' => $chat->messages()->count(),
            'total_participants' => $chat->participants()->count(),
            'date_range' => [
                'start' => $chat->messages()->min('sent_at'),
                'end' => $chat->messages()->max('sent_at'),
            ],
        ];

        // Get transcription statistics for audio files
        $audioStats = \App\Models\Media::whereHas('message', function ($query) use ($chat) {
            $query->where('chat_id', $chat->id);
        })
        ->where('type', 'audio')
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN transcription IS NOT NULL THEN 1 ELSE 0 END) as transcribed
        ')
        ->first();

        $statistics['audio_files'] = $audioStats->total ?? 0;
        $statistics['transcribed_audio'] = $audioStats->transcribed ?? 0;

        // Get all tags for tagging interface (global)
        $tags = Tag::orderBy('name')->get();

        return view('chats.show', compact('chat', 'messages', 'statistics', 'highlightMessageId', 'tags'));
    }

    /**
     * Show the form for editing the specified chat.
     */
    public function edit(Chat $chat)
    {
        // Authorize the user
        $this->authorize('update', $chat);

        // Get all users except the owner
        $users = \App\Models\User::where('id', '!=', $chat->user_id)->get();

        // Get all groups created by this user
        $groups = \App\Models\Group::where('created_by', auth()->id())->get();

        // Get current access grants
        $chatAccess = $chat->access()->with('accessable')->get();

        return view('chats.edit', compact('chat', 'users', 'groups', 'chatAccess'));
    }

    /**
     * Update the specified chat in storage.
     */
    public function update(Request $request, Chat $chat)
    {
        // Authorize the user
        $this->authorize('update', $chat);

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Update the chat
        $chat->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('chats.show', $chat)
            ->with('success', 'Chat updated successfully.');
    }

    /**
     * Remove the specified chat from storage.
     */
    public function destroy(Chat $chat)
    {
        // Authorize the user
        $this->authorize('delete', $chat);

        // Delete associated media files
        foreach ($chat->messages as $message) {
            foreach ($message->media as $media) {
                \Storage::disk('media')->delete($media->file_path);
            }
        }

        // Delete the chat (cascade will handle related records)
        $chat->delete();

        return redirect()->route('chats.index')
            ->with('success', 'Chat deleted successfully.');
    }

    /**
     * Grant access to a user or group.
     */
    public function grantAccess(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $request->validate([
            'accessable_type' => 'required|in:user',
            'accessable_id' => 'required|integer',
            'permission' => 'required|in:view,edit,admin',
        ]);

        // Only user access is currently supported
        if ($request->accessable_type !== 'user') {
            abort(400, 'Only user access currently supported');
        }
        $accessableType = \App\Models\User::class;

        // Check if access already exists
        $existing = $chat->access()
            ->where('accessable_type', $accessableType)
            ->where('accessable_id', $request->accessable_id)
            ->first();

        if ($existing) {
            $existing->update(['permission' => $request->permission]);
        } else {
            $chat->access()->create([
                'accessable_type' => $accessableType,
                'accessable_id' => $request->accessable_id,
                'permission' => $request->permission,
                'granted_by' => auth()->id(),
            ]);
        }

        return redirect()->route('chats.edit', $chat)
            ->with('success', 'Access granted successfully.');
    }

    /**
     * Revoke access from a user or group.
     */
    public function revokeAccess(Chat $chat, $accessId)
    {
        $this->authorize('update', $chat);

        $chat->access()->where('id', $accessId)->delete();

        return redirect()->route('chats.edit', $chat)
            ->with('success', 'Access revoked successfully.');
    }

    /**
     * Display gallery view of all media in a chat.
     */
    public function gallery(Chat $chat, Request $request)
    {
        // Authorize the user
        $this->authorize('view', $chat);

        // Get filter type (all, image, video, audio) and sort
        $type = $request->get('type', 'all');
        $sort = $request->get('sort', 'date_desc');

        // Query media with messages, participants, and tags
        $query = \App\Models\Media::query()
            ->join('messages', 'media.message_id', '=', 'messages.id')
            ->where('messages.chat_id', $chat->id)
            ->with(['message.participant', 'message.tags']);

        // Apply type filter
        if ($type !== 'all') {
            $query->where('media.type', $type);
        }

        // Apply sorting
        $query->select('media.*');

        switch ($sort) {
            case 'date_asc':
                $query->orderBy('messages.sent_at', 'asc');
                break;
            case 'date_desc':
            default:
                $query->orderBy('messages.sent_at', 'desc');
                break;
        }

        // Get media with pagination and preserve query parameters
        $media = $query->paginate(24)->withQueryString();

        // Get media counts by type in a single optimized query
        $countsRaw = \App\Models\Media::query()
            ->join('messages', 'media.message_id', '=', 'messages.id')
            ->where('messages.chat_id', $chat->id)
            ->selectRaw("
                COUNT(*) as all_count,
                SUM(CASE WHEN media.type = 'image' THEN 1 ELSE 0 END) as image,
                SUM(CASE WHEN media.type = 'video' THEN 1 ELSE 0 END) as video,
                SUM(CASE WHEN media.type = 'audio' THEN 1 ELSE 0 END) as audio
            ")
            ->first();

        $counts = [
            'all' => $countsRaw->all_count ?? 0,
            'image' => $countsRaw->image ?? 0,
            'video' => $countsRaw->video ?? 0,
            'audio' => $countsRaw->audio ?? 0,
        ];

        // Get all tags for tagging interface (global)
        $tags = Tag::orderBy('name')->get();

        return view('chats.gallery', compact('chat', 'media', 'type', 'counts', 'tags', 'sort'));
    }

    /**
     * Filter messages in a chat.
     */
    public function filter(Request $request, Chat $chat)
    {
        // Authorize the user
        $this->authorize('view', $chat);

        $query = $chat->messages()
            ->with(['participant', 'media', 'tags']);

        // Filter by participant
        if ($request->filled('participant_id')) {
            $query->where('participant_id', $request->participant_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('sent_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sent_at', '<=', $request->date_to);
        }

        // Filter by stories
        if ($request->filled('only_stories') && $request->only_stories) {
            $query->where('is_story', true);
        }

        // Filter by tag
        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag_id);
            });
        }

        // Filter by media
        if ($request->filled('has_media') && $request->has_media) {
            $query->whereHas('media');
        }

        // Order by sent_at
        $query->orderBy('sent_at', 'asc');

        $messages = $query->paginate(100);

        // Get chat statistics
        $statistics = [
            'total_messages' => $chat->messages()->count(),
            'total_stories' => $chat->messages()->where('is_story', true)->count(),
            'total_participants' => $chat->participants()->count(),
            'date_range' => [
                'start' => $chat->messages()->min('sent_at'),
                'end' => $chat->messages()->max('sent_at'),
            ],
        ];

        // Get participants for filter dropdown
        $participants = $chat->participants()->get();

        // Get all tags for filter dropdown (global)
        $tags = Tag::orderBy('name')->get();

        return view('chats.show', compact('chat', 'messages', 'statistics', 'participants', 'tags'));
    }
}
