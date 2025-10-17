<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $chat->name }} - Gallery
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('chats.show', $chat) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Chat
                </a>
                <a href="{{ route('chats.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    All Chats
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('chats.gallery', ['chat' => $chat, 'type' => 'all']) }}"
                           class="px-4 py-2 rounded {{ $type === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            All Media ({{ $counts['all'] }})
                        </a>
                        <a href="{{ route('chats.gallery', ['chat' => $chat, 'type' => 'image']) }}"
                           class="px-4 py-2 rounded {{ $type === 'image' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            📷 Photos ({{ $counts['image'] }})
                        </a>
                        <a href="{{ route('chats.gallery', ['chat' => $chat, 'type' => 'video']) }}"
                           class="px-4 py-2 rounded {{ $type === 'video' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            🎥 Videos ({{ $counts['video'] }})
                        </a>
                        <a href="{{ route('chats.gallery', ['chat' => $chat, 'type' => 'audio']) }}"
                           class="px-4 py-2 rounded {{ $type === 'audio' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            🎵 Audio ({{ $counts['audio'] }})
                        </a>
                    </div>
                </div>
            </div>

            @if ($media->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-600">
                        No {{ $type === 'all' ? 'media' : $type }} files found in this chat.
                    </div>
                </div>
            @else
                <!-- Gallery Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($media as $item)
                        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                            @if ($item->type === 'image')
                                <!-- Image Thumbnail -->
                                <a href="{{ asset('storage/' . $item->file_path) }}"
                                   class="lightbox-trigger block"
                                   data-lightbox="gallery"
                                   data-title="{{ $item->message->participant->name ?? 'Unknown' }} - {{ $item->message->sent_at->format('M d, Y') }}">
                                    <img src="{{ asset('storage/' . $item->file_path) }}"
                                         alt="{{ $item->filename }}"
                                         class="w-full h-48 object-cover">
                                </a>
                            @elseif ($item->type === 'video')
                                <!-- Video Thumbnail -->
                                <div class="relative">
                                    <video class="w-full h-48 object-cover bg-black">
                                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                                        <svg class="w-16 h-16 text-white opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                        </svg>
                                    </div>
                                    <a href="{{ asset('storage/' . $item->file_path) }}"
                                       class="absolute inset-0"
                                       onclick="openVideoModal(event, '{{ asset('storage/' . $item->file_path) }}', '{{ $item->mime_type }}', '{{ $item->message->participant->name ?? 'Unknown' }}', '{{ $item->message->sent_at->format('M d, Y H:i') }}')">
                                    </a>
                                </div>
                            @elseif ($item->type === 'audio')
                                <!-- Audio Item -->
                                <div class="p-4 bg-gray-50 h-48 flex flex-col justify-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                    <audio controls class="w-full">
                                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                    </audio>
                                </div>
                            @endif

                            <!-- Media Info -->
                            <div class="p-3 bg-white">
                                <p class="text-xs text-gray-600 font-medium truncate">
                                    {{ $item->message->participant->name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $item->message->sent_at->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1 truncate" title="{{ $item->filename }}">
                                    {{ $item->filename }}
                                </p>

                                <!-- Tags Section -->
                                @if($tags->isNotEmpty())
                                <div class="mt-2 pt-2 border-t border-gray-100" x-data="{ showTags: false }">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500">Tags:</span>
                                        <button type="button" @click="showTags = !showTags" class="text-xs text-blue-600 hover:text-blue-800">
                                            <span x-show="!showTags">+ Add</span>
                                            <span x-show="showTags">Hide</span>
                                        </button>
                                    </div>

                                    <!-- Current tags -->
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        @foreach($item->message->tags as $tag)
                                        <form action="{{ route('messages.tag', $item->message) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                                            <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition">
                                                {{ $tag->name }}
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @endforeach
                                    </div>

                                    <!-- Available tags (collapsible) -->
                                    <div x-show="showTags" x-collapse class="flex flex-wrap gap-1 pt-1">
                                        @foreach($tags as $tag)
                                            @if(!$item->message->tags->contains($tag->id))
                                            <form action="{{ route('messages.tag', $item->message) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                                                <button type="submit" class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 transition">
                                                    + {{ $tag->name }}
                                                </button>
                                            </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $media->appends(['type' => $type])->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
        <div class="max-w-4xl w-full">
            <div class="flex justify-between items-center mb-4">
                <div class="text-white">
                    <p class="font-semibold" id="videoParticipant"></p>
                    <p class="text-sm text-gray-300" id="videoDate"></p>
                </div>
                <button onclick="closeVideoModal()" class="text-white hover:text-gray-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <video id="videoPlayer" controls class="w-full rounded-lg">
                <source id="videoSource" src="" type="">
            </video>
        </div>
    </div>

    <!-- Lightbox for Images -->
    <div id="lightbox" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
        <div class="max-w-7xl w-full h-full flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <div class="text-white">
                    <p class="font-semibold" id="lightboxTitle"></p>
                </div>
                <button onclick="closeLightbox()" class="text-white hover:text-gray-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 flex items-center justify-center">
                <img id="lightboxImage" src="" alt="" class="max-w-full max-h-full object-contain">
            </div>
        </div>
    </div>

    <script>
        // Lightbox functionality for images
        document.querySelectorAll('.lightbox-trigger').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const src = this.href;
                const title = this.dataset.title;
                document.getElementById('lightboxImage').src = src;
                document.getElementById('lightboxTitle').textContent = title;
                document.getElementById('lightbox').classList.remove('hidden');
            });
        });

        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
        }

        // Video modal functionality
        function openVideoModal(e, src, type, participant, date) {
            e.preventDefault();
            const videoSource = document.getElementById('videoSource');
            videoSource.src = src;
            videoSource.type = type;
            document.getElementById('videoParticipant').textContent = participant;
            document.getElementById('videoDate').textContent = date;
            document.getElementById('videoPlayer').load();
            document.getElementById('videoModal').classList.remove('hidden');
        }

        function closeVideoModal() {
            const videoPlayer = document.getElementById('videoPlayer');
            videoPlayer.pause();
            document.getElementById('videoModal').classList.add('hidden');
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
                closeVideoModal();
            }
        });
    </script>
</x-app-layout>
