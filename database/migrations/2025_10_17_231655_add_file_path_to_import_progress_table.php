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
        Schema::table('import_progress', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('filename');
            $table->boolean('is_zip')->default(false)->after('file_path');
            $table->text('processing_log')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_progress', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'is_zip', 'processing_log']);
        });
    }
};
