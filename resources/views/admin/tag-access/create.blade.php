<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Grant Tag Access') }}
            </h2>
            <a href="{{ route('admin.tag-access.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.tag-access.store') }}" x-data="{ type: '{{ old('accessable_type', 'user') }}' }">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Tag</label>
                            <select name="tag_id" required class="shadow border rounded w-full py-2 px-3 @error('tag_id') border-red-500 @enderror">
                                <option value="">Select a tag</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}" {{ old('tag_id', $tagId) == $tag->id ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tag_id')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Grant To</label>
                            <select name="accessable_type" x-model="type" required class="shadow border rounded w-full py-2 px-3 mb-2">
                                <option value="user">User</option>
                                <option value="group">Group</option>
                            </select>
                        </div>

                        <div class="mb-4" x-show="type === 'user'">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Select User</label>
                            <select name="accessable_id" class="shadow border rounded w-full py-2 px-3">
                                <option value="">Select a user</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }}) - {{ ucfirst(str_replace('_', ' ', $user->role)) }}</option>
                                @endforeach
                            </select>
                            <p class="text-sm text-gray-600 mt-1">View Only users MUST have tag access to see any content.</p>
                        </div>

                        <div class="mb-4" x-show="type === 'group'">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Select Group</label>
                            <select name="accessable_id" class="shadow border rounded w-full py-2 px-3">
                                <option value="">Select a group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Grant Access
                            </button>
                            <a href="{{ route('admin.tag-access.index') }}" class="text-blue-500 hover:text-blue-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
