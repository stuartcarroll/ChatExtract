<?php

namespace App\Http\Controllers;

use App\Models\ImportProgress;
use App\Jobs\ProcessChatImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    /**
     * Initialize a chunked upload session.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
            'file_size' => 'required|integer|min:1',
            'chat_name' => 'required|string|max:255',
            'chat_description' => 'nullable|string|max:1000',
        ]);

        // Create a unique upload session ID
        $uploadId = Str::uuid()->toString();
        $uploadDir = storage_path('app/uploads/' . $uploadId);

        // Create upload directory
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Create import progress record
        $isZip = pathinfo($request->filename, PATHINFO_EXTENSION) === 'zip';

        $progress = ImportProgress::create([
            'user_id' => auth()->id(),
            'filename' => $request->filename,
            'file_path' => null, // Will be set when upload completes
            'is_zip' => $isZip,
            'status' => 'uploading',
            'upload_id' => $uploadId,
            'total_chunks' => $request->total_chunks,
            'uploaded_chunks' => 0,
        ]);

        $progress->addLog("Upload session initiated: {$request->filename} ({$request->total_chunks} chunks, " . round($request->file_size / 1024 / 1024, 2) . " MB)");

        return response()->json([
            'upload_id' => $uploadId,
            'progress_id' => $progress->id,
        ]);
    }

    /**
     * Upload a single chunk.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->upload_id;
        $chunkIndex = $request->chunk_index;
        $uploadDir = storage_path('app/uploads/' . $uploadId);

        // Verify upload directory exists
        if (!file_exists($uploadDir)) {
            return response()->json(['error' => 'Upload session not found'], 404);
        }

        // Find the progress record
        $progress = ImportProgress::where('upload_id', $uploadId)->first();

        if (!$progress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        // Verify user owns this upload
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Save chunk
            $chunkPath = $uploadDir . '/chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
            $request->file('chunk')->move($uploadDir, basename($chunkPath));

            // Update progress
            $uploadedChunks = count(glob($uploadDir . '/chunk_*'));
            $progress->update([
                'uploaded_chunks' => $uploadedChunks,
            ]);

            return response()->json([
                'success' => true,
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $progress->total_chunks,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Finalize the chunked upload by combining all chunks.
     */
    public function finalize(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chat_name' => 'required|string|max:255',
            'chat_description' => 'nullable|string|max:1000',
        ]);

        $uploadId = $request->upload_id;
        $uploadDir = storage_path('app/uploads/' . $uploadId);

        // Find the progress record
        $progress = ImportProgress::where('upload_id', $uploadId)->first();

        if (!$progress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        // Verify user owns this upload
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Get all chunk files in order
            $chunks = glob($uploadDir . '/chunk_*');
            sort($chunks);

            if (count($chunks) !== $progress->total_chunks) {
                return response()->json([
                    'error' => 'Not all chunks uploaded',
                    'expected' => $progress->total_chunks,
                    'received' => count($chunks),
                ], 400);
            }

            // Combine chunks into final file
            $finalPath = 'imports/' . $progress->filename;
            $finalFullPath = storage_path('app/' . $finalPath);

            // Ensure imports directory exists
            $importsDir = storage_path('app/imports');
            if (!file_exists($importsDir)) {
                mkdir($importsDir, 0755, true);
            }

            // Open final file for writing
            $finalFile = fopen($finalFullPath, 'wb');

            if (!$finalFile) {
                throw new \Exception('Could not create final file');
            }

            // Append each chunk
            foreach ($chunks as $chunkPath) {
                $chunkData = file_get_contents($chunkPath);
                fwrite($finalFile, $chunkData);
                unlink($chunkPath); // Delete chunk after appending
            }

            fclose($finalFile);

            // Remove upload directory
            rmdir($uploadDir);

            // Update progress record
            $progress->update([
                'file_path' => $finalPath,
                'status' => 'pending',
            ]);

            $progress->addLog("File upload completed, combined from {$progress->total_chunks} chunks");

            // Dispatch processing job
            ProcessChatImportJob::dispatch(
                $progress->id,
                $finalFullPath,
                $request->chat_name,
                $request->chat_description,
                auth()->id(),
                $progress->is_zip
            );

            $progress->addLog("Processing job dispatched");

            return response()->json([
                'success' => true,
                'progress_id' => $progress->id,
                'redirect_url' => route('import.progress', $progress),
            ]);

        } catch (\Exception $e) {
            $progress->update([
                'status' => 'failed',
                'error_message' => 'Upload finalization failed: ' . $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get upload status.
     */
    public function status($uploadId)
    {
        $progress = ImportProgress::where('upload_id', $uploadId)->first();

        if (!$progress) {
            return response()->json(['error' => 'Upload not found'], 404);
        }

        // Verify user owns this upload
        if ($progress->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'uploaded_chunks' => $progress->uploaded_chunks,
            'total_chunks' => $progress->total_chunks,
            'status' => $progress->status,
        ]);
    }
}
