<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Global Gallery</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4 sticky top-0 z-20">
                <div class="flex flex-wrap gap-4 items-center">
                    <!-- Type Filter -->
                    <div class="flex gap-2">
                        <a href="{{ route('gallery.index', ['type' => 'all', 'participant' => request('participant'), 'sort' => request('sort', 'date_desc')]) }}"
                           class="px-4 py-2 rounded {{ $type === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            All ({{ $counts['all'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'image', 'participant' => request('participant'), 'sort' => request('sort', 'date_desc')]) }}"
                           class="px-4 py-2 rounded {{ $type === 'image' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Photos ({{ $counts['image'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'video', 'participant' => request('participant'), 'sort' => request('sort', 'date_desc')]) }}"
                           class="px-4 py-2 rounded {{ $type === 'video' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Videos ({{ $counts['video'] }})
                        </a>
                        <a href="{{ route('gallery.index', ['type' => 'audio', 'participant' => request('participant'), 'sort' => request('sort', 'date_desc')]) }}"
                           class="px-4 py-2 rounded {{ $type === 'audio' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                            Audio ({{ $counts['audio'] }})
                        </a>
                    </div>

                    <!-- Participant Filter -->
                    <div class="flex-1 min-w-48">
                        <select onchange="window.location.href='{{ route('gallery.index', ['type' => $type, 'sort' => request('sort', 'date_desc')]) }}&participant=' + this.value"
                                class="rounded border-gray-300 w-full">
                            <option value="">All Participants</option>
                            @foreach ($participants as $participant)
                                <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                    {{ $participant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort Filter -->
                    <div class="min-w-48">
                        <select onchange="window.location.href='{{ route('gallery.index', ['type' => $type, 'participant' => request('participant')]) }}&sort=' + this.value"
                                class="rounded border-gray-300 w-full">
                            <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Newest First</option>
                            <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bulk Tagging Toolbar (Sticky) -->
            <div id="bulk-toolbar" class="hidden bg-blue-600 text-white shadow-lg sm:rounded-lg mb-6 p-4 sticky top-24 z-20">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="font-semibold"><span id="selection-count">0</span> selected</span>
                        <button onclick="window.gallerySelection.selectAll()" class="px-3 py-1 bg-blue-700 hover:bg-blue-800 rounded text-sm">
                            Select All
                        </button>
                        <button onclick="window.gallerySelection.clearAll()" class="px-3 py-1 bg-blue-700 hover:bg-blue-800 rounded text-sm">
                            Clear All
                        </button>
                    </div>

                    <div class="flex items-center gap-2" x-data="{ showTags: false }">
                        <button @click="showTags = !showTags" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded font-semibold">
                            <span x-show="!showTags">Tag Selected</span>
                            <span x-show="showTags">Hide Tags</span>
                        </button>

                        <!-- Tag selection dropdown -->
                        <div x-show="showTags" x-collapse class="absolute right-0 top-full mt-2 bg-white text-gray-800 rounded-lg shadow-xl p-4 w-96 max-h-96 overflow-y-auto">
                            <p class="text-sm font-semibold mb-2">Select tags to apply:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($tags as $tag)
                                <button onclick="window.gallerySelection.applyTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                                        class="px-3 py-1 bg-gray-100 hover:bg-blue-100 rounded text-sm transition">
                                    + {{ $tag->name }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gallery Grid with Infinite Scroll -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div id="media-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @include('gallery.partials.media-grid', ['media' => $media, 'tags' => $tags])
                </div>

                <!-- Loading indicator -->
                <div id="loading-indicator" class="hidden text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-600 mt-2">Loading more...</p>
                </div>

                @if($media->isEmpty())
                    <div class="text-center text-gray-500 py-12">
                        No media found. Import a chat with media files to see them here!
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Gallery Selection Manager
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

            selectAll() {
                document.querySelectorAll('.media-checkbox').forEach(cb => {
                    cb.checked = true;
                    this.selected.add(parseInt(cb.dataset.messageId));
                });
                this.updateUI();
            },

            clearAll() {
                document.querySelectorAll('.media-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                this.selected.clear();
                this.updateUI();
            },

            updateUI() {
                const count = this.selected.size;
                document.getElementById('selection-count').textContent = count;
                document.getElementById('bulk-toolbar').classList.toggle('hidden', count === 0);
            },

            async applyTag(tagId, tagName) {
                if (this.selected.size === 0) return;

                const messageIds = Array.from(this.selected);
                const token = document.querySelector('meta[name="csrf-token"]').content;

                let successCount = 0;
                let errorCount = 0;

                // Show progress
                const progressMsg = `Tagging ${messageIds.length} items...`;
                console.log(progressMsg);

                // Tag each message
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

                // Reload page to show updated tags
                alert(`Tagged ${successCount} items with "${tagName}"${errorCount > 0 ? ` (${errorCount} errors)` : ''}`);
                window.location.reload();
            }
        };

        // Infinite Scroll
        let currentPage = {{ $media->currentPage() }};
        let hasMore = {{ $media->hasMorePages() ? 'true' : 'false' }};
        let isLoading = false;

        const loadingIndicator = document.getElementById('loading-indicator');
        const mediaGrid = document.getElementById('media-grid');

        function loadMore() {
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

                // Append new items
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
        }

        // Detect when user scrolls near bottom
        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
                loadMore();
            }
        });

        // Initial check in case content doesn't fill screen
        if (document.body.offsetHeight <= window.innerHeight && hasMore) {
            loadMore();
        }
    </script>
    @endpush
</x-app-layout>
