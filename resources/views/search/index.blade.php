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
                            <button type="button" @click="open = !open" class="flex items-center text-sm text-gray-600 hover:text-gray-900 mb-3">
                                <svg class="w-4 h-4 mr-2 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <span x-text="open ? 'Hide filters' : 'Show filters'"></span>
                            </button>

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
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-sm rounded-md border-gray-300">
                                </div>

                                <!-- Date To -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full text-sm rounded-md border-gray-300">
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
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                @if ($message->media->isNotEmpty())
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                        {{ $message->media->count() }} {{ Str::plural('attachment', $message->media->count()) }}
                                                    </span>
                                                @endif
                                                @foreach ($message->tags as $tag)
                                                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                            <a href="{{ route('chats.show', $message->chat_id) }}#message-{{ $message->id }}" class="text-sm text-blue-600 hover:text-blue-800">
                                                View in chat →
                                            </a>
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
