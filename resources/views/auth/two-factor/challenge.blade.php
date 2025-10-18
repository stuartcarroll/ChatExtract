<x-guest-layout>
    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <!-- Header -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Two-Factor Authentication</h2>
            <p class="mt-2 text-sm text-gray-600">
                Please enter the 6-digit code from your authenticator app, or use a recovery code.
            </p>
        </div>

        <!-- Code Input -->
        <div>
            <x-input-label for="code" :value="__('Authentication Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl tracking-widest font-mono"
                          type="text"
                          name="code"
                          required
                          autofocus
                          autocomplete="one-time-code"
                          placeholder="000000"
                          maxlength="10" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <!-- Helper Text -->
        <div class="mt-4 text-xs text-gray-600">
            <p>Enter your 6-digit authenticator code or a 10-character recovery code.</p>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>

        <!-- Back to Login -->
        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Back to login
            </a>
        </div>
    </form>
</x-guest-layout>
