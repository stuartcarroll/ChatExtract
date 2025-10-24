<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Media;
use App\Models\Participant;
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

        // CRITICAL PRIVACY CHECK: Verify participant consent
        $participant = $media->message->participant;
        if (!$participant || !$participant->hasTranscriptionConsent()) {
            return back()->withErrors([
                'error' => 'Cannot transcribe - participant "' . ($participant?->name ?? 'Unknown') . '" has not given consent for transcription. Please grant consent in the Transcription Dashboard first.'
            ]);
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

        // Get all untranscribed audio files from participants WHO HAVE GIVEN CONSENT
        $audioFiles = Media::whereHas('message', function ($query) use ($chat) {
            $query->where('chat_id', $chat->id)
                  // CRITICAL PRIVACY FILTER: Only include messages from participants who have consented
                  ->whereHas('participant', function ($participantQuery) {
                      $participantQuery->where('transcription_consent', true);
                  });
        })
        ->where('type', 'audio')
        ->whereNull('transcription')
        ->with('message.participant') // Load relationships for checking
        ->get();

        if ($audioFiles->isEmpty()) {
            return back()->with('info', 'No audio files to transcribe from participants who have given consent. Please grant consent in the Transcription Dashboard first.');
        }

        // Dispatch jobs for each audio file (consent already verified by query)
        $count = 0;
        $skipped = 0;
        foreach ($audioFiles as $audio) {
            // Double-check consent as additional safety measure
            if ($audio->message->participant && $audio->message->participant->hasTranscriptionConsent()) {
                TranscribeMediaJob::dispatch($audio);
                $count++;
            } else {
                $skipped++;
            }
        }

        $message = "Started transcription for {$count} audio " . str('file')->plural($count) . " from participants who have given consent. This may take a while.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} files from participants without consent.";
        }

        return back()->with('success', $message);
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

    /**
     * Show transcription dashboard for all chats.
     */
    public function dashboard()
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can access the transcription dashboard.');
        }

        // Get all user's chats with audio files - optimized with direct SQL
        $chats = Chat::where('user_id', auth()->id())
            ->whereHas('messages.media', function ($query) {
                $query->where('type', 'audio');
            })
            ->get()
            ->map(function ($chat) {
                // Get audio stats using direct query
                $audioStats = \DB::table('media')
                    ->join('messages', 'media.message_id', '=', 'messages.id')
                    ->where('messages.chat_id', $chat->id)
                    ->where('media.type', 'audio')
                    ->selectRaw('
                        COUNT(*) as total_audio,
                        SUM(CASE WHEN transcription IS NOT NULL THEN 1 ELSE 0 END) as transcribed,
                        SUM(CASE WHEN transcription_requested = 1 AND transcription IS NULL THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN transcription_requested IS NULL OR transcription_requested = 0 THEN 1 ELSE 0 END) as not_started
                    ')
                    ->first();

                // Get last message timestamp
                $lastMessage = \DB::table('messages')
                    ->join('media', 'messages.id', '=', 'media.message_id')
                    ->where('messages.chat_id', $chat->id)
                    ->where('media.type', 'audio')
                    ->orderByDesc('messages.sent_at')
                    ->first();

                return [
                    'id' => $chat->id,
                    'name' => $chat->name,
                    'last_message_at' => $lastMessage ? $lastMessage->sent_at : $chat->created_at,
                    'total_audio' => $audioStats->total_audio ?? 0,
                    'transcribed' => $audioStats->transcribed ?? 0,
                    'pending' => $audioStats->pending ?? 0,
                    'not_started' => $audioStats->not_started ?? 0,
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

        return view('transcription.dashboard', compact('chats'));
    }

    /**
     * Get real-time transcription status for dashboard updates.
     */
    public function dashboardStatus()
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        // Get all user's chats with audio files
        $chats = Chat::where('user_id', auth()->id())
            ->whereHas('messages.media', function ($query) {
                $query->where('type', 'audio');
            })
            ->with(['messages.media' => function ($query) {
                $query->where('type', 'audio');
            }, 'messages.participant'])
            ->get()
            ->map(function ($chat) {
                $audioFiles = $chat->messages->pluck('media')->flatten()->where('type', 'audio');

                // Get detailed message list
                $recentTranscriptions = $chat->messages
                    ->filter(function($message) {
                        return $message->media->where('type', 'audio')->whereNotNull('transcription')->isNotEmpty();
                    })
                    ->map(function($message) {
                        return [
                            'id' => $message->id,
                            'participant' => $message->participant?->name ?? 'Unknown',
                            'sent_at' => $message->sent_at->format('M d, Y H:i'),
                            'transcribed_at' => $message->media->where('type', 'audio')->whereNotNull('transcription')->first()?->updated_at?->format('M d, Y H:i'),
                        ];
                    })
                    ->take(5);

                return [
                    'id' => $chat->id,
                    'total_audio' => $audioFiles->count(),
                    'transcribed' => $audioFiles->whereNotNull('transcription')->count(),
                    'pending' => $audioFiles->where('transcription_requested', true)->whereNull('transcription')->count(),
                    'not_started' => $audioFiles->whereNull('transcription_requested')->count(),
                    'recent_transcriptions' => $recentTranscriptions,
                ];
            });

        return response()->json($chats);
    }

    /**
     * Show participant consent management page.
     */
    public function participants()
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can manage transcription consent.');
        }

        // Get all participants from user's chats with audio message counts
        $participants = Participant::whereHas('messages.chat', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->withCount(['messages as audio_messages_count' => function ($query) {
            $query->whereHas('media', function ($q) {
                $q->where('type', 'audio');
            });
        }])
        ->with('consentGrantedBy')
        ->orderBy('name')
        ->get();

        return view('transcription.participants', compact('participants'));
    }

    /**
     * Update participant transcription consent.
     */
    public function updateConsent(Participant $participant, Request $request)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can manage transcription consent.');
        }

        // Verify user has access to this participant's chats
        $hasAccess = $participant->messages()->whereHas('chat', function ($query) {
            $query->where('user_id', auth()->id());
        })->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to manage this participant.');
        }

        $request->validate([
            'consent' => 'required|boolean',
        ]);

        $consent = $request->boolean('consent');

        $participant->update([
            'transcription_consent' => $consent,
            'transcription_consent_given_at' => $consent ? now() : null,
            'transcription_consent_given_by' => $consent ? auth()->id() : null,
        ]);

        $action = $consent ? 'granted' : 'revoked';
        return back()->with('success', "Transcription consent {$action} for {$participant->name}.");
    }

    /**
     * Show participant profile with statistics (admin only).
     */
    public function participantProfile(Participant $participant)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Only administrators can view participant profiles.');
        }

        // Verify user has access to this participant's chats
        $hasAccess = $participant->messages()->whereHas('chat', function ($query) {
            $query->where('user_id', auth()->id());
        })->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to this participant.');
        }

        // Get statistics
        $stats = [
            'total_messages' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->count(),

            'audio_messages' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->whereHas('media', function ($query) {
                $query->where('type', 'audio');
            })->count(),

            'transcribed_audio' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->whereHas('media', function ($query) {
                $query->where('type', 'audio')->whereNotNull('transcription');
            })->count(),

            'images_sent' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->whereHas('media', function ($query) {
                $query->where('type', 'image');
            })->count(),

            'videos_sent' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->whereHas('media', function ($query) {
                $query->where('type', 'video');
            })->count(),

            'chats_participated' => $participant->messages()->whereHas('chat', function ($query) {
                $query->where('user_id', auth()->id());
            })->distinct('chat_id')->count('chat_id'),
        ];

        // Get chats this participant is in
        $chats = Chat::where('user_id', auth()->id())
            ->whereHas('messages', function ($query) use ($participant) {
                $query->where('participant_id', $participant->id);
            })
            ->withCount(['messages' => function ($query) use ($participant) {
                $query->where('participant_id', $participant->id);
            }])
            ->get();

        return view('transcription.participant-profile', compact('participant', 'stats', 'chats'));
    }
}
