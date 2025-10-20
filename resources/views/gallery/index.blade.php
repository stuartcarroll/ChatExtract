<x-app-layout>
    <div class="min-h-screen bg-gray-100">
        <!-- Simple Top Bar -->
        <div class="bg-white border-b sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <!-- Filters Row -->
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <select onchange="updateFilters('type', this.value)"
                            class="px-3 py-2 text-sm border rounded-lg bg-white">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All ({{ $counts['all'] }})</option>
                        <option value="image" {{ $type === 'image' ? 'selected' : '' }}>Photos ({{ $counts['image'] }})</option>
                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Videos ({{ $counts['video'] }})</option>
                        <option value="audio" {{ $type === 'audio' ? 'selected' : '' }}>Audio ({{ $counts['audio'] }})</option>
                    </select>

                    <select onchange="updateFilters('participant', this.value)"
                            class="px-3 py-2 text-sm border rounded-lg bg-white flex-1 min-w-0">
                        <option value="">All People</option>
                        @foreach ($participants as $participant)
                            <option value="{{ $participant->id }}" {{ $participantId == $participant->id ? 'selected' : '' }}>
                                {{ $participant->name }}
                            </option>
                        @endforeach
                    </select>

                    <select onchange="updateFilters('tags', this.value)"
                            class="px-3 py-2 text-sm border rounded-lg bg-white"
                            id="tag-filter"
                            {{ request()->has('tags') && is_array(request('tags')) && count(request('tags')) > 1 ? 'multiple' : '' }}>
                        <option value="">All Items</option>
                        <option value="untagged" {{ request('tags') === 'untagged' ? 'selected' : '' }}>Untagged Only</option>
                        <optgroup label="Filter by Tag">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ (request('tags') == $tag->id || (is_array(request('tags')) && in_array($tag->id, request('tags')))) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>

                    <button onclick="toggleMultiTagMode()"
                            class="px-3 py-2 text-sm border rounded-lg bg-white hover:bg-gray-50 whitespace-nowrap"
                            id="multi-tag-btn">
                        🏷️ Multi-Tag
                    </button>

                    <select onchange="updateFilters('sort', this.value)"
                            class="px-3 py-2 text-sm border rounded-lg bg-white">
                        <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Selection Bar (Fixed when items selected) -->
        <div id="selection-bar" style="display: none; position: fixed; top: 70px; left: 0; right: 0; z-index: 40; background-color: #2563eb; color: white; padding: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);" class="items-center justify-between">
            <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
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
        </div>

        <!-- Quick Tag Input (Fixed) -->
        <div id="quick-tag" style="display: none; position: fixed; top: 130px; left: 0; right: 0; z-index: 40; background-color: #dbeafe; padding: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div class="max-w-7xl mx-auto px-4 flex gap-2">
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

        <!-- Scroll to Top Button -->
        <button id="scroll-to-top" onclick="scrollToTop()"
                style="display: none; position: fixed; bottom: 24px; right: 24px; z-index: 45; background-color: #2563eb; color: white; width: 48px; height: 48px; border-radius: 50%; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);"
                class="flex items-center justify-center hover:bg-blue-700 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            </svg>
        </button>

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
        let multiTagMode = false;

        function updateFilters(param, value) {
            const url = new URL(window.location);

            // Handle multi-tag mode
            if (param === 'tags' && multiTagMode) {
                const select = document.getElementById('tag-filter');
                const selectedOptions = Array.from(select.selectedOptions).map(opt => opt.value).filter(v => v && v !== 'untagged');

                // Clear existing tags param
                url.searchParams.delete('tags');
                url.searchParams.delete('tags[]');

                // Add all selected tags
                if (selectedOptions.length > 0) {
                    selectedOptions.forEach(tagId => {
                        url.searchParams.append('tags[]', tagId);
                    });
                }
            } else {
                // Normal single selection
                if (value) {
                    url.searchParams.set(param, value);
                } else {
                    url.searchParams.delete(param);
                }
            }

            window.location.href = url.toString();
        }

        function toggleMultiTagMode() {
            multiTagMode = !multiTagMode;
            const select = document.getElementById('tag-filter');
            const btn = document.getElementById('multi-tag-btn');

            if (multiTagMode) {
                select.setAttribute('multiple', 'multiple');
                select.size = 5;
                btn.style.backgroundColor = '#2563eb';
                btn.style.color = 'white';
            } else {
                select.removeAttribute('multiple');
                select.size = 1;
                btn.style.backgroundColor = '';
                btn.style.color = '';
            }
        }

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
            const messageIds = Array.from(selected);

            try {
                // Batch tag all messages in a single request
                const response = await fetch('/messages/batch-tag', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        message_ids: messageIds,
                        tag_id: tagId
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    // Update DOM for all successfully tagged messages
                    messageIds.forEach(id => {
                        updateTagChipsInDOM(id, tagId, tagName);
                    });
                } else {
                    console.error('Batch tag failed:', await response.text());
                }
            } catch (e) {
                console.error('Exception during batch tag:', e);
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
            // Infinite scroll
            if (!loading && hasMore) {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
                    loadMore();
                }
            }

            // Show/hide scroll to top button
            const scrollBtn = document.getElementById('scroll-to-top');
            if (window.scrollY > 300) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

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
