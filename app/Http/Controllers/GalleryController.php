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

        // Get user's accessible chat IDs
        $chatIds = auth()->user()->accessibleChats()->pluck('id')->toArray();

        if (empty($chatIds)) {
            $media = collect();
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

            // Get media with pagination and preserve query parameters
            $media = $query->paginate(24)->withQueryString();

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

        // Get all tags for tagging interface (global)
        $tags = Tag::orderBy('name')->get();

        return view('gallery.index', compact('media', 'type', 'counts', 'participants', 'participantId', 'tags', 'sort'));
    }
}
