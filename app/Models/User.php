<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_online',
        'is_blocked',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_online'         => 'boolean',
            'is_blocked'        => 'boolean',
        ];
    }

    // Relasi: satu user bisa punya banyak conversation
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Ambil conversation yang masih aktif/antrean
    public function activeConversation()
    {
        return $this->hasOne(Conversation::class)
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->latest();
    }
}
