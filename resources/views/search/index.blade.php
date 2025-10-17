<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Search Messages') }}
            </h2>
            <a href="{{ route('chats.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Back to Chats
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Search</h3>

                    <form action="{{ route('search.perform') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="query" class="block text-sm font-medium text-gray-700 mb-2">
                                Search Query <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="query"
                                id="query"
                                value="{{ request('query') }}"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Enter keywords to search..."
                            >
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="chat_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Chat (Optional)
                                </label>
                                <select name="chat_id" id="chat_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">All Chats</option>
                                    @foreach ($chats as $chat)
                                        <option value="{{ $chat->id }}" {{ request('chat_id') == $chat->id ? 'selected' : '' }}>
                                            {{ $chat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date From
                                </label>
                                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date To
                                </label>
                                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="only_stories" value="1" {{ request('only_stories') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Stories Only</span>
                            </label>

                            @if (isset($tags) && $tags->isNotEmpty())
                                <div class="flex-1">
                                    <label for="tag_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tag (Optional)
                                    </label>
                                    <select name="tag_id" id="tag_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">All Tags</option>
                                        @foreach ($tags as $tag)
                                            <option value="{{ $tag->id }}" {{ request('tag_id') == $tag->id ? 'selected' : '' }}>
                                                {{ $tag->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Advanced Search -->
            <details class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <summary class="p-6 cursor-pointer font-semibold text-lg hover:bg-gray-50">
                    Advanced Search
                </summary>
                <div class="p-6 pt-0">
                    <form action="{{ route('search.advanced') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="adv_query" class="block text-sm font-medium text-gray-700 mb-2">
                                    Search Query (Optional)
                                </label>
                                <input
                                    type="text"
                                    name="query"
                                    id="adv_query"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter keywords..."
                                >
                            </div>

                            <div>
                                <label for="participant_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Participant Name
                                </label>
                                <input
                                    type="text"
                                    name="participant_name"
                                    id="participant_name"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Search by participant..."
                                >
                            </div>

                            <div>
                                <label for="adv_date_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date From
                                </label>
                                <input type="date" name="date_from" id="adv_date_from" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="adv_date_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date To
                                </label>
                                <input type="date" name="date_to" id="adv_date_to" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="min_confidence" class="block text-sm font-medium text-gray-700 mb-2">
                                    Minimum Story Confidence (0-1)
                                </label>
                                <input
                                    type="number"
                                    name="min_confidence"
                                    id="min_confidence"
                                    step="0.1"
                                    min="0"
                                    max="1"
                                    class="w-full rounded-md border-gray-300 shadow-sm"
                                    placeholder="e.g., 0.7"
                                >
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="only_stories" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Stories Only</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" name="has_media" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Has Media</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded">
                                Advanced Search
                            </button>
                        </div>
                    </form>
                </div>
            </details>

            <!-- Search Results -->
            @if (isset($results))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            Search Results
                            @if (method_exists($results, 'total'))
                                ({{ number_format($results->total()) }} found)
                            @else
                                ({{ number_format($results->count()) }} found)
                            @endif
                        </h3>

                        @if ($results->isEmpty())
                            <p class="text-gray-600 text-center py-8">No messages found matching your search.</p>
                        @else
                            <div class="space-y-4">
                                @foreach ($results as $message)
                                    <div class="border-l-4 {{ $message->is_story ? 'border-purple-500 bg-purple-50' : 'border-blue-500' }} pl-4 py-3">
                                        <div class="flex justify-between items-start mb-1">
                                            <div>
                                                <a href="{{ route('chats.show', $message->chat_id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    {{ $message->chat ? $message->chat->name : 'Unknown Chat' }}
                                                </a>
                                                <span class="mx-2 text-gray-400">|</span>
                                                <span class="font-semibold text-sm text-gray-800">
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

                                        <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ Str::limit($message->content, 300) }}</p>

                                        @if ($message->media && $message->media->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($message->media as $media)
                                                    <div class="bg-gray-100 px-3 py-1 rounded text-xs">
                                                        <span class="font-medium">{{ ucfirst($media->type) }}:</span>
                                                        {{ $media->filename }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($message->tags && $message->tags->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($message->tags as $tag)
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif>

                                        <div class="mt-2">
                                            <a href="{{ route('chats.show', $message->chat_id) }}#message-{{ $message->id }}" class="text-blue-600 hover:text-blue-800 text-xs">
                                                View in Chat &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (method_exists($results, 'links'))
                                <div class="mt-6">
                                    {{ $results->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
