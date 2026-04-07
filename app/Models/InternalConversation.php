<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalConversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne()
    {
        return $this->belongsTo(Admin::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(Admin::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(InternalMessage::class, 'internal_conversation_id');
    }

    public function otherUser($currentUserId)
    {
        return $this->user_one_id == $currentUserId ? $this->userTwo : $this->userOne;
    }

    public static function getOrCreate($userOneId, $userTwoId)
    {
        // Ensure smaller ID is always user_one_id to maintain uniqueness
        $ids = [$userOneId, $userTwoId];
        sort($ids);

        return self::firstOrCreate([
            'user_one_id' => $ids[0],
            'user_two_id' => $ids[1],
        ]);
    }
}
