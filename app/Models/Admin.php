<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role_id',
        'role',
        'is_superadmin',
        'permissions',
        'status',
        'max_active_chats',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relasi: satu admin bisa pegang banyak conversation
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Ambil conversation yang sedang aktif milik admin ini
    public function activeConversations()
    {
        return $this->hasMany(Conversation::class)->where('status', 'active');
    }

    // Cek apakah admin masih bisa ambil chat baru
    public function canTakeNewChat(): bool
    {
        return $this->status !== 'offline'
            && $this->activeConversations()->count() < $this->max_active_chats;
    }

    // Role-Based Access Control logic
    public function hasPermission(string $permission): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        return is_array($this->permissions) && in_array($permission, $this->permissions);
    }
}
