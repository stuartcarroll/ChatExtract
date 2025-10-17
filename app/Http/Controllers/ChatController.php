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
        $chats = auth()->user()
            ->ownedChats()
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

        // Load messages with participants and media
        $messages = $chat->messages()
            ->with(['participant', 'media', 'tags'])
            ->orderBy('sent_at', 'asc')
            ->paginate(100);

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

        return view('chats.show', compact('chat', 'messages', 'statistics'));
    }

    /**
     * Show the form for editing the specified chat.
     */
    public function edit(Chat $chat)
    {
        // Authorize the user
        $this->authorize('update', $chat);

        return view('chats.edit', compact('chat'));
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
     * Display gallery view of all media in a chat.
     */
    public function gallery(Chat $chat, Request $request)
    {
        // Authorize the user
        $this->authorize('view', $chat);

        // Get filter type (all, image, video, audio)
        $type = $request->get('type', 'all');

        // Query media with messages and participants
        $query = \App\Models\Media::whereHas('message', function ($q) use ($chat) {
            $q->where('chat_id', $chat->id);
        })->with(['message.participant']);

        // Apply type filter
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Order by message date (newest first)
        $media = $query->join('messages', 'media.message_id', '=', 'messages.id')
            ->orderBy('messages.sent_at', 'desc')
            ->select('media.*')
            ->paginate(24);

        // Get media counts by type
        $counts = [
            'all' => \App\Models\Media::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })->count(),
            'image' => \App\Models\Media::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })->where('type', 'image')->count(),
            'video' => \App\Models\Media::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })->where('type', 'video')->count(),
            'audio' => \App\Models\Media::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })->where('type', 'audio')->count(),
        ];

        return view('chats.gallery', compact('chat', 'media', 'type', 'counts'));
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

        // Get tags for filter dropdown
        $tags = auth()->user()->tags()->get();

        return view('chats.show', compact('chat', 'messages', 'statistics', 'participants', 'tags'));
    }
}
