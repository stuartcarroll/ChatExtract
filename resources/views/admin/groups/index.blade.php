<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Groups') }}
            </h2>
            <a href="{{ route('admin.groups.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New Group
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chat Access</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tag Access</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($groups as $group)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $group->name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500">{{ Str::limit($group->description, 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $group->users_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $group->chat_access_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $group->tag_access_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $group->creator?->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium">
                                            <a href="{{ route('admin.groups.show', $group) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                            <a href="{{ route('admin.groups.edit', $group) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No groups found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $groups->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
