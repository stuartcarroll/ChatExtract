<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Details') }}: {{ $user->name }}
            </h2>
            <div>
                <a href="{{ route('admin.users.edit', $user) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Edit User
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Users
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- User Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">User Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Name</p>
                            <p class="mt-1">{{ $user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="mt-1">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Role</p>
                            <p class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($user->role === 'admin') bg-purple-100 text-purple-800
                                    @elseif($user->role === 'chat_user') bg-green-100 text-green-800
                                    @else bg-blue-100 text-blue-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created</p>
                            <p class="mt-1">{{ $user->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Owned Chats -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Owned Chats ({{ $user->ownedChats->count() }})</h3>
                    @if($user->ownedChats->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Messages</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($user->ownedChats as $chat)
                                        <tr>
                                            <td class="px-6 py-4">{{ $chat->title }}</td>
                                            <td class="px-6 py-4">{{ $chat->messages_count ?? 0 }}</td>
                                            <td class="px-6 py-4">{{ $chat->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No owned chats.</p>
                    @endif
                </div>
            </div>

            <!-- Chat Access Grants -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Chat Access Grants ({{ $chatAccessCount }})</h3>
                    <p class="text-gray-500">This user has been granted access to {{ $chatAccessCount }} chat(s).</p>
                </div>
            </div>

            <!-- Groups -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Groups ({{ $user->groups->count() }})</h3>
                    @if($user->groups->count() > 0)
                        <ul class="list-disc list-inside">
                            @foreach($user->groups as $group)
                                <li>{{ $group->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">Not a member of any groups.</p>
                    @endif
                </div>
            </div>

            <!-- Tag Access -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Tag Access ({{ $user->tagAccess->count() }})</h3>
                    @if($user->tagAccess->count() > 0)
                        <ul class="list-disc list-inside">
                            @foreach($user->tagAccess as $access)
                                <li>{{ $access->tag->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">No tag access grants.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
