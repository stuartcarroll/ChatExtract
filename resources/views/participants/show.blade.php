<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $participant->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('participants.gallery', $participant) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    View Gallery
                </a>
                <a href="{{ route('participants.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    All Participants
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Profile Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Profile Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-medium">{{ $participant->name }}</p>
                        </div>
                        @if($participant->phone_number)
                        <div>
                            <p class="text-sm text-gray-600">Phone Number</p>
                            <p class="font-medium">{{ $participant->phone_number }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-600">Chat</p>
                            <p class="font-medium">
                                <a href="{{ route('chats.show', $participant->chat) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $participant->chat->name }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Transcription Consent</p>
                            <p class="font-medium">
                                @if($participant->hasTranscriptionConsent())
                                    <span class="text-green-600">✓ Granted</span>
                                @else
                                    <span class="text-red-600">✗ Not granted</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['total_messages']) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Total Messages</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['photos']) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Photos</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-pink-600">{{ number_format($stats['videos']) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Videos</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600">{{ number_format($stats['voice_notes']) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Voice Notes</div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Additional Statistics</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-600">Total Media Files</p>
                            <p class="text-2xl font-bold">{{ number_format($stats['total_media']) }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Voice Notes Duration</p>
                            <p class="text-2xl font-bold">{{ $stats['voice_notes_minutes'] }} min</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Chats Participated In</p>
                            <p class="text-2xl font-bold">{{ $stats['chats_count'] }}</p>
                        </div>

                        @if($stats['deleted_media'] > 0)
                        <div>
                            <p class="text-sm text-gray-600">Deleted Media (NSFW)</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['deleted_media']) }}</p>
                        </div>
                        @endif

                        @if($stats['first_message_at'])
                        <div>
                            <p class="text-sm text-gray-600">First Message</p>
                            <p class="text-lg font-medium">{{ $stats['first_message_at']->format('M d, Y') }}</p>
                        </div>
                        @endif

                        @if($stats['last_message_at'])
                        <div>
                            <p class="text-sm text-gray-600">Last Message</p>
                            <p class="text-lg font-medium">{{ $stats['last_message_at']->format('M d, Y') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="flex gap-3">
                        <a href="{{ route('participants.gallery', $participant) }}"
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            📷 View Full Gallery
                        </a>
                        <a href="{{ route('chats.show', $participant->chat) }}"
                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            💬 View Chat History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
