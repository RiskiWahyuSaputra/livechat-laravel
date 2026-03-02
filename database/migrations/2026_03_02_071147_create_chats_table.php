<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up() {
    Schema::create('chats', function (Blueprint $table) {
        $table->id();
        $table->string('whatsapp_id'); // ID Pengirim
        $table->string('name')->nullable(); // Nama Pengirim
        $table->text('message'); // Isi Pesan
        $table->text('response')->nullable(); // Jawaban AI
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
