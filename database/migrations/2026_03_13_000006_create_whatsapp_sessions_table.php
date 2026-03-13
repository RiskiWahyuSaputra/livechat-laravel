<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('user_name')->nullable();
            $table->foreignId('current_flow_id')
                  ->nullable()
                  ->constrained('conversation_flows')
                  ->onDelete('set null');
            $table->foreignId('current_node_id')
                  ->nullable()
                  ->constrained('flow_nodes')
                  ->onDelete('set null');
            $table->json('flow_context')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
