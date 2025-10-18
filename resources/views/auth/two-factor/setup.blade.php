<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Two-Factor Authentication') }}
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

                    @if ($enabled)
                        <!-- 2FA is Enabled -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-green-600 mb-2">✓ Two-Factor Authentication is Enabled</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Your account is protected with two-factor authentication using an authenticator app.
                            </p>

                            <div class="flex gap-4">
                                <a href="{{ route('two-factor.recovery-codes') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    View Recovery Codes
                                </a>

                                <button onclick="document.getElementById('disable-2fa-form').classList.remove('hidden')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Disable 2FA
                                </button>
                            </div>

                            <!-- Disable Form (hidden by default) -->
                            <form id="disable-2fa-form" method="POST" action="{{ route('two-factor.disable') }}" class="mt-6 hidden">
                                @csrf
                                @method('DELETE')

                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-red-800 mb-2">Disable Two-Factor Authentication</h4>
                                    <p class="text-sm text-red-600 mb-4">Enter your password to confirm you want to disable two-factor authentication.</p>

                                    <div class="mb-4">
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                        <input type="password" id="password" name="password" required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                        @error('password')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                            Confirm Disable
                                        </button>
                                        <button type="button" onclick="document.getElementById('disable-2fa-form').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @else
                        <!-- 2FA Setup -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-2">Enable Two-Factor Authentication</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Two-factor authentication adds an additional layer of security to your account by requiring a code from your phone when you log in.
                            </p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                            <h4 class="font-semibold text-blue-900 mb-4">Step 1: Scan the QR Code</h4>
                            <p class="text-sm text-blue-800 mb-4">
                                Scan this QR code with your authenticator app (such as Google Authenticator, Authy, or 1Password).
                            </p>

                            <div class="flex justify-center mb-4">
                                <div class="bg-white p-4 rounded-lg shadow">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>

                            <div class="bg-white rounded-lg p-4">
                                <p class="text-xs text-gray-600 mb-2">Or enter this code manually:</p>
                                <code class="block text-center text-lg font-mono bg-gray-100 p-3 rounded select-all">{{ $secret }}</code>
                            </div>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <h4 class="font-semibold text-green-900 mb-4">Step 2: Enter the Code</h4>
                            <p class="text-sm text-green-800 mb-4">
                                Enter the 6-digit code from your authenticator app to confirm the setup.
                            </p>

                            <form method="POST" action="{{ route('two-factor.enable') }}">
                                @csrf

                                <div class="mb-4">
                                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Authentication Code
                                    </label>
                                    <input type="text" id="code" name="code" required maxlength="6" pattern="[0-9]{6}"
                                           placeholder="000000"
                                           class="w-full text-center text-2xl tracking-widest font-mono rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                           autofocus>
                                    @error('code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded">
                                    Enable Two-Factor Authentication
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
