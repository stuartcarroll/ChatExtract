<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Chat;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Display the search interface.
     */
    public function index()
    {
        // Get user's chats for filter dropdown
        $chats = auth()->user()->ownedChats()->get();

        // Get user's tags for filter dropdown
        $tags = auth()->user()->tags()->get();

        return view('search.index', compact('chats', 'tags'));
    }

    /**
     * Perform the search.
     */
    public function search(Request $request)
    {
        // Validate the request
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'chat_id' => 'nullable|exists:chats,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'participant_name' => 'nullable|string|max:255',
            'media_type' => 'nullable|in:has_media,no_media,image,video,audio',
            'tag_id' => 'nullable|exists:tags,id',
            'only_stories' => 'nullable|boolean',
        ]);

        // Get user's chat IDs for security
        $userChatIds = auth()->user()->ownedChats()->pluck('id')->toArray();

        if (empty($userChatIds)) {
            $results = collect();
        } else {
            // Use Laravel Scout for full-text search
            $query = Message::search($request->input('query'))
                ->query(function ($builder) use ($userChatIds) {
                    // Ensure user can only search their own chats
                    $builder->whereIn('chat_id', $userChatIds);
                });

            // Apply filters
            if ($request->filled('chat_id')) {
                // Verify user owns this chat
                if (in_array($request->chat_id, $userChatIds)) {
                    $query->where('chat_id', $request->chat_id);
                }
            }

            if ($request->filled('date_from')) {
                $query->where('sent_at', '>=', strtotime($request->date_from));
            }

            if ($request->filled('date_to')) {
                $query->where('sent_at', '<=', strtotime($request->date_to));
            }

            if ($request->filled('only_stories') && $request->only_stories) {
                $query->where('is_story', true);
            }

            // Get results
            $results = $query->paginate(50);

            // Load relationships
            $results->load(['chat', 'participant', 'media']);

            // Apply participant filter (post-search)
            if ($request->filled('participant_name')) {
                $participantName = $request->participant_name;
                $results = $results->filter(function ($message) use ($participantName) {
                    return $message->participant &&
                           stripos($message->participant->name, $participantName) !== false;
                });
            }

            // Apply media type filter (post-search)
            if ($request->filled('media_type')) {
                $mediaType = $request->media_type;
                $results = $results->filter(function ($message) use ($mediaType) {
                    if ($mediaType === 'has_media') {
                        return $message->media->isNotEmpty();
                    } elseif ($mediaType === 'no_media') {
                        return $message->media->isEmpty();
                    } else {
                        // Filter by specific media type (image, video, audio)
                        return $message->media->where('type', $mediaType)->isNotEmpty();
                    }
                });
            }

            // If tag filter is specified, filter results
            if ($request->filled('tag_id')) {
                $tagId = $request->tag_id;
                $results = $results->filter(function ($message) use ($tagId) {
                    return $message->tags()->where('tags.id', $tagId)->exists();
                });
            }
        }

        // Get user's chats for filter dropdown
        $chats = auth()->user()->ownedChats()->get();

        // Get user's tags for filter dropdown
        $tags = auth()->user()->tags()->get();

        return view('search.index', compact('results', 'chats', 'tags'));
    }

    /**
     * Advanced search with multiple criteria.
     */
    public function advanced(Request $request)
    {
        // Validate the request
        $request->validate([
            'query' => 'nullable|string|min:2|max:255',
            'chat_ids' => 'nullable|array',
            'chat_ids.*' => 'exists:chats,id',
            'participant_name' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'only_stories' => 'nullable|boolean',
            'min_confidence' => 'nullable|numeric|min:0|max:1',
            'has_media' => 'nullable|boolean',
        ]);

        // Get user's chat IDs for security
        $userChatIds = auth()->user()->ownedChats()->pluck('id')->toArray();

        // Start with base query
        $query = Message::query()->whereIn('chat_id', $userChatIds);

        // Apply full-text search if query provided
        if ($request->filled('query')) {
            // Use Scout for full-text search
            $searchResults = Message::search($request->input('query'))
                ->query(function ($builder) use ($userChatIds) {
                    $builder->whereIn('chat_id', $userChatIds);
                })
                ->get();

            $messageIds = $searchResults->pluck('id')->toArray();
            $query->whereIn('id', $messageIds);
        }

        // Filter by specific chats
        if ($request->filled('chat_ids')) {
            $validChatIds = array_intersect($request->chat_ids, $userChatIds);
            $query->whereIn('chat_id', $validChatIds);
        }

        // Filter by participant name
        if ($request->filled('participant_name')) {
            $query->whereHas('participant', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->participant_name . '%');
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('sent_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sent_at', '<=', $request->date_to);
        }

        // Filter by tags
        if ($request->filled('tag_ids')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->whereIn('tags.id', $request->tag_ids);
            });
        }

        // Filter by stories
        if ($request->filled('only_stories') && $request->only_stories) {
            $query->where('is_story', true);
        }

        // Filter by minimum story confidence
        if ($request->filled('min_confidence')) {
            $query->where('story_confidence', '>=', $request->min_confidence);
        }

        // Filter by media
        if ($request->filled('has_media') && $request->has_media) {
            $query->whereHas('media');
        }

        // Order by sent_at
        $query->orderBy('sent_at', 'desc');

        // Get results
        $results = $query->with(['chat', 'participant', 'media', 'tags'])->paginate(50);

        // Get user's chats for filter dropdown
        $chats = auth()->user()->ownedChats()->get();

        // Get user's tags for filter dropdown
        $tags = auth()->user()->tags()->get();

        return view('search.index', compact('results', 'chats', 'tags'));
    }
}
