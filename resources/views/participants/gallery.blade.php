<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $participant->name }} - Media Gallery
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('participants.show', $participant) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Profile
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
                        <a href="{{ route('participants.gallery', ['participant' => $participant, 'type' => 'all']) }}"
                           class="px-4 py-2 rounded {{ $type === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            All Media ({{ $counts['all'] }})
                        </a>
                        <a href="{{ route('participants.gallery', ['participant' => $participant, 'type' => 'image']) }}"
                           class="px-4 py-2 rounded {{ $type === 'image' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            📷 Photos ({{ $counts['image'] }})
                        </a>
                        <a href="{{ route('participants.gallery', ['participant' => $participant, 'type' => 'video']) }}"
                           class="px-4 py-2 rounded {{ $type === 'video' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            🎥 Videos ({{ $counts['video'] }})
                        </a>
                        <a href="{{ route('participants.gallery', ['participant' => $participant, 'type' => 'audio']) }}"
                           class="px-4 py-2 rounded {{ $type === 'audio' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            🎵 Audio ({{ $counts['audio'] }})
                        </a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($media->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-600">
                        No {{ $type === 'all' ? 'media' : $type }} files found for this participant.
                    </div>
                </div>
            @else
                <!-- Gallery Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($media as $item)
                        <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition relative">
                            @if ($item->type === 'image')
                                <!-- Image -->
                                <a href="{{ asset('storage/' . $item->file_path) }}"
                                   class="lightbox-trigger block"
                                   data-lightbox="gallery"
                                   data-title="{{ $participant->name }} - {{ $item->message->sent_at->format('M d, Y') }}">
                                    <img src="{{ asset('storage/' . $item->file_path) }}"
                                         alt="{{ $item->filename }}"
                                         class="w-full h-48 object-cover">
                                </a>
                            @elseif ($item->type === 'video')
                                <!-- Video -->
                                <div class="relative">
                                    <video class="w-full h-48 object-cover bg-black">
                                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                                        <svg class="w-16 h-16 text-white opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                        </svg>
                                    </div>
                                </div>
                            @elseif ($item->type === 'audio')
                                <!-- Audio -->
                                <div class="p-4 bg-gray-50 h-48 flex flex-col justify-between">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <audio controls class="w-full">
                                            <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                                        </audio>
                                    </div>
                                </div>
                            @endif

                            <!-- Media Info & Actions -->
                            <div class="p-3 bg-white">
                                <p class="text-xs text-gray-600">
                                    {{ $item->message->sent_at->format('M d, Y H:i') }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1 truncate" title="{{ $item->filename }}">
                                    {{ $item->filename }}
                                </p>

                                <!-- Delete Button -->
                                <form action="{{ route('participants.media.delete', [$participant, $item]) }}"
                                      method="POST"
                                      class="mt-3"
                                      onsubmit="return confirm('Are you sure you want to delete this media? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-full bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-1 px-3 rounded">
                                        🗑️ Delete (NSFW)
                                    </button>
                                </form>
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
        // Lightbox functionality
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    </script>
</x-app-layout>
