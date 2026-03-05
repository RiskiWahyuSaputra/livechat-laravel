<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns for search functionality
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('has_media')->default(false)->after('content');
            $table->string('media_type', 20)->nullable()->after('has_media');
            $table->json('extracted_urls')->nullable()->after('media_type');
        });

        // Add FULLTEXT index for Full-Text Search
        // Note: FULLTEXT index only works with InnoDB in MySQL 5.6+ and MyISAM
        DB::statement('ALTER TABLE messages ADD FULLTEXT INDEX idx_messages_fts (content)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FULLTEXT index
        DB::statement('DROP INDEX idx_messages_fts ON messages');

        // Drop new columns
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['has_media', 'media_type', 'extracted_urls']);
        });
    }
};
