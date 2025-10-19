<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Search Messages
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Simple Search -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form action="{{ route('search.perform') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Main Search Input -->
                        <div>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="query"
                                    id="query"
                                    value="{{ request('query') }}"
                                    required
                                    class="w-full pl-12 pr-4 py-4 text-lg rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="Search for messages..."
                                    autofocus
                                >
                                <div class="absolute left-4 top-4">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Filters (Collapsible) -->
                        <div x-data="{ open: {{ count(request()->except('_token', 'query')) > 0 ? 'true' : 'false' }} }" class="border-t pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <button type="button" @click="open = !open" class="flex items-center text-sm text-gray-600 hover:text-gray-900">
                                    <svg class="w-4 h-4 mr-2 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span x-text="open ? 'Hide filters' : 'Show filters'"></span>
                                </button>
                                @if(count(request()->except('_token', 'query')) > 0)
                                <a href="{{ route('search.index') }}" class="text-xs text-red-600 hover:text-red-800">Clear all filters</a>
                                @endif
                            </div>

                            <div x-show="open" x-collapse class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Chat Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Chat</label>
                                    <select name="chat_id" class="w-full text-sm rounded-md border-gray-300">
                                        <option value="">All Chats</option>
                                        @foreach ($chats as $chat)
                                            <option value="{{ $chat->id }}" {{ request('chat_id') == $chat->id ? 'selected' : '' }}>
                                                {{ $chat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Any date" class="w-full text-sm rounded-md border-gray-300">
                                </div>

                                <!-- Date To -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Any date" class="w-full text-sm rounded-md border-gray-300">
                                </div>

                                <!-- Participant Filter -->
                                @if (isset($participants) && $participants->isNotEmpty())
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Participant</label>
                                    <select name="participant_id" class="w-full text-sm rounded-md border-gray-300">
                                        <option value="">All Participants</option>
                                        @foreach ($participants as $participant)
                                            <option value="{{ $participant->id }}" {{ request('participant_id') == $participant->id ? 'selected' : '' }}>
                                                {{ $participant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Media Type Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Media Type</label>
                                    <select name="media_type" class="w-full text-sm rounded-md border-gray-300">
                                        <option value="">All</option>
                                        <option value="has_media" {{ request('media_type') == 'has_media' ? 'selected' : '' }}>Has Media</option>
                                        <option value="no_media" {{ request('media_type') == 'no_media' ? 'selected' : '' }}>Text Only</option>
                                        <option value="image" {{ request('media_type') == 'image' ? 'selected' : '' }}>Photos</option>
                                        <option value="video" {{ request('media_type') == 'video' ? 'selected' : '' }}>Videos</option>
                                        <option value="audio" {{ request('media_type') == 'audio' ? 'selected' : '' }}>Audio</option>
                                    </select>
                                </div>

                                <!-- Tag Filter -->
                                @if (isset($tags) && (is_array($tags) ? count($tags) > 0 : $tags->isNotEmpty()))
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tag</label>
                                    <select name="tag_id" class="w-full text-sm rounded-md border-gray-300">
                                        <option value="">Any Tag</option>
                                        @foreach ($tags as $tag)
                                            <option value="{{ $tag->id }}" {{ request('tag_id') == $tag->id ? 'selected' : '' }}>
                                                {{ $tag->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Stories Only -->
                                <div class="flex items-center">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="only_stories" value="1" {{ request('only_stories') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Stories only</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Active Filters Notice -->
                        @if(count(request()->except('_token', 'query')) > 0)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span class="text-sm text-yellow-800 font-medium">Filters are active - results may be limited</span>
                            </div>
                            <a href="{{ route('search.index') }}" class="text-xs text-yellow-800 hover:text-yellow-900 underline">Clear all</a>
                        </div>
                        @endif

                        <!-- Search Button -->
                        <div class="flex justify-center pt-2">
                            <button type="submit" class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-8 rounded-lg transition shadow-md hover:shadow-lg">
                                Search Messages
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            @if (isset($results))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        @if ($results->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms or filters</p>
                            </div>
                        @else
                            <div class="mb-4 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $results->total() }} {{ Str::plural('result', $results->total()) }} found
                                </h3>
                            </div>

                            <div class="space-y-4">
                                @foreach ($results as $message)
                                    <div class="border-l-4 {{ $message->is_story ? 'border-purple-500' : 'border-gray-300' }} bg-gray-50 p-4 rounded-r-lg hover:bg-gray-100 transition">
                                        <!-- Message Header -->
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="font-semibold text-gray-900">{{ $message->participant->name ?? 'Unknown' }}</span>
                                                <span class="text-sm text-gray-500 ml-2">in {{ $message->chat->name }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @if ($message->is_story)
                                                    <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Story</span>
                                                @endif
                                                <span class="text-xs text-gray-500">{{ $message->sent_at->format('M d, Y H:i') }}</span>
                                            </div>
                                        </div>

                                        <!-- Message Content -->
                                        <p class="text-gray-700 mb-2">{{ Str::limit($message->content, 300) }}</p>

                                        <!-- Media & Tags -->
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center space-x-2">
                                                @if ($message->media->isNotEmpty())
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                        {{ $message->media->count() }} {{ Str::plural('attachment', $message->media->count()) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <a href="{{ route('chats.show', ['chat' => $message->chat_id, 'message' => $message->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                                View in chat →
                                            </a>
                                        </div>

                                        <!-- Tagging Section -->
                                        <div class="bg-gray-50 rounded-lg p-2 border border-gray-200" x-data="{ showTags: false, showNewTag: false, newTagName: '' }">
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
                                                        <input type="text" name="name" x-model="newTagName" placeholder="New tag name" class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500" required maxlength="50">
                                                        <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition">
                                                            Create
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6">
                                {{ $results->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
