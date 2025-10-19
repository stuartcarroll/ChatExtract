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
            'chunk_index' => 'required|integer|min:0|max:10000',
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
            // Check if chunk already exists (resume case)
            $chunkPath = $uploadDir . '/chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);

            if (file_exists($chunkPath)) {
                // Chunk already uploaded, skip it
                $uploadedChunks = count(glob($uploadDir . '/chunk_*'));
                return response()->json([
                    'success' => true,
                    'uploaded_chunks' => $uploadedChunks,
                    'total_chunks' => $progress->total_chunks,
                    'skipped' => true,
                ]);
            }

            // Save chunk
            $uploadedFile = $request->file('chunk');

            if (!$uploadedFile || !$uploadedFile->isValid()) {
                \Log::error('Chunk upload failed - invalid file', [
                    'upload_id' => $uploadId,
                    'chunk_index' => $chunkIndex,
                    'error' => $uploadedFile ? $uploadedFile->getErrorMessage() : 'No file uploaded',
                ]);
                return response()->json([
                    'error' => 'Invalid chunk file: ' . ($uploadedFile ? $uploadedFile->getErrorMessage() : 'No file')
                ], 400);
            }

            $uploadedFile->move($uploadDir, basename($chunkPath));

            // Verify chunk was saved
            if (!file_exists($chunkPath)) {
                throw new \Exception('Chunk file was not saved to disk');
            }

            // Update progress
            $uploadedChunks = count(glob($uploadDir . '/chunk_*'));
            $progress->update([
                'uploaded_chunks' => $uploadedChunks,
            ]);

            // Log to processing log every 10 chunks or on milestones
            if ($uploadedChunks % 10 === 0 || $uploadedChunks === 1 || $uploadedChunks === $progress->total_chunks) {
                $percentage = round(($uploadedChunks / $progress->total_chunks) * 100, 1);
                $progress->addLog("Uploading: {$uploadedChunks}/{$progress->total_chunks} chunks ({$percentage}%)");
            }

            \Log::info('Chunk uploaded successfully', [
                'upload_id' => $uploadId,
                'chunk_index' => $chunkIndex,
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $progress->total_chunks,
            ]);

            return response()->json([
                'success' => true,
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $progress->total_chunks,
            ]);
        } catch (\Exception $e) {
            \Log::error('Chunk upload exception', [
                'upload_id' => $uploadId,
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

            // Append each chunk using streaming to avoid memory issues
            foreach ($chunks as $chunkPath) {
                $chunkHandle = fopen($chunkPath, 'rb');
                if ($chunkHandle) {
                    while (!feof($chunkHandle)) {
                        fwrite($finalFile, fread($chunkHandle, 8192)); // 8KB chunks
                    }
                    fclose($chunkHandle);
                }
                unlink($chunkPath); // Delete chunk after appending
            }

            fclose($finalFile);

            // Verify final file was created successfully
            if (!file_exists($finalFullPath)) {
                throw new \Exception('Final file does not exist after combining chunks');
            }

            $finalSize = filesize($finalFullPath);
            if ($finalSize === 0) {
                throw new \Exception('Final file is empty after combining chunks');
            }

            \Log::info('Chunks combined successfully', [
                'upload_id' => $uploadId,
                'final_path' => $finalFullPath,
                'final_size' => $finalSize,
                'total_chunks' => $progress->total_chunks,
            ]);

            // Remove upload directory
            rmdir($uploadDir);

            // Update progress record
            $progress->update([
                'file_path' => $finalPath,
                'status' => 'pending',
            ]);

            $progress->addLog("File upload completed, combined from {$progress->total_chunks} chunks (" . round($finalSize / 1024 / 1024, 2) . " MB)");

            // Dispatch processing job
            ProcessChatImportJob::dispatch(
                $progress->id,
                $finalFullPath,
                $request->chat_name,
                $request->chat_description,
                auth()->id(),
                $progress->is_zip
            );

            $progress->addLog("Processing job dispatched for file: $finalFullPath");

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
