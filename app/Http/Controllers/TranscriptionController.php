<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Media;
use App\Jobs\TranscribeMediaJob;
use Illuminate\Http\Request;

class TranscriptionController extends Controller
{
    /**
     * Transcribe a single audio media file.
     */
    public function transcribeSingle(Media $media)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can transcribe audio files.');
        }

        // Verify user has access to this media's chat
        $chat = $media->message->chat;
        if ($chat->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if it's an audio file
        if ($media->type !== 'audio') {
            return back()->withErrors(['error' => 'Only audio files can be transcribed.']);
        }

        // Check if already transcribed
        if ($media->transcription) {
            return back()->with('info', 'This audio has already been transcribed.');
        }

        // Dispatch transcription job
        TranscribeMediaJob::dispatch($media);

        return back()->with('success', 'Transcription started. This may take a few minutes.');
    }

    /**
     * Transcribe all audio files in a chat.
     */
    public function transcribeChat(Chat $chat)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can transcribe audio files.');
        }

        // Verify user owns this chat
        if ($chat->user_id !== auth()->id()) {
            abort(403);
        }

        // Get all untranscribed audio files from this chat
        $audioFiles = Media::whereHas('message', function ($query) use ($chat) {
            $query->where('chat_id', $chat->id);
        })
        ->where('type', 'audio')
        ->whereNull('transcription')
        ->get();

        if ($audioFiles->isEmpty()) {
            return back()->with('info', 'No audio files to transcribe.');
        }

        // Dispatch jobs for each audio file
        $count = 0;
        foreach ($audioFiles as $audio) {
            TranscribeMediaJob::dispatch($audio);
            $count++;
        }

        return back()->with('success', "Started transcription for {$count} audio " . str('file')->plural($count) . ". This may take a while.");
    }

    /**
     * Show transcription status for a chat.
     */
    public function status(Chat $chat)
    {
        // Verify user owns this chat
        if ($chat->user_id !== auth()->id()) {
            abort(403);
        }

        // Get transcription statistics
        $stats = Media::whereHas('message', function ($query) use ($chat) {
            $query->where('chat_id', $chat->id);
        })
        ->where('type', 'audio')
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN transcription IS NOT NULL THEN 1 ELSE 0 END) as transcribed,
            SUM(CASE WHEN transcription_requested = 1 AND transcription IS NULL THEN 1 ELSE 0 END) as pending
        ')
        ->first();

        return response()->json([
            'total' => $stats->total ?? 0,
            'transcribed' => $stats->transcribed ?? 0,
            'pending' => $stats->pending ?? 0,
            'remaining' => ($stats->total ?? 0) - ($stats->transcribed ?? 0) - ($stats->pending ?? 0),
        ]);
    }
}
