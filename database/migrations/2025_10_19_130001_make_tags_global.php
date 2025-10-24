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
        // Check if user_id column exists before attempting modifications
        if (Schema::hasColumn('tags', 'user_id')) {
            // Try to drop foreign key (may not exist)
            try {
                Schema::table('tags', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            // Try to drop unique constraint (may not exist)
            try {
                Schema::table('tags', function (Blueprint $table) {
                    $table->dropUnique(['name', 'user_id']);
                });
            } catch (\Exception $e) {
                // Unique constraint doesn't exist, continue
            }

            // Try to drop index (may not exist)
            try {
                Schema::table('tags', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Drop the user_id column
            Schema::table('tags', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });

            // Add a unique constraint on name only (tags are now global)
            try {
                Schema::table('tags', function (Blueprint $table) {
                    $table->unique('name');
                });
            } catch (\Exception $e) {
                // Unique constraint already exists, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Drop the global unique constraint
            $table->dropUnique(['name']);

            // Re-add user_id column
            $table->foreignId('user_id')->after('name')->constrained()->onDelete('cascade');

            // Re-add the composite unique constraint
            $table->unique(['name', 'user_id']);

            // Re-add the user_id index
            $table->index('user_id');
        });
    }
};
