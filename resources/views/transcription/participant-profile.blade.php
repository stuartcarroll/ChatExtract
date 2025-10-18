<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Participant Profile: {{ $participant->name }}
            </h2>
            <a href="{{ route('transcription.participants') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Participants
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Participant Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $participant->name }}</h3>
                            @if($participant->phone_number)
                            <p class="text-sm text-gray-500 mt-1">{{ $participant->phone_number }}</p>
                            @endif
                        </div>
                        <div>
                            @if($participant->transcription_consent)
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    ✓ Transcription Consent Given
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    ✗ No Transcription Consent
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($participant->transcription_consent && $participant->transcription_consent_given_at)
                    <div class="mt-4 text-sm text-gray-600">
                        Consent granted {{ $participant->transcription_consent_given_at->diffForHumans() }}
                        @if($participant->consentGrantedBy)
                            by {{ $participant->consentGrantedBy->name }}
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Total Messages</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_messages']) }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Voice Notes</div>
                        <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['audio_messages']) }}</div>
                        @if($stats['audio_messages'] > 0)
                        <div class="text-xs text-gray-500 mt-1">
                            {{ number_format($stats['transcribed_audio']) }} transcribed
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Chats</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['chats_participated']) }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Images Sent</div>
                        <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['images_sent']) }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Videos Sent</div>
                        <div class="text-3xl font-bold text-pink-600">{{ number_format($stats['videos_sent']) }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Total Media</div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ number_format($stats['images_sent'] + $stats['videos_sent'] + $stats['audio_messages']) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chats Participated In -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Chats Participated In</h3>

                    @if($chats->isEmpty())
                        <p class="text-gray-500 text-center py-4">No chats found.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chat Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages from {{ $participant->name }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Message</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($chats as $chat)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $chat->name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ number_format($chat->messages_count) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $chat->last_message_at ? \Carbon\Carbon::parse($chat->last_message_at)->diffForHumans() : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('chats.show', $chat->id) }}" class="text-blue-600 hover:text-blue-900">
                                                    View Chat
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
