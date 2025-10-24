<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Message;
use Illuminate\Http\Request;
use ZipArchive;

class TagController extends Controller
{
    /**
     * Display a listing of all tags (global).
     */
    public function index()
    {
        // Get all tags with message count (only counting messages user has access to)
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        $tags = Tag::withCount(['messages' => function($query) use ($userChatIds) {
            $query->whereIn('chat_id', $userChatIds);
        }])->orderBy('name')->get();

        return view('tags.index', compact('tags'));
    }

    /**
     * Store a newly created tag (global).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
        ]);

        // If AJAX request, return JSON with tag data
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id' => $tag->id,
                'name' => $tag->name,
                'tag' => $tag
            ], 201);
        }

        // If called from inline form, redirect back to where they were
        if ($request->has('redirect_back')) {
            return back()->with('success', 'Tag "' . $tag->name . '" created successfully.');
        }

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag (global - any user can update).
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name,' . $tag->id,
        ]);

        $tag->update(['name' => $request->name]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag (global - any user can delete).
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('tags.index')
            ->with('success', 'Tag deleted successfully.');
    }

    /**
     * Tag a message.
     */
    public function tagMessage(Request $request, Message $message)
    {
        $request->validate([
            'tag_id' => 'required|exists:tags,id',
        ]);

        $tag = Tag::findOrFail($request->tag_id);

        // Authorize - user must have access to the message's chat
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();
        if (!in_array($message->chat_id, $userChatIds)) {
            abort(403);
        }

        // Toggle tag
        if ($message->tags()->where('tags.id', $tag->id)->exists()) {
            $message->tags()->detach($tag->id);
            $action = 'removed';
        } else {
            $message->tags()->attach($tag->id);
            $action = 'added';
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => "Tag {$action} successfully."
            ]);
        }

        return back()->with('success', "Tag {$action} successfully.");
    }

    /**
     * Batch tag multiple messages.
     */
    public function batchTag(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id',
            'tag_id' => 'required|exists:tags,id',
        ]);

        // Get user's accessible chat IDs for authorization
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        // Get messages that user has access to
        $messages = Message::whereIn('id', $request->message_ids)
            ->whereIn('chat_id', $userChatIds)
            ->get();

        if ($messages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No accessible messages found'
            ], 403);
        }

        $tag = Tag::findOrFail($request->tag_id);
        $tagged = 0;

        // Batch attach tags (only attach if not already tagged)
        foreach ($messages as $message) {
            if (!$message->tags()->where('tags.id', $tag->id)->exists()) {
                $message->tags()->attach($tag->id);
                $tagged++;
            }
        }

        return response()->json([
            'success' => true,
            'tagged' => $tagged,
            'total' => $messages->count(),
            'message' => "Tagged {$tagged} of {$messages->count()} messages"
        ]);
    }

    /**
     * Export all messages with a specific tag as a ZIP file.
     */
    public function export(Tag $tag)
    {
        // Get user's accessible chat IDs
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        // Get all messages with this tag that user has access to
        $messages = $tag->messages()
            ->whereIn('chat_id', $userChatIds)
            ->with(['participant', 'media', 'chat', 'tags'])
            ->get();

        if ($messages->isEmpty()) {
            return back()->with('error', 'No messages found for this tag.');
        }

        // Create temporary directory
        $tempDir = storage_path('app/temp_exports/' . uniqid());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/export.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Failed to create export file.');
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
        $manifest = $this->generateTagManifest($tag, $messages);
        $zip->addFromString('MANIFEST.txt', $manifest);

        $zip->close();

        // Generate filename
        $timestamp = now()->format('Y-m-d_H-i-s');
        $zipFilename = sprintf(
            'Tag_%s_%d_messages_%s.zip',
            preg_replace('/[^a-zA-Z0-9._-]/', '_', $tag->name),
            $messages->count(),
            $timestamp
        );

        // Return ZIP file for download
        return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
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
                    '- %s (%s)',
                    $media->filename,
                    $media->type
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
     * Generate a manifest file for tag export.
     */
    protected function generateTagManifest(Tag $tag, $messages): string
    {
        $output = [];
        $output[] = 'ChatExtract Tag Export';
        $output[] = '================================';
        $output[] = '';
        $output[] = 'Tag: ' . $tag->name;
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
}
