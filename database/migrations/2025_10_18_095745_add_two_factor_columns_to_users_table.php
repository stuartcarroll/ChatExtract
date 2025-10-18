<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Two-factor authentication secret (for authenticator apps)
            $table->text('two_factor_secret')->nullable()->after('password');

            // Recovery codes (JSON array)
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // When 2FA was confirmed/enabled
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Email OTP fields
            $table->string('email_otp_secret', 6)->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp_secret');

            // Preferred MFA method: 'authenticator' or 'email'
            $table->string('two_factor_method', 20)->default('authenticator')->after('email_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'email_otp_secret',
                'email_otp_expires_at',
                'two_factor_method',
            ]);
        });
    }
};
