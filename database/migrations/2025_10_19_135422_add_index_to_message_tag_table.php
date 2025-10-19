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
        Schema::table('message_tag', function (Blueprint $table) {
            // Add index on tag_id for faster tag filtering
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_tag', function (Blueprint $table) {
            $table->dropIndex(['tag_id']);
        });
    }
};
