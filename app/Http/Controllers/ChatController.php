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
        
        // Coba cari dari token dulu, lalu fallback ke contact
        $customer = null;
        if ($token) {
            $customer = Customer::where('session_token', $token)->first();
        }
        
        if (!$customer) {
            $customer = Customer::where('contact', $contact)->first();
        }

        if (!$customer) {
            $token = Str::random(40);
            $customer = Customer::create([
                'session_token' => $token,
                'name'      => $request->name,
                'contact'   => $contact,
                'origin'    => $request->origin,
            ]);
        } else {
            $token = $customer->session_token;
            $customer->update([
                'name'      => $request->name,
                'origin'    => $request->origin,
            ]);
        }

        // Set Cookie berlaku 7 hari
        Cookie::queue('guest_chat_token', $token, 60 * 24 * 7);

        return response()->json([
            'success' => true,
            'user'    => [
                'id'      => $customer->id,
                'name'    => $customer->name,
                'contact' => $customer->contact,
                'origin'  => $customer->origin,
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

        $customer = Customer::where('session_token', $token)->first();
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($customer->is_blocked) {
            return response()->json(['error' => 'Akun Anda telah diblokir.'], 403);
        }

        // Ambil conversation aktif user (pending/active/queued)
        // Jika ada conversation yang aktif, kita pakai itu. Jika tidak, buat baru.
        // Kita juga butuh mengirimkan histori pesan dari semua conversation Budi.
        
        $activeConversation = $customer->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if (!$activeConversation) {
            $activeConversation = $this->createConversation($customer);
        }

        // Ambil semua pesan dari semua percakapan Budi, termasuk yang soft deleted (closed)
        $allConversations = $customer->conversations()->withTrashed()->pluck('id');
        $messages = Message::whereIn('conversation_id', $allConversations)
            ->where('message_type', '!=', 'whisper')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'conversation' => $activeConversation,
            'messages'     => $messages,
            'user_id'      => $customer->id,
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
            'conversation_id' => ['required', 'exists:conversations,id'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $customer = Customer::where('session_token', $token)->first();
        
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($customer->is_blocked) {
            return response()->json(['error' => 'Diblokir'], 403);
        }

        // Conversation mungkin sudah di soft delete (closed), jadi kita pakai withTrashed untuk mengeceknya
        $conversation = Conversation::withTrashed()->find($request->conversation_id);

        if (!$conversation) {
            return response()->json(['error' => 'Chat tidak ditemukan.'], 404);
        }

        // Pastikan conversation milik user ini
        if ($conversation->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Jika conversation ini sudah ditutup (closed / soft deleted), kita butuh buat tiket baru!
        if (!$conversation->isOpen() || $conversation->trashed()) {
            $conversation = $this->createConversation($customer);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $customer->id,
            'sender_type'     => 'customer',
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
            'conversation_id' => ['required'], // Bisa juga check exists withTrashed
            'is_typing'       => ['required', 'boolean'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $customer = Customer::where('session_token', $token)->first();

        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        broadcast(new TypingIndicator(
            conversationId: $request->conversation_id,
            senderId:       $customer->id,
            senderType:     'customer',
            senderRole:     'customer',
            senderName:     $customer->name,
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
    private function createConversation($customer): Conversation
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
            'customer_id'    => $customer->id,
            'admin_id'       => null,
            'status'         => $status,
            'queue_position' => $queuePosition,
            'last_message_at'=> now(),
        ]);

        // Kirim pesan otomatis (User Intro)
        $intro = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $customer->id,
            'sender_type'     => 'customer',
            'message_type'    => 'text',
            'content'         => "Halo! Saya {$customer->name} dari {$customer->origin}, ingin terhubung dengan tim Support.",
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
