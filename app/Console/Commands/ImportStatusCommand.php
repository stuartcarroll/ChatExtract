<?php

namespace App\Console\Commands;

use App\Models\ImportProgress;
use Illuminate\Console\Command;

class ImportStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:import-status {progress_id : The import progress ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of a chat import';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $progressId = $this->argument('progress_id');
        $progress = ImportProgress::find($progressId);

        if (!$progress) {
            $this->error('Import progress not found');
            return 1;
        }

        $this->info('Import Status');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Field', 'Value'],
            [
                ['Status', ucfirst($progress->status)],
                ['Filename', $progress->filename],
                ['Messages', "{$progress->processed_messages} / {$progress->total_messages}"],
                ['Progress', $progress->progress_percentage . '%'],
                ['Media Files', "{$progress->processed_media} / {$progress->total_media}"],
                ['Media Progress', $progress->media_progress_percentage . '%'],
                ['Images', $progress->images_count],
                ['Videos', $progress->videos_count],
                ['Audio', $progress->audio_count],
                ['Chat ID', $progress->chat_id ?? 'N/A'],
                ['Started', $progress->started_at ? $progress->started_at->format('Y-m-d H:i:s') : 'Not started'],
                ['Completed', $progress->completed_at ? $progress->completed_at->format('Y-m-d H:i:s') : 'Not completed'],
            ]
        );

        if ($progress->error_message) {
            $this->error("\nError: " . $progress->error_message);
        }

        return 0;
    }
}
