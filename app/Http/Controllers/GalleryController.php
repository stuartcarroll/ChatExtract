<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Participant;
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
            })->with(['message.participant', 'message.chat']);

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

            // Get media with pagination
            $media = $query->join('messages', 'media.message_id', '=', 'messages.id')
                ->orderBy('messages.sent_at', 'desc')
                ->select('media.*')
                ->paginate(24);

            // Get counts
            $counts = [
                'all' => Media::whereHas('message', function ($q) use ($chatIds) {
                    $q->whereIn('chat_id', $chatIds);
                })->count(),
                'image' => Media::where('type', 'image')->whereHas('message', function ($q) use ($chatIds) {
                    $q->whereIn('chat_id', $chatIds);
                })->count(),
                'video' => Media::where('type', 'video')->whereHas('message', function ($q) use ($chatIds) {
                    $q->whereIn('chat_id', $chatIds);
                })->count(),
                'audio' => Media::where('type', 'audio')->whereHas('message', function ($q) use ($chatIds) {
                    $q->whereIn('chat_id', $chatIds);
                })->count(),
            ];

            // Get all unique participants from accessible chats
            $participants = Participant::whereIn('chat_id', $chatIds)
                ->select('id', 'name', 'chat_id')
                ->orderBy('name')
                ->get()
                ->unique('name');
        }

        return view('gallery.index', compact('media', 'type', 'counts', 'participants', 'participantId'));
    }
}
