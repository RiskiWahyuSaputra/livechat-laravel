<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Super Admin
        Admin::create([
            'username'        => 'superadmin',
            'email'           => 'admin@livechat.com',
            'password'        => Hash::make('admin123'),
            'role'            => 'super_admin',
            'status'          => 'offline',
            'max_active_chats' => 10,
        ]);

        // Buat Agent 1
        Admin::create([
            'username'        => 'agent1',
            'email'           => 'agent1@livechat.com',
            'password'        => Hash::make('agent123'),
            'role'            => 'agent',
            'status'          => 'offline',
            'max_active_chats' => 5,
        ]);
    }
}
