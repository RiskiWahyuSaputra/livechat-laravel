<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('conversation_flows')->onDelete('cascade');
            $table->foreignId('from_node_id')->constrained('flow_nodes')->onDelete('cascade');
            $table->foreignId('to_node_id')->constrained('flow_nodes')->onDelete('cascade');
            $table->enum('condition_type', ['always', 'user_choice', 'within_schedule', 'outside_schedule'])
                  ->default('always');
            $table->json('condition_value')->nullable(); // {"choice":"1"} or {"service":"cs_general"}
            $table->unsignedSmallInteger('priority')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_edges');
    }
};
