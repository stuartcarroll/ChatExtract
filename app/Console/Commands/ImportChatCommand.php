<?php

namespace App\Console\Commands;

use App\Jobs\ProcessChatImportJob;
use App\Models\ImportProgress;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:import
                            {file : Path to the chat file (.txt or .zip)}
                            {--user= : User ID or email to import for}
                            {--name= : Name for the chat}
                            {--description= : Description for the chat}
                            {--sync : Run synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a WhatsApp chat from a file on the server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Get user
        $userInput = $this->option('user');
        if (!$userInput) {
            $userInput = $this->ask('Enter user ID or email');
        }

        $user = is_numeric($userInput)
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        // Get chat name
        $chatName = $this->option('name');
        if (!$chatName) {
            $chatName = $this->ask('Enter chat name', basename($filePath, '.txt'));
        }

        // Get description
        $description = $this->option('description');
        if (!$description) {
            $description = $this->ask('Enter chat description (optional)');
        }

        $extractPath = null;
        $fullPath = $filePath;

        // Handle ZIP files
        if (str_ends_with(strtolower($filePath), '.zip')) {
            $this->info('Extracting ZIP file...');
            $extractPath = storage_path('app/temp/' . Str::uuid());
            mkdir($extractPath, 0755, true);

            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                // Find the .txt file
                $files = glob($extractPath . '/*.txt');
                if (empty($files)) {
                    $this->error('No .txt file found in ZIP archive');
                    $this->deleteDirectory($extractPath);
                    return 1;
                }

                $fullPath = $files[0];
                $this->info("Found chat file: " . basename($fullPath));
            } else {
                $this->error('Failed to extract ZIP file');
                return 1;
            }
        }

        // Create import progress record
        $progress = ImportProgress::create([
            'user_id' => $user->id,
            'filename' => basename($filePath),
            'status' => 'pending',
        ]);

        $this->info("Created import progress record: #{$progress->id}");

        if ($this->option('sync')) {
            $this->info('Running import synchronously...');
            $this->info('This may take a while for large files.');

            $job = new ProcessChatImportJob(
                $progress->id,
                $fullPath,
                $chatName,
                $description,
                $user->id,
                $extractPath
            );

            try {
                $job->handle(app(\App\Services\WhatsAppParserService::class));
                $this->info('Import completed successfully!');

                $progress->refresh();
                $this->info("Chat ID: {$progress->chat_id}");
                $this->info("Messages: {$progress->processed_messages}/{$progress->total_messages}");
                $this->info("Media: {$progress->processed_media}/{$progress->total_media}");

                return 0;
            } catch (\Exception $e) {
                $this->error('Import failed: ' . $e->getMessage());
                return 1;
            }
        } else {
            // Dispatch job to queue
            ProcessChatImportJob::dispatch(
                $progress->id,
                $fullPath,
                $chatName,
                $description,
                $user->id,
                $extractPath
            );

            $this->info('Import job queued successfully!');
            $this->info("Progress ID: {$progress->id}");
            $this->info('You can monitor progress with: php artisan chat:import-status ' . $progress->id);

            return 0;
        }
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
