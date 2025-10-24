<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'chat_user', 'view_only'])->default('chat_user')->after('email');
        });

        // Migrate existing is_admin users to admin role
        DB::table('users')->where('is_admin', 1)->update(['role' => 'admin']);
        DB::table('users')->where('is_admin', 0)->update(['role' => 'chat_user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
