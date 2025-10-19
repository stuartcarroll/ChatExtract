@foreach ($media as $item)
<div class="relative bg-white rounded overflow-hidden shadow-sm hover:shadow-md transition group">
    <!-- Checkbox -->
    <label class="absolute top-2 left-2 z-10 cursor-pointer">
        <input type="checkbox" class="item-checkbox w-5 h-5 rounded"
               onchange="toggleSelection({{ $item->message->id }}, this)">
    </label>

    <!-- Media -->
    @if ($item->type === 'image')
        <img src="{{ asset('storage/' . $item->file_path) }}"
             class="w-full h-48 object-cover" loading="lazy">
    @elseif ($item->type === 'video')
        <div class="relative h-48 bg-gray-900">
            <video class="w-full h-full object-cover" preload="metadata">
                <source src="{{ asset('storage/' . $item->file_path) }}">
            </video>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                    </svg>
                </div>
            </div>
        </div>
    @else
        <div class="h-48 bg-gradient-to-br from-purple-50 to-blue-50 p-3 flex flex-col justify-center">
            <svg class="w-10 h-10 mx-auto text-purple-600 mb-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z"/>
            </svg>
            @if($item->transcription)
            <p class="text-xs text-gray-600 italic mb-2 line-clamp-2">{{ Str::limit($item->transcription, 60) }}</p>
            @endif
            <audio controls class="w-full" preload="none">
                <source src="{{ asset('storage/' . $item->file_path) }}">
            </audio>
        </div>
    @endif

    <!-- Tags -->
    <div class="p-2 border-t">
        @if($item->message->tags->count() > 0)
            <div class="flex flex-wrap gap-1">
                @foreach($item->message->tags->take(2) as $tag)
                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ $tag->name }}</span>
                @endforeach
                @if($item->message->tags->count() > 2)
                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">+{{ $item->message->tags->count() - 2 }}</span>
                @endif
            </div>
        @else
            <span class="text-xs text-gray-400">No tags</span>
        @endif
    </div>
</div>
@endforeach
