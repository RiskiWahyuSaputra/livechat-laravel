<?php

namespace App\Services;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Models\Admin;
use App\Models\BotMenu;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class ConversationFlowService
{
    public function __construct(protected GeminiService $geminiService)
    {
    }

    public function createConversation(User $user, ?int $selectedMenuId = null): array
    {
        $availableAdmin = Admin::where('status', '!=', 'offline')->get()->first(fn($admin) => $admin->canTakeNewChat());
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $queuePosition = null;

        if ($anyOnline && !$availableAdmin) {
            $status = 'queued';
            $queuePosition = Conversation::where('status', 'queued')->count() + 1;
        }

        $menu = $selectedMenuId ? BotMenu::find($selectedMenuId) : null;

        $botPhase = 'off';
        if ($menu) {
            if ($menu->action_type === 'submenu') {
                $botPhase = 'awaiting_submenu';
            } elseif ($menu->action_type === 'connect_cs') {
                $botPhase = 'chatting_with_ai';
            }
        } else {
            $botPhase = $this->usesBotMenuFlow()
                ? 'awaiting_main_menu'
                : 'awaiting_category';
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'status' => $status,
            'bot_phase' => $botPhase,
            'queue_position' => $queuePosition,
            'last_message_at' => now(),
        ]);

        $isAnonymousCS = ($user->name === 'Guest' && $menu && $menu->action_type === 'connect_cs');
        $intro = null;

        if (!$isAnonymousCS) {
            $introLabel = $menu ? $menu->label : 'Bantuan';
            $intro = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => "Halo! Saya {$user->name} dari {$user->origin}. Saya memilih: {$introLabel}",
            ]);
        }

        $botReplies = [];
        if ($menu) {
            if ($menu->action_type === 'link' && $menu->action_value) {
                $content = $this->buildLinkMenuResponse($menu);
            } else {
                $content = $menu->message_response ?? '';
            }

            if ($content) {
                $botReplies[] = $content;
            }

            if ($menu->action_type === 'link') {
                $conversation->update(['bot_phase' => 'awaiting_main_menu']);
            } elseif ($menu->action_type === 'connect_cs') {
                $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                $botReplies[] = 'Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya. Kalau mau langsung ngobrol sama Agent, klik tombol **Hubungi Agent** di bawah ya.';
            }
        } else {
            $botReplies[] = $this->buildInitialPrompt();
        }

        $createdMessages = [];
        foreach ($botReplies as $index => $content) {
            $botMsg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 0,
                'sender_type' => 'admin',
                'message_type' => 'text',
                'content' => $content,
            ]);

            // Attach buttons for WhatsApp if it's the menu prompt
            if ($index === count($botReplies) - 1) {
                if ($menu && $menu->action_type === 'submenu') {
                    $botMsg->whatsapp_buttons = $this->buildSubmenuButtons($menu->id);
                } elseif (!$menu && $this->usesBotMenuFlow()) {
                    $botMsg->whatsapp_buttons = $this->buildMainMenuButtons();
                } elseif ($menu && $menu->action_type === 'connect_cs') {
                    $botMsg->whatsapp_buttons = [
                        ['type' => 'reply', 'reply' => ['id' => 'lanjut', 'title' => 'Tanya BEST AI']],
                        ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']]
                    ];
                }
            }

            $createdMessages[] = $botMsg;

            try {
                broadcast(new MessageSent($botMsg));
            } catch (\Exception $e) {
            }
        }

        try {
            if ($intro) {
                broadcast(new MessageSent($intro))->toOthers();
            }
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        } catch (\Exception $e) {
        }

        return [
            'conversation' => $conversation,
            'bot_messages' => $createdMessages
        ];
    }

    public function processInboundMessage(User $user, Conversation $conversation, string $content, string $messageType = 'text', bool $broadcast = true): array
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message_type' => $messageType,
            'content' => $content,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'reminder_count' => 0,
        ]);

        if ($broadcast) {
            try {
                broadcast(new MessageSent($message));
            } catch (\Exception $e) {
            }
        }

        $botResult = ['formatted' => [], 'messages' => []];
        if ($messageType === 'text' && ($conversation->bot_phase !== 'off' || is_null($conversation->admin_id))) {
            $botResult = $this->handleBotResponse($user, $conversation, $content, $broadcast);
        }

        return [
            'message' => $message,
            'bot_replies' => $botResult['formatted'],
            'bot_messages' => $botResult['messages'],
            'bot_phase' => $conversation->fresh()->bot_phase,
        ];
    }

    public function handleBotResponse(User $user, Conversation $conversation, string $userMessage, bool $broadcast = true): array
    {
        $newBotMessages = [];
        if (strtolower(trim($userMessage)) === 'menu') {
            $resetPhase = $this->usesBotMenuFlow() ? 'awaiting_main_menu' : 'awaiting_category';

            $conversation->update([
                'bot_phase' => $resetPhase,
                'problem_category' => null,
            ]);

            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 0,
                'sender_type' => 'admin',
                'message_type' => 'text',
                'content' => $this->usesBotMenuFlow()
                    ? $this->buildMainMenuPrompt(false)
                    : $this->buildCategoryPrompt(false),
            ]);

            if ($this->usesBotMenuFlow()) {
                $msg->whatsapp_buttons = $this->buildMainMenuButtons();
            }

            $newBotMessages[] = $msg;

            return $this->formatBotReplies($newBotMessages, $conversation, $broadcast);
        }

        if ($conversation->bot_phase === 'awaiting_category') {
            $botCategories = config('chat.complaint_categories', []);
            $matchedCategory = null;

            foreach ($botCategories as $category) {
                if (mb_strtolower(trim((string) $category)) === mb_strtolower(trim($userMessage))) {
                    $matchedCategory = $category;
                    break;
                }
            }

            if ($matchedCategory) {
                $conversation->update([
                    'problem_category' => $matchedCategory,
                    'bot_phase' => 'awaiting_explanation',
                ]);

                $msg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Baik, Anda memilih kategori {$matchedCategory}. Silakan jelaskan permasalahan Anda.\n\nKetik 'YA' untuk bantuan BEST AI instan, atau langsung jelaskan masalah Anda untuk admin.",
                ]);

                $msg->whatsapp_buttons = [
                    ['type' => 'reply', 'reply' => ['id' => 'ya', 'title' => 'YA']]
                ];

                $newBotMessages[] = $msg;
            } else {
                $content = "Mohon pilih salah satu kategori berikut:\n\n";
                foreach ($botCategories as $category) {
                    $content .= "- {$category}\n";
                }
                $content .= "\nAtau ketik 'menu' untuk kembali ke menu utama.";

                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => $content,
                ]);
            }
        } elseif ($conversation->bot_phase === 'awaiting_submenu') {
            $parentMenu = $this->resolveAwaitingSubmenuParentMenu($conversation);
            $child = $this->findSubmenuSelection($userMessage, $parentMenu?->id);
            
            // Web fallback: if label matching fails but it's a known submenu option
            if (!$child) {
                $normalizedInput = mb_strtolower(trim($userMessage));
                if ($normalizedInput === 'customer service') {
                    $child = BotMenu::where('label', 'Customer service')->first();
                } elseif ($normalizedInput === 'cs voucher') {
                    $child = BotMenu::where('label', 'CS Voucher')->first();
                }
            }

            if ($child) {
                if ($child->action_type === 'connect_cs') {
                    if (is_null($child->action_value) || $child->action_value === '' || $child->action_value === 'General Support') {
                        $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                        $msg = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id' => 0,
                            'sender_type' => 'admin',
                            'message_type' => 'text',
                            'content' => 'Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya. Kalau mau langsung ngobrol sama Agent, klik tombol **Hubungi Agent** di bawah ya.',
                        ]);
                        $msg->whatsapp_buttons = [
                            ['type' => 'reply', 'reply' => ['id' => 'lanjut', 'title' => 'Tanya BEST AI']],
                            ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']]
                        ];
                        $newBotMessages[] = $msg;
                    } else {
                        // For specific departments, we also transition to offer_agent_transfer
                        $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                        $content = $child->message_response ?: 'Hai! Saya BEST AI, asisten virtual kamu. Ceritakan aja kendala atau pertanyaan kamu, nanti saya bantu sebisa saya. Kalau mau langsung ngobrol sama Agent, klik tombol **Hubungi Agent** di bawah ya.';
                        
                        $msg = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id' => 0,
                            'sender_type' => 'admin',
                            'message_type' => 'text',
                            'content' => $content,
                        ]);
                        $msg->whatsapp_buttons = [
                            ['type' => 'reply', 'reply' => ['id' => 'lanjut', 'title' => 'Tanya BEST AI']],
                            ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']]
                        ];
                        $newBotMessages[] = $msg;
                    }
                } elseif ($child->action_type === 'link' && $child->action_value) {
                    $content = $child->message_response ?? '';
                    $content .= '<div class="mt-2"><a href="' . $child->action_value . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full font-bold no-underline shadow-md hover:bg-red-700 transition-all" style="font-size: 11px; text-decoration: none; color: white;"><i class="fas fa-external-link-alt"></i> Buka Link</a></div>';
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => $content,
                    ]);
                }
            } else {
                $submenuList = $this->buildSubmenuPrompt();
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Maaf, opsi tidak ditemukan. Silakan pilih salah satu menu yang tersedia atau ketik 'menu' untuk kembali ke menu utama." . ($submenuList ? "\n\n" . $submenuList : ''),
                ]);
            }
        } elseif ($conversation->bot_phase === 'chatting_with_ai') {
            if ($this->wantsAgentTransfer($userMessage)) {
                if ($user->name === 'Guest') {
                    $conversation->update(['bot_phase' => 'require_registration']);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => 'Oke, saya sambungkan ke Agent ya. Silakan isi form data diri dulu di layar kamu.',
                    ]);
                } else {
                    $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                    $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Oke, saya sambungkan ke Agent ya. Kamu sekarang ada di antrean ke-{$queueCount}. Tunggu sebentar ya.",
                    ]);
                }
                return $this->formatBotReplies($newBotMessages, $conversation, $broadcast);
            }

            $aiResponse = $this->geminiService->askGemini($userMessage, 'Pertanyaan pelanggan ke BEST AI: ');
            $conversation->update(['bot_phase' => 'offer_agent_transfer']);
            $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));

            if (!$this->isAiFallbackResponse($aiResponse)) {
                $msg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Gimana, jawaban di atas cukup membantu? 😊\n\nKlik salah satu opsi di bawah ya:",
                ]);
                $msg->whatsapp_buttons = [
                    ['type' => 'reply', 'reply' => ['id' => 'lanjut', 'title' => 'Lanjut Tanya']],
                    ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']],
                ];
                $newBotMessages[] = $msg;
            }
        } elseif ($conversation->bot_phase === 'offer_agent_transfer') {
            if ($this->wantsAgentTransfer($userMessage)) {
                if ($user->name === 'Guest') {
                    $conversation->update(['bot_phase' => 'require_registration']);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => 'Oke, saya sambungkan ke Agent ya. Silakan isi form data diri dulu di layar kamu.',
                    ]);
                } else {
                    $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                    $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Oke, saya sambungkan ke Agent ya. Kamu sekarang ada di antrean ke-{$queueCount}. Tunggu sebentar ya.",
                    ]);
                }
            } elseif ($this->wantsContinueWithAi($userMessage)) {
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => 'Oke, silakan tanya lagi. Saya siap bantu! 😊',
                ]);
                $conversation->update(['bot_phase' => 'chatting_with_ai']);
            } else {
                $recentBotAsks = Message::where('conversation_id', $conversation->id)
                    ->where('sender_type', 'admin')
                    ->where('content', 'LIKE', '%cukup%membantu%')
                    ->orderBy('created_at', 'desc')
                    ->count();

                $conversation->update(['bot_phase' => 'chatting_with_ai']);
                $aiResponse = $this->geminiService->askGemini($userMessage, 'Pertanyaan pelanggan lanjutan ke BEST AI: ');
                $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));

                if ($recentBotAsks >= 2 && !$this->isAiFallbackResponse($aiResponse)) {
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                    $msg = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => 'Kalau butuh bantuan lebih lanjut, kamu bisa langsung klik tombol **Hubungi Agent** di bawah ya.',
                    ]);
                    $msg->whatsapp_buttons = [
                        ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']]
                    ];
                    $newBotMessages[] = $msg;
                } elseif (!$this->isAiFallbackResponse($aiResponse)) {
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                    $msg = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Gimana, jawaban di atas cukup membantu? 😊\n\nKlik salah satu opsi di bawah ya:",
                    ]);
                    $msg->whatsapp_buttons = [
                        ['type' => 'reply', 'reply' => ['id' => 'lanjut', 'title' => 'Lanjut Tanya']],
                        ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hubungi Agent']],
                    ];
                    $newBotMessages[] = $msg;
                } else {
                    $conversation->update(['bot_phase' => 'offer_agent_transfer']);
                }
            }
        } elseif ($conversation->bot_phase === 'require_registration') {
             // Generate token if not exists
             if (!$user->registration_token) {
                 $user->update(['registration_token' => Str::random(32)]);
             }

             $regUrl = route('chat.register.whatsapp', ['token' => $user->registration_token]);

             $newBotMessages[] = Message::create([
                 'conversation_id' => $conversation->id,
                 'sender_id'       => 0,
                 'sender_type'     => 'admin',
                 'message_type'    => 'text',
                 'content'         => "Silakan isi data diri Anda melalui link berikut agar dapat terhubung dengan Agent:\n\n" . $regUrl,
             ]);
        } elseif ($conversation->bot_phase === 'awaiting_main_menu') {
            $menu = $this->findRootMenuSelection($userMessage);
            if ($menu) {
                if ($menu->action_type === 'link' && $menu->action_value) {
                    $content = $this->buildLinkMenuResponse($menu);
                } else {
                    $content = $menu->message_response ?? '';
                }

                if ($content) {
                    $msg = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => $content,
                    ]);

                    $newBotMessages[] = $msg;
                }

                if ($menu->action_type === 'submenu') {
                    $conversation->update(['bot_phase' => 'awaiting_submenu']);
                    $submenuPrompt = $this->buildSubmenuPrompt($menu->id);
                    if ($submenuPrompt) {
                        $msg = Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_id' => 0,
                            'sender_type' => 'admin',
                            'message_type' => 'text',
                            'content' => $submenuPrompt,
                        ]);
                        $msg->whatsapp_buttons = $this->buildSubmenuButtons($menu->id);
                        $newBotMessages[] = $msg;
                    }
                }
            } else {
                $msg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Silakan pilih salah satu menu utama berikut ya:\n\n" . $this->buildMainMenuPrompt(),
                ]);
                $msg->whatsapp_buttons = $this->buildMainMenuButtons();
                $newBotMessages[] = $msg;
            }
        } elseif ($conversation->bot_phase === 'awaiting_explanation') {
            if (strtoupper(trim($userMessage)) === 'YA') {
                $msg = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . "Silakan ajukan pertanyaan Anda mengenai {$conversation->problem_category}.",
                ]);
                $newBotMessages[] = $msg;
            } else {
                $aiResponse = $this->geminiService->askGemini($userMessage, "Pertanyaan {$conversation->problem_category}: ");
                $queueCount = Conversation::whereIn('status', ['pending', 'queued'])->whereNull('admin_id')->where('id', '<=', $conversation->id)->count();
                $conversation->update(['bot_phase' => 'off', 'queue_position' => $queueCount]);

                $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Pesan diterima. Antrean ke-{$queueCount}. Sambil menunggu, silakan baca jawaban AI di atas.",
                ]);
            }
        } elseif ($conversation->bot_phase === 'off' && is_null($conversation->admin_id)) {
            $aiResponse = $this->geminiService->askGemini($userMessage, 'Pertanyaan lanjutan dari pelanggan (Admin belum bergabung): ');
            $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
        }

        return $this->formatBotReplies($newBotMessages, $conversation, $broadcast);
    }

    private function createAiReplyMessages(Conversation $conversation, string $userMessage, string $aiResponse): array
    {
        $messages = [];
        $productImage = $this->detectProductImageForMessage($userMessage);
        $isFallbackResponse = $this->isAiFallbackResponse($aiResponse);

        if ($productImage && !$isFallbackResponse) {
            $aiResponse = $this->normalizeAiResponseForProductImage($aiResponse, $productImage['label']);
        }

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 0,
            'sender_type' => 'admin',
            'message_type' => 'text',
            'content' => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiResponse,
        ]);
        $messages[] = $msg;

        if ($productImage && !$isFallbackResponse && $this->shouldAttachProductImage($aiResponse)) {
            $messages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 0,
                'sender_type' => 'admin',
                'message_type' => 'text',
                'content' => 'Berikut gambaran produk ' . $productImage['label'] . ':',
            ]);

            $messages[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 0,
                'sender_type' => 'admin',
                'message_type' => 'image',
                'content' => asset('images/produk/' . $productImage['file']),
            ]);
        }

        return $messages;
    }

    private function isAiFallbackResponse(string $aiResponse): bool
    {
        return $this->geminiService->isFallbackResponse($aiResponse);
    }

    private function wantsAgentTransfer(string $userMessage): bool
    {
        $normalized = $this->normalizeBotInput($userMessage);

        if (in_array($normalized, [
            '2',
            'agent',
            'hubungi agent',
            'hubungin agent',
            'hubungi cs',
            'hubungin cs',
            'cs',
            'customer service',
        ], true)) {
            return true;
        }

        return str_contains($normalized, 'agent')
            || str_contains($normalized, 'customer service');
    }

    private function wantsContinueWithAi(string $userMessage): bool
    {
        $normalized = $this->normalizeBotInput($userMessage);

        if (in_array($normalized, [
            '1',
            'lanjut',
            'lanjut tanya',
            'tanya best ai',
            'best ai',
            'tanya ai',
        ], true)) {
            return true;
        }

        return str_contains($normalized, 'lanjut')
            || str_contains($normalized, 'tanya best ai');
    }

    private function normalizeBotInput(string $userMessage): string
    {
        $normalized = mb_strtolower(trim(strip_tags($userMessage)));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim((string) $normalized);
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

    private function detectProductImageForMessage(string $message): ?array
    {
        $normalized = strtolower($message);
        $genericProductIntentKeywords = [
            'produk',
            'product',
            'kategori produk',
            'jenis produk',
            'katalog',
            'catalog',
            'gambar produk',
            'foto produk',
            'barang',
        ];

        $productMap = [
            [
                'keywords' => ['kecantikan', 'beauty', 'skincare', 'kosmetik'],
                'file' => 'produk-kecantikan.png',
                'label' => 'kecantikan',
            ],
            [
                'keywords' => ['kesehatan', 'health', 'herbal', 'vitamin', 'suplemen'],
                'file' => 'produk-kesehatan.png',
                'label' => 'kesehatan',
            ],
            [
                'keywords' => ['otomotif', 'motor', 'mobil', 'bengkel', 'oli'],
                'file' => 'produk-otomotif.png',
                'label' => 'otomotif',
            ],
            [
                'keywords' => ['pertanian', 'pupuk', 'tani', 'agrikultur', 'agro'],
                'file' => 'produk-pertanian.png',
                'label' => 'pertanian',
            ],
        ];

        foreach ($productMap as $product) {
            foreach ($product['keywords'] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return [
                        'file' => $product['file'],
                        'label' => $product['label'],
                    ];
                }
            }
        }

        foreach ($genericProductIntentKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return [
                    'file' => 'produk-best.png',
                    'label' => 'BEST',
                ];
            }
        }

        if (str_contains($normalized, 'best') && str_contains($normalized, 'produk')) {
            return [
                'file' => 'produk-best.png',
                'label' => 'BEST',
            ];
        }

        return null;
    }

    private function formatBotReplies(array $messages, Conversation $conversation, bool $broadcast = true): array
    {
        $formatted = [];
        foreach ($messages as $message) {
            $formatted[] = [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
                'message_type' => $message->message_type,
                'content' => $message->content,
                'created_at' => $message->created_at->format('H:i'),
            ];

            if ($broadcast) {
                try {
                    broadcast(new MessageSent($message));
                } catch (\Exception $e) {
                }
            }
        }

        if ($broadcast && $conversation->wasChanged('bot_phase')) {
            try {
                broadcast(new ConversationStatusChanged($conversation, 'system'));
            } catch (\Exception $e) {
            }
        }

        $submenus = [];
        if ($conversation->bot_phase === 'awaiting_submenu') {
            // Find the last user message to determine which parent menu was selected
            $lastUserMessage = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'user')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastUserMessage) {
                $parentMenu = $this->findRootMenuSelection($lastUserMessage->content);
                if ($parentMenu) {
                    $submenus = BotMenu::where('parent_id', $parentMenu->id)
                        ->orderBy('order_index')
                        ->get(['id', 'label', 'parent_id'])
                        ->map(fn($m) => ['id' => $m->id, 'label' => $m->label, 'parent_id' => $m->parent_id]);
                }
            }
            
            // Fallback: if we can't find by label (e.g. from WhatsApp buttons), 
            // we could look at the last admin message to see if it was a menu prompt
        }

        return [
            'formatted' => $formatted,
            'messages' => $messages,
            'submenus' => $submenus,
        ];
    }

    private function buildMainMenuPrompt(bool $includeGreeting = false): string
    {
        return $this->buildMainMenuPromptWithGreeting($includeGreeting);
    }

    private function buildInitialPrompt(): string
    {
        if ($this->usesBotMenuFlow()) {
            return $this->buildMainMenuPromptWithGreeting(true);
        }

        return $this->buildCategoryPrompt();
    }

    private function buildMainMenuPromptWithGreeting(bool $includeGreeting = true): string
    {
        $menus = $this->rootMenus();
        
        $lines = [];

        if ($includeGreeting) {
            $lines[] = trim((string) Setting::get(
                'bot_greeting_message',
                'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?',
            ));
            $lines[] = "";
        }

        if ($menus->isEmpty()) {
             $lines[] = "Menu utama belum tersedia saat ini.";
        } else {
            $lines[] = "Silakan pilih salah satu menu utama berikut:";
            $lines[] = "";
            
            foreach ($menus as $index => $menu) {
                $lines[] = "[" . ($index + 1) . "] " . $menu->label;
            }
        }
        
        $lines[] = "";
        $lines[] = "Balas dengan angka atau nama menu yang kamu pilih.";

        return implode("\n", $lines);
    }

    private function buildLinkMenuResponse(BotMenu $menu): string
    {
        $menuLabel = trim((string) $menu->label);
        $menuLabelLower = mb_strtolower($menuLabel, 'UTF-8');
        $menuList = $this->buildMainMenuListOnly();

        if ($this->isYoutubeLink($menu)) {
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
        $menus = $this->rootMenus();
        if ($menus->isEmpty()) {
            return 'Menu utama belum tersedia saat ini.';
        }

        return $menus
            ->values()
            ->map(fn ($menu, $index) => '[' . ($index + 1) . '] ' . $menu->label)
            ->implode("\n");
    }

    private function isYoutubeLink(BotMenu $menu): bool
    {
        $url = mb_strtolower((string) $menu->action_value, 'UTF-8');

        return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
    }

    private function buildCategoryPrompt(bool $includeIntro = true): string
    {
        $categories = config('chat.complaint_categories', []);
        if (empty($categories)) {
            return 'Kategori kendala belum tersedia saat ini.';
        }

        $lines = [];

        if ($includeIntro) {
            $lines[] = '👋 Selamat datang di BRILLIAN.BIS. Pilih kategori kendala Anda:';
            $lines[] = '';
        } else {
            $lines[] = 'Silakan pilih kembali kategori kendala Anda:';
            $lines[] = '';
        }

        foreach ($categories as $category) {
            $lines[] = "- {$category}";
        }

        return implode("\n", $lines);
    }

    private function buildSubmenuPrompt(?int $parentId = null): ?string
    {
        if (!$parentId) {
            return null;
        }

        $children = BotMenu::where('parent_id', $parentId)->orderBy('order_index')->get(['label']);
        if ($children->isEmpty()) {
            return null;
        }

        $lines = ['Pilih salah satu submenu berikut:'];
        foreach ($children as $index => $child) {
            $lines[] = '[' . ($index + 1) . '] ' . $child->label;
        }
        $lines[] = '';
        $lines[] = 'Balas dengan nama submenu yang kamu pilih.';

        return implode("\n", $lines);
    }

    public function usesBotMenuFlow(): bool
    {
        $count = $this->rootMenus()->count();
        if ($count === 0) {
             \Illuminate\Support\Facades\Log::warning('usesBotMenuFlow: Root menus count is 0');
        }
        return $count > 0;
    }

    private function buildMainMenuButtons(): array
    {
        $menus = $this->rootMenus();
        $buttons = [];
        // WhatsApp allows max 3 reply buttons
        foreach ($menus->take(3) as $menu) {
            $buttons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => 'menu_' . $menu->id,
                    'title' => mb_strimwidth($menu->label, 0, 20, '')
                ]
            ];
        }
        return $buttons;
    }

    private function buildSubmenuButtons(int $parentId): array
    {
        $children = BotMenu::where('parent_id', $parentId)->orderBy('order_index')->get(['id', 'label']);
        $buttons = [];
        foreach ($children->take(3) as $child) {
            $buttons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => 'menu_' . $child->id,
                    'title' => mb_strimwidth($child->label, 0, 20, '')
                ]
            ];
        }
        return $buttons;
    }

    private function rootMenus()
    {
        return BotMenu::whereNull('parent_id')
            ->orderBy('order_index')
            ->get(['id', 'label', 'action_type', 'action_value', 'message_response']);
    }

    private function findRootMenuByLabel(string $label): ?BotMenu
    {
        return BotMenu::whereNull('parent_id')
            ->whereRaw('LOWER(label) = ?', [mb_strtolower(trim($label))])
            ->first();
    }

    private function findRootMenuSelection(string $input): ?BotMenu
    {
        $menu = $this->findRootMenuByLabel($input);
        if ($menu) {
            return $menu;
        }

        $index = $this->extractNumericSelection($input);
        if ($index === null) {
            return null;
        }

        return $this->rootMenus()->values()->get($index - 1);
    }

    private function findMenuByLabel(string $label): ?BotMenu
    {
        return BotMenu::whereRaw('LOWER(label) = ?', [mb_strtolower(trim($label))])
            ->first();
    }

    private function findSubmenuSelection(string $input, ?int $parentId = null): ?BotMenu
    {
        if ($parentId) {
            $child = BotMenu::where('parent_id', $parentId)
                ->whereRaw('LOWER(label) = ?', [mb_strtolower(trim($input))])
                ->first();

            if ($child) {
                return $child;
            }

            $index = $this->extractNumericSelection($input);
            if ($index !== null) {
                return BotMenu::where('parent_id', $parentId)
                    ->orderBy('order_index')
                    ->get()
                    ->values()
                    ->get($index - 1);
            }
        }

        return $this->findMenuByLabel($input);
    }

    private function extractNumericSelection(string $input): ?int
    {
        if (preg_match('/^\s*\[?(\d+)\]?\s*$/', trim($input), $matches) !== 1) {
            return null;
        }

        $value = (int) ($matches[1] ?? 0);

        return $value > 0 ? $value : null;
    }

    private function resolveAwaitingSubmenuParentMenu(Conversation $conversation): ?BotMenu
    {
        $lastUserMessage = Message::where('conversation_id', $conversation->id)
            ->where('sender_type', 'user')
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        if (!$lastUserMessage) {
            return null;
        }

        return $this->findRootMenuSelection($lastUserMessage->content);
    }
}
