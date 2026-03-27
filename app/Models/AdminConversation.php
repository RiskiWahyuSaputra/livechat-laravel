<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminConversation extends Model
{
    protected $fillable = [
        'admin_1_id',
        'admin_2_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function admin1()
    {
        return $this->belongsTo(Admin::class, 'admin_1_id');
    }

    public function admin2()
    {
        return $this->belongsTo(Admin::class, 'admin_2_id');
    }

    public function messages()
    {
        return $this->hasMany(AdminMessage::class, 'admin_conversation_id')->orderBy('created_at');
    }
}
