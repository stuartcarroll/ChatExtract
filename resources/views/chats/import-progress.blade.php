<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Import Progress: {{ $progress->filename }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-2">Status</h3>
                        <div class="flex items-center">
                            <span id="status-badge" class="px-3 py-1 rounded-full text-sm font-semibold
                                @if($progress->status === 'completed') bg-green-100 text-green-800
                                @elseif($progress->status === 'failed') bg-red-100 text-red-800
                                @elseif(in_array($progress->status, ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'])) bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                <span id="status-text">{{ str_replace('_', ' ', ucfirst($progress->status)) }}</span>
                            </span>
                            <svg id="status-spinner" class="animate-spin ml-3 h-5 w-5 text-blue-500 {{ in_array($progress->status, ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media']) ? '' : 'hidden' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    @if($progress->error_message)
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                            <strong>Error:</strong> <span id="error-message">{{ $progress->error_message }}</span>
                        </div>
                    @endif

                    <!-- Messages Progress -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-semibold text-gray-700">Messages</h4>
                            <span class="text-sm text-gray-600">
                                <span id="processed-messages">{{ number_format($progress->processed_messages) }}</span> /
                                <span id="total-messages">{{ number_format($progress->total_messages) }}</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-6">
                            <div id="messages-progress-bar" class="bg-blue-600 h-6 rounded-full transition-all duration-300 flex items-center justify-center text-white text-xs font-semibold"
                                 style="width: {{ $progress->progress_percentage }}%">
                                <span id="messages-percentage">{{ $progress->progress_percentage }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Media Progress -->
                    <div class="mb-6" id="media-section" style="{{ $progress->total_media > 0 || $progress->status === 'processing' ? '' : 'display: none;' }}">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-semibold text-gray-700">Media Files</h4>
                            <span class="text-sm text-gray-600">
                                <span id="processed-media">{{ number_format($progress->processed_media) }}</span> /
                                <span id="total-media">{{ number_format($progress->total_media) }}</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-6">
                            <div id="media-progress-bar" class="bg-purple-600 h-6 rounded-full transition-all duration-300 flex items-center justify-center text-white text-xs font-semibold"
                                 style="width: {{ $progress->media_progress_percentage }}%">
                                <span id="media-percentage">{{ $progress->media_progress_percentage }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Media Type Breakdown -->
                    <div id="media-breakdown" style="{{ $progress->total_media > 0 ? '' : 'display: none;' }}">
                        <h4 class="font-semibold text-gray-700 mb-3">Media Breakdown</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded text-center">
                                <div class="text-3xl mb-1">📷</div>
                                <div class="text-2xl font-bold text-blue-600" id="images-count">{{ number_format($progress->images_count) }}</div>
                                <div class="text-sm text-gray-600">Photos</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded text-center">
                                <div class="text-3xl mb-1">🎥</div>
                                <div class="text-2xl font-bold text-purple-600" id="videos-count">{{ number_format($progress->videos_count) }}</div>
                                <div class="text-sm text-gray-600">Videos</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded text-center">
                                <div class="text-3xl mb-1">🎵</div>
                                <div class="text-2xl font-bold text-green-600" id="audio-count">{{ number_format($progress->audio_count) }}</div>
                                <div class="text-sm text-gray-600">Audio</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center space-x-4">
                @if($progress->status === 'completed' && $progress->chat_id)
                    <a href="{{ route('chats.show', $progress->chat_id) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
                        View Chat
                    </a>
                @endif
                <a href="{{ route('chats.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded">
                    Back to Chats
                </a>
            </div>
        </div>
    </div>

    <script>
        let pollInterval;
        const progressId = {{ $progress->id }};
        const currentStatus = '{{ $progress->status }}';

        function updateProgress() {
            fetch(`/import/${progressId}/status`)
                .then(response => response.json())
                .then(data => {
                    // Update status with formatted text
                    const statusText = data.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    document.getElementById('status-text').textContent = statusText;

                    // Update status badge colors
                    const badge = document.getElementById('status-badge');
                    badge.className = 'px-3 py-1 rounded-full text-sm font-semibold';
                    const spinner = document.getElementById('status-spinner');

                    const activeStatuses = ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'];

                    if (data.status === 'completed') {
                        badge.classList.add('bg-green-100', 'text-green-800');
                        spinner.classList.add('hidden');
                    } else if (data.status === 'failed') {
                        badge.classList.add('bg-red-100', 'text-red-800');
                        spinner.classList.add('hidden');
                    } else if (activeStatuses.includes(data.status)) {
                        badge.classList.add('bg-blue-100', 'text-blue-800');
                        spinner.classList.remove('hidden');
                    } else {
                        badge.classList.add('bg-gray-100', 'text-gray-800');
                        spinner.classList.add('hidden');
                    }

                    // Update messages progress
                    document.getElementById('processed-messages').textContent = data.processed_messages.toLocaleString();
                    document.getElementById('total-messages').textContent = data.total_messages.toLocaleString();
                    document.getElementById('messages-percentage').textContent = data.progress_percentage + '%';
                    document.getElementById('messages-progress-bar').style.width = data.progress_percentage + '%';

                    // Update media progress
                    if (data.total_media > 0) {
                        document.getElementById('media-section').style.display = 'block';
                        document.getElementById('media-breakdown').style.display = 'block';
                        document.getElementById('processed-media').textContent = data.processed_media.toLocaleString();
                        document.getElementById('total-media').textContent = data.total_media.toLocaleString();
                        document.getElementById('media-percentage').textContent = data.media_progress_percentage + '%';
                        document.getElementById('media-progress-bar').style.width = data.media_progress_percentage + '%';

                        document.getElementById('images-count').textContent = data.images_count.toLocaleString();
                        document.getElementById('videos-count').textContent = data.videos_count.toLocaleString();
                        document.getElementById('audio-count').textContent = data.audio_count.toLocaleString();
                    }

                    // Show error if present
                    if (data.error_message) {
                        document.getElementById('error-message').textContent = data.error_message;
                    }

                    // Stop polling if completed or failed
                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(pollInterval);

                        // Reload page to show action buttons
                        if (data.status === 'completed') {
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching progress:', error);
                });
        }

        // Start polling if still in progress
        const activeStatuses = ['pending', 'uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'];
        if (activeStatuses.includes(currentStatus)) {
            pollInterval = setInterval(updateProgress, 2000); // Poll every 2 seconds
        }
    </script>
</x-app-layout>
