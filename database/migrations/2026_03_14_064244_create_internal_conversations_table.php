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
        Schema::create('internal_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_one_id'); // Admin 1
            $table->unsignedBigInteger('user_two_id'); // Admin 2
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('user_one_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('user_two_id')->references('id')->on('admins')->onDelete('cascade');
            
            // Unique pair to ensure only one conversation per pair
            $table->unique(['user_one_id', 'user_two_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_conversations');
    }
};
