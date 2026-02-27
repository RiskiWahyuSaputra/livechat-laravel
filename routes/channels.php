<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Di sini kita mendefinisikan otorisasi untuk setiap private channel.
| Fungsi callback mengembalikan true jika user/admin boleh JOIN channel.
|
*/

// Channel default Laravel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ─────────────────────────────────────────────────────────────────────────
// Channel: conversation.{conversationId}
// Siapa yang boleh: User pemilik conversation ATAU Admin yang menanganinya
// ─────────────────────────────────────────────────────────────────────────
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // 1. Cek jika dia adalah Admin
    if (auth('admin')->check()) {
        return true;
    }

    // 2. Cek jika dia adalah User pemilik percakapan
    $conversation = \App\Models\Conversation::find($conversationId);
    if ($conversation && (int)$user->id === (int)$conversation->user_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'type' => 'user'
        ];
    }

    return false;
});

// ─────────────────────────────────────────────────────────────────────────
// Channel: admin.dashboard
// Hanya admin yang boleh JOIN (untuk notifikasi antrian masuk)
// ─────────────────────────────────────────────────────────────────────────
Broadcast::channel('admin.dashboard', function ($user) {
    // Menggunakan auth('admin')->check() lebih robust untuk admin guard
    return auth('admin')->check();
});
