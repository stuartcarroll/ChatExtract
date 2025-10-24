<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Group Details') }}: {{ $group->name }}
            </h2>
            <div>
                <a href="{{ route('admin.groups.edit', $group) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Edit Group
                </a>
                <a href="{{ route('admin.groups.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Groups
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Group Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Group Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Name</p>
                            <p class="mt-1">{{ $group->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created By</p>
                            <p class="mt-1">{{ $group->creator?->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm font-medium text-gray-500">Description</p>
                            <p class="mt-1">{{ $group->description ?? 'No description' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Members -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Group Members ({{ $group->users->count() }})</h3>
                    @if($group->users->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($group->users as $user)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    @if($user->role === 'admin') bg-purple-100 text-purple-800
                                                    @elseif($user->role === 'chat_user') bg-green-100 text-green-800
                                                    @else bg-blue-100 text-blue-800
                                                    @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <form action="{{ route('admin.groups.users.remove', [$group, $user]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this user from the group?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No members in this group.</p>
                    @endif
                </div>
            </div>

            <!-- Chat Access -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Chat Access Grants ({{ $group->chatAccess->count() }})</h3>
                    @if($group->chatAccess->count() > 0)
                        <ul class="list-disc list-inside">
                            @foreach($group->chatAccess as $access)
                                <li>{{ $access->chat->name }} ({{ $access->permission }})</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">No chat access grants for this group.</p>
                    @endif
                </div>
            </div>

            <!-- Tag Access -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Tag Access Grants ({{ $group->tagAccess->count() }})</h3>
                    @if($group->tagAccess->count() > 0)
                        <ul class="list-disc list-inside">
                            @foreach($group->tagAccess as $access)
                                <li>{{ $access->tag->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">No tag access grants for this group.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
