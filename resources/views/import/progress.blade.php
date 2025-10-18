<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Progress: {{ $progress->filename }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('import.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Import Dashboard
                </a>
                @if($progress->chat_id)
                <a href="{{ route('chats.show', $progress->chat_id) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    View Chat
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ autoRefresh: {{ in_array($progress->status, ['uploading', 'processing', 'parsing', 'extracting', 'creating_chat', 'importing_messages', 'processing_media']) ? 'true' : 'false' }} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Import Status</h3>
                        <div>
                            @if($progress->status === 'completed')
                                <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    ✓ Completed
                                </span>
                            @elseif($progress->status === 'failed')
                                <span class="px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                                    ✗ Failed
                                </span>
                            @elseif($progress->status === 'cancelled')
                                <span class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold">
                                    Cancelled
                                </span>
                            @else
                                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ ucfirst(str_replace('_', ' ', $progress->status)) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 mb-1">Started</div>
                            <div class="text-lg font-semibold">
                                {{ $progress->started_at ? $progress->started_at->format('M d, H:i') : 'Not started' }}
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 mb-1">Duration</div>
                            <div class="text-lg font-semibold">
                                @if($progress->started_at)
                                    {{ $progress->completed_at ? $progress->started_at->diffForHumans($progress->completed_at, true) : $progress->started_at->diffForHumans(null, true) }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 mb-1">File Size</div>
                            <div class="text-lg font-semibold">
                                @if($progress->upload_id && $progress->file_path && file_exists(storage_path('app/imports/' . basename($progress->file_path))))
                                    {{ number_format(filesize(storage_path('app/imports/' . basename($progress->file_path))) / 1024 / 1024, 2) }} MB
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 mb-1">Type</div>
                            <div class="text-lg font-semibold">
                                {{ $progress->is_zip ? 'ZIP (with media)' : 'Text only' }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        @if(in_array($progress->status, ['uploading', 'processing', 'parsing', 'extracting', 'creating_chat', 'importing_messages', 'processing_media']))
                            <form action="{{ route('import.cancel', $progress) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this import? This cannot be undone.');">
                                @csrf
                                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Cancel Import
                                </button>
                            </form>
                        @endif

                        @if($progress->canRetry())
                            <form action="{{ route('import.retry', $progress) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Retry Import
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Import Statistics</h3>

                    <!-- Messages Progress -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Messages</span>
                            <span class="text-sm text-gray-600">
                                {{ number_format($progress->processed_messages) }} / {{ number_format($progress->total_messages) }}
                                @if($progress->total_messages > 0)
                                    ({{ $progress->progress_percentage }}%)
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full transition-all duration-300"
                                 style="width: {{ $progress->progress_percentage }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Media Progress -->
                    @if($progress->total_media > 0)
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Media Files</span>
                            <span class="text-sm text-gray-600">
                                {{ number_format($progress->processed_media) }} / {{ number_format($progress->total_media) }}
                                ({{ $progress->media_progress_percentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-purple-600 h-4 rounded-full transition-all duration-300"
                                 style="width: {{ $progress->media_progress_percentage }}%">
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Media Type Breakdown -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-purple-600">{{ number_format($progress->images_count) }}</div>
                            <div class="text-sm text-gray-600 mt-1">Images</div>
                        </div>
                        <div class="bg-pink-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-pink-600">{{ number_format($progress->videos_count) }}</div>
                            <div class="text-sm text-gray-600 mt-1">Videos</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ number_format($progress->audio_count) }}</div>
                            <div class="text-sm text-gray-600 mt-1">Voice Notes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            @if($progress->error_message)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Error Details</h3>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4">
                        <p class="text-sm text-red-700 font-mono">{{ $progress->error_message }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Processing Log -->
            @if($progress->processing_log)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Processing Log</h3>
                        <button onclick="document.getElementById('log-content').scrollTop = document.getElementById('log-content').scrollHeight"
                                class="text-sm text-blue-600 hover:text-blue-800">
                            Scroll to bottom
                        </button>
                    </div>
                    <div id="log-content" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-auto max-h-96 whitespace-pre-wrap">{{ $progress->processing_log }}</div>
                </div>
            </div>
            @endif

        </div>
    </div>

    <!-- Auto-refresh script -->
    <script>
        // Auto-refresh every 3 seconds if import is in progress
        const status = '{{ $progress->status }}';
        const inProgressStatuses = ['uploading', 'processing', 'parsing', 'extracting', 'creating_chat', 'importing_messages', 'processing_media'];

        if (inProgressStatuses.includes(status)) {
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        }
    </script>
</x-app-layout>
