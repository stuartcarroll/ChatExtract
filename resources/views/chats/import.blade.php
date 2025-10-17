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

                    <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data">
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
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Import Chat
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PHP.ini Configuration Note -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Server Configuration for Large Files</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        To allow uploads up to 10GB, ensure your php.ini has these settings:
                    </p>
                    <pre class="bg-gray-100 p-4 rounded text-xs overflow-x-auto"><code>upload_max_filesize = 10240M
post_max_size = 10240M
max_execution_time = 600
max_input_time = 600
memory_limit = 2048M</code></pre>
                    <p class="text-xs text-gray-500 mt-2">
                        Note: You may need to restart your web server after changing these settings.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
