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
        Cookie::queue('guest_chat_token', $user->email, 60);
        Auth::guard('web')->login($user, true);

        // Pastikan conversation dan pesan otomatis dibuat
        $activeConversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->first();

        if (!$activeConversation) {
            $activeConversation = $this->createConversation($user, $request->selected_option);
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
            $publicData = [
                'csrf_token'   => csrf_token(),
                'chat_greeting' => \App\Models\Setting::get('chat_greeting', 'anda berapa di layanan whatsapp BRILLIAN.BIS kami terus melayani'),
                'chat_main_menu' => \App\Models\BotMenu::whereNull('parent_id')->orderBy('order_index')->get()->map(fn($m) => [
                    'id' => $m->id,
                    'label' => $m->label,
                    'action_type' => $m->action_type,
                    'message_response' => $m->message_response
                ]),
            ];

            if (!$token) {
                return response()->json($publicData);
            }

            $user = User::where('email', $token)->first();
            if (!$user) {
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
                'botCategories' => config('chat.complaint_categories'),
                'bot_submenus' => ($activeConversation->bot_phase === 'awaiting_submenu' && $activeConversation->last_message_at) 
                    ? \App\Models\BotMenu::whereNotNull('parent_id')->whereIn('parent_id', \App\Models\BotMenu::pluck('id'))->get()->map(fn($m) => ['id' => $m->id, 'label' => $m->label])
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

        $conversation->update([
            'last_message_at' => now(),
            'reminder_count' => 0,
        ]);

        try {
            broadcast(new MessageSent($message));
            
            // Dispatch background processing (WhatsApp & Gemini)
            ProcessUserMessage::dispatch($message);

        } catch (\Exception $e) { 
            \Log::error('Broadcast/Job dispatch failed', ['error' => $e->getMessage()]); 
        }

        // Tangani Bot Response dan kumpulkan untuk dikirim di JSON
        $botReplies = [];
        if ($conversation->bot_phase !== 'off' || is_null($conversation->admin_id)) {
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
        } elseif ($conversation->bot_phase === 'awaiting_submenu') {
            // Find child menu by label
            $child = \App\Models\BotMenu::where('label', $userMessage)->first();
            if ($child) {
                if ($child->action_type === 'connect_cs') {
                    if ($child->label === 'Customer service') {
                        $conversation->update(['bot_phase' => 'awaiting_ai_optin']);
                        $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                        $newBotMessages[] = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id'       => 0,
                            'sender_type'     => 'admin',
                            'message_type'    => 'text',
                            'content'         => "Sebelum terhubung dengan Customer service kami apakah ada yang ingin ditanyakan ke BEST AI ketik \"YA\" jika tidak abaikan saja.\n\nAntrean Anda saat ini: ke-{$queueCount}.",
                        ]);
                    } else {
                        $conversation->update(['bot_phase' => 'off']);
                        $newBotMessages[] = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id'       => 0,
                            'sender_type'     => 'admin',
                            'message_type'    => 'text',
                            'content'         => $child->message_response ?? "Anda akan terhubung dengan {$child->label}, isi kebutuhan anda.",
                        ]);
                    }
                }
            }
        } elseif ($conversation->bot_phase === 'awaiting_ai_optin') {
            if (strtoupper(trim($userMessage)) === 'YA') {
                $conversation->update(['bot_phase' => 'awaiting_ai_question']);
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => "Silakan ajukan pertanyaan Anda ke BEST AI.",
                ]);
            } else {
                // User didn't type YA, probably explaining needs. Turn off bot but don't respond further.
                $conversation->update(['bot_phase' => 'off']);
            }
        } elseif ($conversation->bot_phase === 'awaiting_ai_question') {
            $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan pelanggan ke BEST AI: ");
            $newBotMessages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
            ]);
        } elseif ($conversation->bot_phase === 'awaiting_main_menu') {
            $menu = \App\Models\BotMenu::where('label', $userMessage)->whereNull('parent_id')->first();
            if ($menu) {
                if ($menu->message_response) $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => $menu->message_response . ($menu->action_type === 'link' ? "\n\nPilih layanan kami lainnya:" : ""),
                ]);

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
                $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);

                $rawReplies = [
                    ['content' => $aiResponse, 'type' => 'ai'],
                    ['content' => "Pesan diterima. Antrean ke-{$queueCount}. Sambil menunggu, silakan baca jawaban AI di atas.", 'type' => 'system']
                ];

                $newBotMessages = []; // Clear previous if any, though in this branch it should be empty
                foreach ($rawReplies as $bm) {
                    $newBotMessages[] = Message::create([
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

    private function createConversation($user, $selectedMenuId = null): Conversation
    {
        $availableAdmin = Admin::where('status', '!=', 'offline')->get()->first(fn($admin) => $admin->canTakeNewChat());
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $queuePosition = null;

        if ($anyOnline && !$availableAdmin) {
            $status = 'queued';
            $queuePosition = Conversation::where('status', 'queued')->count() + 1;
        }

        // Fetch selected menu
        $menu = $selectedMenuId ? \App\Models\BotMenu::find($selectedMenuId) : null;
        
        // Set bot_phase based on menu action
        $botPhase = 'off';
        if ($menu) {
            if ($menu->action_type === 'submenu') $botPhase = 'awaiting_submenu';
            elseif ($menu->action_type === 'connect_cs') {
                if ($menu->label === 'Customer service') $botPhase = 'awaiting_ai_optin';
                else $botPhase = 'off';
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
        $introLabel = ($menu ? $menu->label : 'Bantuan');
        $intro = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'user',
            'message_type'    => 'text',
            'content'         => "Halo! Saya {$user->name} dari {$user->origin}. Saya memilih: {$introLabel}",
        ]);

        // Bot Response
        $botReplies = [];
        if ($menu) {
            if ($menu->message_response) $botReplies[] = $menu->message_response;
            
            if ($menu->action_type === 'submenu') {
                // No need to send text list, Alpine.js will render buttons
            } elseif ($menu->action_type === 'link') {
                // Also show main menu again for links
                $conversation->update(['bot_phase' => 'awaiting_main_menu']);
                $botReplies[] = "Pilih layanan kami lainnya:";
            } elseif ($menu->action_type === 'connect_cs' && $menu->label === 'Customer service') {
                $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                $botReplies[] = "Sebelum terhubung dengan Customer service kami apakah ada yang ingin ditanyakan ke BEST AI ketik \"YA\" jika tidak abaikan saja.\n\nAntrean Anda saat ini: ke-{$queueCount}.";
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
            broadcast(new MessageSent($intro))->toOthers();
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        } catch (\Exception $e) {}

        return $conversation;
    }
}