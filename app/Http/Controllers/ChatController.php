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
        Cookie::queue('guest_chat_token', $user->email, 35);
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
        $activeConversation = $this->createConversation($user, $request->selected_option);
        
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
             $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $activeConversation->id)->count();
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
                'bot_submenus' => ($activeConversation->bot_phase === 'awaiting_submenu') 
                    ? \App\Models\BotMenu::whereNotNull('parent_id')->orderBy('order_index')->get()->map(fn($m) => ['id' => $m->id, 'label' => $m->label])
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
            
            $newBotMessages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => 0,
                'sender_type'     => 'admin',
                'message_type'    => 'text',
                'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
            ]);
            
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

                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
                ]);

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
                $content = $menu->message_response ?? '';
                if ($menu->action_value && (str_contains(strtolower($menu->action_value), 'youtube.com') || str_contains(strtolower($menu->action_value), 'youtu.be'))) {
                    // Extract Video ID
                    $embedUrl = false;
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $menu->action_value, $match)) {
                        $embedUrl = "https://www.youtube.com/embed/" . $match[1];
                    } 
                    
                    if ($embedUrl) {
                         $content .= '<div class="mt-3 mb-1 overflow-hidden rounded-xl border border-gray-100 shadow-sm w-full max-w-[280px]"><div class="relative w-full" style="padding-bottom: 56.25%;"><iframe class="absolute top-0 left-0 w-full h-full" src="' . $embedUrl . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><div class="p-2 bg-white"><a href="' . $menu->action_value . '" target="_blank" class="flex items-center justify-center gap-2 px-3 py-1.5 w-full bg-red-600 text-white rounded-full font-bold no-underline hover:bg-red-700 transition-all" style="font-size: 11px;"><i class="fab fa-youtube"></i> Buka di YouTube</a></div></div>';
                    } else {
                         // Improved fallback for channel or non-video links
                         $content .= '<div class="mt-2"><a href="' . $menu->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fab fa-youtube"></i> Kunjungi Channel YouTube</a></div>';
                    }
                } elseif ($menu->action_type === 'link' && $menu->action_value) {
                    $content .= '<div class="mt-2"><a href="' . $menu->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fas fa-external-link-alt"></i> Buka Link</a></div>';
                }

                if ($content) $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => 0,
                    'sender_type'     => 'admin',
                    'message_type'    => 'text',
                    'content'         => $content . ($menu->action_type === 'link' ? "\n\nPilih layanan kami lainnya:" : ""),
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
            $content = $menu->message_response ?? '';
            
            if ($menu->action_type === 'link' && $menu->action_value) {
                $isYoutube = str_contains(strtolower($menu->action_value), 'youtube.com') || str_contains(strtolower($menu->action_value), 'youtu.be');
                if ($isYoutube) {
                    $embedUrl = false;
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $menu->action_value, $match)) {
                        $embedUrl = "https://www.youtube.com/embed/" . $match[1];
                    }
                    
                    if ($embedUrl) {
                        $content .= '<div class="mt-3 mb-1 overflow-hidden rounded-xl border border-gray-100 shadow-sm w-full max-w-[280px]"><div class="relative w-full" style="padding-bottom: 56.25%;"><iframe class="absolute top-0 left-0 w-full h-full" src="' . $embedUrl . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><div class="p-2 bg-white"><a href="' . $menu->action_value . '" target="_blank" class="flex items-center justify-center gap-2 px-3 py-1.5 w-full bg-red-600 text-white rounded-full font-bold no-underline hover:bg-red-700 transition-all" style="font-size: 11px;"><i class="fab fa-youtube"></i> Buka di YouTube</a></div></div>';
                    } else {
                        $content .= '<div class="mt-2"><a href="' . $menu->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fab fa-youtube"></i> Kunjungi Channel</a></div>';
                    }
                } else {
                    $content .= '<div class="mt-2"><a href="' . $menu->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fas fa-external-link-alt"></i> Buka Link</a></div>';
                }
            }
            
            if ($content) $botReplies[] = $content;
            
            if ($menu->action_type === 'submenu') {
                // No need to send text list, Alpine.js will render buttons
            } elseif ($menu->action_type === 'link') {
                // Also show main menu again for links
                $conversation->update(['bot_phase' => 'awaiting_main_menu']);
                $botReplies[] = "Pilih layanan kami lainnya:";
            } elseif ($menu->action_type === 'connect_cs') {
                if ($isAnonymousCS) {
                     $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                     $botReplies[] = "Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya. Kalau mau langsung ngobrol sama Agent, klik tombol **Hubungi Agent** di bawah ya.";
                } else {
                     $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
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