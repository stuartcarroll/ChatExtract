@foreach ($media as $item)
    <div class="relative group media-item bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition"
         data-message-id="{{ $item->message->id }}"
         x-data="{ showTags: false }">

        <!-- Checkbox (Tag Mode Only) -->
        <div x-show="$root.mode === 'tag'"
             class="absolute top-2 left-2 z-10">
            <label class="flex items-center justify-center w-10 h-10 bg-white rounded-lg shadow-lg cursor-pointer hover:bg-gray-50 active:scale-95 transition">
                <input type="checkbox"
                       class="media-checkbox w-5 h-5 rounded cursor-pointer accent-blue-600"
                       data-message-id="{{ $item->message->id }}"
                       onchange="if(window.gallerySelection) { window.gallerySelection.toggle({{ $item->message->id }}); }">
            </label>
        </div>

        <!-- Media Content -->
        <div class="relative">
            @if ($item->type === 'image')
                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="block">
                    <img src="{{ asset('storage/' . $item->file_path) }}"
                         alt="{{ $item->filename }}"
                         loading="lazy"
                         class="w-full h-48 sm:h-56 object-cover">
                </a>
            @elseif ($item->type === 'video')
                <div class="relative bg-gray-900">
                    <video class="w-full h-48 sm:h-56 object-cover" loading="lazy" preload="none">
                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                    </video>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-800 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @elseif ($item->type === 'audio')
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 h-48 sm:h-56 flex flex-col justify-center">
                    <div class="text-center mb-3">
                        <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"/>
                        </svg>
                    </div>
                    @if($item->transcription)
                        <div class="mb-2 px-2 py-1 bg-white rounded text-xs text-gray-700 overflow-hidden max-h-16">
                            <p class="italic line-clamp-3">{{ Str::limit($item->transcription, 100) }}</p>
                        </div>
                    @endif
                    <audio controls class="w-full" preload="none">
                        <source src="{{ asset('storage/' . $item->file_path) }}" type="{{ $item->mime_type }}">
                    </audio>
                </div>
            @endif

            <!-- Info Overlay (on hover) -->
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity">
                <p class="text-white text-xs font-medium truncate">{{ $item->message->participant->name ?? 'Unknown' }}</p>
                <p class="text-white/80 text-xs truncate">{{ $item->message->chat->name }}</p>
                <p class="text-white/60 text-xs">{{ $item->message->sent_at->format('M d, Y') }}</p>
            </div>
        </div>

        <!-- Tags Section (Browse Mode Only) -->
        <div x-show="$root.mode === 'browse'" class="p-2 border-t border-gray-100">
            @if($item->message->tags->count() > 0)
                <!-- Existing Tags -->
                <div class="flex flex-wrap gap-1 mb-1">
                    @foreach($item->message->tags->take(3) as $tag)
                        <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                    @if($item->message->tags->count() > 3)
                        <button @click="showTags = !showTags"
                                class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs hover:bg-gray-200">
                            +{{ $item->message->tags->count() - 3 }}
                        </button>
                    @endif
                </div>

                <!-- All Tags (Expandable) -->
                <div x-show="showTags" x-collapse class="flex flex-wrap gap-1 mb-2 pt-1 border-t border-gray-200">
                    @foreach($item->message->tags->skip(3) as $tag)
                        <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic">No tags</p>
            @endif
        </div>

        <!-- Tag Count Badge (Tag Mode Only) -->
        <div x-show="$root.mode === 'tag'" class="absolute top-2 right-2 z-10">
            @if($item->message->tags->count() > 0)
                <span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white rounded-full text-xs font-medium shadow-lg">
                    {{ $item->message->tags->count() }} {{ Str::plural('tag', $item->message->tags->count()) }}
                </span>
            @endif
        </div>
    </div>
@endforeach
