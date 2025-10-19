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
            Schema::table('tags', function (Blueprint $table) {
                // Drop the foreign key constraint first (before index)
                // Foreign key name varies, use the column name
                $table->dropForeign(['user_id']);
            });

            // Drop constraints/indexes separately to avoid conflicts
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();

            // Check and drop unique constraint if it exists
            $uniqueExists = $connection->select(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = 'tags' AND index_name = 'tags_name_user_id_unique'",
                [$databaseName]
            );

            if ($uniqueExists[0]->count > 0) {
                Schema::table('tags', function (Blueprint $table) {
                    $table->dropUnique(['name', 'user_id']);
                });
            }

            // Check and drop user_id index if it exists
            $indexExists = $connection->select(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = 'tags' AND index_name = 'tags_user_id_index'",
                [$databaseName]
            );

            if ($indexExists[0]->count > 0) {
                Schema::table('tags', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
            }

            // Drop the user_id column
            Schema::table('tags', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });

            // Add a unique constraint on name only (tags are now global)
            Schema::table('tags', function (Blueprint $table) {
                $table->unique('name');
            });
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
