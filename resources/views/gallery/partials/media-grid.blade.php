@foreach ($media as $item)
    <div class="relative group media-item" data-message-id="{{ $item->message->id }}">
        <!-- Selection Checkbox -->
        <div class="absolute top-2 left-2 z-10" onclick="event.stopPropagation()">
            <input type="checkbox"
                   class="media-checkbox w-6 h-6 rounded border-2 border-white shadow-lg cursor-pointer bg-white bg-opacity-90"
                   data-message-id="{{ $item->message->id }}"
                   onchange="if(window.gallerySelection) { window.gallerySelection.toggle({{ $item->message->id }}); } else { console.error('gallerySelection not initialized'); }">
        </div>

        @if ($item->type === 'image')
            <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="block">
                <img src="{{ asset('storage/' . $item->file_path) }}"
                     alt="{{ $item->filename }}"
                     loading="lazy"
                     class="w-full h-48 object-cover rounded-lg shadow hover:shadow-lg transition">
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 text-xs rounded-b-lg opacity-0 group-hover:opacity-100 transition">
                    <p class="truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                    <p class="truncate text-gray-300">{{ $item->message->chat->name }}</p>
                    <p class="text-gray-400">{{ $item->message->sent_at->format('M d, Y') }}</p>
                </div>
            </a>
        @elseif ($item->type === 'video')
            <div class="relative">
                <video class="w-full h-48 object-cover rounded-lg shadow" loading="lazy" preload="none">
                    <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                </video>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
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
            <div class="bg-gray-100 rounded-lg p-4 h-48 flex flex-col justify-between overflow-hidden">
                <div class="text-center mb-2">
                    <svg class="w-12 h-12 mx-auto text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"/>
                    </svg>
                </div>

                @if($item->transcription)
                    <div class="mb-2 px-2 py-1 bg-white rounded text-xs text-gray-700 overflow-y-auto flex-1 max-h-20">
                        <p class="italic">{{ Str::limit($item->transcription, 120) }}</p>
                    </div>
                @endif

                <div>
                    <audio controls class="w-full" preload="none">
                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                    </audio>
                    <div class="mt-2 text-xs text-center text-gray-600">
                        <p class="truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                        <p class="truncate text-gray-500">{{ $item->message->chat->name }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tags Section (below media) -->
        <div class="mt-2 bg-white rounded-lg p-2 shadow tags-container" onclick="event.stopPropagation()">
            <!-- Current tags -->
            <div class="flex flex-wrap gap-1">
                @foreach($item->message->tags as $tag)
                <form action="{{ route('messages.tag', $item->message) }}" method="POST" class="inline" onclick="event.stopPropagation()">
                    @csrf
                    <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                    <button type="submit" class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition" onclick="event.stopPropagation()">
                        {{ $tag->name }}
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
