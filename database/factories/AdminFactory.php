<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username'        => fake()->unique()->userName(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => Hash::make('password'),
            'role'            => 'agent',
            'is_superadmin'   => false,
            'permissions'     => [],
            'status'          => 'offline',
            'max_active_chats' => 5,
        ];
    }

    /**
     * Indicate that the admin is a superadmin.
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_superadmin' => true,
            'role'          => 'super_admin',
        ]);
    }

    /**
     * Indicate that the admin has the given permissions.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $permissions,
        ]);
    }
}
