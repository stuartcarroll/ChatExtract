<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gallery</h2>
    </x-slot>

    <div class="min-h-screen bg-gray-50" x-data="galleryApp()" x-init="init()">
        <!-- Mode Toggle Bar (Sticky) -->
        <div class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <!-- Mode Switcher -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex gap-2">
                        <button @click="mode = 'browse'"
                                :class="mode === 'browse' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                class="px-4 py-2 rounded-lg font-medium text-sm transition">
                            Browse
                        </button>
                        <button @click="mode = 'tag'"
                                :class="mode === 'tag' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                class="px-4 py-2 rounded-lg font-medium text-sm transition">
                            Tag Mode
                        </button>
                    </div>

                    <!-- Selection Info (Tag Mode Only) -->
                    <div x-show="mode === 'tag'" class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">
                            <span x-text="selectedCount"></span> selected
                        </span>
                        <button @click="clearSelection()"
                                x-show="selectedCount > 0"
                                class="text-sm text-blue-600 hover:text-blue-800">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Filters (Compact on Mobile) -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <!-- Type Filter -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type=' + this.value + '&participant={{ request('participant') }}&sort={{ request('sort', 'date_desc') }}'"
                            class="flex-1 sm:flex-initial rounded-lg border-gray-300 text-sm">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All ({{ $counts['all'] }})</option>
                        <option value="image" {{ $type === 'image' ? 'selected' : '' }}>Photos ({{ $counts['image'] }})</option>
                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Videos ({{ $counts['video'] }})</option>
                        <option value="audio" {{ $type === 'audio' ? 'selected' : '' }}>Audio ({{ $counts['audio'] }})</option>
                    </select>

                    <!-- Participant Filter -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant=' + this.value + '&sort={{ request('sort', 'date_desc') }}'"
                            class="flex-1 sm:flex-initial rounded-lg border-gray-300 text-sm">
                        <option value="">All Participants</option>
                        @foreach ($participants as $participant)
                            <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                {{ $participant->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Sort Filter -->
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant={{ request('participant') }}&sort=' + this.value"
                            class="flex-1 sm:flex-initial rounded-lg border-gray-300 text-sm">
                        <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Newest First</option>
                        <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>

                <!-- Tag Actions Bar (Tag Mode Only) -->
                <div x-show="mode === 'tag' && selectedCount > 0"
                     x-collapse
                     class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button @click="selectAll()"
                                class="flex-1 sm:flex-initial px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                            Select All
                        </button>
                        <button @click="showTagPanel = !showTagPanel"
                                class="flex-1 sm:flex-initial px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                            <span x-show="!showTagPanel">📌 Tag Selected</span>
                            <span x-show="showTagPanel">✕ Close Tags</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tag Panel (Full-screen on mobile, sidebar on desktop) -->
        <div x-show="showTagPanel && mode === 'tag'"
             x-transition
             class="fixed inset-0 sm:inset-auto sm:right-0 sm:top-0 sm:bottom-0 sm:w-96 bg-white shadow-2xl z-50 overflow-y-auto">
            <div class="p-4">
                <!-- Close button -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Apply Tags</h3>
                    <button @click="showTagPanel = false" class="p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Selected count -->
                <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <span class="font-semibold" x-text="selectedCount"></span> items selected
                    </p>
                </div>

                <!-- Existing tags -->
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Select tags to apply:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                        <button onclick="window.gallerySelection.applyTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                                class="px-3 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm transition active:scale-95">
                            + {{ $tag->name }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Create new tag -->
                <div class="border-t border-gray-200 pt-4">
                    <button @click="showNewTag = !showNewTag"
                            class="text-sm font-medium text-green-600 hover:text-green-700 mb-2">
                        <span x-show="!showNewTag">+ Create New Tag</span>
                        <span x-show="showNewTag">✕ Cancel</span>
                    </button>
                    <div x-show="showNewTag" x-collapse class="mt-2">
                        <input type="text"
                               x-model="newTagName"
                               @keydown.enter="createAndApplyTag()"
                               placeholder="New tag name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 mb-2"
                               maxlength="50">
                        <button @click="createAndApplyTag()"
                                :disabled="!newTagName.trim()"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Create & Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay for tag panel on mobile -->
        <div x-show="showTagPanel && mode === 'tag'"
             @click="showTagPanel = false"
             x-transition.opacity
             class="fixed inset-0 bg-black bg-opacity-50 z-40 sm:hidden"></div>

        <!-- Gallery Grid -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                @include('gallery.partials.media-grid-v2', ['media' => $media, 'tags' => $tags])
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
                    <p class="text-sm mt-2">Import a chat with media files to see them here!</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function galleryApp() {
            return {
                mode: 'browse',
                selectedCount: 0,
                showTagPanel: false,
                showNewTag: false,
                newTagName: '',

                init() {
                    // Initialize gallery selection
                    window.gallerySelection = {
                        selected: new Set(),

                        toggle(messageId) {
                            if (this.selected.has(messageId)) {
                                this.selected.delete(messageId);
                            } else {
                                this.selected.add(messageId);
                            }
                            this.updateUI();
                        },

                        updateUI() {
                            const count = this.selected.size;
                            // Dispatch event to update Alpine component
                            window.dispatchEvent(new CustomEvent('selection-changed', {
                                detail: { count }
                            }));
                        },

                        async applyTag(tagId, tagName) {
                            if (this.selected.size === 0) return;

                            const messageIds = Array.from(this.selected);
                            const token = document.querySelector('meta[name="csrf-token"]').content;

                            let successCount = 0;
                            let errorCount = 0;

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

                                    if (response.ok) {
                                        successCount++;
                                    } else {
                                        errorCount++;
                                    }
                                } catch (error) {
                                    console.error('Error tagging message:', messageId, error);
                                    errorCount++;
                                }
                            }

                            alert(`Tagged ${successCount} items with "${tagName}"${errorCount > 0 ? ` (${errorCount} errors)` : ''}`);
                            window.location.reload();
                        },

                        async createAndApplyTag(tagName) {
                            if (this.selected.size === 0) {
                                alert('Please select items first');
                                return;
                            }

                            if (!tagName || !tagName.trim()) {
                                alert('Please enter a tag name');
                                return;
                            }

                            const token = document.querySelector('meta[name="csrf-token"]').content;

                            try {
                                const createResponse = await fetch('/tags', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ name: tagName.trim() })
                                });

                                if (!createResponse.ok) {
                                    const error = await createResponse.json();
                                    alert(`Failed to create tag: ${error.message || 'Unknown error'}`);
                                    return;
                                }

                                const tagData = await createResponse.json();
                                const newTagId = tagData.id || tagData.tag?.id;

                                if (!newTagId) {
                                    alert('Tag created but could not get ID. Please refresh and try again.');
                                    return;
                                }

                                await this.applyTag(newTagId, tagName.trim());
                            } catch (error) {
                                console.error('Error creating tag:', error);
                                alert(`Error creating tag: ${error.message}`);
                            }
                        }
                    };

                    // Listen for selection changes
                    window.addEventListener('selection-changed', (e) => {
                        this.selectedCount = e.detail.count;
                    });

                    // Initialize infinite scroll
                    this.initInfiniteScroll();
                },

                selectAll() {
                    document.querySelectorAll('.media-checkbox').forEach(cb => {
                        cb.checked = true;
                        window.gallerySelection.selected.add(parseInt(cb.dataset.messageId));
                    });
                    window.gallerySelection.updateUI();
                },

                clearSelection() {
                    document.querySelectorAll('.media-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    window.gallerySelection.selected.clear();
                    window.gallerySelection.updateUI();
                },

                async createAndApplyTag() {
                    await window.gallerySelection.createAndApplyTag(this.newTagName);
                    this.newTagName = '';
                    this.showNewTag = false;
                },

                initInfiniteScroll() {
                    let currentPage = {{ $media->currentPage() }};
                    let hasMore = {{ $media->hasMorePages() ? 'true' : 'false' }};
                    let isLoading = false;

                    const loadingIndicator = document.getElementById('loading-indicator');
                    const mediaGrid = document.getElementById('media-grid');

                    const loadMore = () => {
                        if (isLoading || !hasMore) return;

                        isLoading = true;
                        loadingIndicator.classList.remove('hidden');

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

                            while (tempDiv.firstChild) {
                                mediaGrid.appendChild(tempDiv.firstChild);
                            }

                            currentPage = data.next_page;
                            hasMore = data.has_more;
                            isLoading = false;
                            loadingIndicator.classList.add('hidden');
                        })
                        .catch(error => {
                            console.error('Error loading more items:', error);
                            isLoading = false;
                            loadingIndicator.classList.add('hidden');
                        });
                    };

                    window.addEventListener('scroll', () => {
                        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
                            loadMore();
                        }
                    });

                    if (document.body.offsetHeight <= window.innerHeight && hasMore) {
                        loadMore();
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
