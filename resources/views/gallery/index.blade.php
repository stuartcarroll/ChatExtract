<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Global Gallery</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <!-- Type Filter -->
                    <div class="flex gap-2">
                        <a href="{{ route('gallery.index', ['type' => 'all', 'participant' => request('participant')]) }}" 
                           class="px-4 py-2 rounded {{ $type === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            All ({{ $counts['all'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'image', 'participant' => request('participant')]) }}" 
                           class="px-4 py-2 rounded {{ $type === 'image' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Photos ({{ $counts['image'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'video', 'participant' => request('participant')]) }}" 
                           class="px-4 py-2 rounded {{ $type === 'video' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Videos ({{ $counts['video'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'audio', 'participant' => request('participant')]) }}" 
                           class="px-4 py-2 rounded {{ $type === 'audio' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Audio ({{ $counts['audio'] }})
                        </a>
                    </div>

                    <!-- Participant Filter -->
                    <div class="flex-1">
                        <select onchange="window.location.href='{{ route('gallery.index', ['type' => $type]) }}&participant=' + this.value" 
                                class="rounded border-gray-300 w-full">
                            <option value="">All Participants</option>
                            @foreach ($participants as $participant)
                                <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                    {{ $participant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Gallery Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @forelse ($media as $item)
                        <div class="relative group">
                            @if ($item->type === 'image')
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="block">
                                    <img src="{{ asset('storage/' . $item->file_path) }}" 
                                         alt="{{ $item->filename }}"
                                         class="w-full h-48 object-cover rounded-lg shadow hover:shadow-lg transition">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 text-xs rounded-b-lg opacity-0 group-hover:opacity-100 transition">
                                        <p class="truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                                        <p class="truncate text-gray-300">{{ $item->message->chat->name }}</p>
                                        <p class="text-gray-400">{{ $item->message->sent_at->format('M d, Y') }}</p>
                                    </div>
                                </a>
                            @elseif ($item->type === 'video')
                                <div class="relative">
                                    <video class="w-full h-48 object-cover rounded-lg shadow">
                                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-white opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                        </svg>
                                    </div>
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 text-xs rounded-b-lg">
                                        <p class="truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                                        <p class="truncate text-gray-300">{{ $item->message->chat->name }}</p>
                                    </div>
                                </div>
                            @elseif ($item->type === 'audio')
                                <div class="bg-gray-100 rounded-lg p-4 h-48 flex flex-col justify-center">
                                    <div class="text-center mb-2">
                                        <svg class="w-12 h-12 mx-auto text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"/>
                                        </svg>
                                    </div>
                                    <audio controls class="w-full">
                                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                    </audio>
                                    <div class="mt-2 text-xs text-center text-gray-600">
                                        <p class="truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                                        <p class="truncate text-gray-500">{{ $item->message->chat->name }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-500 py-12">
                            No media found. Import a chat with media files to see them here!
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $media->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
