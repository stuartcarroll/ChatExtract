<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tag Access Management') }}
            </h2>
            <a href="{{ route('admin.tag-access.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Grant Tag Access
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Filters -->
            <div class="mb-4 bg-white p-4 rounded shadow-sm">
                <form method="GET" action="{{ route('admin.tag-access.index') }}" class="flex gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tag</label>
                        <select name="tag_id" class="mt-1 block border-gray-300 rounded-md shadow-sm">
                            <option value="">All Tags</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" {{ request('tag_id') == $tag->id ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" class="mt-1 block border-gray-300 rounded-md shadow-sm">
                            <option value="">All Types</option>
                            <option value="user" {{ request('type') == 'user' ? 'selected' : '' }}>User</option>
                            <option value="group" {{ request('type') == 'group' ? 'selected' : '' }}>Group</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Granted To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Granted By</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($accesses as $access)
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 inline-flex text-xs font-semibold rounded" style="background-color: {{ $access->tag->color }}20; color: {{ $access->tag->color }}">
                                            {{ $access->tag->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">{{ $access->accessable->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $access->accessable_type === 'App\Models\User' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $access->accessable_type === 'App\Models\User' ? 'User' : 'Group' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">{{ $access->grantedBy?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <form action="{{ route('admin.tag-access.destroy', $access) }}" method="POST" class="inline" onsubmit="return confirm('Revoke this access?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No tag access grants found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $accesses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
