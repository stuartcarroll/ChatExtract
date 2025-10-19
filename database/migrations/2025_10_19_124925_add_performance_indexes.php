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
        // Add indexes to media table (only if they don't exist)
        if (!$this->indexExists('media', 'media_transcription_requested_index')) {
            Schema::table('media', function (Blueprint $table) {
                $table->index('transcription_requested');
            });
        }
        if (!$this->indexExists('media', 'media_type_message_id_index')) {
            Schema::table('media', function (Blueprint $table) {
                $table->index(['type', 'message_id']);
            });
        }

        // Add indexes to import_progress table (only if they don't exist)
        if (!$this->indexExists('import_progress', 'import_progress_user_id_index')) {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index('user_id');
            });
        }
        if (!$this->indexExists('import_progress', 'import_progress_status_index')) {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index('status');
            });
        }
        if (!$this->indexExists('import_progress', 'import_progress_user_id_status_index')) {
            Schema::table('import_progress', function (Blueprint $table) {
                $table->index(['user_id', 'status']);
            });
        }

        // Add indexes to chat_access table (only if they don't exist)
        if (!$this->indexExists('chat_access', 'chat_access_accessable_type_accessable_id_index')) {
            Schema::table('chat_access', function (Blueprint $table) {
                $table->index(['accessable_type', 'accessable_id']);
            });
        }
        if (!$this->indexExists('chat_access', 'chat_access_chat_id_index')) {
            Schema::table('chat_access', function (Blueprint $table) {
                $table->index('chat_id');
            });
        }

        // Add indexes to messages table (only if they don't exist)
        if (!$this->indexExists('messages', 'messages_chat_id_sent_at_index')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index(['chat_id', 'sent_at']);
            });
        }
        if (!$this->indexExists('messages', 'messages_participant_id_index')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index('participant_id');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
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
