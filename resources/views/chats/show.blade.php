<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $chat->name }}
            </h2>
            <div class="flex space-x-2">
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
                            <p class="text-gray-600 text-sm">Stories Detected</p>
                            <p class="text-2xl font-bold">{{ number_format($statistics['total_stories']) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Participants</p>
                            <p class="text-2xl font-bold">{{ number_format($statistics['total_participants']) }}</p>
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
                                <input type="checkbox" name="only_stories" value="1" {{ request('only_stories') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Stories Only</span>
                            </label>
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
                                <div class="border-l-4 {{ $message->is_story ? 'border-purple-500 bg-purple-50' : ($message->is_system_message ? 'border-gray-400 bg-gray-50' : 'border-blue-500') }} pl-4 py-3">
                                    <div class="flex justify-between items-start mb-1">
                                        <div>
                                            <span class="font-semibold text-sm {{ $message->is_system_message ? 'text-gray-600' : 'text-gray-800' }}">
                                                {{ $message->participant ? $message->participant->name : 'Unknown' }}
                                            </span>
                                            @if ($message->is_story)
                                                <span class="ml-2 bg-purple-500 text-white text-xs px-2 py-1 rounded">
                                                    Story ({{ number_format($message->story_confidence * 100) }}%)
                                                </span>
                                            @endif
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
                                                        <p class="text-xs text-gray-500 mt-1">{{ $media->filename }}</p>
                                                    </div>
                                                @elseif ($media->type === 'video')
                                                    <div class="max-w-md">
                                                        <video controls class="w-full rounded-lg shadow">
                                                            <source src="{{ asset('storage/' . $media->file_path) }}" type="{{ $media->mime_type }}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                        <p class="text-xs text-gray-500 mt-1">{{ $media->filename }}</p>
                                                    </div>
                                                @elseif ($media->type === 'audio')
                                                    <div class="max-w-md">
                                                        <audio controls class="w-full">
                                                            <source src="{{ asset('storage/' . $media->file_path) }}" type="{{ $media->mime_type }}">
                                                            Your browser does not support the audio tag.
                                                        </audio>
                                                        <p class="text-xs text-gray-500 mt-1">{{ $media->filename }} (Voice Note)</p>
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

                                    @if ($message->tags->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($message->tags as $tag)
                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
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
</x-app-layout>
