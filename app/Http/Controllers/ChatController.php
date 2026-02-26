<?php

namespace App\Http\Controllers;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman chat user.
     * Jika belum punya conversation aktif, buat baru otomatis.
     */
    public function index()
    {
        $user = Auth::user();

        // Ambil conversation aktif user (pending/active/queued)
        $conversation = $user->activeConversation()->first();

        // Jika belum ada, buat conversation baru
        if (!$conversation) {
            $conversation = $this->createConversation($user);
        }

        $messages = $conversation->publicMessages()->get();

        return view('chat.index', compact('conversation', 'messages'));
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
        $isEmail = strpos($contact, '@') !== false;

        $user = User::where('contact', $contact)
                    ->orWhere('email', $contact)
                    ->first();

        if (!$user) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $isEmail ? $contact : $contact . '@' . \Illuminate\Support\Str::random(5) . '.guest.local',
                'contact'   => $contact,
                'origin'    => $request->origin,
                'password'  => bcrypt(\Illuminate\Support\Str::random(16)),
                'is_online' => true,
            ]);
        } else {
            $user->update([
                'name'      => $request->name,
                'origin'    => $request->origin,
                'is_online' => true,
            ]);
        }

        Auth::login($user, true);

        return response()->json([
            'success' => true,
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
        $user = Auth::user();

        // Ambil conversation aktif user (pending/active/queued)
        $conversation = $user->activeConversation()->first();

        // Jika belum ada, buat conversation baru
        if (!$conversation) {
            $conversation = $this->createConversation($user);
        }

        $messages = $conversation->publicMessages()->get();

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
            'user_id'      => $user->id,
            'status'       => $conversation->status,
        ]);
    }

    /**
     * Kirim pesan baru dari user.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'content'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['required', 'exists:conversations,id'],
        ]);

        $user         = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Pastikan conversation milik user ini
        if ($conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Pastikan conversation masih terbuka
        if (!$conversation->isOpen()) {
            return response()->json(['error' => 'Chat sudah ditutup.'], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => $request->content,
        ]);

        // Update waktu pesan terakhir
        $conversation->update(['last_message_at' => now()]);

        // Broadcast pesan real-time ke semua peserta
        broadcast(new MessageSent($message))->toOthers();

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
            'conversation_id' => ['required', 'exists:conversations,id'],
            'is_typing'       => ['required', 'boolean'],
        ]);

        $user = Auth::user();

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
     *
     * Logic:
     * - Ada admin online yang bisa terima chat? → status: pending
     * - Semua admin offline? → kirim pesan otomatis offline
     * - Semua admin penuh (max_active_chats)? → status: queued + posisi antrian
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

        // Kirim pesan sistem otomatis jika diperlukan
        if ($autoMessage) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'system',
                'message_type'    => 'text',
                'content'         => $autoMessage,
            ]);
        } else {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'system',
                'message_type'    => 'text',
                'content'         => "Permintaan Anda telah diterima. Mohon tunggu sebentar sampai agen kami terhubung.",
            ]);
        }

        // Beritahu semua admin ada chat masuk
        broadcast(new ConversationStatusChanged($conversation, 'system'));

        return $conversation;
    }
}
