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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConversationFlowService
{
    public function __construct(
        protected GeminiService $geminiService,
        protected OpenClawWhatsappService $openClawWhatsappService,
    ) {
    }

    public function getSystemMode(): string
    {
        $mode = Setting::get('system_mode', 'office_hour');

        if ($mode === 'office_hour') {
            if (!$this->isWithinOfficeHours()) {
                return 'closed';
            }
        }

        $validModes = ['office_hour', 'outside_office_hour', 'closed'];
        return in_array($mode, $validModes) ? $mode : 'office_hour';
    }

    /**
     * Check if current time is within office hours.
     */
    public function isWithinOfficeHours(): bool
    {
        $hours = $this->getOfficeHoursForToday();

        if (!$hours['is_active']) {
            return false;
        }

        try {
            $now = now($hours['timezone'])->format('H:i');
        } catch (\Exception $e) {
            $now = now()->format('H:i');
        }

        return $now >= $hours['start'] && $now <= $hours['end'];
    }

    public function createConversation(User $user, ?int $selectedMenuId = null): array
    {
        $systemMode = $this->getSystemMode();

        // Check system mode first — reject immediately if closed
        if ($systemMode === 'closed') {
            $defaultMessage = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
            $rejectMessage = Setting::get('bot_greeting_closed', $defaultMessage) ?? $defaultMessage;

            return [
                'conversation' => null,
                'bot_messages' => [],
                'rejected' => true,
                'reject_message' => $rejectMessage,
            ];
        }

        // Outside office hour: create conversation but only serve via AI, no queue
        if ($systemMode === 'outside_office_hour') {
            return $this->createOutsideOfficeHourConversation($user);
        }

        $availableAdmin = Admin::where('status', '!=', 'offline')->get()->first(fn($admin) => $admin->canTakeNewChat());
        $anyOnline = Admin::whereIn('status', ['online', 'busy'])->exists();

        $status = 'pending';
        $needsQueuePosition = false;

        if ($anyOnline && !$availableAdmin) {
            $status = 'queued';
            $needsQueuePosition = true;
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

        $conversation = DB::transaction(function () use ($user, $status, $botPhase, $menu, $needsQueuePosition) {
            $conv = Conversation::create([
                'user_id' => $user->id,
                'status' => $status,
                'bot_phase' => $botPhase,
                'selected_menu_id' => ($menu && $menu->action_type === 'submenu') ? $menu->id : null,
                'queue_position' => null,
                'last_message_at' => now(),
            ]);

            if ($needsQueuePosition) {
                $this->reorderQueue();
                $conv = $conv->fresh();
            }

            return $conv;
        });

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
            'bot_messages' => $createdMessages,
            'rejected' => false,
            'reject_message' => '',
        ];
    }

    public function processInboundMessage(User $user, Conversation $conversation, string $content, string $messageType = 'text', bool $broadcast = true): array
    {
        $systemMode = $this->getSystemMode();

        if ($systemMode === 'closed') {
            $defaultMessage = 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.';
            $rejectMessage = Setting::get('bot_greeting_closed', $defaultMessage) ?? $defaultMessage;

            return [
                'message' => null,
                'bot_replies' => [],
                'bot_messages' => [],
                'bot_phase' => $conversation->bot_phase,
                'rejected' => true,
                'reject_message' => $rejectMessage,
            ];
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message_type' => $messageType,
            'content' => $content,
        ]);

        $conversation->update([
            'last_message_at' => now(),
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
                // Requirement 3.4, 3.5: Prevent queuing when outside_office_hour
                if ($this->getSystemMode() === 'outside_office_hour') {
                    $hours = $this->getOfficeHoursForToday();
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Mohon maaf, Agent kami saat ini tidak tersedia karena di luar jam kerja. Silakan hubungi kembali pada jam operasional kami: {$hours['start']} - {$hours['end']}. Sementara itu, saya (BEST AI) siap membantu pertanyaan Anda. 😊",
                    ]);
                    return $this->formatBotReplies($newBotMessages, $conversation, $broadcast);
                }
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
                    $conversation->update(['bot_phase' => 'off']);
                    $this->reorderQueue();
                    $conversation = $conversation->fresh();
                    $queuePosition = $conversation->queue_position ?? Conversation::whereIn('status', ['pending', 'queued'])->count();
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Oke, saya sambungkan ke Agent ya. Kamu sekarang ada di antrean ke-{$queuePosition}. Tunggu sebentar ya.",
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
                // Requirement 3.4, 3.5: Prevent queuing when outside_office_hour
                if ($this->getSystemMode() === 'outside_office_hour') {
                    $hours = $this->getOfficeHoursForToday();
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Mohon maaf, Agent kami saat ini tidak tersedia karena di luar jam kerja. Silakan hubungi kembali pada jam operasional kami: {$hours['start']} - {$hours['end']}. Sementara itu, saya (BEST AI) siap membantu pertanyaan Anda. 😊",
                    ]);
                } elseif ($user->name === 'Guest') {
                    $conversation->update(['bot_phase' => 'require_registration']);
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => 'Oke, saya sambungkan ke Agent ya. Silakan isi form data diri dulu di layar kamu.',
                    ]);
                } else {
                    $conversation->update(['bot_phase' => 'off']);
                    $this->reorderQueue();
                    $conversation = $conversation->fresh();
                    $queuePosition = $conversation->queue_position ?? Conversation::whereIn('status', ['pending', 'queued'])->count();
                    $newBotMessages[] = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => 0,
                        'sender_type' => 'admin',
                        'message_type' => 'text',
                        'content' => "Oke, saya sambungkan ke Agent ya. Kamu sekarang ada di antrean ke-{$queuePosition}. Tunggu sebentar ya.",
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
                'sender_id' => 0,
                'sender_type' => 'admin',
                'message_type' => 'text',
                'content' => "Silakan isi data diri Anda melalui link berikut agar dapat terhubung dengan Agent:\n\n" . $regUrl,
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
                    $conversation->update(['bot_phase' => 'awaiting_submenu', 'selected_menu_id' => $menu->id]);
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
                $conversation->update(['bot_phase' => 'off']);
                $this->reorderQueue();
                $conversation = $conversation->fresh();
                $queuePosition = $conversation->queue_position ?? Conversation::whereIn('status', ['pending', 'queued'])->count();

                $newBotMessages = array_merge($newBotMessages, $this->createAiReplyMessages($conversation, $userMessage, $aiResponse));
                $newBotMessages[] = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => 0,
                    'sender_type' => 'admin',
                    'message_type' => 'text',
                    'content' => "Pesan diterima. Antrean ke-{$queuePosition}. Sambil menunggu, silakan baca jawaban AI di atas.",
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
        $aiResponse = $this->sanitizeAiResponse($aiResponse);

        if ($productImage && !$isFallbackResponse) {
            $aiResponse = $this->normalizeAiResponseForProductImage($aiResponse, $productImage['label']);
        }

        if ($productImage && $this->isOutOfScopeProductRefusal($aiResponse)) {
            $aiResponse = $productImage['description'] ?? "Produk {$productImage['label']} termasuk bagian dari PT BEST CORPORATION SYARIAH. Kalau kamu mau, saya juga bisa bantu tampilkan gambarnya.";
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
                'content' => asset('images/' . $productImage['path']),
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

        if (
            in_array($normalized, [
                '2',
                'agent',
                'hubungi agent',
                'hubungin agent',
                'hubungi cs',
                'hubungin cs',
                'cs',
                'customer service',
            ], true)
        ) {
            return true;
        }

        return str_contains($normalized, 'agent')
            || str_contains($normalized, 'customer service');
    }

    private function wantsContinueWithAi(string $userMessage): bool
    {
        $normalized = $this->normalizeBotInput($userMessage);

        if (
            in_array($normalized, [
                '1',
                'lanjut',
                'lanjut tanya',
                'tanya best ai',
                'best ai',
                'tanya ai',
            ], true)
        ) {
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
            $parentMenu = $conversation->selected_menu_id
                ? BotMenu::find($conversation->selected_menu_id)
                : null;

            // Fallback: try to resolve from last user message if selected_menu_id not set
            if (!$parentMenu) {
                $lastUserMessage = Message::where('conversation_id', $conversation->id)
                    ->where('sender_type', 'user')
                    ->orderBy('created_at', 'desc')
                    ->skip(1)
                    ->first();

                if ($lastUserMessage) {
                    $parentMenu = $this->findRootMenuSelection($lastUserMessage->content);
                }
            }

            if ($parentMenu) {
                $submenus = BotMenu::where('parent_id', $parentMenu->id)
                    ->orderBy('order_index')
                    ->get(['id', 'label', 'parent_id'])
                    ->map(fn($m) => ['id' => $m->id, 'label' => $m->label, 'parent_id' => $m->parent_id]);
            }
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
            $mode = $this->getSystemMode();
            $greetingKey = 'bot_greeting_' . $mode;
            $lines[] = trim((string) Setting::get(
                $greetingKey,
                Setting::get('bot_greeting_message', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?'),
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

    private function buildSubmenuPrompt(?int $parentId = null): ?string
    {
        if (!$parentId)
            return null;
        $children = BotMenu::where('parent_id', $parentId)->orderBy('order_index')->get(['label']);
        if ($children->isEmpty())
            return null;

        $lines = ["Silakan pilih salah satu submenu berikut:", ""];
        foreach ($children as $index => $child) {
            $lines[] = "[" . ($index + 1) . "] " . $child->label;
        }

        $lines[] = "";
        $lines[] = "Balas dengan angka atau nama menu pilihan Anda.";

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
            ->map(fn($menu, $index) => '[' . ($index + 1) . '] ' . $menu->label)
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
            ->where('flow_type', $this->getSystemMode())
            ->orderBy('order_index')
            ->get(['id', 'label', 'action_type', 'action_value', 'message_response', 'flow_type']);
    }

    private function findRootMenuByLabel(string $label): ?BotMenu
    {
        return BotMenu::whereNull('parent_id')
            ->where('flow_type', $this->getSystemMode())
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

    /**
     * Get office hours for today based on per-day settings and timezone.
     */
    public function getOfficeHoursForToday(): array
    {
        $timezone = Setting::get('office_hours_timezone', 'Asia/Jakarta');
        try {
            $now = now($timezone);
        } catch (\Exception $e) {
            $now = now();
        }
        $day = strtolower($now->format('l'));

        $isActive = Setting::get("office_hours_{$day}_active", in_array($day, ['saturday', 'sunday']) ? '0' : '1');
        $start = Setting::get("office_hours_{$day}_start", Setting::get('office_hours_start', '08:00'));
        $end = Setting::get("office_hours_{$day}_end", Setting::get('office_hours_end', '17:00'));

        return [
            'is_active' => $isActive == '1',
            'start' => $start,
            'end' => $end,
            'timezone' => $timezone
        ];
    }

    /**
     * Notify all queued conversations that agent service is temporarily unavailable.
     *
     * Called when system_mode changes to `outside_office_hour` or `closed`.
     * Finds all conversations with status `queued` or `pending` and admin_id = null,
     * then sends a system message to each informing the customer.
     *
     * Validates: Requirements 6.4
     */
    public function notifyQueuedConversationsOfModeChange(string $newMode): void
    {
        if (!in_array($newMode, ['outside_office_hour', 'closed'])) {
            return;
        }

        if ($newMode === 'closed') {
            $defaultClosed = 'Mohon maaf, layanan Agent kami saat ini tidak tersedia karena sistem sedang ditutup. Kami akan segera kembali melayani Anda.';
            $message = Setting::get('bot_greeting_closed', $defaultClosed) ?? $defaultClosed;
        } else {
            $hours = $this->getOfficeHoursForToday();
            $defaultOutside = 'Mohon maaf, saat ini kami sedang di luar jam kerja. Silakan tinggalkan pesan dan kami akan segera membalas saat jam kerja dimulai.';
            $outsideMessage = Setting::get('bot_greeting_outside_office_hour', $defaultOutside) ?? $defaultOutside;
            $message = $outsideMessage . "\n\nJam operasional kami: {$hours['start']} - {$hours['end']}.";
        }

        $queuedConversations = Conversation::whereIn('status', ['queued', 'pending'])
            ->whereNull('admin_id')
            ->with('customer')
            ->get();

        foreach ($queuedConversations as $conversation) {
            $notif = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 0,
                'sender_type' => 'system',
                'message_type' => 'text',
                'content' => $message,
            ]);

            try {
                broadcast(new MessageSent($notif));
            } catch (\Exception $e) {
                \Log::warning('Broadcast failed for queue notification: ' . $e->getMessage());
            }

            // Also send via WhatsApp if the user came from WhatsApp
            if ($conversation->customer && $conversation->customer->origin === 'WhatsApp') {
                try {
                    $this->openClawWhatsappService->sendText($conversation->customer, $message);
                } catch (\Exception $e) {
                    \Log::warning('WhatsApp notify failed for conversation ' . $conversation->id . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Create a conversation for outside_office_hour mode.
     *
     * Conversation is created with bot_phase = chatting_with_ai so the customer
     * is only served by AI. queue_position stays null and status never becomes
     * 'queued' in this mode.
     *
     * Validates: Requirements 3.1, 3.2, 3.4
     */
    private function createOutsideOfficeHourConversation(User $user): array
    {
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'bot_phase' => 'chatting_with_ai',
            'queue_position' => null,
            'last_message_at' => now(),
        ]);

        $defaultMessage = 'Mohon maaf, saat ini kami sedang di luar jam kerja. Silakan tinggalkan pesan dan kami akan segera membalas saat jam kerja dimulai.';
        $outsideMessage = Setting::get('bot_greeting_outside_office_hour', $defaultMessage) ?? $defaultMessage;

        $hours = $this->getOfficeHoursForToday();

        $fullMessage = $outsideMessage . "\n\nJam operasional kami: {$hours['start']} - {$hours['end']}.";

        $botMsg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 0,
            'sender_type' => 'admin',
            'message_type' => 'text',
            'content' => $fullMessage,
        ]);

        try {
            broadcast(new MessageSent($botMsg));
        } catch (\Exception $e) {
        }

        try {
            broadcast(new ConversationStatusChanged($conversation, 'system'));
        } catch (\Exception $e) {
        }

        return [
            'conversation' => $conversation,
            'bot_messages' => [$botMsg],
            'rejected' => false,
            'reject_message' => '',
        ];
    }

    /**
     * Urutkan ulang posisi antrian setelah ada yang diklaim/ditutup/masuk.
     * Metode ini adalah satu-satunya sumber kebenaran untuk reorder antrian.
     */
    public function reorderQueue(): void
    {
        DB::transaction(function () {
            $queued = Conversation::whereIn('status', ['pending', 'queued'])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($queued as $i => $conv) {
                $conv->update(['queue_position' => $i + 1]);
            }
        });
    }

    /**
     * Hitung posisi antrian untuk satu percakapan berdasarkan created_at FIFO.
     * Metode ini adalah satu-satunya sumber kebenaran untuk perhitungan posisi antrian.
     *
     * Menghitung berapa banyak percakapan pending/queued yang created_at-nya
     * lebih awal atau sama dengan percakapan yang diberikan.
     */
    public function calculateQueuePosition(Conversation $conversation): int
    {
        return Conversation::whereIn('status', ['pending', 'queued'])
            ->where('created_at', '<', $conversation->created_at)
            ->count() + 1;
    }

    private function resolveAwaitingSubmenuParentMenu(Conversation $conversation): ?BotMenu
    {
        // Use stored selected_menu_id — reliable, no guessing from message history
        if ($conversation->selected_menu_id) {
            return BotMenu::find($conversation->selected_menu_id);
        }

        // Fallback: scan last user messages (legacy path, less reliable)
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
