<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'session_token',
        'name',
        'contact',
        'origin',
        'is_blocked',
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
