<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New Group') }}
            </h2>
            <a href="{{ route('admin.groups.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Groups
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.groups.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Group Name
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('name') border-red-500 @enderror"
                                required>
                            @error('name')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="3"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Add Users to Group
                            </label>
                            <div class="border rounded p-4 max-h-64 overflow-y-auto">
                                @foreach($users as $user)
                                    <div class="mb-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                                {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}
                                                class="form-checkbox h-4 w-4 text-blue-600">
                                            <span class="ml-2">{{ $user->name }} ({{ $user->email }})</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Group
                            </button>
                            <a href="{{ route('admin.groups.index') }}"
                                class="font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
