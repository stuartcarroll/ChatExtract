<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('chats.index');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('chats.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Two-Factor Challenge routes (outside auth middleware)
Route::get('/two-factor/challenge', [App\Http\Controllers\TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
Route::post('/two-factor/challenge', [App\Http\Controllers\TwoFactorChallengeController::class, 'store'])->name('two-factor.verify');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Two-Factor Authentication routes
    Route::get('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/two-factor', [App\Http\Controllers\TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::get('/two-factor/recovery-codes', [App\Http\Controllers\TwoFactorController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes', [App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes.regenerate');

    // Chat routes
    Route::resource('chats', ChatController::class);
    Route::get('/chats/{chat}/filter', [ChatController::class, 'filter'])->name('chats.filter');
    Route::get('/chats/{chat}/gallery', [ChatController::class, 'gallery'])->name('chats.gallery');

    // Global Gallery
    Route::get('/gallery', [App\Http\Controllers\GalleryController::class, 'index'])->name('gallery.index');

    // Import routes (chat_user and admin only, NOT view_only)
    Route::middleware('chat_user')->group(function() {
        Route::get('/import', [ImportController::class, 'create'])->name('import.create');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');
        Route::get('/import/dashboard', [ImportController::class, 'dashboard'])->name('import.dashboard');
        Route::get('/import/dashboard/status', [ImportController::class, 'dashboardStatus'])->name('import.dashboard.status');
        Route::get('/import/{progress}/progress', [ImportController::class, 'progress'])->name('import.progress');
        Route::get('/import/{progress}/status', [ImportController::class, 'progressStatus'])->name('import.status');
        Route::post('/import/{progress}/retry', [ImportController::class, 'retry'])->name('import.retry');
        Route::post('/import/{progress}/cancel', [ImportController::class, 'cancel'])->name('import.cancel');
        Route::delete('/import/{progress}/delete-files', [ImportController::class, 'deleteFiles'])->name('import.deleteFiles');
        Route::post('/chats/{chat}/media', [ImportController::class, 'uploadMedia'])->name('import.media');
    });

    // Chunked upload routes
    Route::post('/upload/initiate', [App\Http\Controllers\ChunkedUploadController::class, 'initiate'])->name('upload.initiate');
    Route::post('/upload/chunk', [App\Http\Controllers\ChunkedUploadController::class, 'uploadChunk'])->name('upload.chunk');
    Route::post('/upload/finalize', [App\Http\Controllers\ChunkedUploadController::class, 'finalize'])->name('upload.finalize');
    Route::get('/upload/{uploadId}/status', [App\Http\Controllers\ChunkedUploadController::class, 'status'])->name('upload.status');

    // Search routes
    Route::get('/search', [SearchController::class, 'search'])->name('search.index');
    Route::post('/search/advanced', [SearchController::class, 'advanced'])->name('search.advanced');

    // Tag routes
    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags/{tag}/export', [TagController::class, 'export'])->name('tags.export');

    // Tag creation/modification (chat_user and admin only, NOT view_only)
    Route::middleware('chat_user')->group(function() {
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/messages/{message}/tag', [TagController::class, 'tagMessage'])->name('messages.tag');
        Route::post('/messages/batch-tag', [TagController::class, 'batchTag'])->name('messages.batch-tag');
    });

    // Transcription routes (admin only)
    Route::middleware('admin')->group(function() {
        Route::get('/transcription/dashboard', [TranscriptionController::class, 'dashboard'])->name('transcription.dashboard');
        Route::get('/transcription/dashboard/status', [TranscriptionController::class, 'dashboardStatus'])->name('transcription.dashboard.status');
        Route::get('/transcription/participants', [TranscriptionController::class, 'participants'])->name('transcription.participants');
        Route::get('/transcription/participants/{participant}', [TranscriptionController::class, 'participantProfile'])->name('transcription.participant.profile');
        Route::post('/transcription/participants/{participant}/consent', [TranscriptionController::class, 'updateConsent'])->name('transcription.consent.update');
        Route::post('/media/{media}/transcribe', [TranscriptionController::class, 'transcribeSingle'])->name('media.transcribe');
        Route::post('/chats/{chat}/transcribe', [TranscriptionController::class, 'transcribeChat'])->name('chats.transcribe');
        Route::get('/chats/{chat}/transcription-status', [TranscriptionController::class, 'status'])->name('chats.transcription.status');
    });

    // Export and download routes
    Route::post('/export', [App\Http\Controllers\ExportController::class, 'export'])->name('export.bulk');
    Route::get('/media/{media}/download', [App\Http\Controllers\ExportController::class, 'downloadMedia'])->name('media.download');

    // Admin routes (admin only)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function() {
        // User management
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);

        // Group management
        Route::resource('groups', App\Http\Controllers\Admin\GroupController::class);
        Route::post('groups/{group}/users', [App\Http\Controllers\Admin\GroupController::class, 'addUser'])->name('groups.users.add');
        Route::delete('groups/{group}/users/{user}', [App\Http\Controllers\Admin\GroupController::class, 'removeUser'])->name('groups.users.remove');

        // Chat access management
        Route::resource('chat-access', App\Http\Controllers\Admin\ChatAccessController::class);
        Route::post('chats/{chat}/access', [App\Http\Controllers\Admin\ChatAccessController::class, 'grantFromChat'])->name('chats.access.grant');

        // Tag access management
        Route::resource('tag-access', App\Http\Controllers\Admin\TagAccessController::class)->except(['edit', 'update']);
        Route::post('tags/{tag}/access', [App\Http\Controllers\Admin\TagAccessController::class, 'grantFromTag'])->name('tags.access.grant');
    });
});

require __DIR__.'/auth.php';
