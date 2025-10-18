<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Import WhatsApp Chat') }}
            </h2>
            <a href="{{ route('chats.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Chats
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Upload WhatsApp Export File</h3>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>How to export from WhatsApp:</strong><br>
                                    1. Open the chat in WhatsApp<br>
                                    2. Tap the menu (three dots) > More > Export chat<br>
                                    3. Choose "Include Media" to export with photos, videos, and voice notes<br>
                                    4. Save the .zip file (or .txt if without media) and upload it here
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Upload Progress (hidden by default) -->
                    <div id="upload-progress" class="hidden mb-6">
                        <div class="bg-white border-2 border-green-500 rounded-lg p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <svg class="animate-spin h-6 w-6 text-green-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-lg font-bold text-gray-800">Uploading file...</span>
                            </div>
                            <div class="relative w-full bg-gray-300 rounded-full h-8 mb-3 border border-gray-400">
                                <div id="upload-progress-bar" class="bg-gradient-to-r from-green-500 to-green-600 h-8 rounded-full transition-all duration-300 flex items-center justify-center shadow-inner" style="width: 0%; min-width: 0%;">
                                    <span id="upload-percentage" class="text-white text-sm font-bold drop-shadow-md"></span>
                                </div>
                            </div>
                            <p class="text-base font-semibold text-gray-700 mb-2">
                                <span id="upload-status">Preparing upload...</span>
                            </p>
                            <p class="text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded p-3">
                                ⚠️ Please keep this page open while the file uploads. Once complete, you'll be redirected to the progress page.
                            </p>
                        </div>
                    </div>

                    <form id="import-form" action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="chat_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Chat Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="chat_name"
                                id="chat_name"
                                value="{{ old('chat_name') }}"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="e.g., Family Group Chat"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="chat_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description (Optional)
                            </label>
                            <textarea
                                name="chat_description"
                                id="chat_description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Add a description for this chat..."
                            >{{ old('chat_description') }}</textarea>
                        </div>

                        <div class="mb-6">
                            <label for="chat_file" class="block text-sm font-medium text-gray-700 mb-2">
                                WhatsApp Export File (.txt or .zip with media) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="file"
                                name="chat_file"
                                id="chat_file"
                                accept=".txt,.zip"
                                required
                                class="w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100"
                            >
                            <p class="mt-1 text-xs text-gray-500">
                                Maximum file size: 10GB (upload may take several minutes for large files)
                            </p>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Note:</strong> After importing, story detection will run in the background.
                                        This may take a few minutes depending on the number of messages.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('chats.index') }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <button
                                id="submit-btn"
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded disabled:bg-gray-400 disabled:cursor-not-allowed"
                            >
                                Import Chat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
        const MAX_RETRIES = 3;

        document.getElementById('import-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = this;
            const fileInput = document.getElementById('chat_file');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please select a file to upload');
                return;
            }

            const chatName = document.getElementById('chat_name').value;
            const chatDescription = document.getElementById('chat_description').value;
            const submitBtn = document.getElementById('submit-btn');
            const progressDiv = document.getElementById('upload-progress');
            const progressBar = document.getElementById('upload-progress-bar');
            const percentageSpan = document.getElementById('upload-percentage');
            const statusSpan = document.getElementById('upload-status');
            const csrfToken = document.querySelector('input[name="_token"]').value;

            // Disable submit button and show progress
            submitBtn.disabled = true;
            progressDiv.classList.remove('hidden');
            form.classList.add('hidden');

            try {
                // Calculate total chunks
                const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                statusSpan.textContent = `Preparing upload (${totalChunks} chunks)...`;

                // Step 1: Initiate upload
                const initiateResponse = await fetch('{{ route('upload.initiate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        filename: file.name,
                        total_chunks: totalChunks,
                        file_size: file.size,
                        chat_name: chatName,
                        chat_description: chatDescription
                    })
                });

                if (!initiateResponse.ok) {
                    throw new Error('Failed to initiate upload');
                }

                const { upload_id, progress_id } = await initiateResponse.json();
                statusSpan.textContent = `Uploading chunks...`;

                // Step 2: Upload chunks
                for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                    const start = chunkIndex * CHUNK_SIZE;
                    const end = Math.min(start + CHUNK_SIZE, file.size);
                    const chunk = file.slice(start, end);

                    // Upload chunk with retry
                    let retries = 0;
                    let success = false;

                    while (retries < MAX_RETRIES && !success) {
                        try {
                            const chunkFormData = new FormData();
                            chunkFormData.append('upload_id', upload_id);
                            chunkFormData.append('chunk_index', chunkIndex);
                            chunkFormData.append('chunk', chunk);

                            const chunkResponse = await fetch('{{ route('upload.chunk') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: chunkFormData
                            });

                            if (!chunkResponse.ok) {
                                const errorData = await chunkResponse.json().catch(() => ({ error: 'Unknown error' }));
                                throw new Error(errorData.error || `Chunk ${chunkIndex} upload failed with status ${chunkResponse.status}`);
                            }

                            const chunkResult = await chunkResponse.json();
                            success = true;

                            // Update progress
                            const percentComplete = Math.round(((chunkIndex + 1) / totalChunks) * 100);
                            progressBar.style.width = percentComplete + '%';
                            percentageSpan.textContent = percentComplete + '%';

                            const uploadedMB = ((chunkIndex + 1) * CHUNK_SIZE / 1024 / 1024).toFixed(1);
                            const totalMB = (file.size / 1024 / 1024).toFixed(1);
                            statusSpan.textContent = `Uploaded ${uploadedMB} MB of ${totalMB} MB (chunk ${chunkIndex + 1}/${totalChunks})`;

                        } catch (error) {
                            retries++;
                            if (retries < MAX_RETRIES) {
                                statusSpan.textContent = `Chunk ${chunkIndex} failed, retrying (${retries}/${MAX_RETRIES})...`;
                                await new Promise(resolve => setTimeout(resolve, 1000 * retries)); // Exponential backoff
                            } else {
                                throw new Error(`Failed to upload chunk ${chunkIndex} after ${MAX_RETRIES} attempts`);
                            }
                        }
                    }
                }

                // Step 3: Finalize upload
                statusSpan.textContent = 'Combining chunks and starting import...';
                progressBar.style.width = '100%';
                percentageSpan.textContent = '100%';

                const finalizeResponse = await fetch('{{ route('upload.finalize') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        upload_id: upload_id,
                        chat_name: chatName,
                        chat_description: chatDescription
                    })
                });

                if (!finalizeResponse.ok) {
                    throw new Error('Failed to finalize upload');
                }

                const finalResult = await finalizeResponse.json();
                statusSpan.textContent = 'Upload complete! Redirecting to progress page...';

                // Redirect to progress page
                setTimeout(() => {
                    window.location.href = finalResult.redirect_url;
                }, 1000);

            } catch (error) {
                console.error('Upload error:', error);
                statusSpan.textContent = 'Upload failed: ' + error.message;
                progressDiv.classList.add('hidden');
                form.classList.remove('hidden');
                submitBtn.disabled = false;
                alert('Upload failed: ' + error.message + '. Please try again.');
            }
        });

        // Show file size when selected
        document.getElementById('chat_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(1);
                const sizeText = document.querySelector('#chat_file + p');
                const chunks = Math.ceil(file.size / CHUNK_SIZE);
                sizeText.textContent = `Selected: ${file.name} (${sizeMB} MB, will be uploaded in ${chunks} chunks) - Maximum file size: 10GB`;
            }
        });
    </script>
</x-app-layout>
