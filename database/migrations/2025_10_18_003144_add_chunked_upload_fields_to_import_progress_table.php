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
            $table->string('upload_id')->nullable()->after('file_path');
            $table->integer('total_chunks')->nullable()->after('upload_id');
            $table->integer('uploaded_chunks')->default(0)->after('total_chunks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_progress', function (Blueprint $table) {
            $table->dropColumn(['upload_id', 'total_chunks', 'uploaded_chunks']);
        });
    }
};
