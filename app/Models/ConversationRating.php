<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationRating extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'admin_id',
        'rating',
        'comment',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
