<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recovery Codes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($isNewSetup)
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Important:</strong> Store these recovery codes in a safe place. They can be used to access your account if you lose access to your authenticator device.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Your Recovery Codes</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Each recovery code can only be used once. If you use a code or lose access to your codes, you can regenerate a new set.
                        </p>
                    </div>

                    <!-- Recovery Codes Display -->
                    <div class="bg-gray-100 rounded-lg p-6 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($recoveryCodes as $code)
                                <div class="bg-white rounded px-4 py-3 text-center">
                                    <code class="text-lg font-mono">{{ $code }}</code>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            <button onclick="printCodes()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Print Codes
                            </button>
                            <button onclick="copyCodes()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Copy to Clipboard
                            </button>
                        </div>

                        @if (!$isNewSetup)
                            <button onclick="document.getElementById('regenerate-form').classList.remove('hidden')" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                Regenerate Codes
                            </button>
                        @else
                            <a href="{{ route('dashboard') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Continue to Dashboard
                            </a>
                        @endif
                    </div>

                    <!-- Regenerate Form (hidden by default) -->
                    @if (!$isNewSetup)
                        <form id="regenerate-form" method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="mt-6 hidden">
                            @csrf

                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-800 mb-2">Regenerate Recovery Codes</h4>
                                <p class="text-sm text-yellow-600 mb-4">
                                    This will invalidate all your current recovery codes. Enter your password to confirm.
                                </p>

                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                    <input type="password" id="password" name="password" required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                        Confirm Regenerate
                                    </button>
                                    <button type="button" onclick="document.getElementById('regenerate-form').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function printCodes() {
            window.print();
        }

        function copyCodes() {
            const codes = @json($recoveryCodes);
            const text = codes.join('\n');

            navigator.clipboard.writeText(text).then(() => {
                alert('Recovery codes copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy codes:', err);
            });
        }
    </script>
</x-app-layout>
