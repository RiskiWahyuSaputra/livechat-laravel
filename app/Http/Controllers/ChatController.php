<?php

namespace App\Http\Controllers;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

use App\Models\User;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman chat user.
     * Tidak digunakan lagi secara langsung, di-handle via react/vue atau Blade initChat.
     */
    public function index()
    {
        return redirect()->route('user.home');
    }

    /**
     * Register guest user dari form landing page
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'origin'  => 'required|string|max:255',
        ]);

        $contact = $request->contact;
        $token = $request->cookie('guest_chat_token');
        
        $user = null;
        if ($token) {
            // Kita simpan token di field email (sebagai ID unik) atau biarkan token di cookie saja
            // Tapi User butuh email. Kita buat email dummy dari contact.
            $email = $contact . '@livechat.best';
            $user = User::where('email', $email)->first();
        }
        
        if (!$user) {
            $email = $contact . '@livechat.best';
            $user = User::where('email', $email)->orWhere('contact', $contact)->first();
        }

        if (!$user) {
            $token = Str::random(40);
            $user = User::create([
                'name'      => $request->name,
                'email'     => $contact . '@livechat.best',
                'contact'   => $contact,
                'origin'    => $request->origin,
                'password'  => bcrypt('guest123'),
                'is_online' => true,
            ]);
        } else {
            $user->update([
                'name'      => $request->name,
                'origin'    => $request->origin,
                'is_online' => true,
            ]);
            $token = $user->email; // Gunakan email sebagai token pengenal di cookie
        }

        // Set Cookie berlaku 7 hari
        Cookie::queue('guest_chat_token', $user->email, 60 * 24 * 7);

        // Login user secara otomatis
        Auth::guard('web')->login($user, true);

        return response()->json([
            'success' => true,
            'csrf_token' => csrf_token(),
            'user'    => [
                'id'      => $user->id,
                'name'    => $user->name,
                'contact' => $user->contact,
                'origin'  => $user->origin,
            ]
        ]);
    }

    /**
     * Initialize chat widget
     */
    public function initChat(Request $request)
    {
        $token = $request->cookie('guest_chat_token');
        if (!$token) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user = User::where('email', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->is_blocked) {
            return response()->json(['error' => 'Akun Anda telah diblokir.'], 403);
        }
        
        // Mark user online & ensure logged in
        $user->update(['is_online' => true]);
        Auth::guard('web')->login($user, true);

        // Ambil conversation aktif user (pending/active/queued)
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if (!$activeConversation) {
            $activeConversation = $this->createConversation($user);
        }

        // Ambil semua pesan dari semua percakapan Budi
        $allConversations = $user->conversations()->withTrashed()->pluck('id');
        $messages = Message::whereIn('conversation_id', $allConversations)
            ->where('message_type', '!=', 'whisper')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'csrf_token'   => csrf_token(),
            'conversation' => $activeConversation,
            'messages'     => $messages,
            'user_id'      => $user->id,
            'status'       => $activeConversation->status,
        ]);
    }

    /**
     * Kirim pesan baru dari user.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'content'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['required'], // We handle existence and trash manually
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->is_blocked) {
            return response()->json(['error' => 'Diblokir'], 403);
        }

        // Conversation mungkin sudah di soft delete (closed), jadi kita pakai withTrashed untuk mengeceknya
        $conversation = Conversation::withTrashed()->find($request->conversation_id);

        if (!$conversation) {
            return response()->json(['error' => 'Chat tidak ditemukan.'], 404);
        }

        // Pastikan conversation milik user ini (Gunakan non-strict agar aman dengan type)
        if ($conversation->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Jika conversation ini sudah ditutup (closed / soft deleted), kita butuh buat tiket baru!
        if (!$conversation->isOpen() || $conversation->trashed()) {
            $conversation = $this->createConversation($user);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => $request->content,
        ]);

        \Log::info('Message created by user', ['id' => $message->id]);

        // Update waktu pesan terakhir
        $conversation->update(['last_message_at' => now()]);

        try {
            // Broadcast pesan real-time ke semua peserta
            broadcast(new MessageSent($message));
            \Log::info('Broadcast MessageSent success');
        } catch (\Exception $e) {
            \Log::error('Broadcast MessageSent failed', ['error' => $e->getMessage()]);
            // We still return success because it's saved in DB
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'content'    => $message->content,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Broadcast typing indicator.
     */
    public function typing(Request $request)
    {
        $request->validate([
            'conversation_id' => ['required'], // Bisa juga check exists withTrashed
            'is_typing'       => ['required', 'boolean'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        broadcast(new TypingIndicator(
            conversationId: $request->conversation_id,
            senderId:       $user->id,
            senderType:     'user',
            senderRole:     'user',
            senderName:     $user->name,
            isTyping:       $request->boolean('is_typing')
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Buat conversation baru dan tentukan status awal.
     */
    private function createConversation($user): Conversation
    {
        // Cari admin yang bisa terima chat
        $availableAdmin = Admin::where('status', '!=', 'offline')
            ->get()
            ->first(fn($admin) => $admin->canTakeNewChat());

        // Cek apakah ada admin online sama sekali
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $queuePosition = null;
        $autoMessage = null;

        if (!$anyOnline) {
            // Semua admin offline
            $status      = 'pending';
            $autoMessage = 'Saat ini kami sedang offline. Pesan Anda sudah kami terima dan akan dibalas segera.';
        } elseif (!$availableAdmin) {
            // Admin online tapi semua penuh → masuk antrian
            $status        = 'queued';
            $queuePosition = Conversation::where('status', 'queued')->count() + 1;
            $autoMessage   = "Semua agen sedang sibuk. Anda berada di posisi antrian #{$queuePosition}. Harap menunggu.";
        }

        $conversation = Conversation::create([
            'user_id'        => $user->id,
            'admin_id'       => null,
            'status'         => $status,
            'queue_position' => $queuePosition,
            'last_message_at'=> now(),
        ]);

        // Kirim pesan otomatis (User Intro)
        $intro = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => "Halo! Saya {$user->name} dari {$user->origin}, ingin terhubung dengan tim Support.",
        ]);

        // Broadcast intro message
        broadcast(new MessageSent($intro))->toOthers();

        // Kirim pesan sistem otomatis jika diperlukan
        if ($autoMessage) {
            $systemMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'system',
                'message_type'    => 'text',
                'content'         => $autoMessage,
            ]);
            broadcast(new MessageSent($systemMsg))->toOthers();
        } else {
            $systemMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'system',
                'message_type'    => 'text',
                'content'         => "Permintaan Anda telah diterima. Mohon tunggu sebentar sampai agen kami terhubung.",
            ]);
            broadcast(new MessageSent($systemMsg))->toOthers();
        }

        // Beritahu semua admin ada chat masuk
        broadcast(new ConversationStatusChanged($conversation, 'system'));

        return $conversation;
    }
}
