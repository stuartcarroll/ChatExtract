<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ParticipantController extends Controller
{
    /**
     * Display a listing of all participants.
     */
    public function index()
    {
        // Get all participants from user's chats with stats
        $participants = Participant::whereHas('messages.chat', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->withCount('messages')
        ->with('chat')
        ->orderBy('name')
        ->paginate(50);

        return view('participants.index', compact('participants'));
    }

    /**
     * Display the participant's profile with detailed stats.
     */
    public function show(Participant $participant)
    {
        // Verify access
        if ($participant->chat->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this participant.');
        }

        // Get comprehensive stats
        $stats = $this->getParticipantStats($participant);

        return view('participants.show', compact('participant', 'stats'));
    }

    /**
     * Display participant's media gallery.
     */
    public function gallery(Participant $participant, Request $request)
    {
        // Verify access
        if ($participant->chat->user_id !== auth()->id()) {
            abort(403);
        }

        // Get filter type
        $type = $request->get('type', 'all');

        // Get media query
        $query = Media::whereHas('message', function ($q) use ($participant) {
            $q->where('participant_id', $participant->id);
        });

        // Apply type filter
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Get media counts by type
        $counts = [
            'all' => Media::whereHas('message', function ($q) use ($participant) {
                $q->where('participant_id', $participant->id);
            })->count(),
            'image' => Media::whereHas('message', function ($q) use ($participant) {
                $q->where('participant_id', $participant->id);
            })->where('type', 'image')->count(),
            'video' => Media::whereHas('message', function ($q) use ($participant) {
                $q->where('participant_id', $participant->id);
            })->where('type', 'video')->count(),
            'audio' => Media::whereHas('message', function ($q) use ($participant) {
                $q->where('participant_id', $participant->id);
            })->where('type', 'audio')->count(),
        ];

        $media = $query->with('message')->orderByDesc('created_at')->paginate(48);

        return view('participants.gallery', compact('participant', 'media', 'type', 'counts'));
    }

    /**
     * Delete a media file (NSFW content removal).
     */
    public function deleteMedia(Participant $participant, Media $media)
    {
        // Verify access and ownership
        if ($participant->chat->user_id !== auth()->id()) {
            abort(403);
        }

        if ($media->message->participant_id !== $participant->id) {
            abort(403, 'Media does not belong to this participant.');
        }

        // Delete file from storage
        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // Mark as deleted (keep record for audit trail)
        $media->update([
            'deleted_at' => now(),
            'deleted_reason' => 'NSFW content removed by admin',
        ]);

        // Or completely delete
        $media->delete();

        return redirect()->back()->with('success', 'Media deleted successfully.');
    }

    /**
     * Get comprehensive stats for a participant.
     */
    private function getParticipantStats(Participant $participant): array
    {
        // Total messages
        $totalMessages = $participant->messages()->count();

        // Media stats
        $mediaStats = DB::table('media')
            ->join('messages', 'media.message_id', '=', 'messages.id')
            ->where('messages.participant_id', $participant->id)
            ->selectRaw('
                COUNT(*) as total_media,
                SUM(CASE WHEN type = "image" THEN 1 ELSE 0 END) as photos,
                SUM(CASE WHEN type = "video" THEN 1 ELSE 0 END) as videos,
                SUM(CASE WHEN type = "audio" THEN 1 ELSE 0 END) as voice_notes
            ')
            ->first();

        // Voice notes duration (if available in metadata)
        $voiceNotesMinutes = DB::table('media')
            ->join('messages', 'media.message_id', '=', 'messages.id')
            ->where('messages.participant_id', $participant->id)
            ->where('media.type', 'audio')
            ->sum('duration') / 60; // Convert seconds to minutes

        // Chats participated in
        $chatsCount = DB::table('messages')
            ->where('participant_id', $participant->id)
            ->distinct('chat_id')
            ->count();

        // Deleted media count (NSFW)
        $deletedMedia = DB::table('media')
            ->join('messages', 'media.message_id', '=', 'messages.id')
            ->where('messages.participant_id', $participant->id)
            ->whereNotNull('media.deleted_at')
            ->count();

        // First and last message dates
        $firstMessage = $participant->messages()->orderBy('sent_at')->first();
        $lastMessage = $participant->messages()->orderByDesc('sent_at')->first();

        return [
            'total_messages' => $totalMessages,
            'total_media' => $mediaStats->total_media ?? 0,
            'photos' => $mediaStats->photos ?? 0,
            'videos' => $mediaStats->videos ?? 0,
            'voice_notes' => $mediaStats->voice_notes ?? 0,
            'voice_notes_minutes' => round($voiceNotesMinutes, 1),
            'chats_count' => $chatsCount,
            'deleted_media' => $deletedMedia,
            'first_message_at' => $firstMessage?->sent_at,
            'last_message_at' => $lastMessage?->sent_at,
        ];
    }
}
