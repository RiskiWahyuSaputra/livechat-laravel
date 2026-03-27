<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_conversation_id')->constrained('admin_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('admins')->onDelete('cascade');
            $table->string('message_type')->default('text'); // text, image, file, etc.
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
    }
};
