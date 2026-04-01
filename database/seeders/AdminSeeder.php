<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        if (!Admin::where('username', 'admin')->exists()) {
            Admin::create([
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_superadmin' => true,
                'permissions' => null,
                'status' => 'offline',
                'max_active_chats' => 10,
            ]);
            $this->command->info('Super admin user created successfully!');
            $this->command->info('Email: admin@example.com, Password: password');
        } else {
            $this->command->info('Super admin user already exists, skipping...');
        }

        // Create Agent
        if (!Admin::where('username', 'agent')->exists()) {
            Admin::create([
                'username' => 'agent',
                'email' => 'agent@example.com',
                'password' => Hash::make('password'),
                'role' => 'agent',
                'is_superadmin' => false,
                'permissions' => null,
                'status' => 'offline',
                'max_active_chats' => 5,
            ]);
            $this->command->info('Agent user created successfully!');
            $this->command->info('Email: agent@example.com, Password: password');
        } else {
            $this->command->info('Agent user already exists, skipping...');
        }

        // Create Agent 1 (Supervisor)
        if (!Admin::where('username', 'agent1')->exists()) {
            $role = \App\Models\Role::where('slug', 'agent1')->first();
            Admin::create([
                'username' => 'agent1',
                'email' => 'agent1@example.com',
                'password' => Hash::make('password'),
                'role' => 'agent1',
                'role_id' => $role ? $role->id : null,
                'is_superadmin' => false,
                'permissions' => ['view_chat', 'view_history', 'manage_quick_replies', 'manage_customers'],
                'status' => 'offline',
                'max_active_chats' => 10,
            ]);
            $this->command->info('Agent 1 created successfully!');
        }

        // Create Agent 2 (Staff)
        if (!Admin::where('username', 'agent2')->exists()) {
            $role = \App\Models\Role::where('slug', 'agent2')->first();
            Admin::create([
                'username' => 'agent2',
                'email' => 'agent2@example.com',
                'password' => Hash::make('password'),
                'role' => 'agent2',
                'role_id' => $role ? $role->id : null,
                'is_superadmin' => false,
                'permissions' => ['view_chat', 'view_history'],
                'status' => 'offline',
                'max_active_chats' => 5,
            ]);
            $this->command->info('Agent 2 created successfully!');
        }
    }
}
