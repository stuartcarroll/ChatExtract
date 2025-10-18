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
        Schema::table('participants', function (Blueprint $table) {
            // Default false - must explicitly opt-in for privacy
            $table->boolean('transcription_consent')->default(false)->after('name');
            $table->timestamp('transcription_consent_given_at')->nullable()->after('transcription_consent');
            $table->unsignedBigInteger('transcription_consent_given_by')->nullable()->after('transcription_consent_given_at');

            // Foreign key to track who gave consent (admin user)
            $table->foreign('transcription_consent_given_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['transcription_consent_given_by']);
            $table->dropColumn(['transcription_consent', 'transcription_consent_given_at', 'transcription_consent_given_by']);
        });
    }
};
