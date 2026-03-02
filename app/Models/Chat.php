<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'whatsapp_id',
        'name',
        'message',
        'response',
    ];
}
