<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Manage Tags
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Create New Tag -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Create New Tag</h3>
                    <form action="{{ route('tags.store') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input
                            type="text"
                            name="name"
                            placeholder="Tag name..."
                            required
                            maxlength="50"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            Create
                        </button>
                    </form>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Tags List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Your Tags</h3>

                    @if($tags->isEmpty())
                        <p class="text-gray-500 text-center py-8">No tags yet. Create one above to get started!</p>
                    @else
                        <div class="space-y-2">
                            @foreach($tags as $tag)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                            {{ $tag->name }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            {{ $tag->messages_count }} {{ Str::plural('message', $tag->messages_count) }}
                                        </span>
                                    </div>

                                    <div class="flex gap-2">
                                        <!-- Edit Form -->
                                        <form action="{{ route('tags.update', $tag) }}" method="POST" class="inline" x-data="{ editing: false }">
                                            @csrf
                                            @method('PUT')
                                            <div x-show="!editing" class="inline">
                                                <button type="button" @click="editing = true" class="text-blue-600 hover:text-blue-800 text-sm">
                                                    Edit
                                                </button>
                                            </div>
                                            <div x-show="editing" class="inline-flex gap-1">
                                                <input
                                                    type="text"
                                                    name="name"
                                                    value="{{ $tag->name }}"
                                                    required
                                                    maxlength="50"
                                                    class="text-sm rounded border-gray-300"
                                                >
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm">
                                                    Save
                                                </button>
                                                <button type="button" @click="editing = false" class="text-gray-600 hover:text-gray-800 text-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Delete Form -->
                                        <form action="{{ route('tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tag? It will be removed from all messages.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
