@foreach ($media as $item)
<div class="relative bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition"
     data-message-id="{{ $item->message->id }}"
     x-data="{ parentApp: $root }">

    <!-- Checkbox (Always Visible) -->
    <label class="absolute top-2 left-2 z-20 cursor-pointer group">
        <div class="w-8 h-8 bg-white rounded-md shadow-lg flex items-center justify-center group-hover:bg-blue-50 transition">
            <input type="checkbox"
                   class="media-checkbox w-4 h-4 rounded cursor-pointer accent-blue-600"
                   data-message-id="{{ $item->message->id }}"
                   @change="parentApp.toggleSelection({{ $item->message->id }})">
        </div>
    </label>

    @if(auth()->user()->is_admin)
    <!-- Admin Controls -->
    <div class="absolute top-2 right-2 z-20">
        <button @click="parentApp.deleteItem({{ $item->message->id }})"
                class="w-8 h-8 bg-red-600 text-white rounded-md shadow-lg hover:bg-red-700 transition flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </div>
    @endif

    <!-- Media Content -->
    <div class="relative group cursor-pointer">
        @if ($item->type === 'image')
            <img src="{{ asset('storage/' . $item->file_path) }}"
                 alt="{{ $item->filename }}"
                 loading="lazy"
                 class="w-full h-48 object-cover">
        @elseif ($item->type === 'video')
            <div class="relative bg-gray-900 h-48">
                <video class="w-full h-full object-cover" preload="metadata">
                    <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                </video>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-800 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                        </svg>
                    </div>
                </div>
            </div>
        @elseif ($item->type === 'audio')
            <div class="bg-gradient-to-br from-purple-50 to-blue-50 p-4 h-48 flex flex-col justify-center">
                <div class="text-center mb-2">
                    <svg class="w-12 h-12 mx-auto text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"/>
                    </svg>
                </div>
                @if($item->transcription)
                    <div class="mb-2 px-2 py-1 bg-white/80 rounded text-xs text-gray-700 line-clamp-3">
                        <p class="italic">{{ Str::limit($item->transcription, 80) }}</p>
                    </div>
                @endif
                <audio controls class="w-full" preload="none">
                    <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                </audio>
            </div>
        @endif

        <!-- Info overlay on hover (desktop) -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
            <div class="text-white text-xs">
                <p class="font-medium truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                <p class="text-white/80 truncate">{{ $item->message->chat->name }}</p>
                <p class="text-white/60">{{ $item->message->sent_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Tag Chips (Always Visible) -->
    <div class="p-2 border-t border-gray-100 min-h-[2.5rem]">
        @if($item->message->tags->count() > 0)
            <div class="flex flex-wrap gap-1">
                @foreach($item->message->tags->take(3) as $tag)
                <form action="{{ route('messages.tag', $item->message) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="tag_id" value="{{ $tag->id }}">
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition">
                        {{ $tag->name }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
                @endforeach
                @if($item->message->tags->count() > 3)
                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">
                        +{{ $item->message->tags->count() - 3 }}
                    </span>
                @endif
            </div>
        @else
            <p class="text-xs text-gray-400 italic">No tags</p>
        @endif
    </div>
</div>
@endforeach
