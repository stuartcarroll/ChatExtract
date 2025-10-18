<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Transcription Consent Management
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('transcription.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Privacy Notice -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Privacy Notice:</strong> Voice notes will only be transcribed from participants who have given explicit consent below.
                            By default, all participants are set to <strong>NO CONSENT</strong>. You must explicitly grant consent for each participant before their voice notes can be transcribed.
                        </p>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Participants Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Participants Across All Your Chats</h3>

                    @if ($participants->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p>No participants found in your chats.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voice Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consent Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consent Details</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($participants as $participant)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('transcription.participant.profile', $participant) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                                                    {{ $participant->name }}
                                                </a>
                                                @if($participant->phone_number)
                                                <div class="text-xs text-gray-500">{{ $participant->phone_number }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $participant->audio_messages_count }} voice notes
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($participant->transcription_consent)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        ✓ Consent Given
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        ✗ No Consent
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                @if($participant->transcription_consent && $participant->transcription_consent_given_at)
                                                    <div class="text-xs">
                                                        Given {{ $participant->transcription_consent_given_at->diffForHumans() }}
                                                        @if($participant->consentGrantedBy)
                                                            <br>by {{ $participant->consentGrantedBy->name }}
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400">Not granted</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($participant->transcription_consent)
                                                    <form action="{{ route('transcription.consent.update', $participant) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="consent" value="0">
                                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium"
                                                                onclick="return confirm('Revoke transcription consent for {{ $participant->name }}? Their voice notes will no longer be transcribed.')">
                                                            Revoke Consent
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('transcription.consent.update', $participant) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="consent" value="1">
                                                        <button type="submit" class="text-green-600 hover:text-green-900 font-medium"
                                                                onclick="return confirm('Grant transcription consent for {{ $participant->name }}? This will allow their voice notes to be transcribed using OpenAI Whisper API.')">
                                                            Grant Consent
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Stats -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm text-gray-600">Total Participants</div>
                                <div class="text-2xl font-bold text-gray-800">{{ $participants->count() }}</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-sm text-green-600">Consent Granted</div>
                                <div class="text-2xl font-bold text-green-600">{{ $participants->where('transcription_consent', true)->count() }}</div>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg">
                                <div class="text-sm text-red-600">No Consent</div>
                                <div class="text-2xl font-bold text-red-600">{{ $participants->where('transcription_consent', false)->count() }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
