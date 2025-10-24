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
        // Add indexes - wrap in try/catch for duplicate index errors
        try {
            Schema::table('media', function (Blueprint $table) {
                $table->index(['type', 'message_id'], 'media_type_message_id_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index('user_id', 'import_progress_user_id_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index('status', 'import_progress_status_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'import_progress_user_id_status_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('messages', function (Blueprint $table) {
                $table->index(['chat_id', 'sent_at'], 'messages_chat_id_sent_at_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        try {
            Schema::table('messages', function (Blueprint $table) {
                $table->index('participant_id', 'messages_participant_id_index');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $databaseName = $connection->getDatabaseName();
            $result = $connection->select(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $index]
            );
            return $result[0]->count > 0;
        }

        // For other drivers (like SQLite), just try to create the index
        // Laravel will handle duplicate index creation gracefully
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['transcription_requested']);
            $table->dropIndex(['type', 'message_id']);
        });

        Schema::table('import_progress', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('chat_access', function (Blueprint $table) {
            $table->dropIndex(['accessable_type', 'accessable_id']);
            $table->dropIndex(['chat_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'sent_at']);
            $table->dropIndex(['participant_id']);
        });
    }
};
