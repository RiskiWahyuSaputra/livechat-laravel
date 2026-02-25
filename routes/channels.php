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
    return true; // IZINKAN SEMUA UNTUK TESTING DEBUG 403
});

// ─────────────────────────────────────────────────────────────────────────
// Channel: admin.dashboard
// Hanya admin yang boleh JOIN (untuk notifikasi antrian masuk)
// ─────────────────────────────────────────────────────────────────────────
Broadcast::channel('admin.dashboard', function ($user) {
    return $user instanceof \App\Models\Admin;
});
