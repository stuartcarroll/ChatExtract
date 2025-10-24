<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $chat->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('chats.gallery', $chat) }}" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    📷 Gallery
                </a>
                @if(auth()->user()->isAdmin())
                <form action="{{ route('chats.transcribe', $chat) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Transcribe all voice notes in this chat? This will use OpenAI API credits.')">
                        🎤 Transcribe All
                    </button>
                </form>
                @endif
                <a href="{{ route('search.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Search
                </a>
                <a href="{{ route('chats.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Back to Chats
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Chat Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Chat Statistics</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm">Total Messages</p>
                            <p class="text-2xl font-bold">{{ number_format($statistics['total_messages']) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Participants</p>
                            <p class="text-2xl font-bold">{{ number_format($statistics['total_participants']) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Voice Notes</p>
                            <p class="text-2xl font-bold">{{ number_format($statistics['audio_files']) }}</p>
                            @if($statistics['audio_files'] > 0)
                            <p class="text-xs text-gray-500 mt-1">
                                {{ number_format($statistics['transcribed_audio']) }} transcribed
                                @if($statistics['transcribed_audio'] > 0)
                                <span class="text-green-600">({{ round(($statistics['transcribed_audio'] / $statistics['audio_files']) * 100) }}%)</span>
                                @endif
                            </p>
                            @endif
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Date Range</p>
                            <p class="text-sm font-medium">
                                @if ($statistics['date_range']['start'])
                                    {{ \Carbon\Carbon::parse($statistics['date_range']['start'])->format('M d, Y') }}
                                    <br>to<br>
                                    {{ \Carbon\Carbon::parse($statistics['date_range']['end'])->format('M d, Y') }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Filter Messages</h3>
                    <form action="{{ route('chats.filter', $chat) }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if (isset($participants))
                            <div>
                                <label for="participant_id" class="block text-sm font-medium text-gray-700">Participant</label>
                                <select name="participant_id" id="participant_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">All Participants</option>
                                    @foreach ($participants as $participant)
                                        <option value="{{ $participant->id }}" {{ request('participant_id') == $participant->id ? 'selected' : '' }}>
                                            {{ $participant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center">
                                <input type="checkbox" name="has_media" value="1" {{ request('has_media') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Has Media</span>
                            </label>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Messages -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Messages</h3>

                    @if ($messages->isEmpty())
                        <p class="text-gray-600 text-center py-8">No messages found.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($messages as $message)
                                <div id="message-{{ $message->id }}" class="border-l-4 {{ $message->is_system_message ? 'border-gray-400 bg-gray-50' : 'border-blue-500' }} pl-4 py-3 scroll-mt-4">
                                    <div class="flex justify-between items-start mb-1">
                                        <div>
                                            <span class="font-semibold text-sm {{ $message->is_system_message ? 'text-gray-600' : 'text-gray-800' }}">
                                                {{ $message->participant ? $message->participant->name : 'Unknown' }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            {{ $message->sent_at->format('M d, Y H:i') }}
                                        </span>
                                    </div>

                                    <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ $message->content }}</p>

                                    @if ($message->media->isNotEmpty())
                                        <div class="mt-3 space-y-2">
                                            @foreach ($message->media as $media)
                                                @if ($media->type === 'image')
                                                    <div class="inline-block">
                                                        <a href="{{ asset('storage/' . $media->file_path) }}" target="_blank">
                                                            <img src="{{ asset('storage/' . $media->file_path) }}"
                                                                 alt="{{ $media->filename }}"
                                                                 class="max-w-xs max-h-64 rounded-lg shadow hover:opacity-90 transition">
                                                        </a>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <p class="text-xs text-gray-500">{{ $media->filename }}</p>
                                                            <a href="{{ route('media.download', $media->id) }}"
                                                               class="text-xs text-green-600 hover:text-green-800 hover:underline ml-2"
                                                               download>
                                                                ⬇ Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                @elseif ($media->type === 'video')
                                                    <div class="max-w-md">
                                                        <video controls class="w-full rounded-lg shadow">
                                                            <source src="{{ asset('storage/' . $media->file_path) }}" type="{{ $media->mime_type }}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <p class="text-xs text-gray-500">{{ $media->filename }}</p>
                                                            <a href="{{ route('media.download', $media->id) }}"
                                                               class="text-xs text-green-600 hover:text-green-800 hover:underline"
                                                               download>
                                                                ⬇ Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                @elseif ($media->type === 'audio')
                                                    <div class="max-w-md bg-gray-50 p-3 rounded-lg">
                                                        <audio controls class="w-full">
                                                            <source src="{{ asset('storage/' . $media->file_path) }}" type="{{ $media->mime_type }}">
                                                            Your browser does not support the audio tag.
                                                        </audio>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <p class="text-xs text-gray-500">{{ $media->filename }}</p>
                                                            <a href="{{ route('media.download', $media->id) }}"
                                                               class="text-xs text-green-600 hover:text-green-800 hover:underline"
                                                               download>
                                                                ⬇ Download
                                                            </a>
                                                        </div>

                                                        @if($media->transcription)
                                                            <!-- Show transcription -->
                                                            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded">
                                                                <div class="flex items-start">
                                                                    <svg class="w-4 h-4 text-blue-600 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                                    </svg>
                                                                    <div class="flex-1">
                                                                        <p class="text-xs font-semibold text-blue-900">Transcription:</p>
                                                                        <p class="text-sm text-gray-700 mt-1">{{ $media->transcription }}</p>
                                                                        <p class="text-xs text-gray-500 mt-1">Transcribed {{ $media->transcribed_at->diffForHumans() }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @elseif($media->transcription_requested)
                                                            <!-- Transcription pending -->
                                                            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                                                ⏳ Transcription in progress...
                                                            </div>
                                                        @elseif(auth()->user()->isAdmin())
                                                            <!-- Transcribe button (admin only) -->
                                                            <form action="{{ route('media.transcribe', $media) }}" method="POST" class="mt-2">
                                                                @csrf
                                                                <button type="submit" class="text-xs bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded transition">
                                                                    🎤 Transcribe
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="bg-gray-100 px-4 py-2 rounded inline-flex items-center">
                                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                        </svg>
                                                        <a href="{{ asset('storage/' . $media->file_path) }}"
                                                           download="{{ $media->filename }}"
                                                           class="text-sm text-blue-600 hover:underline">
                                                            {{ $media->filename }} ({{ number_format($media->file_size / 1024, 2) }} KB)
                                                        </a>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Tagging Section -->
                                    <div class="mt-3 bg-gray-50 rounded-lg p-2 border border-gray-200" x-data="{ showTags: false, showNewTag: false, newTagName: '' }">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-gray-600">Tags:</span>
                                            <button type="button" @click="showTags = !showTags" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                <span x-show="!showTags">+ Add Tag</span>
                                                <span x-show="showTags">Hide</span>
                                            </button>
                                        </div>

                                        <!-- Current tags -->
                                        <div class="flex flex-wrap gap-1 mb-1">
                                            @foreach($message->tags as $tag)
                                            <form action="{{ route('messages.tag', $message) }}" method="POST" class="inline" onclick="event.stopPropagation()">
                                                @csrf
                                                <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                                                <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition">
                                                    {{ $tag->name }}
                                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            @endforeach
                                        </div>

                                        <!-- Available tags (collapsible) -->
                                        <div x-show="showTags" x-collapse class="pt-1 border-t border-gray-200 mt-1">
                                            <div class="flex flex-wrap gap-1 mb-2">
                                                @foreach($tags as $tag)
                                                    @if(!$message->tags->contains($tag->id))
                                                    <form action="{{ route('messages.tag', $message) }}" method="POST" class="inline" onclick="event.stopPropagation()">
                                                        @csrf
                                                        <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                                                        <button type="submit" class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 transition">
                                                            + {{ $tag->name }}
                                                        </button>
                                                    </form>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <!-- Create new tag inline -->
                                            <div class="border-t border-gray-300 pt-2">
                                                <button type="button" @click="showNewTag = !showNewTag" class="text-xs text-green-600 hover:text-green-800 font-medium mb-1">
                                                    <span x-show="!showNewTag">+ Create New Tag</span>
                                                    <span x-show="showNewTag">Cancel</span>
                                                </button>
                                                <form x-show="showNewTag" x-collapse action="{{ route('tags.store') }}" method="POST" class="flex gap-1" onclick="event.stopPropagation()">
                                                    @csrf
                                                    <input type="hidden" name="redirect_back" value="1">
                                                    <input type="text" name="name" x-model="newTagName" placeholder="New tag name" class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500" required maxlength="50" onclick="event.stopPropagation()">
                                                    <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition" onclick="event.stopPropagation()">
                                                        Create
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($highlightMessageId) && $highlightMessageId)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageId = {{ $highlightMessageId }};
            const messageElement = document.getElementById('message-' + messageId);

            if (messageElement) {
                // Scroll to the message with smooth behavior
                setTimeout(() => {
                    messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Add highlight animation
                    messageElement.style.transition = 'background-color 0.5s ease';
                    messageElement.style.backgroundColor = '#fef3c7'; // yellow-100

                    // Remove highlight after 3 seconds
                    setTimeout(() => {
                        messageElement.style.backgroundColor = '';
                    }, 3000);
                }, 100);
            }
        });
    </script>
    @endif
</x-app-layout>
