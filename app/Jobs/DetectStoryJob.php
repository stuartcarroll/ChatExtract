<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\StoryDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DetectStoryJob implements ShouldQueue
{
    use Queueable;

    public Message $message;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(StoryDetectionService $storyDetectionService): void
    {
        try {
            // Get context (previous messages for better detection)
            $context = $this->getContext();

            // Detect if the message contains a story
            $result = $storyDetectionService->detectStory($this->message->content, $context);

            // Update the message
            $this->message->update([
                'is_story' => $result['is_story'],
                'story_confidence' => $result['confidence'],
            ]);

            Log::info('Story detection completed', [
                'message_id' => $this->message->id,
                'is_story' => $result['is_story'],
                'confidence' => $result['confidence'],
                'method' => $result['method'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('Story detection failed', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            // Rethrow to trigger retry
            throw $e;
        }
    }

    /**
     * Get context for better story detection.
     *
     * @return array
     */
    protected function getContext(): array
    {
        // Get previous 3 messages from the same participant for context
        $previousMessages = Message::where('chat_id', $this->message->chat_id)
            ->where('participant_id', $this->message->participant_id)
            ->where('sent_at', '<', $this->message->sent_at)
            ->orderBy('sent_at', 'desc')
            ->limit(3)
            ->pluck('content')
            ->toArray();

        return [
            'previous_messages' => $previousMessages,
            'participant' => $this->message->participant?->name,
            'chat' => $this->message->chat?->name,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Story detection job failed permanently', [
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark message with low confidence if detection fails
        $this->message->update([
            'is_story' => false,
            'story_confidence' => 0.0,
        ]);
    }
}
