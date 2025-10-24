<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportController extends Controller
{
    /**
     * Export selected messages/media as a ZIP file.
     */
    public function export(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array|min:1',
            'message_ids.*' => 'required|integer|exists:messages,id',
        ]);

        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        // Get messages that user has access to
        $messages = Message::whereIn('id', $request->message_ids)
            ->whereIn('chat_id', $userChatIds)
            ->with(['participant', 'media', 'chat'])
            ->get();

        if ($messages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No accessible messages found'
            ], 403);
        }

        // If single message/media, return direct download
        if ($messages->count() === 1) {
            return $this->handleSingleExport($messages->first());
        }

        // Multiple items - create ZIP
        return $this->handleBulkExport($messages);
    }

    /**
     * Handle export of a single item.
     */
    protected function handleSingleExport(Message $message)
    {
        // If message has exactly one media file, download that
        if ($message->media->count() === 1) {
            $media = $message->media->first();
            $filePath = storage_path('app/public/' . $media->file_path);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->download($filePath, $media->filename);
        }

        // If message has multiple media or no media, export as text
        return $this->exportMessageAsText($message);
    }

    /**
     * Export a single message as a text file.
     */
    protected function exportMessageAsText(Message $message)
    {
        $content = $this->formatMessageAsText($message);

        $filename = sprintf(
            '%s_%s_%s.txt',
            $message->chat->name,
            $message->participant->name ?? 'Unknown',
            $message->sent_at->format('Y-m-d_H-i-s')
        );

        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Handle bulk export as ZIP file.
     */
    protected function handleBulkExport($messages)
    {
        // Create temporary directory
        $tempDir = storage_path('app/temp_exports/' . uniqid());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/export.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ZIP file'
            ], 500);
        }

        // Track filenames to avoid duplicates
        $filenameCounts = [];

        // Add messages and media to ZIP
        foreach ($messages as $message) {
            // Add message text
            $txtContent = $this->formatMessageAsText($message);
            $txtFilename = sprintf(
                '%s_%s_%s.txt',
                $message->chat->name,
                $message->participant->name ?? 'Unknown',
                $message->sent_at->format('Y-m-d_H-i-s')
            );
            $txtFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $txtFilename);
            $txtFilename = $this->getUniqueFilename($txtFilename, $filenameCounts);

            $zip->addFromString($txtFilename, $txtContent);

            // Add media files
            foreach ($message->media as $media) {
                $mediaPath = storage_path('app/public/' . $media->file_path);

                if (file_exists($mediaPath)) {
                    $mediaFilename = $this->getUniqueFilename($media->filename, $filenameCounts);
                    $zip->addFile($mediaPath, $mediaFilename);
                }
            }
        }

        // Add manifest file
        $manifest = $this->generateManifest($messages);
        $zip->addFromString('MANIFEST.txt', $manifest);

        $zip->close();

        // Return ZIP file
        return response()->download($zipPath, $this->generateZipFilename($messages))->deleteFileAfterSend(true);
    }

    /**
     * Format a message as plain text.
     */
    protected function formatMessageAsText(Message $message): string
    {
        $output = [];
        $output[] = '======================================';
        $output[] = 'CHAT MESSAGE EXPORT';
        $output[] = '======================================';
        $output[] = '';
        $output[] = 'Chat: ' . $message->chat->name;
        $output[] = 'From: ' . ($message->participant->name ?? 'Unknown');
        $output[] = 'Date: ' . $message->sent_at->format('Y-m-d H:i:s');

        if ($message->is_story) {
            $output[] = 'Type: Story';
        }

        if ($message->is_system_message) {
            $output[] = 'Type: System Message';
        }

        $output[] = '';
        $output[] = '--- Message Content ---';
        $output[] = $message->content;

        // Add media information
        if ($message->media->isNotEmpty()) {
            $output[] = '';
            $output[] = '--- Attached Media ---';
            foreach ($message->media as $media) {
                $output[] = sprintf(
                    '- %s (%s, %s)',
                    $media->filename,
                    $media->type,
                    $this->formatFileSize($media->file_size)
                );

                if ($media->transcription) {
                    $output[] = '  Transcription: ' . $media->transcription;
                }
            }
        }

        // Add tags
        if ($message->tags->isNotEmpty()) {
            $output[] = '';
            $output[] = '--- Tags ---';
            $output[] = $message->tags->pluck('name')->join(', ');
        }

        $output[] = '';
        $output[] = '======================================';
        $output[] = 'Exported from ChatExtract';
        $output[] = 'Export Date: ' . now()->format('Y-m-d H:i:s');
        $output[] = '======================================';

        return implode("\n", $output);
    }

    /**
     * Generate a manifest file for the export.
     */
    protected function generateManifest($messages): string
    {
        $output = [];
        $output[] = 'ChatExtract Bulk Export Manifest';
        $output[] = '================================';
        $output[] = '';
        $output[] = 'Export Date: ' . now()->format('Y-m-d H:i:s');
        $output[] = 'Total Messages: ' . $messages->count();
        $output[] = 'Total Media Files: ' . $messages->sum(fn($m) => $m->media->count());
        $output[] = '';
        $output[] = 'Chats Included:';

        $chatCounts = [];
        foreach ($messages as $message) {
            $chatName = $message->chat->name;
            $chatCounts[$chatName] = ($chatCounts[$chatName] ?? 0) + 1;
        }

        foreach ($chatCounts as $chatName => $count) {
            $output[] = sprintf('  - %s (%d messages)', $chatName, $count);
        }

        $output[] = '';
        $output[] = 'Files in this export:';
        $output[] = '  - Text files (.txt): Message content and metadata';
        $output[] = '  - Media files: Original attachments (images, videos, audio)';
        $output[] = '  - MANIFEST.txt: This file';

        return implode("\n", $output);
    }

    /**
     * Generate filename for ZIP export.
     */
    protected function generateZipFilename($messages): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        if ($messages->count() === 1) {
            $message = $messages->first();
            $filename = sprintf(
                'ChatExtract_%s_%s.zip',
                $message->chat->name,
                $timestamp
            );
        } else {
            $chatCount = $messages->pluck('chat_id')->unique()->count();
            $filename = sprintf(
                'ChatExtract_%d_messages_%d_chats_%s.zip',
                $messages->count(),
                $chatCount,
                $timestamp
            );
        }

        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }

    /**
     * Get a unique filename to avoid duplicates.
     */
    protected function getUniqueFilename(string $filename, array &$counts): string
    {
        if (!isset($counts[$filename])) {
            $counts[$filename] = 0;
            return $filename;
        }

        $counts[$filename]++;
        $pathinfo = pathinfo($filename);
        $basename = $pathinfo['filename'];
        $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';

        return sprintf('%s_%d%s', $basename, $counts[$filename], $extension);
    }

    /**
     * Format file size in human-readable format.
     */
    protected function formatFileSize(?int $bytes): string
    {
        if ($bytes === null) {
            return 'unknown size';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Download a single media file directly.
     */
    public function downloadMedia(Media $media)
    {
        // Check authorization - user must have access to the chat
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        if (!in_array($media->message->chat_id, $userChatIds)) {
            abort(403, 'Unauthorized');
        }

        $filePath = storage_path('app/public/' . $media->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $media->filename);
    }
}
