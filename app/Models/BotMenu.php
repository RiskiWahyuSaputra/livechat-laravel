<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotMenu extends Model
{
    protected $fillable = ['parent_id', 'label', 'message_response', 'action_type', 'action_value', 'order_index'];

    public function children()
    {
        return $this->hasMany(BotMenu::class, 'parent_id')->orderBy('order_index');
    }

    public function parent()
    {
        return $this->belongsTo(BotMenu::class, 'parent_id');
    }
}
