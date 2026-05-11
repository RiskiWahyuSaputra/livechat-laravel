<?php

namespace App\Http\Controllers;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;
use App\Events\TypingIndicator;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\Message;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use App\Models\User;
use App\Services\ConversationFlowService;
use App\Services\GeminiService;

class ChatController extends Controller
{
    protected $geminiService;
    protected $conversationFlowService;

    public function __construct(GeminiService $geminiService, ConversationFlowService $conversationFlowService)
    {
        $this->geminiService = $geminiService;
        $this->conversationFlowService = $conversationFlowService;
    }

    /**
     * Tampilkan widget chat untuk di-embed di website lain.
     */
    public function showWidget(Request $request)
    {
        $token = $request->cookie('guest_chat_token');
        $isAuthenticated = false;
        
        if ($token) {
            $user = User::where('email', $token)->first();
            if ($user) {
                $isAuthenticated = true;
                Auth::guard('web')->login($user, true);
            }
        }

        return response()
            ->view('chat.widget', ['isAuthenticated' => $isAuthenticated])
            ->header('X-Frame-Options', 'ALLOWALL') // or remove it
            ->header('Content-Security-Policy', "frame-ancestors *");
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

        Auth::guard('web')->login($user, true);

        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        $pendingFeedbackConversation = $this->findPendingFeedbackConversation($user);

        if (!$activeConversation && $pendingFeedbackConversation) {
            $activeConversation = $pendingFeedbackConversation;
        }

        if (!$activeConversation && !$pendingFeedbackConversation) {
            $result = $this->conversationFlowService->createConversation($user);
            if ($result['rejected'] ?? false) {
                return view('chat.index', [
                    'conversation'  => null,
                    'messages'      => collect(),
                    'botCategories' => config('chat.complaint_categories'),
                    'rejected'      => true,
                    'reject_message' => $result['reject_message'],
                ]);
            }
            $activeConversation = $result['conversation'];
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
            'feedbackPending' => $this->conversationRequiresFeedback($activeConversation),
        ]);
    }

    /**
     * Tampilkan form registrasi khusus WhatsApp
     */
    public function showWhatsappRegister($token)
    {
        $user = User::where('registration_token', $token)->firstOrFail();
        return view('chat.whatsapp_register', ['token' => $token, 'user' => $user]);
    }

    /**
     * Simpan data registrasi WhatsApp dan hubungkan ke Agent
     */
    public function submitWhatsappRegister(Request $request)
    {
        $request->validate([
            'token'  => 'required|string',
            'name'   => 'required|string|max:255',
            'origin' => 'required|string|max:255',
        ]);

        $user = User::where('registration_token', $request->token)->firstOrFail();
        
        $user->update([
            'name'   => $request->name,
            'origin' => $request->origin,
            'registration_token' => null, // Clear token after use
        ]);

        // Cari percakapan aktif
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if ($activeConversation) {
            $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $activeConversation->id)->count();
            
            $activeConversation->update([
                'bot_phase' => 'off',
                'queue_position' => $queueCount
            ]);

            $msg = Message::create([
                'conversation_id' => $activeConversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => "Terima kasih, data Anda sudah kami terima. Anda sekarang ada di antrean ke-{$queueCount} untuk terhubung dengan Agent.",
            ]);

            // Kirim notifikasi konfirmasi ke WhatsApp juga
            $whatsappService = app(\App\Services\OpenClawWhatsappService::class);
            $whatsappService->sendText($user, "Terima kasih {$user->name}, data Anda sudah dikonfirmasi. Tunggu sebentar ya, saya sedang menyambungkan Anda ke Agent.");

            try {
                broadcast(new MessageSent($msg));
                broadcast(new ConversationStatusChanged($activeConversation, 'system'));
            } catch (\Exception $e) {}
        }

        return view('chat.whatsapp_register_success');
    }

    /**
     * Register guest user dari form landing page
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'contact'         => 'required|string|max:255',
            'origin'          => 'required|string|max:255',
            'selected_option' => 'nullable', // Boleh string (legacy) atau ID (numeric)
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
        Cookie::queue('guest_chat_token', $user->email, 35);
        Auth::guard('web')->login($user, true);

        // Pastikan conversation dan pesan otomatis dibuat
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        // Cek mode closed — tolak meski ada conversation lama (kecuali sudah active dengan agent)
        $systemMode = $this->conversationFlowService->getSystemMode();
        if ($systemMode === 'closed') {
            $hasActiveWithAgent = $activeConversation && $activeConversation->status === 'active' && $activeConversation->admin_id;
            if (!$hasActiveWithAgent) {
                $defaultMsg = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
                $rejectMsg = \App\Models\Setting::get('bot_greeting_closed', $defaultMsg) ?? $defaultMsg;
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'rejected' => true, 'reject_message' => $rejectMsg], 503);
                }
                return redirect()->route('user.home')->with('error', $rejectMsg);
            }
        }

        if (!$activeConversation) {
            $result = $this->conversationFlowService->createConversation($user, $request->selected_option);
            if ($result['rejected'] ?? false) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success'        => false,
                        'rejected'       => true,
                        'reject_message' => $result['reject_message'],
                    ], 503);
                }
                return redirect()->route('user.home')->with('error', $result['reject_message']);
            }
            $activeConversation = $result['conversation'];
        }

        if ($request->expectsJson()) {
            // Get submenus if the phase is awaiting_submenu
            $submenus = [];
            if ($activeConversation->bot_phase === 'awaiting_submenu') {
                $menu = \App\Models\BotMenu::find($request->selected_option);
                if ($menu) {
                    $submenus = $menu->children->map(fn($m) => ['id' => $m->id, 'label' => $m->label]);
                }
            }

            return response()->json([
                'success' => true,
                'user'    => $user,
                'conversation' => $activeConversation,
                'bot_phase' => $activeConversation->bot_phase,
                'bot_submenus' => $submenus
            ]);
        }

        return redirect()->route('chat.index');
    }

    /**
     * Register Anonymous User to talk with bot first
     */
    public function registerAnonymous(Request $request)
    {
        $guestId = 'anon_' . Str::random(10);
        $user = User::create([
            'name'      => 'Guest',
            'email'     => $guestId . '@livechat.best',
            'contact'   => $guestId,
            'origin'    => 'Unknown',
            'password'  => bcrypt('guest123'),
            'is_online' => true,
        ]);

        // Set Cookie & Login
        Cookie::queue('guest_chat_token', $user->email, 35);
        Auth::guard('web')->login($user, true);

        // Pastikan conversation otomatis dibuat
        $result = $this->conversationFlowService->createConversation($user, $request->selected_option);
        if ($result['rejected'] ?? false) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'        => false,
                    'rejected'       => true,
                    'reject_message' => $result['reject_message'],
                ], 503);
            }
            return redirect()->route('user.home')->with('error', $result['reject_message']);
        }
        $activeConversation = $result['conversation'];
        
        // Atur agar langsung ke fase bot 'awaiting_submenu' atau sesuai menu yang dipencet
        if ($request->selected_option) {
             $menu = \App\Models\BotMenu::find($request->selected_option);
             if ($menu && $menu->action_type === 'submenu') {
                  $activeConversation->update(['bot_phase' => 'awaiting_submenu']);
                  $activeConversation->refresh();
             }
        }

        if ($request->expectsJson()) {
            $submenus = [];
            if ($activeConversation->bot_phase === 'awaiting_submenu') {
                $menu = \App\Models\BotMenu::find($request->selected_option);
                if ($menu) {
                    $submenus = $menu->children->map(fn($m) => ['id' => $m->id, 'label' => $m->label]);
                }
            }

            return response()->json([
                'success' => true,
                'user'    => $user,
                'conversation' => $activeConversation,
                'bot_phase' => $activeConversation->bot_phase,
                'bot_submenus' => $submenus
            ]);
        }

        return redirect()->route('chat.index');
    }

    /**
     * Memperbarui profil ketika Form User diisi (setelah bot mengarahkan ke agent)
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'contact'         => 'required|string|max:255',
            'origin'          => 'required|string|max:255',
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user) {
             return response()->json(['success' => false, 'message' => 'Sesi tidak valid.'], 401);
        }

        $user->update([
            'name'      => $request->name,
            'contact'   => $request->contact,
            'origin'    => $request->origin,
        ]);

        // Ganti bot_phase ke antrean
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if ($activeConversation) {
             // Hitung posisi antrian berdasarkan waktu (FIFO), bukan ID
             $queueCount = Conversation::whereIn('status', ['pending', 'queued'])
                 ->whereNull('admin_id')
                 ->where('created_at', '<=', $activeConversation->created_at)
                 ->count();
             $activeConversation->update([
                 'bot_phase' => 'off',
                 'queue_position' => $queueCount
             ]);
             
             Message::create([
                 'conversation_id' => $activeConversation->id,
                 'sender_id'       => 0,
                 'sender_type'     => 'admin',
                 'message_type'    => 'text',
                 'content'         => "Terima kasih, datanya sudah dikonfirmasi. Kamu sekarang ada di antrean ke-{$queueCount} untuk terhubung dengan Agent.",
             ]);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                 'id' => $user->id,
                 'name' => $user->name,
            ],
            'bot_phase' => 'off',
        ]);
    }

    /**
     * Logout guest user secara bersih.
     */
    public function logout(Request $request)
    {
        $token = $request->cookie('guest_chat_token');
        $userId = $request->input('user_id');
        
        $user = null;
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        } elseif ($token) {
            $user = User::where('email', $token)->first();
        } elseif ($userId) {
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
            $publicData = [
                'csrf_token'   => csrf_token(),
                'chat_greeting' => \App\Models\Setting::get('bot_greeting_message', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?'),
                'chat_main_menu' => \App\Models\BotMenu::with('children')->whereNull('parent_id')->orderBy('order_index')->get()->map(fn($m) => [
                    'id' => $m->id,
                    'label' => $m->label,
                    'action_type' => $m->action_type,
                    'action_value' => $m->action_value,
                    'message_response' => $m->message_response,
                    'submenus' => $m->children->map(fn($c) => [
                        'id' => $c->id,
                        'label' => $c->label,
                        'action_type' => $c->action_type,
                        'action_value' => $c->action_value,
                        'message_response' => $c->message_response
                    ])
                ]),
            ];

            if (!$token) {
                // Cek mode closed bahkan untuk user yang belum login
                $systemMode = $this->conversationFlowService->getSystemMode();
                if ($systemMode === 'closed') {
                    $defaultMsg = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
                    return response()->json(array_merge($publicData, [
                        'rejected'       => true,
                        'reject_message' => \App\Models\Setting::get('bot_greeting_closed', $defaultMsg) ?? $defaultMsg,
                    ]));
                }
                return response()->json($publicData);
            }

            $user = User::where('email', $token)->first();
            if (!$user) {
                $systemMode = $this->conversationFlowService->getSystemMode();
                if ($systemMode === 'closed') {
                    $defaultMsg = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
                    return response()->json(array_merge($publicData, [
                        'rejected'       => true,
                        'reject_message' => \App\Models\Setting::get('bot_greeting_closed', $defaultMsg) ?? $defaultMsg,
                    ]));
                }
                return response()->json($publicData);
            }

            if ($user->is_blocked) {
                return response()->json(['error' => 'Akun Anda telah diblokir.'], 403);
            }
            
            $user->update(['is_online' => true]);
            Auth::guard('web')->login($user, true);

            $activeConversation = $user->conversations()
                ->whereIn('status', ['pending', 'active', 'queued'])
                ->first();

            $pendingFeedbackConversation = $this->findPendingFeedbackConversation($user);

            if (!$activeConversation && $pendingFeedbackConversation) {
                $activeConversation = $pendingFeedbackConversation;
            }

            // Jika mode closed, tolak kecuali conversation sudah active dengan agent
            $systemMode = $this->conversationFlowService->getSystemMode();
            if ($systemMode === 'closed') {
                $hasActiveWithAgent = $activeConversation && $activeConversation->status === 'active' && $activeConversation->admin_id;
                if (!$hasActiveWithAgent) {
                    $defaultMsg = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
                    return response()->json(array_merge($publicData, [
                        'rejected'       => true,
                        'reject_message' => \App\Models\Setting::get('bot_greeting_closed', $defaultMsg) ?? $defaultMsg,
                    ]));
                }
            }

            if (!$activeConversation && !$pendingFeedbackConversation) {
                $result = $this->conversationFlowService->createConversation($user);
                if ($result['rejected'] ?? false) {
                    return response()->json(array_merge($publicData, [
                        'rejected'       => true,
                        'reject_message' => $result['reject_message'],
                    ]));
                }
                $activeConversation = $result['conversation'];
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

            return response()->json(array_merge($publicData, [
                'user'         => [
                    'id'   => $user->id,
                    'name' => $user->name,
                ],
                'conversation' => $activeConversation,
                'messages'     => $messages,
                'user_id'      => $user->id,
                'status'       => $activeConversation->status,
                'bot_phase'    => $activeConversation->bot_phase,
                'feedback_pending' => $this->conversationRequiresFeedback($activeConversation),
                'feedback_status' => $activeConversation->feedback_status,
                'botCategories' => config('chat.complaint_categories'),
                'bot_submenus' => ($activeConversation->bot_phase === 'awaiting_submenu') 
                    ? \App\Models\BotMenu::whereNotNull('parent_id')->orderBy('order_index')->get()->map(fn($m) => ['id' => $m->id, 'label' => $m->label, 'parent_id' => $m->parent_id])
                    : []
            ]));
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
            $result = $this->conversationFlowService->createConversation($user);
            if ($result['rejected'] ?? false) {
                return response()->json([
                    'success'        => false,
                    'rejected'       => true,
                    'reject_message' => $result['reject_message'],
                ], 503);
            }
            $conversation = $result['conversation'];
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

        $result = $this->conversationFlowService->processInboundMessage(
            user: $user,
            conversation: $conversation,
            content: $content ?? '',
            messageType: $messageType,
            broadcast: true
        );

        if ($result['rejected'] ?? false) {
            return response()->json([
                'success'        => false,
                'rejected'       => true,
                'reject_message' => $result['reject_message'],
            ], 503);
        }

        $message = $result['message'];
        $botReplies = $result['bot_replies'];
        $botSubmenus = $result['submenus'] ?? [];

        return response()->json([
            'success' => true,
            'message' => [
                'id'           => $message->id,
                'content'      => $message->content,
                'message_type' => $message->message_type,
                'created_at'   => $message->created_at->format('H:i'),
            ],
            'bot_replies' => $botReplies,
            'bot_phase'   => $result['bot_phase'],
            'bot_submenus' => $botSubmenus,
        ]);
    }

    /**
     * Update pesan yang dikirim oleh user (jika diperbolehkan).
     */
    public function updateMessage(Request $request, Message $message)
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user || $message->sender_id != $user->id || $message->sender_type !== 'user') {
            Log::warning('Edit gagal: Akses ditolak.', [
                'user_id' => $user->id ?? 'null',
                'msg_sender_id' => $message->sender_id,
                'msg_sender_type' => $message->sender_type,
                'cookie_token' => $token ?? 'null'
            ]);
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $message->update(['content' => $request->content]);

        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id'      => $message->id,
                'content' => $message->content,
            ]
        ]);
    }

    /**
     * Hapus pesan yang dikirim oleh user.
     */
    public function deleteMessage(Request $request, Message $message)
    {
        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user || $message->sender_id != $user->id || $message->sender_type !== 'user') {
            Log::warning('Hapus gagal: Akses ditolak.', [
                'user_id' => $user->id ?? 'null',
                'msg_sender_id' => $message->sender_id,
                'msg_sender_type' => $message->sender_type,
                'cookie_token' => $token ?? 'null'
            ]);
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $messageId = $message->id;
        $conversationId = $message->conversation_id;

        $message->delete();

        broadcast(new MessageDeleted($messageId, $conversationId))->toOthers();

        return response()->json(['success' => true]);
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

    public function submitFeedback(Request $request, $conversationId)
    {
        $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Sesi tidak valid.'], 401);
        }

        $conversation = Conversation::withTrashed()->findOrFail($conversationId);

        if ($conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        if (!$this->conversationRequiresFeedback($conversation)) {
            return response()->json(['error' => 'Feedback untuk chat ini sudah tidak tersedia.'], 422);
        }

        ConversationRating::updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'user_id' => $user->id,
                'admin_id' => $conversation->admin_id,
                'rating' => (int) $request->rating,
                'comment' => trim((string) $request->comment) ?: null,
            ]
        );

        $conversation->update([
            'feedback_status' => 'submitted',
        ]);

        return response()->json(['success' => true]);
    }

    public function skipFeedback(Request $request, $conversationId)
    {
        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Sesi tidak valid.'], 401);
        }

        $conversation = Conversation::withTrashed()->findOrFail($conversationId);

        if ($conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        if ($conversation->feedback_status === 'pending') {
            $conversation->update([
                'feedback_status' => 'skipped',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function conversationSummary(Request $request)
    {
        $token = $request->cookie('guest_chat_token');
        $user = User::where('email', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Sesi tidak valid.'], 401);
        }

        $latestConversation = $user->conversations()
            ->withTrashed()
            ->latest('updated_at')
            ->first();

        if ($latestConversation && $latestConversation->status !== 'closed') {
            // Allow summary for active conversations too — just use the latest conversation
        }

        $summarySource = $this->buildConversationSummarySource(
            $this->getVisibleMessagesForSummary($user)
        );

        if (!$summarySource['available']) {
            return response()->json([
                'available' => false,
                'message' => $summarySource['message'],
                'message_count' => $summarySource['message_count'],
            ]);
        }

        $cacheKey = 'chat_user_conversation_summary_' . $user->id . '_' . $summarySource['history_hash'];
        $summary = Cache::get($cacheKey);

        if (!is_array($summary)) {
            $summary = $this->geminiService->summarizeConversationForCustomer($summarySource['history']);

            if (is_array($summary)) {
                Cache::put($cacheKey, $summary, now()->addMinutes(30));
            }
        }

        if (!is_array($summary)) {
            $summary = $this->buildDeterministicConversationSummary($summarySource['lines']);

            if (is_array($summary)) {
                Cache::put($cacheKey, $summary, now()->addMinutes(10));
            }
        }

        if (!is_array($summary)) {
            return response()->json([
                'available' => false,
                'message' => 'Ringkasan AI belum tersedia saat ini. Silakan coba lagi sebentar lagi.',
                'message_count' => $summarySource['message_count'],
                'history_hash' => $summarySource['history_hash'],
            ]);
        }

        return response()->json([
            'available' => true,
            'summary' => $summary['summary'],
            'sentiment' => $summary['sentiment'],
            'message_count' => $summarySource['message_count'],
            'history_hash' => $summarySource['history_hash'],
            'updated_at' => now()->format('H:i'),
        ]);
    }

    private function findPendingFeedbackConversation(User $user): ?Conversation
    {
        return $user->conversations()
            ->withTrashed()
            ->where('status', 'closed')
            ->where('feedback_status', 'pending')
            ->whereNotNull('admin_id')
            ->latest('feedback_requested_at')
            ->latest('updated_at')
            ->first();
    }

    private function conversationRequiresFeedback(?Conversation $conversation): bool
    {
        return $conversation instanceof Conversation
            && $conversation->hasPendingFeedback();
    }

    private function getVisibleMessagesForSummary(User $user): Collection
    {
        $conversationIds = $user->conversations()->withTrashed()->pluck('id');

        return Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('message_type', '!=', 'whisper')
            ->latest('created_at')
            ->limit(80)
            ->get();
    }

    private function buildConversationSummarySource(Collection $messages): array
    {
        $lines = $messages
            ->sortBy('created_at')
            ->map(fn (Message $message) => $this->normalizeMessageForConversationSummary($message))
            ->filter(fn ($line) => is_string($line) && trim($line) !== '')
            ->values();

        $messageCount = $lines->count();
        $userLines = $lines->filter(fn (string $line) => str_starts_with($line, 'Customer:'))->count();
        $supportLines = $lines->filter(fn (string $line) => str_starts_with($line, 'BEST AI:') || str_starts_with($line, 'Agent:'))->count();

        if ($messageCount < 4 || $userLines === 0 || $supportLines === 0) {
            return [
                'available' => false,
                'message' => 'Ringkasan AI akan muncul setelah percakapan punya konteks yang cukup.',
                'message_count' => $messageCount,
                'history' => '',
                'history_hash' => null,
                'lines' => [],
            ];
        }

        $historyLines = $lines->slice(-40)->values();
        $history = $historyLines->implode("\n");

        return [
            'available' => true,
            'message' => null,
            'message_count' => $messageCount,
            'history' => $history,
            'history_hash' => sha1($history),
            'lines' => $historyLines->all(),
        ];
    }

    private function normalizeMessageForConversationSummary(Message $message): ?string
    {
        if ($message->sender_type === 'system') {
            return null;
        }

        $sender = match ($message->sender_type) {
            'user' => 'Customer',
            'admin' => (int) $message->sender_id === 0 ? 'BEST AI' : 'Agent',
            default => 'Support',
        };

        $messageType = $message->message_type ?: 'text';

        if ($messageType === 'image') {
            $content = Str::startsWith((string) $message->content, 'whatsapp-media-placeholder:')
                ? 'Mengirim gambar dari WhatsApp.'
                : 'Mengirim gambar' . $this->conversationSummaryFileSuffix($message->content) . '.';
        } elseif ($messageType === 'file') {
            $content = Str::startsWith((string) $message->content, 'whatsapp-media-placeholder:')
                ? 'Mengirim file dari WhatsApp.'
                : 'Mengirim file' . $this->conversationSummaryFileSuffix($message->content) . '.';
        } else {
            $content = preg_replace('/<img\b[^>]*alt=["\']([^"\']+)["\'][^>]*>/i', ' [Gambar produk: $1] ', (string) $message->content);
            $content = preg_replace('/<img\b[^>]*>/i', ' [Gambar produk] ', (string) $content);
            $content = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $content = preg_replace('/\s+/u', ' ', (string) $content);
            $content = trim((string) $content);
        }

        if ($content === '') {
            return null;
        }

        return $sender . ': ' . $content;
    }

    private function conversationSummaryFileSuffix(?string $content): string
    {
        $path = parse_url((string) $content, PHP_URL_PATH);
        $filename = is_string($path) ? basename($path) : '';

        return $filename !== '' ? ' (' . $filename . ')' : '';
    }

    private function buildDeterministicConversationSummary(array $lines): ?array
    {
        $userMessages = collect($lines)
            ->filter(fn ($line) => is_string($line) && str_starts_with($line, 'Customer:'))
            ->map(fn (string $line) => trim(substr($line, strlen('Customer:'))))
            ->filter(fn (string $line) => $this->isMeaningfulSummaryLine($line))
            ->values();

        $supportMessages = collect($lines)
            ->filter(fn ($line) => is_string($line) && (str_starts_with($line, 'BEST AI:') || str_starts_with($line, 'Agent:')))
            ->map(function (string $line) {
                $line = preg_replace('/^(BEST AI|Agent):\s*/', '', $line);

                return trim((string) $line);
            })
            ->filter(fn (string $line) => $this->isMeaningfulSummaryLine($line))
            ->values();

        if ($userMessages->isEmpty() || $supportMessages->isEmpty()) {
            return null;
        }

        $userTopic = $this->limitSummaryText(
            $userMessages
                ->unique()
                ->take(2)
                ->implode(' ')
        );

        $supportResponse = $this->limitSummaryText(
            $supportMessages
                ->reverse()
                ->unique()
                ->take(2)
                ->reverse()
                ->implode(' ')
        );

        $summaryParts = [];
        if ($userTopic !== '') {
            $summaryParts[] = 'Pelanggan membahas ' . $this->lcfirstSafe($userTopic) . '.';
        }
        if ($supportResponse !== '') {
            $summaryParts[] = 'Support menanggapi dengan ' . $this->lcfirstSafe($supportResponse) . '.';
        }

        $latestSupport = trim((string) $supportMessages->last());
        if ($latestSupport !== '' && $latestSupport !== $supportResponse) {
            $summaryParts[] = 'Status terakhir: ' . $this->lcfirstSafe($this->limitSummaryText($latestSupport, 160)) . '.';
        }

        $summaryText = trim(implode(' ', $summaryParts));
        if ($summaryText === '') {
            return null;
        }

        return [
            'summary' => $summaryText,
            'sentiment' => $this->detectDeterministicSentiment($userMessages, $supportMessages),
        ];
    }

    private function detectDeterministicSentiment(Collection $userMessages, Collection $supportMessages): string
    {
        $allText = Str::lower($userMessages->implode(' ') . ' ' . $supportMessages->implode(' '));

        $negativeSignals = [
            'kendala',
            'gagal',
            'error',
            'tidak bisa',
            'belum bisa',
            'komplain',
            'masalah',
            'bingung',
            'maaf, sistem best ai lagi mengalami kendala',
        ];

        foreach ($negativeSignals as $signal) {
            if (str_contains($allText, $signal)) {
                return 'Negative';
            }
        }

        $positiveSignals = [
            'terima kasih',
            'sudah jelas',
            'cukup membantu',
            'sudah paham',
            'baik',
            'siap',
            'berhasil',
            'sudah bisa',
        ];

        foreach ($positiveSignals as $signal) {
            if (str_contains($allText, $signal)) {
                return 'Positive';
            }
        }

        return 'Neutral';
    }

    private function isMeaningfulSummaryLine(string $line): bool
    {
        $normalized = Str::lower(trim($line));

        if ($normalized === '') {
            return false;
        }

        $ignoredLines = [
            'agent',
            'hubungi agent',
            'tanya lagi',
            'lanjut',
            'menu',
            'menu utama',
        ];

        return !in_array($normalized, $ignoredLines, true);
    }

    private function limitSummaryText(string $text, int $maxLength = 220): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return '';
        }

        return Str::limit($text, $maxLength, '...');
    }

    private function lcfirstSafe(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $first = Str::substr($text, 0, 1);
        $second = Str::substr($text, 1, 1);

        if ($second !== '' && Str::upper($second) === $second) {
            return $text;
        }

        return Str::lower($first) . Str::substr($text, 1);
    }

    private function handleBotResponse(Conversation $conversation, string $userMessage): array
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
        } elseif ($conversation->bot_phase === 'awaiting_submenu') {
            // Find child menu by label
            $child = \App\Models\BotMenu::where('label', $userMessage)->first();
            if ($child) {
                if ($child->action_type === 'connect_cs') {
                    if (is_null($child->action_value) || $child->action_value === '' || $child->action_value === 'General Support') {
                        $conversation->update(['bot_phase' => 'chatting_with_ai']);
                        $newBotMessages[] = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id'       => 0,
                            'sender_type'     => 'admin',
                            'message_type'    => 'text',
                            'content'         => "Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya.",
                        ]);
                    } else {
                        // Untuk CS Voucher dll — cek apakah butuh registration atau langsung chatting
                        if ($child->message_response) {
                            $conversation->update(['bot_phase' => 'require_registration']);
                            $newBotMessages[] = Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id'       => 0,
                                'sender_type'     => 'admin',
                                'message_type'    => 'text',
                                'content'         => $child->message_response,
                            ]);
                        } else {
                            $conversation->update(['bot_phase' => 'chatting_with_ai']);
                            $newBotMessages[] = Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id'       => 0,
                                'sender_type'     => 'admin',
                                'message_type'    => 'text',
                                'content'         => "Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya.",
                            ]);
                        }
                    }
                } elseif ($child->action_type === 'link' && $child->action_value) {
                    $content = $child->message_response ?? '';
                    $content .= '<div class="mt-2"><a href="' . $child->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fas fa-external-link-alt"></i> Buka Link</a></div>';
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => $content,
                    ]);
                }
            } else {
                // FALLBACK: label tidak ditemukan di database
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Maaf, opsi tidak ditemukan. Silakan pilih salah satu menu yang tersedia atau ketik 'menu' untuk kembali ke menu utama.",
                ]);
            }
        } elseif ($conversation->bot_phase === 'chatting_with_ai') {
            // Cek kata kunci AGENT sebelum ke Gemini agar lebih responsif
            if (strtoupper(trim($userMessage)) === 'AGENT') {
                $conversation->update(['bot_phase' => 'require_registration']);
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                     'content'         => "Oke, saya sambungkan ke Agent ya. Silakan isi form data diri dulu di layar kamu.",
                ]);
                return $this->formatBotReplies($newBotMessages, $conversation);
            }

            $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan pelanggan ke BEST AI: ");
            $conversation->update(['bot_phase' => 'offer_agent_transfer']);
            $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
            
            if (!str_contains($aiResponse, 'Maaf, saat ini sistem BEST AI')) {
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Gimana, jawaban di atas cukup membantu? 😊\n\nKlik salah satu opsi di bawah ya:",
                ]);
            }
        } elseif ($conversation->bot_phase === 'offer_agent_transfer') {
            if (strtoupper(trim($userMessage)) === 'AGENT') {
                $conversation->update(['bot_phase' => 'require_registration']);
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                     'content'         => "Oke, saya sambungkan ke Agent ya. Silakan isi form data diri dulu di layar kamu.",
                ]);
            } elseif (strtoupper(trim($userMessage)) === 'LANJUT') {
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Oke, silakan tanya lagi. Saya siap bantu! 😊",
                ]);
                $conversation->update(['bot_phase' => 'chatting_with_ai']);
            } else {
                // If they ask another question directly, keep chatting with AI
                // LOOP GUARD: Hitung berapa kali user mengabaikan opsi AGENT/LANJUT
                $recentBotAsks = Message::where('conversation_id', $conversation->id)
                    ->where('sender_type', 'admin')
                    ->where('content', 'LIKE', '%cukup%membantu%')
                    ->orderBy('created_at', 'desc')
                    ->count();

                $conversation->update(['bot_phase' => 'chatting_with_ai']);
                $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan pelanggan lanjutan ke BEST AI: ");

                $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));

                if ($recentBotAsks >= 2 && !str_contains($aiResponse, 'Maaf, saat ini sistem BEST AI')) {
                    // Setelah 2x berturut-turut user abaikan opsi, tawarkan AGENT secara eksplisit tanpa loop
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => "Kalau butuh bantuan lebih lanjut, kamu bisa langsung klik tombol **Hubungi Agent** di bawah ya.",
                    ]);
                } elseif (!str_contains($aiResponse, 'Maaf, saat ini sistem BEST AI')) {
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => "Gimana, jawaban di atas cukup membantu? 😊\n\nKlik salah satu opsi di bawah ya:",
                    ]);
                } else {
                    // AI error, tetap di chatting_with_ai agar user bisa coba lagi
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                }
            }
        } elseif ($conversation->bot_phase === 'require_registration') {
             $newBotMessages[] = Message::create([
                 'conversation_id' => $conversation->id,
                 'sender_id'       => 0,
                 'sender_type'     => 'admin',
                 'message_type'    => 'text',
                 'content'         => "Silakan isi form data diri dulu ya, biar bisa saya sambungkan ke Agent.",
             ]);
        } elseif ($conversation->bot_phase === 'awaiting_main_menu') {
            $menu = \App\Models\BotMenu::where('label', $userMessage)->whereNull('parent_id')->first();
            if ($menu) {
                if ($menu->action_type === 'link' && $menu->action_value) {
                    $content = $this->buildLinkMenuResponse($menu);
                } else {
                    $content = $menu->message_response ?? '';
                }

                if ($content) {
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => $content,
                    ]);
                }

                if ($menu->action_type === 'submenu') {
                    $conversation->update(['bot_phase' => 'awaiting_submenu']);
                    // Alpine.js will render buttons, no need for text list here
                }
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
                // Hitung posisi antrian berdasarkan waktu (FIFO), bukan ID
                $queueCount = Conversation::whereIn('status', ['pending', 'queued'])
                    ->whereNull('admin_id')
                    ->where('created_at', '<=', $conversation->created_at)
                    ->count();
                $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);

                $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Pesan diterima. Antrean ke-{$queueCount}. Sambil menunggu, silakan baca jawaban AI di atas.",
                ]);
            }
        } elseif ($conversation->bot_phase === 'off' && is_null($conversation->admin_id)) {
            // Jika bot sudah OFF tapi admin belum klaim, bot tetap menjawab sebagai asisten pintar
            $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan lanjutan dari pelanggan (Admin belum bergabung): ");

            $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
        }

        return $this->formatBotReplies($newBotMessages, $conversation);
    }

    private function createAiReplyMessages(Conversation $conversation, string $userMessage, string $aiResponse): array
    {
        $messages = [];
        $productImage = $this->detectProductImageForMessage($userMessage);
        $aiResponse = $this->sanitizeAiResponse($aiResponse);

        if ($productImage) {
            $aiResponse = $this->normalizeAiResponseForProductImage($aiResponse, $productImage['label']);
        }

        if ($productImage && $this->isOutOfScopeProductRefusal($aiResponse)) {
            $aiResponse = $productImage['description'] ?? "Produk {$productImage['label']} termasuk bagian dari PT BEST CORPORATION SYARIAH. Kalau kamu mau, saya juga bisa bantu tampilkan gambarnya.";
        }

        $messages[] = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'admin',
            'message_type'    => 'text',
            'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
        ]);

        if ($productImage && $this->shouldAttachProductImage($aiResponse)) {
            $messages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => 'Berikut gambaran produk ' . $productImage['label'] . ':',
            ]);

            $messages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'image',
                'content'         => asset('images/' . $productImage['path']),
            ]);
        }

        return $messages;
    }

    private function normalizeAiResponseForProductImage(string $aiResponse, string $label): string
    {
        if (!$this->responseConflictsWithProductImage($aiResponse)) {
            return $aiResponse;
        }

        return "Berikut gambaran produk {$label} dari PT BEST CORPORATION SYARIAH. Kalau kamu mau, saya juga bisa bantu jelaskan detail manfaat, kategori, atau informasi lanjutannya.";
    }

    private function shouldAttachProductImage(string $aiResponse): bool
    {
        return !$this->responseConflictsWithProductImage($aiResponse);
    }

    private function responseConflictsWithProductImage(string $aiResponse): bool
    {
        $normalized = strtolower(strip_tags($aiResponse));

        $conflictingPhrases = [
            'tidak bisa langsung memberikan foto',
            'tidak bisa memberikan foto',
            'tidak bisa kirim foto',
            'tidak bisa mengirim foto',
            'tidak dapat menampilkan gambar',
            'tidak bisa menampilkan gambar',
            'belum memiliki detail data untuk menampilkan gambar',
            'tidak memiliki gambar',
            'tidak punya gambar',
            'maaf, saya tidak bisa langsung',
            'sistem akan menampilkan gambar',
            'secara otomatis di sini',
        ];

        foreach ($conflictingPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function isOutOfScopeProductRefusal(string $aiResponse): bool
    {
        $normalized = strtolower(trim(strip_tags($aiResponse)));

        return str_contains($normalized, 'maaf, saya hanya bisa membantu pertanyaan seputar pt best corporation syariah');
    }

    private function detectProductImageForMessage(string $message): ?array
    {
        $normalized = $this->normalizeProductLookupText($message);

        if ($specificProduct = $this->findSpecificProductImage($normalized)) {
            return $specificProduct;
        }

        return $this->findCategoryProductImage($normalized);
    }

    private function findSpecificProductImage(string $normalizedMessage): ?array
    {
        foreach ($this->productImageCatalog() as $product) {
            foreach ($product['keywords'] as $keyword) {
                if (str_contains($normalizedMessage, $keyword)) {
                    return [
                        'path' => $product['path'],
                        'label' => $product['label'],
                    ];
                }
            }
        }

        return null;
    }

    private function findCategoryProductImage(string $normalizedMessage): ?array
    {
        $productIntentKeywords = [
            'produk',
            'product',
            'daftar produk',
            'kategori produk',
            'jenis produk',
            'katalog',
            'catalog',
            'gambar produk',
            'foto produk',
            'lihat produk',
            'tampilkan produk',
        ];

        $hasProductIntent = false;
        foreach ($productIntentKeywords as $keyword) {
            if (str_contains($normalizedMessage, $this->normalizeProductLookupText($keyword))) {
                $hasProductIntent = true;
                break;
            }
        }

        if (!$hasProductIntent) {
            return null;
        }

        $categoryMap = [
            [
                'keywords' => ['otomotif', 'kendaraan', 'motor', 'mobil'],
                'path' => 'produk/produk-otomotif.png',
                'label' => 'otomotif',
            ],
            [
                'keywords' => ['kesehatan', 'herbal', 'suplemen', 'vitamin'],
                'path' => 'produk/produk-kesehatan.png',
                'label' => 'herbal dan kesehatan',
            ],
            [
                'keywords' => ['kecantikan', 'beauty', 'skincare', 'kosmetik'],
                'path' => 'produk/produk-kecantikan.png',
                'label' => 'skincare dan kecantikan',
            ],
            [
                'keywords' => ['pertanian', 'perkebunan', 'pupuk', 'tani', 'agrikultur'],
                'path' => 'produk/produk-pertanian.png',
                'label' => 'pertanian dan perkebunan',
            ],
            [
                'keywords' => ['minuman kesehatan', 'minuman', 'kopi', 'coffee'],
                'path' => 'produk minuman untuk kesehatan tubuh/Evitgo 100.jpg',
                'label' => 'minuman kesehatan',
            ],
            [
                'keywords' => ['pembersih tubuh', 'pembersih area tubuh', 'hygiene', 'kesehatan area tubuh'],
                'path' => 'produk pembersih untuk kesehatan tubuh/LVN CRYSTAL V LVN CRYSTAL Q.jpg',
                'label' => 'pembersih area tubuh',
            ],
        ];

        foreach ($categoryMap as $category) {
            foreach ($category['keywords'] as $keyword) {
                if (str_contains($normalizedMessage, $this->normalizeProductLookupText($keyword))) {
                    return $category;
                }
            }
        }

        if (str_contains($normalizedMessage, 'best')) {
            return [
                'path' => 'produk/produk-best.png',
                'label' => 'BEST',
            ];
        }

        return null;
    }

    private function sanitizeAiResponse(string $aiResponse): string
    {
        $cleaned = preg_replace('/<img\b[^>]*>/i', '', $aiResponse);
        $cleaned = preg_replace('/<figure\b[^>]*>.*?<\/figure>/is', '', (string) $cleaned);
        $cleaned = preg_replace('/\n{3,}/', "\n\n", (string) $cleaned);

        return trim((string) $cleaned);
    }

    private function productImageCatalog(): array
    {
        static $catalog = null;

        if (is_array($catalog)) {
            return $catalog;
        }

        $catalog = [];
        $directories = collect(File::directories(public_path('images')))
            ->map(fn (string $path) => basename($path))
            ->reject(fn (string $directory) => in_array($directory, ['produk'], true))
            ->values()
            ->all();
        $aliasMap = [
            'bmaxxx' => 'B MAXX',
            'agro sawit' => 'Agrosawit',
            'nano tech' => 'Eco Racing Nano Tech atau Nano Oil',
            'nano oil' => 'Eco Racing Nano Tech atau Nano Oil',
        ];
        $labelOverrides = [
            'ecoracing' => 'Eco Racing',
            'ecodiesel' => 'Eco Diesel',
            'ecoracingnanotechataunanooil' => 'Eco Racing Nano Tech atau Nano Oil',
            'agrosawit' => 'Agrosawit',
            'bmaxx' => 'B-MAXX',
            'ecovico' => 'ECO VICO',
            'habspro' => 'HABSPRO',
            'redoneboost' => 'RED ONE BOOST',
            'lvnserum' => 'LVN Serum',
            'lvnlipcream' => 'LVN Lipcream',
            'lvndayandnightcream' => 'LVN Day and Night Cream',
        ];
        $descriptionOverrides = [
            'agrosawit' => 'Agrosawit adalah produk pupuk untuk pertanian dan perkebunan dari PT BEST yang pada artikel bisnisraksasa.com dijelaskan sebagai Premium Water Soluble Fertilizer, mudah larut, cepat diserap tanaman, dan diposisikan untuk membantu meningkatkan fungsi akar, batang, dan daun pada tanaman sawit.',
            'ecoracing' => 'Eco Racing adalah aditif bahan bakar PT BEST untuk membantu mengoptimalkan pembakaran pada kendaraan. Menurut halaman produk bisnisraksasa.com, manfaat umumnya meliputi membantu membersihkan dan merawat mesin, menyempurnakan pembakaran, mengurangi knocking, meningkatkan akselerasi, dan membantu mengurangi emisi gas buang.',
            'ecodiesel' => 'Eco Diesel adalah aditif bahan bakar PT BEST yang ditujukan untuk mesin diesel. Menurut halaman produk bisnisraksasa.com, produk ini diposisikan untuk membantu mengoptimalkan pembakaran dan meningkatkan kualitas bahan bakar.',
            'ecoracingnanotechataunanooil' => 'Eco Racing Nano Tech atau Nano Oil adalah aditif oli mesin PT BEST berbasis teknologi nano yang diposisikan untuk membantu memberikan pelumasan lebih baik dan melindungi komponen mesin.',
            'bmaxx' => 'B-MAXX adalah kapsul herbal PT BEST yang pada bisnisraksasa.com dijelaskan sebagai penyeimbang nutrisi organ dengan kandungan seperti cabe jawa, merica, gamat emas, purwaceng, dan pasak bumi.',
            'ecovico' => 'ECO VICO adalah kapsul herbal PT BEST yang dibuat dari penyulingan minyak kelapa murni dan pada bisnisraksasa.com diposisikan untuk membantu menjaga stamina dan kesehatan tubuh.',
            'habspro' => 'HABSPRO adalah suplemen herbal PT BEST berbentuk kapsul dengan kandungan utama habbatussauda, bee pollen, dan propolis. Di bisnisraksasa.com produk ini diposisikan untuk membantu menjaga stamina dan daya tahan tubuh.',
            'lvnserum' => 'LVN Serum adalah produk skincare PT BEST yang pada bisnisraksasa.com dijelaskan mengandung Hyaluronic Acid, Vitamin C, Vitamin E, dan Collagen untuk membantu menutrisi kulit dan menyamarkan tanda-tanda penuaan dini.',
            'lvnlipcream' => 'LVN Lipcream adalah produk kecantikan PT BEST yang pada bisnisraksasa.com dijelaskan memiliki banyak pilihan warna dengan tampilan natural, tekstur ringan, dan tidak mudah luntur.',
            'lvndayandnightcream' => 'LVN Day and Night Cream adalah produk skincare PT BEST. Menurut bisnisraksasa.com, varian day cream diposisikan untuk membantu mencerahkan, melembapkan, dan melindungi kulit dari paparan sinar matahari.',
        ];

        foreach ($directories as $directory) {
            $fullPath = public_path('images/' . $directory);

            if (!File::isDirectory($fullPath)) {
                continue;
            }

            foreach (File::files($fullPath) as $file) {
                $filename = $file->getFilenameWithoutExtension();
                $normalizedName = $this->normalizeProductLookupText($filename);
                $label = $labelOverrides[$normalizedName] ?? $this->humanizeProductFilename($filename);
                $keywords = array_values(array_unique(array_filter([
                    $normalizedName,
                    $this->normalizeProductLookupText($label),
                ])));

                foreach ($aliasMap as $alias => $aliasLabel) {
                    if ($normalizedName === $this->normalizeProductLookupText($aliasLabel)) {
                        $keywords[] = $this->normalizeProductLookupText($alias);
                    }
                }

                $catalog[] = [
                    'path' => $directory . '/' . $file->getFilename(),
                    'label' => $label,
                    'description' => $descriptionOverrides[$normalizedName] ?? null,
                    'keywords' => array_values(array_unique($keywords)),
                ];
            }
        }

        return $catalog;
    }

    private function normalizeProductLookupText(string $text): string
    {
        $text = preg_replace('/(?<!^)([A-Z])/', ' $1', $text);
        $text = Str::lower((string) $text);
        $text = preg_replace('/[^a-z0-9]+/i', '', $text);

        return trim((string) $text);
    }

    private function humanizeProductFilename(string $filename): string
    {
        $label = preg_replace('/(?<!^)([A-Z])/', ' $1', $filename);
        $label = str_replace(['-', '_'], ' ', (string) $label);
        $label = preg_replace('/\s+/', ' ', (string) $label);

        return trim((string) $label);
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

    private function buildLinkMenuResponse(\App\Models\BotMenu $menu): string
    {
        $menuLabel = trim((string) $menu->label);
        $menuLabelLower = mb_strtolower($menuLabel, 'UTF-8');
        $menuList = $this->buildMainMenuListOnly();

        if ($this->isYoutubeLink($menu->action_value)) {
            return implode("\n", [
                'Anda dapat menonton channel youtube BRILLIAN.BIZ di sini:',
                '',
                $menu->action_value,
                '',
                'Kunjungi Channel YouTube',
                '',
                'Pilih layanan kami lainnya:',
                '',
                $menuList,
            ]);
        }

        if (str_contains($menuLabelLower, 'jadwal seminar')) {
            return implode("\n", [
                'Berikut jadwal seminar BRILLIAN.BIZ: Buka Link',
                '',
                $menu->action_value,
                '',
                'Pilih layanan kami lainnya:',
                '',
                $menuList,
            ]);
        }

        $lines = [];
        if (!empty($menu->message_response)) {
            $lines[] = trim((string) $menu->message_response);
            $lines[] = '';
        }
        $lines[] = $menu->action_value;
        $lines[] = '';
        $lines[] = 'Pilih layanan kami lainnya:';
        $lines[] = '';
        $lines[] = $menuList;

        return implode("\n", $lines);
    }

    private function buildMainMenuListOnly(): string
    {
        $menus = \App\Models\BotMenu::whereNull('parent_id')
            ->orderBy('order_index')
            ->get(['label']);

        if ($menus->isEmpty()) {
            return 'Menu utama belum tersedia saat ini.';
        }

        return $menus
            ->values()
            ->map(fn ($menu, $index) => '[' . ($index + 1) . '] ' . $menu->label)
            ->implode("\n");
    }

    private function isYoutubeLink(?string $url): bool
    {
        $normalized = mb_strtolower((string) $url, 'UTF-8');

        return str_contains($normalized, 'youtube.com') || str_contains($normalized, 'youtu.be');
    }

    private function createConversation($user, $selectedMenuId = null): Conversation
    {
        $availableAdmin = Admin::where('status', '!=', 'offline')->get()->first(fn($admin) => $admin->canTakeNewChat());
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $queuePosition = null;

        if ($anyOnline && !$availableAdmin) {
            $status = 'queued';
            // Hitung posisi antrian: semua yang queued + 1
            $queuePosition = Conversation::where('status', 'queued')->count() + 1;
        }

        // Fetch selected menu
        $menu = $selectedMenuId ? \App\Models\BotMenu::find($selectedMenuId) : null;
        
        // Set bot_phase based on menu action
        $botPhase = 'off';
        if ($menu) {
            if ($menu->action_type === 'submenu') $botPhase = 'awaiting_submenu';
            elseif ($menu->action_type === 'connect_cs') {
                $botPhase = 'chatting_with_ai';
            }
        } else {
            $botPhase = 'awaiting_category';
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'status' => $status,
            'bot_phase' => $botPhase,
            'queue_position' => $queuePosition,
            'last_message_at'=> now(),
        ]);

        // User intro
        $isAnonymousCS = ($user->name === 'Guest' && $menu && $menu->action_type === 'connect_cs');
        $intro = null;
        if (!$isAnonymousCS) {
            $introLabel = ($menu ? $menu->label : 'Bantuan');
            $intro = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $user->id,
                'sender_type'     => 'user',
                'message_type'    => 'text',
                'content'         => "Halo! Saya {$user->name} dari {$user->origin}. Saya memilih: {$introLabel}",
            ]);
        }

        // Bot Response
        $botReplies = [];
        if ($menu) {
            if ($menu->action_type === 'link' && $menu->action_value) {
                $content = $this->buildLinkMenuResponse($menu);
            } else {
                $content = $menu->message_response ?? '';
            }

            if ($content) $botReplies[] = $content;

            if ($menu->action_type === 'link') {
                $conversation->update(['bot_phase' => 'awaiting_main_menu']);
            } elseif ($menu->action_type === 'connect_cs') {
                if ($isAnonymousCS) {
                     $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                     $botReplies[] = "Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya. Kalau mau langsung ngobrol sama Agent, klik tombol **Hubungi Agent** di bawah ya.";
                } else {
                     // Hitung posisi antrian berdasarkan waktu (FIFO), bukan ID
                     $queueCount = Conversation::whereIn('status', ['pending', 'queued'])
                         ->whereNull('admin_id')
                         ->where('created_at', '<=', $conversation->created_at)
                         ->count();
                     $botReplies[] = "Sebelum terhubung dengan Customer service kami apakah ada yang ingin ditanyakan ke BEST AI ketik \"YA\" jika tidak abaikan saja.\n\nAntrean Anda saat ini: ke-{$queueCount}.";
                }
            }
        } else {
            // Default legacy behavior
            $botCategories = config('chat.complaint_categories');
            $categoryButtons = "";
            foreach ($botCategories as $cat) { $categoryButtons .= "- {$cat}\n"; }
            $botReplies[] = "👋 Selamat datang di BRILLIAN.BIS. Pilih kategori kendala Anda:\n\n" . $categoryButtons;
        }

        foreach ($botReplies as $content) {
            $botMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => $content,
            ]);
            try { broadcast(new MessageSent($botMsg)); } catch (\Exception $e) {}
        }

        try {
            if ($intro) {
                broadcast(new MessageSent($intro))->toOthers();
            }
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        } catch (\Exception $e) {}

        return $conversation;
    }
}
