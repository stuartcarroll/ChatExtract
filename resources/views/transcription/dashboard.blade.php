<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Transcription Dashboard
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('transcription.participants') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Manage Consent
                </a>
                <a href="{{ route('chats.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    All Chats
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Privacy Notice -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-blue-700">
                            <strong>Privacy:</strong> Only voice notes from participants who have given consent will be transcribed.
                        </p>
                    </div>
                    <a href="{{ route('transcription.participants') }}" class="text-sm text-blue-600 hover:text-blue-800 underline font-medium">
                        Manage Consent →
                    </a>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <div>
                            <span class="text-gray-600">Total:</span>
                            <span class="font-semibold ml-1" id="total-audio">{{ $chats->sum('total_audio') }}</span>
                        </div>
                        <div>
                            <span class="text-green-600">✓ Transcribed:</span>
                            <span class="font-semibold ml-1" id="transcribed-count">{{ $chats->sum('transcribed') }}</span>
                            @if($chats->sum('total_audio') > 0)
                            <span class="text-xs text-gray-500 ml-1" id="transcribed-percentage">({{ round(($chats->sum('transcribed') / $chats->sum('total_audio')) * 100) }}%)</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-blue-600">⏳ In Progress:</span>
                            <span class="font-semibold ml-1" id="pending-count">{{ $chats->sum('pending') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">⭕ Not Started:</span>
                            <span class="font-semibold ml-1" id="not-started-count">{{ $chats->sum('not_started') }}</span>
                        </div>
                    </div>
                    @if($chats->sum('pending') > 0)
                    <div class="text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded">
                        <strong>Processing Stage:</strong> Transcription jobs are queued and being processed by OpenAI Whisper API. Audio files are being converted to text in real-time.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Live Updates Notice -->
            @if($chats->sum('pending') > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center">
                <svg id="live-indicator" class="animate-pulse h-3 w-3 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3" />
                </svg>
                <span class="text-sm text-blue-700 font-semibold">Live updates enabled - refreshing every 5 seconds</span>
            </div>
            @endif

            <!-- Chats Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Chats with Voice Notes</h3>

                    @if ($chats->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p class="mb-4">No chats with voice notes found.</p>
                            <a href="{{ route('chats.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                View All Chats
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voice Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Message</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="chats-table">
                                    @foreach ($chats as $chat)
                                        <tr data-chat-id="{{ $chat['id'] }}" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $chat['name'] }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <span class="total-audio">{{ number_format($chat['total_audio']) }}</span> total
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($chat['total_audio'] > 0)
                                                <div class="w-full">
                                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                                        <div class="progress-bar bg-green-600 h-2 rounded-full transition-all"
                                                             style="width: {{ round(($chat['transcribed'] / $chat['total_audio']) * 100) }}%">
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <span class="transcribed">{{ $chat['transcribed'] }}</span> /
                                                        <span class="total">{{ $chat['total_audio'] }}</span>
                                                        (<span class="percentage">{{ round(($chat['transcribed'] / $chat['total_audio']) * 100) }}</span>%)
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="space-y-1 status-badges">
                                                    @if($chat['transcribed'] === $chat['total_audio'])
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Complete
                                                        </span>
                                                    @elseif($chat['pending'] > 0)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 animate-pulse">
                                                            <span class="pending">{{ $chat['pending'] }}</span> in progress
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            <span class="not-started">{{ $chat['not_started'] }}</span> not started
                                                        </span>
                                                    @endif
                                                    @if($chat['transcribed'] > 0 && $chat['transcribed'] < $chat['total_audio'])
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <span class="transcribed">{{ $chat['transcribed'] }}</span> done
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ \Carbon\Carbon::parse($chat['last_message_at'])->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <a href="{{ route('chats.show', $chat['id']) }}" class="text-blue-600 hover:text-blue-900">View Chat</a>
                                                @if($chat['not_started'] > 0 || ($chat['transcribed'] < $chat['total_audio'] && $chat['pending'] === 0))
                                                <form action="{{ route('chats.transcribe', $chat['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900"
                                                            onclick="return confirm('Start transcribing all remaining voice notes? This will use OpenAI API credits.')">
                                                        Transcribe All
                                                    </button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        let updateInterval;

        function updateDashboard() {
            fetch('/transcription/dashboard/status')
                .then(response => response.json())
                .then(data => {
                    let totalAudio = 0;
                    let totalTranscribed = 0;
                    let totalPending = 0;
                    let totalNotStarted = 0;

                    // Update each chat row
                    data.forEach(chatData => {
                        const row = document.querySelector(`tr[data-chat-id="${chatData.id}"]`);
                        if (!row) return;

                        totalAudio += chatData.total_audio;
                        totalTranscribed += chatData.transcribed;
                        totalPending += chatData.pending;
                        totalNotStarted += chatData.not_started;

                        // Update progress bar
                        const progressBar = row.querySelector('.progress-bar');
                        if (progressBar && chatData.total_audio > 0) {
                            const percentage = Math.round((chatData.transcribed / chatData.total_audio) * 100);
                            progressBar.style.width = percentage + '%';

                            const percentageSpan = row.querySelector('.percentage');
                            if (percentageSpan) percentageSpan.textContent = percentage;
                        }

                        // Update counts
                        const transcribedSpan = row.querySelector('.transcribed');
                        const pendingSpan = row.querySelector('.pending');
                        const notStartedSpan = row.querySelector('.not-started');

                        if (transcribedSpan) transcribedSpan.textContent = chatData.transcribed;
                        if (pendingSpan) pendingSpan.textContent = chatData.pending;
                        if (notStartedSpan) notStartedSpan.textContent = chatData.not_started;

                        // Update status badges with animation for pending
                        const statusBadges = row.querySelector('.status-badges');
                        if (statusBadges && chatData.pending > 0) {
                            const pendingBadge = statusBadges.querySelector('.animate-pulse');
                            if (!pendingBadge && statusBadges.querySelector('.pending')) {
                                // Add animation if not present
                                const badge = statusBadges.querySelector('.px-2');
                                if (badge) badge.classList.add('animate-pulse');
                            }
                        } else if (statusBadges && chatData.pending === 0) {
                            // Remove animation when complete
                            const badge = statusBadges.querySelector('.animate-pulse');
                            if (badge) badge.classList.remove('animate-pulse');
                        }
                    });

                    // Update summary stats
                    document.getElementById('total-audio').textContent = totalAudio.toLocaleString();
                    document.getElementById('transcribed-count').textContent = totalTranscribed.toLocaleString();
                    document.getElementById('pending-count').textContent = totalPending.toLocaleString();
                    document.getElementById('not-started-count').textContent = totalNotStarted.toLocaleString();

                    // Update percentage
                    const percentageEl = document.getElementById('transcribed-percentage');
                    if (percentageEl && totalAudio > 0) {
                        const percentage = Math.round((totalTranscribed / totalAudio) * 100);
                        percentageEl.textContent = `(${percentage}%)`;
                    }

                    // If no pending transcriptions, slow down polling
                    if (totalPending === 0) {
                        clearInterval(updateInterval);
                        updateInterval = setInterval(updateDashboard, 30000); // Poll every 30s instead
                    }
                })
                .catch(error => {
                    console.error('Error updating dashboard:', error);
                });
        }

        // Start polling every 5 seconds if there are pending transcriptions
        @if($chats->sum('pending') > 0)
        updateInterval = setInterval(updateDashboard, 5000);

        // Update once immediately
        updateDashboard();
        @endif
    </script>
</x-app-layout>
