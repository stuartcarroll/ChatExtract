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
        Schema::create('tag_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->morphs('accessable'); // accessable_type and accessable_id for User or Group
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tag_id', 'accessable_type', 'accessable_id']);
            $table->index('granted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_access');
    }
};
