<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /**
     * Show the two-factor authentication setup page.
     */
    public function show()
    {
        $user = auth()->user();

        // If user hasn't set up 2FA yet, generate a new secret
        if (!$user->two_factor_secret) {
            $google2fa = new Google2FA();
            $secret = $google2fa->generateSecretKey();

            // Store temporarily (not confirmed yet)
            $user->two_factor_secret = encrypt($secret);
            $user->save();
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        // Generate QR code
        $qrCodeUrl = $this->getQRCodeUrl($user->email, $secret);

        return view('auth.two-factor.setup', [
            'secret' => $secret,
            'qrCodeSvg' => $this->generateQRCode($qrCodeUrl),
            'enabled' => $user->hasTwoFactorEnabled(),
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        if (!$user->two_factor_secret) {
            return back()->withErrors(['code' => 'Two-factor authentication is not set up.']);
        }

        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();

        // Verify the code
        $valid = $google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'The provided code is invalid.']);
        }

        // Enable 2FA
        $user->two_factor_confirmed_at = now();
        $user->two_factor_method = 'authenticator';
        $user->save();

        // Generate recovery codes
        $recoveryCodes = $user->generateRecoveryCodes();

        return redirect()->route('two-factor.recovery-codes')
            ->with('recoveryCodes', $recoveryCodes)
            ->with('status', 'Two-factor authentication has been enabled!');
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->route('two-factor.show')
            ->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * Show recovery codes.
     */
    public function showRecoveryCodes()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.show');
        }

        $recoveryCodes = session('recoveryCodes') ?? $user->getRecoveryCodes();

        return view('auth.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'isNewSetup' => session()->has('recoveryCodes'),
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.show');
        }

        $recoveryCodes = $user->generateRecoveryCodes();

        return redirect()->route('two-factor.recovery-codes')
            ->with('recoveryCodes', $recoveryCodes)
            ->with('status', 'Recovery codes have been regenerated. Your old codes will no longer work.');
    }

    /**
     * Generate QR code as SVG.
     */
    protected function generateQRCode(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    /**
     * Get the QR code URL for Google Authenticator.
     */
    protected function getQRCodeUrl(string $email, string $secret): string
    {
        $appName = config('app.name');

        return "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";
    }
}
