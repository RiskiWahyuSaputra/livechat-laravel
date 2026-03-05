<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'messages_conversation_created_idx');
            $table->index('message_type', 'messages_type_idx');
            $table->index('is_read', 'messages_is_read_idx');
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE messages ADD FULLTEXT messages_content_fulltext (content)');
            } catch (\Throwable $exception) {
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE messages DROP INDEX messages_content_fulltext');
            } catch (\Throwable $exception) {
            }
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_created_idx');
            $table->dropIndex('messages_type_idx');
            $table->dropIndex('messages_is_read_idx');
        });
    }
};
