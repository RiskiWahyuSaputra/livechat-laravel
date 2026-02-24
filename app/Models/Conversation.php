<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'status',
        'queue_position',
        'problem_category',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Admin (nullable — belum diklaim = null)
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // Relasi ke semua pesan dalam conversation ini
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    // Ambil hanya pesan yang bisa dilihat user (bukan whisper)
    public function publicMessages()
    {
        return $this->hasMany(Message::class)
            ->where('message_type', '!=', 'whisper')
            ->orderBy('created_at');
    }

    // Cek apakah conversation masih bisa dibalas
    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'active', 'queued']);
    }
}
