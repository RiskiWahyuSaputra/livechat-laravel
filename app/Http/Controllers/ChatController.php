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
use App\Services\WhatsappService;
use App\Services\GeminiService;

class ChatController extends Controller
{
    const BOT_CATEGORIES = ['Pendaftaran & Aktivasi', 'Dukungan Teknis', 'Masalah Pembayaran', 'Komplain / Keluhan', 'Lain-lain'];

    protected $whatsappService;
    protected $geminiService;

    public function __construct(WhatsappService $whatsappService, GeminiService $geminiService)
    {
        $this->whatsappService = $whatsappService;
        $this->geminiService = $geminiService;
    }

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

        // Set Cookie di server lebih lama (60 menit) agar tidak 401 saat user sedang aktif
        // Tapi Alpine tetap akan logout otomatis dalam 30 menit jika user diam.
        Cookie::queue('guest_chat_token', $user->email, 60);

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
     * Logout guest user secara bersih.
     */
    public function logout(Request $request)
    {
        // Ambil token dari cookie atau user_id dari request body
        $token = $request->cookie('guest_chat_token');
        $userId = $request->input('user_id');
        
        $user = null;
        if ($token) {
            $user = User::where('email', $token)->first();
        }
        
        if (!$user && $userId) {
            $user = User::find($userId);
        }

        if ($user) {
            // 1. Mark user offline di DB
            $user->is_online = false;
            $user->save();

            // 2. Cari percakapan yang masih terbuka
            $conversation = $user->conversations()
                ->whereIn('status', ['pending', 'active', 'queued'])
                ->first();

            if ($conversation) {
                // 3. Update status ke closed
                $conversation->update(['status' => 'closed']);
                
                // 4. Kirim pesan sistem penutup DULU agar tersimpan di DB
                $sysMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'system',
                    'message_type'    => 'text',
                    'content'         => 'Sesi berakhir karena pelanggan tidak aktif.',
                ]);

                // Muat ulang relasi customer agar status is_online terbaru terbawa
                $conversation->load('customer');

                // 5. Broadcast pesan ke iframe (MessageSent) dan status ke parent (ConversationStatusChanged)
                broadcast(new MessageSent($sysMessage));
                broadcast(new ConversationStatusChanged($conversation, 'system'));

                // 6. Soft delete dilakukan terakhir
                $conversation->delete();
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['success' => true])
            ->withoutCookie('guest_chat_token');
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
            'content'         => ['required_without:file', 'nullable', 'string', 'max:2000'],
            'file'            => ['nullable', 'file', 'max:10240'], // Max 10MB
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

