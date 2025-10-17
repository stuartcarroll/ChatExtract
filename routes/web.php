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

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Chat routes
    Route::resource('chats', ChatController::class);
    Route::get('/chats/{chat}/filter', [ChatController::class, 'filter'])->name('chats.filter');
    Route::get('/chats/{chat}/gallery', [ChatController::class, 'gallery'])->name('chats.gallery');

    // Global Gallery
    Route::get('/gallery', [App\Http\Controllers\GalleryController::class, 'index'])->name('gallery.index');

    // Import routes
    Route::get('/import', [ImportController::class, 'create'])->name('import.create');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');
    Route::get('/import/dashboard', [ImportController::class, 'dashboard'])->name('import.dashboard');
    Route::get('/import/dashboard/status', [ImportController::class, 'dashboardStatus'])->name('import.dashboard.status');
    Route::get('/import/{progress}/progress', [ImportController::class, 'progress'])->name('import.progress');
    Route::get('/import/{progress}/status', [ImportController::class, 'progressStatus'])->name('import.status');
    Route::post('/import/{progress}/retry', [ImportController::class, 'retry'])->name('import.retry');
    Route::post('/chats/{chat}/media', [ImportController::class, 'uploadMedia'])->name('import.media');

    // Search routes
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::post('/search', [SearchController::class, 'search'])->name('search.perform');
    Route::post('/search/advanced', [SearchController::class, 'advanced'])->name('search.advanced');

    // Tag routes
    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('/messages/{message}/tag', [TagController::class, 'tagMessage'])->name('messages.tag');

    // Transcription routes (admin only)
    Route::post('/media/{media}/transcribe', [TranscriptionController::class, 'transcribeSingle'])->name('media.transcribe');
    Route::post('/chats/{chat}/transcribe', [TranscriptionController::class, 'transcribeChat'])->name('chats.transcribe');
    Route::get('/chats/{chat}/transcription-status', [TranscriptionController::class, 'status'])->name('chats.transcription.status');
});

require __DIR__.'/auth.php';
