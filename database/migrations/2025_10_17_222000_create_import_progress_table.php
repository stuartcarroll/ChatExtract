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
        Schema::create('import_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('chat_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('status'); // pending, processing, completed, failed
            $table->integer('total_messages')->default(0);
            $table->integer('processed_messages')->default(0);
            $table->integer('total_media')->default(0);
            $table->integer('processed_media')->default(0);
            $table->integer('images_count')->default(0);
            $table->integer('videos_count')->default(0);
            $table->integer('audio_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_progress');
    }
};
