<x-app-layout>
    <div class="min-h-screen bg-gray-100">
        <!-- Simple Top Bar -->
        <div class="bg-white border-b sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <!-- Filters Row -->
                <div class="flex gap-2 mb-3 overflow-x-auto">
                    <select onchange="window.location.href='{{ route('gallery.index') }}?type=' + this.value + '&participant={{ request('participant') }}&sort={{ request('sort', 'date_desc') }}'"
                            class="px-3 py-2 text-sm border rounded-lg bg-white">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All ({{ $counts['all'] }})</option>
                        <option value="image" {{ $type === 'image' ? 'selected' : '' }}>Photos ({{ $counts['image'] }})</option>
                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Videos ({{ $counts['video'] }})</option>
                        <option value="audio" {{ $type === 'audio' ? 'selected' : '' }}>Audio ({{ $counts['audio'] }})</option>
                    </select>

                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant=' + this.value + '&sort={{ request('sort', 'date_desc') }}'"
                            class="px-3 py-2 text-sm border rounded-lg bg-white flex-1 min-w-0">
                        <option value="">All People</option>
                        @foreach ($participants as $participant)
                            <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                {{ $participant->name }}
                            </option>
                        @endforeach
                    </select>

                    <select onchange="window.location.href='{{ route('gallery.index') }}?type={{ $type }}&participant={{ request('participant') }}&sort=' + this.value"
                            class="px-3 py-2 text-sm border rounded-lg bg-white">
                        <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </div>

                <!-- Selection Bar (shows when items selected) - Sticky -->
                <div id="selection-bar" style="display: none; position: sticky; top: 0; z-index: 50; background-color: #2563eb; color: white; padding: 12px; border-radius: 8px;" class="items-center justify-between shadow-lg">
                    <span class="text-sm font-semibold">
                        <span id="selection-count">0</span> selected
                    </span>
                    <div class="flex gap-2">
                        <button onclick="clearSelection()" style="background-color: rgba(255,255,255,0.2); color: white;" class="px-4 py-2 text-sm rounded-lg font-medium hover:bg-white/30">
                            Clear
                        </button>
                        <button onclick="showQuickTag()" style="background-color: #10b981; color: white;" class="px-3 py-2 text-sm rounded-lg font-medium hover:bg-green-600">
                            Quick Tag
                        </button>
                        <button onclick="openTagSheet()" style="background-color: #16a34a; color: white;" class="px-4 py-2 text-sm rounded-lg font-medium hover:bg-green-700">
                            🏷️ All Tags
                        </button>
                    </div>
                </div>

                <!-- Quick Tag Input (inline) - Sticky -->
                <div id="quick-tag" style="display: none; position: sticky; top: 60px; z-index: 50; background-color: #dbeafe; padding: 12px; border-radius: 8px; margin-top: 8px;" class="flex gap-2">
                    <input type="text" id="quick-tag-input" placeholder="New tag name..."
                           style="flex: 1; padding: 8px 12px; border: 1px solid #93c5fd; border-radius: 6px;"
                           onkeypress="if(event.key==='Enter') createQuickTag()">
                    <button id="quick-tag-btn" onclick="createQuickTag()" style="background-color: #16a34a; color: white;" class="px-4 py-2 text-sm rounded-lg font-medium hover:bg-green-700">
                        Create & Apply
                    </button>
                    <button onclick="hideQuickTag()" style="background-color: #6b7280; color: white;" class="px-3 py-2 text-sm rounded-lg font-medium hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Simple Grid -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                @include('gallery.partials.media-grid', ['media' => $media, 'tags' => $tags])
            </div>

            <div id="loading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            @if($media->isEmpty())
                <div class="text-center py-12 text-gray-500">
                    <p>No media found</p>
                </div>
            @endif
        </div>

        <!-- Tag Sheet (slides up from bottom) -->
        <div id="tag-sheet" class="hidden fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/50" onclick="closeTagSheet()"></div>
            <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b px-4 py-3 flex items-center justify-between">
                    <h3 class="font-semibold">Tag Items</h3>
                    <button onclick="closeTagSheet()" class="p-1 hover:bg-gray-100 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-4 space-y-4">
                    <!-- Existing Tags -->
                    <div>
                        <p class="text-sm font-medium mb-2">Tags:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                            <button onclick="applyTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                                    class="px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm">
                                {{ $tag->name }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Create New Tag -->
                    <div class="border-t pt-4">
                        <p class="text-sm font-medium mb-2">New Tag:</p>
                        <div class="flex gap-2">
                            <input type="text" id="new-tag-input" placeholder="Tag name..."
                                   class="flex-1 px-3 py-2 border rounded-lg"
                                   onkeypress="if(event.key==='Enter') createTag()">
                            <button onclick="createTag()"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Create
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let selected = new Set();

        function toggleSelection(id, checkbox) {
            if (checkbox.checked) {
                selected.add(id);
            } else {
                selected.delete(id);
            }
            updateSelectionBar();
        }

        function updateSelectionBar() {
            const bar = document.getElementById('selection-bar');
            const count = document.getElementById('selection-count');
            count.textContent = selected.size;

            bar.style.display = selected.size > 0 ? 'flex' : 'none';
        }

        function clearSelection() {
            selected.clear();
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
            updateSelectionBar();
            hideQuickTag();
        }

        function showQuickTag() {
            document.getElementById('quick-tag').style.display = 'flex';
            document.getElementById('quick-tag-input').focus();
        }

        function hideQuickTag() {
            document.getElementById('quick-tag').style.display = 'none';
            document.getElementById('quick-tag-input').value = '';
        }

        async function createQuickTag() {
            const input = document.getElementById('quick-tag-input');
            const btn = document.getElementById('quick-tag-btn');
            const name = input.value.trim();

            if (!name || selected.size === 0) return;

            // Show loading state
            btn.disabled = true;
            btn.style.backgroundColor = '#9ca3af';
            btn.textContent = 'Creating...';

            const token = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const res = await fetch('/tags', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ name })
                });

                if (!res.ok) {
                    const errorText = await res.text();
                    console.error('Error response:', errorText);
                    // Show error in button
                    btn.style.backgroundColor = '#dc2626';
                    btn.textContent = res.status === 422 ? 'Tag exists' : '✗ Error';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.style.backgroundColor = '#16a34a';
                        btn.textContent = 'Create & Apply';
                    }, 2000);
                    return;
                }

                const data = await res.json();

                // Show applying state
                btn.textContent = 'Applying...';

                await applyTag(data.id, name);

                // Success - show checkmark briefly
                btn.style.backgroundColor = '#059669';
                btn.textContent = '✓ Done!';

            } catch (e) {
                console.error('Exception:', e);
                // Show error in button briefly
                btn.style.backgroundColor = '#dc2626';
                btn.textContent = '✗ Error';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.style.backgroundColor = '#16a34a';
                    btn.textContent = 'Create & Apply';
                }, 2000);
            }
        }

        function openTagSheet() {
            if (selected.size === 0) return;
            hideQuickTag();
            document.getElementById('tag-sheet').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeTagSheet() {
            document.getElementById('tag-sheet').classList.add('hidden');
            document.body.style.overflow = '';
        }

        async function applyTag(tagId, tagName) {
            if (selected.size === 0) return;

            const token = document.querySelector('meta[name="csrf-token"]').content;
            let success = 0;

            for (const id of selected) {
                try {
                    const response = await fetch(`/messages/${id}/tag`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ tag_id: tagId })
                    });

                    if (response.ok) {
                        success++;
                        // Add tag chip to the item in DOM
                        updateTagChipsInDOM(id, tagId, tagName);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            // Clear selection and hide UI
            clearSelection();
            hideQuickTag();
            closeTagSheet();
        }

        function updateTagChipsInDOM(messageId, tagId, tagName) {
            // Find all items with this message and update their tag chips
            const items = document.querySelectorAll(`input[onchange*="toggleSelection(${messageId}"]`);
            items.forEach(checkbox => {
                const itemDiv = checkbox.closest('.relative');
                const tagsContainer = itemDiv.querySelector('.p-2.border-t');

                // Check if tag already exists
                const existingTags = tagsContainer.querySelectorAll('.bg-blue-100');
                const tagExists = Array.from(existingTags).some(t => t.textContent === tagName);

                if (!tagExists) {
                    // Remove "No tags" message if present
                    const noTags = tagsContainer.querySelector('.text-gray-400');
                    if (noTags) {
                        noTags.remove();
                        // Create flex container if needed
                        const flexDiv = document.createElement('div');
                        flexDiv.className = 'flex flex-wrap gap-1';
                        tagsContainer.appendChild(flexDiv);
                    }

                    // Add new tag chip
                    const tagChip = document.createElement('span');
                    tagChip.className = 'text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full';
                    tagChip.textContent = tagName;

                    const flexContainer = tagsContainer.querySelector('.flex');
                    if (flexContainer) {
                        flexContainer.appendChild(tagChip);
                    }
                }
            });
        }

        async function createTag() {
            const input = document.getElementById('new-tag-input');
            const name = input.value.trim();

            if (!name || selected.size === 0) return;

            const token = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const res = await fetch('/tags', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ name })
                });

                const data = await res.json();
                await applyTag(data.id, name);
            } catch (e) {
                alert('Error creating tag');
            }
        }

        // Infinite scroll
        let page = {{ $media->currentPage() }};
        let hasMore = {{ $media->hasMorePages() ? 'true' : 'false' }};
        let loading = false;

        window.addEventListener('scroll', () => {
            if (loading || !hasMore) return;
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
                loadMore();
            }
        });

        async function loadMore() {
            loading = true;
            document.getElementById('loading').classList.remove('hidden');

            const params = new URLSearchParams(window.location.search);
            params.set('page', page + 1);

            try {
                const res = await fetch(`{{ route('gallery.index') }}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                const temp = document.createElement('div');
                temp.innerHTML = data.html;
                document.getElementById('media-grid').append(...temp.children);

                page = data.next_page;
                hasMore = data.has_more;
            } catch (e) {
                console.error(e);
            }

            loading = false;
            document.getElementById('loading').classList.add('hidden');
        }
    </script>
    @endpush
</x-app-layout>
