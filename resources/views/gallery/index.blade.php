<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gallery</h2>
    </x-slot>

    <div class="min-h-screen bg-gray-50 pb-24" x-data="galleryManager()">
        <!-- Compact Filters Bar -->
        <div class="sticky top-0 z-30 bg-white border-b shadow-sm">
            <div class="max-w-7xl mx-auto px-3 py-2">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <!-- Type Filter -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type=' + this.value + '&participant={{ request('participant') }}&sort={{ request('sort', 'date_desc') }}'"
                            class="text-xs sm:text-sm rounded-lg border-gray-300">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All ({{ $counts['all'] }})</option>
                        <option value="image" {{ $type === 'image' ? 'selected' : '' }}>Photos ({{ $counts['image'] }})</option>
                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Videos ({{ $counts['video'] }})</option>
                        <option value="audio" {{ $type === 'audio' ? 'selected' : '' }}>Audio ({{ $counts['audio'] }})</option>
                    </select>

                    <!-- Participant Filter -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant=' + this.value + '&sort={{ request('sort', 'date_desc') }}'"
                            class="text-xs sm:text-sm rounded-lg border-gray-300">
                        <option value="">All People</option>
                        @foreach ($participants as $participant)
                            <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                {{ $participant->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Sort -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant={{ request('participant') }}&sort=' + this.value"
                            class="text-xs sm:text-sm rounded-lg border-gray-300">
                        <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Oldest</option>
                    </select>

                    <!-- Selection Info -->
                    <div class="flex items-center justify-end">
                        <span x-show="selectedCount > 0" class="text-xs sm:text-sm font-medium text-blue-600">
                            <span x-text="selectedCount"></span> selected
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="max-w-7xl mx-auto px-3 py-4">
            <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                @foreach ($media as $item)
                <div class="relative bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition"
                     data-message-id="{{ $item->message->id }}">

                    <!-- Checkbox (Always Visible) -->
                    <label class="absolute top-2 left-2 z-20 cursor-pointer group">
                        <div class="w-8 h-8 bg-white rounded-md shadow-lg flex items-center justify-center group-hover:bg-blue-50 transition">
                            <input type="checkbox"
                                   class="media-checkbox w-4 h-4 rounded cursor-pointer accent-blue-600"
                                   data-message-id="{{ $item->message->id }}"
                                   @change="toggleSelection({{ $item->message->id }})">
                        </div>
                    </label>

                    @if(auth()->user()->is_admin)
                    <!-- Admin Controls -->
                    <div class="absolute top-2 right-2 z-20">
                        <button @click="deleteItem({{ $item->message->id }})"
                                class="w-8 h-8 bg-red-600 text-white rounded-md shadow-lg hover:bg-red-700 transition flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    @endif

                    <!-- Media Content -->
                    <div class="relative group cursor-pointer" onclick="openLightbox({{ $loop->index }})">
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
            </div>

            <!-- Loading indicator -->
            <div id="loading-indicator" class="hidden text-center py-8">
                <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600 mt-2 text-sm">Loading more...</p>
            </div>

            @if($media->isEmpty())
                <div class="text-center text-gray-500 py-12">
                    <p class="text-lg">No media found</p>
                    <p class="text-sm mt-2">Try adjusting your filters</p>
                </div>
            @endif
        </div>

        <!-- Floating Action Button (appears when items selected) -->
        <div x-show="selectedCount > 0"
             x-transition
             class="fixed bottom-6 right-6 z-40">
            <button @click="showTagSheet = true"
                    class="w-16 h-16 bg-blue-600 text-white rounded-full shadow-2xl hover:bg-blue-700 active:scale-95 transition flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </button>
            <div class="absolute -top-2 -left-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold">
                <span x-text="selectedCount"></span>
            </div>
        </div>

        <!-- Bottom Sheet for Tagging -->
        <div x-show="showTagSheet"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[80vh] overflow-y-auto">

            <!-- Handle bar -->
            <div class="sticky top-0 bg-white pt-3 pb-2 border-b z-10">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto mb-3"></div>
                <div class="flex items-center justify-between px-6 pb-2">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Tag <span x-text="selectedCount"></span> Items
                    </h3>
                    <button @click="showTagSheet = false" class="p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Quick Actions -->
                <div class="flex gap-2 mb-6">
                    <button @click="selectAll()" class="flex-1 px-4 py-3 bg-blue-100 text-blue-700 rounded-lg font-medium hover:bg-blue-200 transition">
                        Select All
                    </button>
                    <button @click="clearSelection()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                        Clear
                    </button>
                </div>

                <!-- Existing Tags -->
                <div class="mb-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Quick Tag:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                        <button @click="applyTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                                class="px-4 py-2 bg-gray-100 hover:bg-blue-100 text-gray-800 hover:text-blue-700 rounded-lg text-sm font-medium transition active:scale-95">
                            {{ $tag->name }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Create New Tag -->
                <div class="border-t pt-6">
                    <p class="text-sm font-medium text-gray-700 mb-3">Create New Tag:</p>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="newTagName"
                               @keydown.enter="createAndApplyTag()"
                               placeholder="Tag name..."
                               class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition"
                               maxlength="50">
                        <button @click="createAndApplyTag()"
                                :disabled="!newTagName.trim()"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                            Create & Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay -->
        <div x-show="showTagSheet"
             @click="showTagSheet = false"
             x-transition.opacity
             class="fixed inset-0 bg-black/50 z-40"></div>
    </div>

    @push('scripts')
    <script>
        function galleryManager() {
            return {
                selectedCount: 0,
                selectedIds: new Set(),
                showTagSheet: false,
                newTagName: '',

                toggleSelection(messageId) {
                    if (this.selectedIds.has(messageId)) {
                        this.selectedIds.delete(messageId);
                    } else {
                        this.selectedIds.add(messageId);
                    }
                    this.selectedCount = this.selectedIds.size;
                },

                selectAll() {
                    document.querySelectorAll('.media-checkbox').forEach(cb => {
                        cb.checked = true;
                        this.selectedIds.add(parseInt(cb.dataset.messageId));
                    });
                    this.selectedCount = this.selectedIds.size;
                },

                clearSelection() {
                    document.querySelectorAll('.media-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    this.selectedIds.clear();
                    this.selectedCount = 0;
                },

                async applyTag(tagId, tagName) {
                    if (this.selectedIds.size === 0) return;

                    const messageIds = Array.from(this.selectedIds);
                    const token = document.querySelector('meta[name="csrf-token"]').content;

                    let successCount = 0;

                    for (const messageId of messageIds) {
                        try {
                            const response = await fetch(`/messages/${messageId}/tag`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ tag_id: tagId })
                            });

                            if (response.ok) successCount++;
                        } catch (error) {
                            console.error('Error tagging:', error);
                        }
                    }

                    alert(`Tagged ${successCount} items with "${tagName}"`);
                    window.location.reload();
                },

                async createAndApplyTag() {
                    if (!this.newTagName.trim() || this.selectedIds.size === 0) return;

                    const token = document.querySelector('meta[name="csrf-token"]').content;

                    try {
                        const createResponse = await fetch('/tags', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ name: this.newTagName.trim() })
                        });

                        if (!createResponse.ok) {
                            alert('Failed to create tag');
                            return;
                        }

                        const tagData = await createResponse.json();
                        const newTagId = tagData.id || tagData.tag?.id;

                        await this.applyTag(newTagId, this.newTagName.trim());
                        this.newTagName = '';
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error creating tag');
                    }
                },

                async deleteItem(messageId) {
                    if (!confirm('Delete this item? This cannot be undone.')) return;

                    const token = document.querySelector('meta[name="csrf-token"]').content;

                    try {
                        const response = await fetch(`/messages/${messageId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Failed to delete item');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error deleting item');
                    }
                }
            }
        }

        // Infinite scroll
        let currentPage = {{ $media->currentPage() }};
        let hasMore = {{ $media->hasMorePages() ? 'true' : 'false' }};
        let isLoading = false;

        function loadMore() {
            if (isLoading || !hasMore) return;

            isLoading = true;
            document.getElementById('loading-indicator').classList.remove('hidden');

            const params = new URLSearchParams(window.location.search);
            params.set('page', currentPage + 1);

            fetch(`{{ route('gallery.index') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;

                const grid = document.getElementById('media-grid');
                while (tempDiv.firstChild) {
                    grid.appendChild(tempDiv.firstChild);
                }

                currentPage = data.next_page;
                hasMore = data.has_more;
                isLoading = false;
                document.getElementById('loading-indicator').classList.add('hidden');
            })
            .catch(error => {
                console.error('Error loading more:', error);
                isLoading = false;
                document.getElementById('loading-indicator').classList.add('hidden');
            });
        }

        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
                loadMore();
            }
        });
    </script>
    @endpush
</x-app-layout>
