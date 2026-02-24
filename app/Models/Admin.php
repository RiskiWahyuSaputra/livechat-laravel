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
        'role',
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
        ];
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
}
