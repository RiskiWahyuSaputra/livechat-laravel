<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    /**
     * Relasi ke Conversation (Many-to-Many)
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_tag');
    }
}
