<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            [
                'name' => 'Agent 1 (Supervisor)',
                'slug' => 'agent1',
                'description' => 'Atasan/Supervisor Agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agent 2 (Staff)',
                'slug' => 'agent2',
                'description' => 'Staff Agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            if (!DB::table('roles')->where('slug', $role['slug'])->exists()) {
                DB::table('roles')->insert($role);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->whereIn('slug', ['agent1', 'agent2'])->delete();
    }
};
