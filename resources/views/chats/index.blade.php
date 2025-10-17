<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Chats') }}
            </h2>
            <a href="{{ route('import.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Import New Chat
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if ($chats->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 text-center">
                        <p class="mb-4">You don't have any chats yet.</p>
                        <a href="{{ route('import.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Import Your First Chat
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($chats as $chat)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-2">
                                    <a href="{{ route('chats.show', $chat) }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $chat->name }}
                                    </a>
                                </h3>

                                @if ($chat->description)
                                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($chat->description, 100) }}</p>
                                @endif

                                <div class="text-sm text-gray-500 space-y-1">
                                    <p>
                                        <span class="font-medium">Messages:</span> {{ number_format($chat->messages_count) }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Created:</span> {{ $chat->created_at->format('M d, Y') }}
                                    </p>
                                </div>

                                <div class="mt-4 flex space-x-2">
                                    <a href="{{ route('chats.show', $chat) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                        View
                                    </a>
                                    <a href="{{ route('chats.edit', $chat) }}" class="text-yellow-600 hover:text-yellow-800 text-sm">
                                        Edit
                                    </a>
                                    <form action="{{ route('chats.destroy', $chat) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this chat?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $chats->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
