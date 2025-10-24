<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Dashboard
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('import.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    New Import
                </a>
                <a href="{{ route('chats.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    All Chats
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Summary Stats -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-3">
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <span class="text-gray-600">Total:</span>
                            <span class="font-semibold ml-1">{{ $imports->total() }}</span>
                        </div>
                        <div>
                            <span class="text-blue-600">In Progress:</span>
                            <span class="font-semibold ml-1" id="in-progress-count">{{ $imports->whereIn('status', ['pending', 'uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'])->count() }}</span>
                        </div>
                        <div>
                            <span class="text-green-600">Completed:</span>
                            <span class="font-semibold ml-1" id="completed-count">{{ $imports->where('status', 'completed')->count() }}</span>
                        </div>
                        <div>
                            <span class="text-red-600">Failed:</span>
                            <span class="font-semibold ml-1" id="failed-count">{{ $imports->where('status', 'failed')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Updates Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center">
                <svg id="live-indicator" class="animate-pulse h-3 w-3 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3" />
                </svg>
                <span class="text-sm text-blue-700 font-semibold">Live updates enabled - refreshing every 3 seconds</span>
            </div>

            <!-- Queue Worker Status -->
            @if(!$queueWorkerRunning)
            <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-1">⚠️ Queue Worker Not Running</h3>
                        <p class="text-sm text-red-700 mb-2">
                            The background queue worker is not running. Import jobs will not be processed until the worker is started.
                            @if($pendingJobsCount > 0)
                            <strong>{{ $pendingJobsCount }} job(s) waiting in queue.</strong>
                            @endif
                        </p>
                        <p class="text-xs text-red-600">
                            Contact your administrator to start the queue worker or restart the development server.
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-green-50 border border-green-300 rounded-lg p-4 mb-6 flex items-center">
                <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm text-green-700 font-semibold">✓ Queue Worker Running
                @if($pendingJobsCount > 0)
                    ({{ $pendingJobsCount }} job(s) in queue)
                @endif
                </span>
            </div>
            @endif

            <!-- Import Jobs Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Imports</h3>

                    @if ($imports->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p class="mb-4">No imports yet.</p>
                            <a href="{{ route('import.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Start Your First Import
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Media</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="imports-table">
                                    @foreach ($imports as $import)
                                        <tr data-import-id="{{ $import->id }}" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $import->filename }}</div>
                                                @if ($import->chat)
                                                    <div class="text-xs text-gray-500">{{ $import->chat->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    @if($import->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($import->status === 'failed') bg-red-100 text-red-800
                                                    @elseif(in_array($import->status, ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'])) bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ str_replace('_', ' ', ucfirst($import->status)) }}
                                                </span>
                                                @if (in_array($import->status, ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media']))
                                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                                        <div class="progress-bar bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $import->progress_percentage }}%"></div>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1 progress-text">{{ $import->progress_percentage }}%</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900 messages-count">
                                                    <span class="processed">{{ number_format($import->processed_messages) }}</span>
                                                    @if($import->total_messages > 0)
                                                        / <span class="total">{{ number_format($import->total_messages) }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 media-counts">
                                                    @if($import->total_media > 0)
                                                        <div>📷 <span class="images">{{ $import->images_count }}</span></div>
                                                        <div>🎥 <span class="videos">{{ $import->videos_count }}</span></div>
                                                        <div>🎵 <span class="audio">{{ $import->audio_count }}</span></div>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $import->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex gap-2">
                                                        <a href="{{ route('import.progress', $import) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                                        @if ($import->chat)
                                                            <a href="{{ route('chats.show', $import->chat) }}" class="text-green-600 hover:text-green-900">Open Chat</a>
                                                        @endif
                                                    </div>
                                                    <div class="flex gap-2">
                                                        @if (in_array($import->status, ['pending', 'uploading', 'processing', 'parsing', 'extracting', 'creating_chat', 'importing_messages', 'processing_media']))
                                                            <form action="{{ route('import.cancel', $import) }}" method="POST" class="inline"
                                                                  onsubmit="return confirm('Are you sure you want to cancel this import?');">
                                                                @csrf
                                                                <button type="submit" class="text-orange-600 hover:text-orange-900">Cancel</button>
                                                            </form>
                                                        @endif
                                                        @if (in_array($import->status, ['cancelled', 'failed', 'completed']))
                                                            <form action="{{ route('import.deleteFiles', $import) }}" method="POST" class="inline"
                                                                  onsubmit="return confirm('Are you sure you want to delete all files for this import? This cannot be undone.');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete Files</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $imports->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        let updateInterval;

        function updateDashboard() {
            fetch('/import/dashboard/status')
                .then(response => response.json())
                .then(data => {
                    // Update each import row
                    data.forEach(importData => {
                        const row = document.querySelector(`tr[data-import-id="${importData.id}"]`);
                        if (!row) return;

                        // Update status badge
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            const statusText = importData.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            statusBadge.textContent = statusText;

                            // Update badge color
                            statusBadge.className = 'status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
                            const activeStatuses = ['uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'];

                            if (importData.status === 'completed') {
                                statusBadge.classList.add('bg-green-100', 'text-green-800');
                            } else if (importData.status === 'failed') {
                                statusBadge.classList.add('bg-red-100', 'text-red-800');
                            } else if (activeStatuses.includes(importData.status)) {
                                statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                            } else {
                                statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                            }
                        }

                        // Update progress bar
                        const progressBar = row.querySelector('.progress-bar');
                        const progressText = row.querySelector('.progress-text');
                        if (progressBar && progressText) {
                            progressBar.style.width = importData.progress_percentage + '%';
                            progressText.textContent = importData.progress_percentage + '%';
                        }

                        // Update message counts
                        const processedSpan = row.querySelector('.messages-count .processed');
                        const totalSpan = row.querySelector('.messages-count .total');
                        if (processedSpan) processedSpan.textContent = importData.processed_messages.toLocaleString();
                        if (totalSpan) totalSpan.textContent = importData.total_messages.toLocaleString();

                        // Update media counts
                        const imagesSpan = row.querySelector('.media-counts .images');
                        const videosSpan = row.querySelector('.media-counts .videos');
                        const audioSpan = row.querySelector('.media-counts .audio');
                        if (imagesSpan) imagesSpan.textContent = importData.images_count;
                        if (videosSpan) videosSpan.textContent = importData.videos_count;
                        if (audioSpan) audioSpan.textContent = importData.audio_count;
                    });

                    // Update summary counts
                    const inProgress = data.filter(i => ['pending', 'uploading', 'extracting', 'parsing', 'creating_chat', 'importing_messages', 'processing_media'].includes(i.status)).length;
                    const completed = data.filter(i => i.status === 'completed').length;
                    const failed = data.filter(i => i.status === 'failed').length;

                    document.getElementById('in-progress-count').textContent = inProgress;
                    document.getElementById('completed-count').textContent = completed;
                    document.getElementById('failed-count').textContent = failed;

                    // If no active imports, slow down polling
                    if (inProgress === 0) {
                        clearInterval(updateInterval);
                        updateInterval = setInterval(updateDashboard, 10000); // Poll every 10s instead
                    }
                })
                .catch(error => {
                    console.error('Error updating dashboard:', error);
                });
        }

        // Start polling every 3 seconds
        updateInterval = setInterval(updateDashboard, 3000);

        // Update once immediately
        updateDashboard();
    </script>
</x-app-layout>
