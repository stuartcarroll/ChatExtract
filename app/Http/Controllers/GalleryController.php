<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Participant;
use App\Models\Tag;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Display global gallery across all accessible chats.
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $participantId = $request->get('participant');
        $sort = $request->get('sort', 'date_desc');
        $page = $request->get('page', 1);
        $tagFilter = $request->get('tags');

        // Get user's accessible chat IDs
        $chatIds = auth()->user()->accessibleChats()->pluck('id')->toArray();

        if (empty($chatIds)) {
            // Return empty paginated result instead of collection
            $media = new \Illuminate\Pagination\LengthAwarePaginator(
                [], // empty items
                0,  // total
                50, // per page
                1   // current page
            );
            $participants = collect();
            $counts = ['all' => 0, 'image' => 0, 'video' => 0, 'audio' => 0];
        } else {
            // Build query
            $query = Media::whereHas('message', function ($q) use ($chatIds) {
                $q->whereIn('chat_id', $chatIds);
            })->with(['message.participant', 'message.chat', 'message.tags']);

            // Filter by type
            if ($type !== 'all') {
                $query->where('type', $type);
            }

            // Filter by participant
            if ($participantId) {
                $query->whereHas('message', function ($q) use ($participantId) {
                    $q->where('participant_id', $participantId);
                });
            }

            // Filter by tags
            if ($tagFilter) {
                if ($tagFilter === 'untagged') {
                    // Show only items with no tags
                    $query->whereHas('message', function ($q) {
                        $q->doesntHave('tags');
                    });
                } elseif (is_array($tagFilter)) {
                    // Multiple tags - show items that have ALL selected tags
                    $query->whereHas('message', function ($q) use ($tagFilter) {
                        $q->whereHas('tags', function ($tagQuery) use ($tagFilter) {
                            $tagQuery->whereIn('tags.id', $tagFilter);
                        }, '=', count($tagFilter));
                    });
                } else {
                    // Single tag filter
                    $query->whereHas('message.tags', function ($q) use ($tagFilter) {
                        $q->where('tags.id', $tagFilter);
                    });
                }
            }

            // Apply sorting
            $query->join('messages', 'media.message_id', '=', 'messages.id')
                ->select('media.*');

            switch ($sort) {
                case 'date_asc':
                    $query->orderBy('messages.sent_at', 'asc');
                    break;
                case 'date_desc':
                default:
                    $query->orderBy('messages.sent_at', 'desc');
                    break;
            }

            // Get media with pagination (50 items per page for infinite scroll)
            $media = $query->paginate(50)->withQueryString();

            // Get counts in a single optimized query
            $countsRaw = Media::join('messages', 'media.message_id', '=', 'messages.id')
                ->whereIn('messages.chat_id', $chatIds)
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

            // Get all unique participants from accessible chats
            $participants = Participant::whereIn('chat_id', $chatIds)
                ->select('id', 'name', 'chat_id')
                ->orderBy('name')
                ->get()
                ->unique('name');
        }

        // Get tags based on user role
        // - Admin and chat_user: all tags
        // - View-only: only tags they have access to
        $user = auth()->user();
        if ($user->isViewOnly()) {
            $accessibleTagIds = $user->accessibleTagIds();
            $tags = Tag::whereIn('id', $accessibleTagIds)->orderBy('name')->get();
        } else {
            $tags = Tag::orderBy('name')->get();
        }

        // If AJAX request, return JSON for infinite scroll
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('gallery.partials.media-grid', compact('media', 'tags'))->render(),
                'has_more' => $media->hasMorePages(),
                'next_page' => $media->currentPage() + 1
            ]);
        }

        return view('gallery.index', compact('media', 'type', 'counts', 'participants', 'participantId', 'tags', 'sort'));
    }
}
