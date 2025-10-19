<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Chat') }}
            </h2>
            <a href="{{ route('chats.show', $chat) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Chat
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Edit Chat Details</h3>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('chats.update', $chat) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Chat Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $chat->name) }}"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description (Optional)
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('description', $chat->description) }}</textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('chats.show', $chat) }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Update Chat
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Access Management Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Access Management</h3>
                    <p class="text-sm text-gray-600 mb-6">Grant or revoke access to this chat for other users or groups.</p>

                    <!-- Grant Access Form -->
                    <form action="{{ route('chats.access.grant', $chat) }}" method="POST" class="mb-6 bg-gray-50 p-4 rounded-lg">
                        @csrf
                        <h4 class="font-medium mb-3">Grant New Access</h4>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="accessable_type" id="accessable_type" class="w-full rounded-md border-gray-300 shadow-sm" required onchange="toggleAccessOptions()">
                                    <option value="">Select...</option>
                                    <option value="user">User</option>
                                    <option value="group">Group</option>
                                </select>
                            </div>

                            <div id="user_select" style="display:none;">
                                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                                <select name="accessable_id_user" id="accessable_id_user" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Select user...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="group_select" style="display:none;">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                                <select name="accessable_id_group" id="accessable_id_group" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Select group...</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                                <select name="permission" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="view">View Only</option>
                                    <option value="edit">Edit</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="accessable_id" id="accessable_id">

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Grant Access
                        </button>
                    </form>

                    <!-- Current Access List -->
                    <div>
                        <h4 class="font-medium mb-3">Current Access Grants</h4>

                        @if($chatAccess->isEmpty())
                            <p class="text-sm text-gray-500 italic">No access grants yet. This chat is only accessible to you.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($chatAccess as $access)
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                                        <div class="flex-1">
                                            <div class="font-medium">
                                                {{ $access->accessable_type === 'App\\Models\\User' ? '👤' : '👥' }}
                                                {{ $access->accessable->name ?? 'Unknown' }}
                                                @if($access->accessable_type === 'App\\Models\\User')
                                                    <span class="text-sm text-gray-500">({{ $access->accessable->email ?? '' }})</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                Permission: <span class="font-medium capitalize">{{ $access->permission }}</span>
                                            </div>
                                        </div>
                                        <form action="{{ route('chats.access.revoke', [$chat, $access->id]) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to revoke this access?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                Revoke
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAccessOptions() {
            const type = document.getElementById('accessable_type').value;
            const userSelect = document.getElementById('user_select');
            const groupSelect = document.getElementById('group_select');
            const userSelectInput = document.getElementById('accessable_id_user');
            const groupSelectInput = document.getElementById('accessable_id_group');
            const hiddenInput = document.getElementById('accessable_id');

            if (type === 'user') {
                userSelect.style.display = 'block';
                groupSelect.style.display = 'none';
                userSelectInput.required = true;
                groupSelectInput.required = false;

                userSelectInput.addEventListener('change', function() {
                    hiddenInput.value = this.value;
                });
            } else if (type === 'group') {
                userSelect.style.display = 'none';
                groupSelect.style.display = 'block';
                userSelectInput.required = false;
                groupSelectInput.required = true;

                groupSelectInput.addEventListener('change', function() {
                    hiddenInput.value = this.value;
                });
            } else {
                userSelect.style.display = 'none';
                groupSelect.style.display = 'none';
                userSelectInput.required = false;
                groupSelectInput.required = false;
            }
        }
    </script>
</x-app-layout>
