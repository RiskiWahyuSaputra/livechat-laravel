<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('conversation_flows')->onDelete('cascade');
            $table->string('code');
            $table->enum('type', ['START', 'MESSAGE', 'MENU', 'INPUT', 'SWITCH_FLOW', 'FALLBACK', 'END']);
            $table->json('content')->nullable();
            $table->json('position')->nullable(); // {x, y} for visual editor
            $table->timestamps();

            $table->unique(['flow_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_nodes');
    }
};
