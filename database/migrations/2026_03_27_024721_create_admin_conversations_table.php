<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_1_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('admin_2_id')->constrained('admins')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->unique(['admin_1_id', 'admin_2_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_conversations');
    }
};
