<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->foreign('supervisor_id')->references('id')->on('admins')->nullOnDelete();
            $table->timestamps();
        });

        // Migrate existing hardcoded divisions to the new table
        DB::table('divisions')->insert([
            ['name' => 'Cyber',            'slug' => 'cyber',            'description' => null, 'supervisor_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Topup',            'slug' => 'topup',            'description' => null, 'supervisor_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Customer Service', 'slug' => 'customer_service', 'description' => null, 'supervisor_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
