<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    /**
     * Show the two-factor authentication challenge page.
     */
    public function show()
    {
        if (!session()->has('two_factor:user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.challenge');
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = session('two_factor:user');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            session()->forget('two_factor:user');
            return redirect()->route('login')
                ->withErrors(['code' => 'Two-factor authentication is not enabled.']);
        }

        $code = str_replace(' ', '', $request->code);

        // Check if it's a recovery code first
        if (strlen($code) === 10 && $user->useRecoveryCode($code)) {
            // Recovery code used successfully
            session()->forget('two_factor:user');
            auth()->login($user, session('two_factor:remember', false));
            session()->forget('two_factor:remember');

            return redirect()->intended(route('dashboard'))
                ->with('warning', 'You used a recovery code. Consider regenerating your recovery codes.');
        }

        // Verify the authenticator code
        if (strlen($code) === 6) {
            $google2fa = new Google2FA();
            $secret = decrypt($user->two_factor_secret);

            $valid = $google2fa->verifyKey($secret, $code);

            if ($valid) {
                session()->forget('two_factor:user');
                auth()->login($user, session('two_factor:remember', false));
                session()->forget('two_factor:remember');

                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors([
            'code' => 'The provided code is invalid.',
        ]);
    }
}
