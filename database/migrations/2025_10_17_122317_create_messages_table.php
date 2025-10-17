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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->nullable()->constrained()->onDelete('set null');
            $table->text('content');
            $table->dateTime('sent_at');
            $table->string('message_hash', 64)->unique();
            $table->boolean('is_system_message')->default(false);
            $table->boolean('is_story')->default(false);
            $table->float('story_confidence')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('participant_id');
            $table->index('sent_at');
            $table->index('is_story');
            $table->index('message_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