        $messageType = 'text';
        $content = $request->content;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $messageType = str_starts_with($mime, 'image/') ? 'image' : 'file';
            
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/chat', $fileName, 'public');
            $content = asset('storage/' . $path);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => $messageType,
            'content'         => $content ?? '',
        ]);

        \Log::info('Message created by user', ['id' => $message->id]);

        // Update waktu pesan terakhir
        $conversation->update(['last_message_at' => now()]);

        try {
            // Broadcast pesan real-time ke semua peserta
            broadcast(new MessageSent($message));
            \Log::info('Broadcast MessageSent success');

            // --- WHAPI NOTIFICATION START ---
            // Beri tahu admin via WhatsApp jika bot sedang off (berarti ini chat ke manusia)
            if (!$conversation->bot_phase || $conversation->bot_phase === 'off') {
                $adminText = "💬 Pesan baru dari web!\nDari: {$user->name} ({$user->origin})\nIsi: " . ($messageType === 'text' ? $message->content : "[Media]");
                $this->whatsappService->notifyAdmin($adminText);
                
                if ($messageType !== 'text') {
                    $this->whatsappService->sendMedia(env('WHAPI_ADMIN_NUMBER'), $message->content, "Media dari {$user->name}", $messageType);
                }

                // --- AUTO AI REPLY IF NO ADMIN CLAIMED ---
                if (!$conversation->admin_id && $messageType === 'text') {
                    $aiAutoResponse = $this->geminiService->askGemini($message->content, "Berikan jawaban singkat atas pertanyaan berikut dari pelanggan web:");
                    
                    $aiMessage = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => "🤖 **BEST AI Auto-Reply:** " . $aiAutoResponse,
                    ]);
                    broadcast(new MessageSent($aiMessage));
                }
            }
            // --- WHAPI NOTIFICATION END ---

        } catch (\Exception $e) {
            \Log::error('Broadcast/Whapi MessageSent failed', ['error' => $e->getMessage()]);
            // We still return success because it's saved in DB
        }

        // --- BOT LOGIC START ---
        if ($conversation->bot_phase && $conversation->bot_phase !== 'off') {
            $this->handleBotResponse($conversation, $message->content);
        }
        // --- BOT LOGIC END ---

        return response()->json([
            'success' => true,
            'message' => [
                'id'           => $message->id,
                'content'      => $message->content,
                'message_type' => $message->message_type,
                'created_at'   => $message->created_at->format('H:i'),
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
     * Tangani respon bot berdasarkan fase percakapan.
     */
    private function handleBotResponse($conversation, $userMessage)
    {
        if ($conversation->bot_phase === 'awaiting_category') {
            // Cek apakah pesan user adalah salah satu kategori
            if (in_array($userMessage, self::BOT_CATEGORIES)) {
                $conversation->update([
                    'problem_category' => $userMessage,
                    'bot_phase' => 'awaiting_explanation'
                ]);

                $botMsg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Baik, Anda memilih kategori {$userMessage}. Silakan jelaskan permasalahan atau pertanyaan Anda secara singkat agar kami dapat membantu lebih cepat.",
                ]);
                broadcast(new MessageSent($botMsg));
            } else {
                // Jika tidak valid, minta pilih lagi
                $botMsg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Mohon pilih salah satu kategori yang tersedia di atas (klik pada pilihan kategori).",
                ]);
                broadcast(new MessageSent($botMsg));
            }
        } elseif ($conversation->bot_phase === 'awaiting_explanation') {
            // Dapatkan jawaban AI untuk membantu user sementara
            $aiResponse = $this->geminiService->askGemini($userMessage, "Pelanggan bertanya tentang {$conversation->problem_category}: ");

            // Hitung posisi antrian
            $queueCount = Conversation::whereIn('status', ['pending', 'queued'])
                ->whereNull('admin_id')
                ->where('id', '<=', $conversation->id)
                ->count();

            $conversation->update([
                'bot_phase' => 'off',
                'queue_position' => $queueCount
            ]);

            // Kirim jawaban AI
            $logoUrl = asset('images/best-logo-1.png');
            $botMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => "🤖 **BEST AI Helpdesk:** " . $aiResponse,
            ]);
            broadcast(new MessageSent($botMsg));

            // Pesan info antrian
            $queueMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => "Pesan Anda sudah kami terima. Sambil menunggu agen kami (Antrean ke-{$queueCount}), silakan baca jawaban AI di atas.",
            ]);
            broadcast(new MessageSent($queueMsg));

            // Jika admin online, beri tahu ada chat masuk
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        }
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
            'bot_phase'      => 'awaiting_category',
            'queue_position' => $queuePosition,
            'last_message_at'=> now(),
        ]);

        // Kirim pesan otomatis (User Intro)
        $intro = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => "Halo! Saya {$user->name} dari {$user->origin}, ingin bantuan tim Support.",
        ]);

        // Broadcast intro message
        broadcast(new MessageSent($intro))->toOthers();

        // Bot Welcome Message with Categories
        $categories = self::BOT_CATEGORIES;
        $categoryButtons = "";
        foreach ($categories as $cat) {
            $categoryButtons .= "- {$cat}\n";
        }
        
        $botMsg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'admin',
            'message_type'    => 'text',
            'content'         => "👋 Selamat datang di layanan bantuan BEST CORP. Silakan pilih kategori kendala yang ingin Anda tanyakan:\n\n" . $categoryButtons,
        ]);
        broadcast(new MessageSent($botMsg));

        // Broadcast status change untuk admin dashboard
        broadcast(new ConversationStatusChanged($conversation, 'system'));

        return $conversation;
    }
}
