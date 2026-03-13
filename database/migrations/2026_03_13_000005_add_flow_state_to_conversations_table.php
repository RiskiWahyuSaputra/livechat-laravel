<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('current_flow_id')
                  ->nullable()
                  ->after('bot_phase')
                  ->constrained('conversation_flows')
                  ->onDelete('set null');
            $table->foreignId('current_node_id')
                  ->nullable()
                  ->after('current_flow_id')
                  ->constrained('flow_nodes')
                  ->onDelete('set null');
            $table->json('flow_context')->nullable()->after('current_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['current_flow_id']);
            $table->dropForeign(['current_node_id']);
            $table->dropColumn(['current_flow_id', 'current_node_id', 'flow_context']);
        });
    }
};
