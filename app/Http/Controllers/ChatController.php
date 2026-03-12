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
use App\Jobs\ProcessUserMessage;

class ChatController extends Controller
{
    protected $whatsappService;
    protected $geminiService;

    public function __construct(WhatsappService $whatsappService, GeminiService $geminiService)
    {
        $this->whatsappService = $whatsappService;
        $this->geminiService = $geminiService;
    }

    /**
     * Tampilkan halaman chat user.
     */
    public function showChat(Request $request)
    {
        $token = $request->cookie('guest_chat_token');
        if (!$token) {
            return redirect()->route('user.home')->with('error', 'Silakan isi data diri terlebih dahulu.');
        }

        $user = User::where('email', $token)->first();
        if (!$user) {
            return redirect()->route('user.home');
        }

        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if (!$activeConversation) {
            $activeConversation = $this->createConversation($user);
        }

        // Ambil pesan awal agar tidak kosong saat render
        $allConversations = $user->conversations()->withTrashed()->pluck('id');
        $messages = Message::whereIn('conversation_id', $allConversations)
            ->where('message_type', '!=', 'whisper')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_type' => $msg->sender_type,
                    'message_type' => $msg->message_type,
                    'content' => $msg->content,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            });

        return view('chat.index', [
            'conversation' => $activeConversation,
            'messages' => $messages,
            'botCategories' => config('chat.complaint_categories'),
        ]);
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
        $user = User::where('contact', $contact)->orWhere('email', $contact . '@livechat.best')->first();

        if (!$user) {
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
        }

        // Set Cookie & Login
        Cookie::queue('guest_chat_token', $user->email, 60);
        Auth::guard('web')->login($user, true);

        // Pastikan conversation dan pesan otomatis dibuat
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if (!$activeConversation) {
            $activeConversation = $this->createConversation($user);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user'    => $user,
                'conversation' => $activeConversation
            ]);
        }

        return redirect()->route('chat.index');
    }

    /**
     * Logout guest user secara bersih.
     */
    public function logout(Request $request)
    {
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
            $user->is_online = false;
            $user->save();

            $conversation = $user->conversations()
                ->whereIn('status', ['pending', 'active', 'queued'])
                ->first();

            if ($conversation) {
                $conversation->update(['status' => 'closed']);
                
                $sysMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'system',
                    'message_type'    => 'text',
                    'content'         => 'Sesi berakhir karena pelanggan tidak aktif.',
                ]);

                $conversation->load('customer');

                try {
                    broadcast(new MessageSent($sysMessage));
                    broadcast(new ConversationStatusChanged($conversation, 'system'));
                } catch (\Exception $e) {
                    \Log::warning('Broadcast failed during logout: ' . $e->getMessage());
                }

                $conversation->delete();
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($request->expectsJson()) {
            return response()->json(['success' => true])
                ->withoutCookie('guest_chat_token');
        }

        return redirect()->route('user.home')->withoutCookie('guest_chat_token');
    }

    /**
     * Initialize chat widget
     */
    public function initChat(Request $request)
    {
        try {
            $token = $request->cookie('guest_chat_token');
            if (!$token) {
                return response()->json(['error' => 'Sesi tidak ditemukan atau kedaluwarsa.'], 401);
            }

            $user = User::where('email', $token)->first();
            if (!$user) {
                return response()->json(['error' => 'Pengguna tidak terautentikasi.'], 401);
            }

            if ($user->is_blocked) {
                return response()->json(['error' => 'Akun Anda telah diblokir.'], 403);
            }
            
            $user->update(['is_online' => true]);
            Auth::guard('web')->login($user, true);

            $activeConversation = $user->conversations()
                ->whereIn('status', ['pending', 'active', 'queued'])
                ->first();

            if (!$activeConversation) {
                $activeConversation = $this->createConversation($user);
            }

            $allConversations = $user->conversations()->withTrashed()->pluck('id');
            $messages = Message::whereIn('conversation_id', $allConversations)
                ->where('message_type', '!=', 'whisper')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($msg) {
                    return [
                        'id' => $msg->id,
                        'sender_id' => $msg->sender_id,
                        'sender_type' => $msg->sender_type,
                        'message_type' => $msg->message_type,
                        'content' => $msg->content,
                        'created_at' => $msg->created_at->format('H:i'),
                    ];
                });

            return response()->json([
                'csrf_token'   => csrf_token(),
                'user'         => [
                    'id'   => $user->id,
                    'name' => $user->name,
                ],
                'conversation' => $activeConversation,
                'messages'     => $messages,
                'user_id'      => $user->id,
                'status'       => $activeConversation->status,
                'bot_phase'    => $activeConversation->bot_phase,
                'botCategories' => config('chat.complaint_categories'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil data chat', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Kirim pesan baru dari user.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'content'         => ['required_without:file', 'nullable', 'string', 'max:2000'],
            'file'            => ['nullable', 'file', 'max:10240'],
            'conversation_id' => ['required'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();
        
        if (!$user) return response()->json(['error' => 'Sesi tidak valid.'], 401);
        if ($user->is_blocked) return response()->json(['error' => 'Akun diblokir.'], 403);

        $conversation = Conversation::withTrashed()->find($request->conversation_id);
        if (!$conversation) return response()->json(['error' => 'Sesi tidak ditemukan.'], 404);
        if ($conversation->user_id != $user->id) return response()->json(['error' => 'Akses ditolak.'], 403);

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

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new MessageSent($message));
            
            // Dispatch background processing (WhatsApp & Gemini)
            ProcessUserMessage::dispatch($message);

        } catch (\Exception $e) { 
            \Log::error('Broadcast/Job dispatch failed', ['error' => $e->getMessage()]); 
        }

        // Tangani Bot Response dan kumpulkan untuk dikirim di JSON
        $botReplies = [];
        if ($conversation->bot_phase && $conversation->bot_phase !== 'off') {
            $botReplies = $this->handleBotResponse($conversation, $message->content);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'           => $message->id,
                'content'      => $message->content,
                'message_type' => $message->message_type,
                'created_at'   => $message->created_at->format('H:i'),
            ],
            'bot_replies' => $botReplies,
            'bot_phase'   => $conversation->fresh()->bot_phase
        ]);
    }

    public function typing(Request $request)
    {
        $request->validate(['conversation_id' => ['required'], 'is_typing' => ['required', 'boolean']]);
        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        try {
            broadcast(new TypingIndicator(
                conversationId: $request->conversation_id,
                senderId:       $user->id,
                senderType:     'user',
                senderRole:     'user',
                senderName:     $user->name,
                isTyping:       $request->boolean('is_typing')
            ))->toOthers();
        } catch (\Exception $e) {}

        return response()->json(['success' => true]);
    }

    private function handleBotResponse($conversation, $userMessage)
    {
        $newBotMessages = [];
        $botCategories = config('chat.complaint_categories');

        // Fitur Kembali ke Menu Utama
        if (strtolower(trim($userMessage)) === 'menu') {
            $conversation->update([
                'bot_phase' => 'awaiting_category',
                'problem_category' => null
            ]);

            $categoryButtons = "";
            foreach ($botCategories as $cat) { $categoryButtons .= "- {$cat}\n"; }

            $newBotMessages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => "🔄 Kembali ke Menu Utama.\n\nSilakan pilih kembali kategori kendala Anda:\n\n" . $categoryButtons,
            ]);

            return $this->formatBotReplies($newBotMessages, $conversation);
        }

        if ($conversation->bot_phase === 'awaiting_category') {
            if (in_array($userMessage, $botCategories)) {
                $conversation->update(['problem_category' => $userMessage, 'bot_phase' => 'awaiting_explanation']);
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Baik, Anda memilih kategori {$userMessage}. Silakan jelaskan permasalahan Anda.\n\nKetik 'YA' untuk bantuan BEST AI instan, atau langsung jelaskan masalah Anda untuk admin.",
                ]);
            } else {
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Mohon pilih salah satu kategori di atas.",
                ]);
            }
        } elseif ($conversation->bot_phase === 'awaiting_explanation') {
            if (strtoupper(trim($userMessage)) === 'YA') {
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . "Silakan ajukan pertanyaan Anda mengenai {$conversation->problem_category}.",
                ]);
            } else {
                $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan {$conversation->problem_category}: ");
                $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);

                $newBotMessages[] = [
                    'content' => $aiResponse,
                    'type' => 'ai'
                ];
                $newBotMessages[] = [
                    'content' => "Pesan diterima. Antrean ke-{$queueCount}. Sambil menunggu, silakan baca jawaban AI di atas.",
                    'type' => 'system'
                ];

                // Create actual message records
                foreach ($newBotMessages as $bm) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => ($bm['type'] === 'ai' ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' : '') . $bm['content'],
                    ]);
                }
            }
        } elseif ($conversation->bot_phase === 'off' && is_null($conversation->admin_id)) {
            // Jika bot sudah OFF tapi admin belum klaim, bot tetap menjawab sebagai asisten pintar
            $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan lanjutan dari pelanggan (Admin belum bergabung): ");
            
            $newBotMessages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
            ]);
        }

        return $this->formatBotReplies($newBotMessages, $conversation);
    }

    private function formatBotReplies($messages, $conversation)
    {
        $formatted = [];
        foreach ($messages as $m) {
            $msgData = [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_type' => $m->sender_type,
                'message_type' => $m->message_type,
                'content' => $m->content,
                'created_at' => $m->created_at->format('H:i')
            ];
            $formatted[] = $msgData;
            try { broadcast(new MessageSent($m)); } catch (\Exception $e) {}
        }
        
        if ($conversation->wasChanged('bot_phase')) {
            try { broadcast(new ConversationStatusChanged($conversation, 'system')); } catch (\Exception $e) {}
        }

        return $formatted;
    }

    private function createConversation($user): Conversation
    {
        $availableAdmin = Admin::where('status', '!=', 'offline')->get()->first(fn($admin) => $admin->canTakeNewChat());
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $queuePosition = null;

        if ($anyOnline && !$availableAdmin) {
            $status = 'queued';
            $queuePosition = Conversation::where('status', 'queued')->count() + 1;
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'status' => $status,
            'bot_phase' => 'awaiting_category',
            'queue_position' => $queuePosition,
            'last_message_at'=> now(),
        ]);

        $intro = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => "Halo! Saya {$user->name} dari {$user->origin}, ingin bantuan tim Support.",
        ]);

        $botCategories = config('chat.complaint_categories');
        $categoryButtons = "";
        foreach ($botCategories as $cat) { $categoryButtons .= "- {$cat}\n"; }
        
        $botMsg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'admin',
            'message_type'    => 'text',
            'content'         => "👋 Selamat datang di BEST CORP. Pilih kategori kendala Anda:\n\n" . $categoryButtons,
        ]);

        try {
            broadcast(new MessageSent($intro))->toOthers();
            broadcast(new MessageSent($botMsg));
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        } catch (\Exception $e) {}

        return $conversation;
    }
}