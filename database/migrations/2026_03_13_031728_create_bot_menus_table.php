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
        Schema::create('bot_menus', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('parent_id')->nullable()->constrained('bot_menus')->onDelete('cascade');
            $blueprint->string('label'); // Teks tombol (misal: "Hubungi CS")
            $blueprint->text('message_response')->nullable(); // Pesan bot setelah klik
            $blueprint->string('action_type')->default('submenu'); // submenu, link, form, ai_ask, connect_cs
            $blueprint->string('action_value')->nullable(); // URL link atau Kategori CS
            $blueprint->integer('order_index')->default(0);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_menus');
    }
};
