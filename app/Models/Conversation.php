<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'admin_id',
        'status',
        'bot_phase',
        'queue_position',
        'problem_category',
        'last_message_at',
        'reminder_count',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    // Relasi ke User (sebagai customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
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
