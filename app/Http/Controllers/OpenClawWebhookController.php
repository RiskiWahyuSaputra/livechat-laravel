<?php

namespace App\Http\Controllers;

use App\Models\BotMenu;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationFlowService;
use App\Services\OpenClawWhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenClawWebhookController extends Controller
{
    public function __construct(
        protected ConversationFlowService $conversationFlowService,
        protected OpenClawWhatsappService $openClawWhatsappService
    ) {
    }

    public function handleWhatsapp(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        if (!$this->openClawWhatsappService->isEnabled()) {
            return response()->json(['status' => 'ignored', 'reason' => 'bridge disabled']);
        }

        $payload = $request->all();
        Log::info('OpenClaw WhatsApp payload diterima.', $payload);

        $message = $this->normalizePayload($payload);
        if (!$message) {
            return response()->json(['status' => 'ignored', 'reason' => 'payload not supported']);
        }

        if ($message['from_me']) {
            return response()->json(['status' => 'ignored', 'reason' => 'from me']);
        }

        if ($message['channel'] !== 'whatsapp') {
            return response()->json(['status' => 'ignored', 'reason' => 'not whatsapp']);
        }

        if ($message['external_id'] && Cache::has('openclaw_whatsapp_inbound_' . $message['external_id'])) {
            return response()->json(['status' => 'duplicate']);
        }

        if ($message['external_id']) {
            Cache::put('openclaw_whatsapp_inbound_' . $message['external_id'], true, now()->addMinutes(30));
        }

        $user = $this->findOrCreateWhatsappUser($message);

        // Mark incoming message as read
        if ($message['external_id']) {
            $this->openClawWhatsappService->markAsRead($user, $message['external_id']);
        }

        $conversation = $this->resolveConversation($user, $message['content']);

        $isNewConversation = false;
        if (!$conversation) {
            $usesBotMenu = $this->conversationFlowService->usesBotMenuFlow();
            Log::info('Creating new conversation for WhatsApp user.', [
                'user_id' => $user->id,
                'uses_bot_menu' => $usesBotMenu
            ]);
            $result = $this->conversationFlowService->createConversation($user);
            $conversation = $result['conversation'];
            $isNewConversation = true;

            if (!$this->shouldProcessInitialInbound($conversation, $message['content'])) {
                foreach ($result['bot_messages'] as $botMessage) {
                    $this->sendBotMessageToWhatsapp($user, $botMessage);
                }

                return response()->json([
                    'status' => 'ok',
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        $result = $this->conversationFlowService->processInboundMessage(
            user: $user,
            conversation: $conversation,
            content: $message['content'],
            messageType: $message['message_type'],
            broadcast: true
        );

        foreach ($result['bot_messages'] as $botMessage) {
            $this->sendBotMessageToWhatsapp($user, $botMessage);
        }

        return response()->json([
            'status' => 'ok',
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    private function sendBotMessageToWhatsapp(User $user, $message): void
    {
        $buttons = isset($message->whatsapp_buttons) ? $message->whatsapp_buttons : [];

        if ($message->message_type === 'image' || $message->message_type === 'file') {
            $this->openClawWhatsappService->sendMedia($user, $message->content, '', $buttons);
            return;
        }

        $this->openClawWhatsappService->sendText($user, $message->content, $buttons);
    }

    private function findOrCreateWhatsappUser(array $message): User
    {
        $contact = $this->normalizeContact($message['from']);
        $email = ltrim($contact ?? ('wa_' . uniqid()), '+') . '@livechat.best';

        $user = User::where('contact', $contact)->orWhere('email', $email)->first();
        if ($user) {
            $user->update([
                'name' => $message['sender_name'] ?: $user->name,
                'origin' => 'WhatsApp',
                'is_online' => true,
            ]);

            return $user;
        }

        return User::create([
            'name' => $message['sender_name'] ?: 'Pelanggan WhatsApp',
            'email' => $email,
            'contact' => $contact,
            'origin' => 'WhatsApp',
            'password' => bcrypt('guest123'),
            'is_online' => true,
        ]);
    }

    private function normalizePayload(array $payload): ?array
    {
        $context = $payload['context'] ?? $payload['message'] ?? $payload;

        $channel = strtolower((string) ($this->extractValue($payload, ['context.channelId', 'channel', 'context.channel', 'source.channel']) ?? 'whatsapp'));
        if ($channel !== 'whatsapp') {
            return null;
        }

        $fromMe = (bool) ($this->extractValue($payload, ['context.fromMe', 'fromMe', 'message.fromMe']) ?? false);
        $from = (string) ($this->extractValue($payload, [
            'context.from',
            'from',
            'context.sender',
            'sender',
            'context.remoteJid',
            'remoteJid',
            'context.userId',
        ]) ?? '');

        $content = (string) ($this->extractValue($payload, [
            'context.bodyForAgent',
            'context.content',
            'content',
            'text',
            'body',
            'message.text',
            'message.body',
        ]) ?? '');

        $messageType = strtolower((string) ($this->extractValue($payload, [
            'context.messageType',
            'messageType',
            'context.type',
            'type',
        ]) ?? 'text'));

        $mediaUrl = (string) ($this->extractValue($payload, [
            'context.mediaUrl',
            'mediaUrl',
            'context.attachment.url',
            'attachment.url',
        ]) ?? '');

        if ($content === '' && $mediaUrl !== '') {
            $content = $mediaUrl;
            $messageType = str_contains($messageType, 'image') ? 'image' : 'file';
        }

        if ($content === '' || $from === '') {
            return null;
        }

        if (!in_array($messageType, ['text', 'image', 'file'], true)) {
            $messageType = $mediaUrl !== '' ? 'file' : 'text';
        }

        return [
            'channel' => $channel,
            'from_me' => $fromMe,
            'from' => $from,
            'sender_name' => (string) ($this->extractValue($payload, [
                'context.senderName',
                'senderName',
                'context.pushName',
                'pushName',
                'context.metadata.senderName',
            ]) ?? ''),
            'content' => $content,
            'message_type' => $messageType,
            'external_id' => (string) ($this->extractValue($payload, [
                'context.messageId',
                'messageId',
                'id',
                'context.id',
            ]) ?? ''),
        ];
    }

    private function extractValue(array $payload, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeContact(string $from): string
    {
        $from = preg_replace('/@.+$/', '', $from);
        $from = preg_replace('/[^\d+]/', '', $from);

        if ($from === '') {
            return 'unknown_' . uniqid();
        }

        if (!str_starts_with($from, '+') && !str_starts_with($from, '0')) {
            return '+' . $from;
        }

        return $from;
    }

    private function isAuthorized(Request $request): bool
    {
        $expected = $this->openClawWhatsappService->getBridgeToken();
        if ($expected === '') {
            return true;
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-OpenClaw-Bridge-Token')
            ?? $request->input('token');

        if ($provided !== null && $provided !== '') {
            return hash_equals($expected, (string) $provided);
        }

        // Local OpenClaw bridge traffic may arrive without the managed hook env
        // injected, so allow loopback requests in local deployments.
        return $this->isLoopbackRequest($request);
    }

    private function isLoopbackRequest(Request $request): bool
    {
        $ips = array_filter([
            $request->ip(),
            $request->server('REMOTE_ADDR'),
        ]);

        foreach ($ips as $ip) {
            if (in_array($ip, ['127.0.0.1', '::1'], true)) {
                return true;
            }
        }

        return false;
    }

    private function shouldProcessInitialInbound(Conversation $conversation, string $content): bool
    {
        $normalized = mb_strtolower(trim($content));
        if ($normalized === '') {
            return false;
        }

        if ($normalized === 'menu') {
            return true;
        }

        if ($conversation->bot_phase === 'awaiting_main_menu') {
            return BotMenu::whereNull('parent_id')
                ->whereRaw('LOWER(label) = ?', [$normalized])
                ->exists();
        }

        if ($conversation->bot_phase === 'awaiting_category') {
            foreach (config('chat.complaint_categories', []) as $category) {
                if (mb_strtolower(trim((string) $category)) === $normalized) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    private function resolveConversation(User $user, string $content): ?Conversation
    {
        $conversation = $user->conversations()
            ->whereIn('status', ['pending', 'active', 'queued'])
            ->latest('last_message_at')
            ->first();

        if (!$conversation) {
            return null;
        }

        if ($this->shouldStartFreshConversation($conversation, $content)) {
            $this->closeConversationForWhatsappReset($conversation, $content);
            return null;
        }

        return $conversation;
    }

    private function shouldStartFreshConversation(Conversation $conversation, string $content): bool
    {
        if ($this->hasConversationExpired($conversation)) {
            return true;
        }

        if (!$this->isBotManagedConversation($conversation)) {
            return false;
        }

        $normalized = $this->normalizeIncomingText($content);
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, config('chat.whatsapp_reset_commands', []), true)) {
            return true;
        }

        if (in_array($normalized, config('chat.whatsapp_reset_greetings', []), true)) {
            return true;
        }

        if ($conversation->bot_phase !== 'awaiting_main_menu' && $this->matchesRootMenuLabel($normalized)) {
            return true;
        }

        return false;
    }

    private function hasConversationExpired(Conversation $conversation): bool
    {
        if (!$conversation->last_message_at) {
            return false;
        }

        $reuseMinutes = max(1, (int) config('chat.whatsapp_conversation_reuse_minutes', 30));

        return $conversation->last_message_at->diffInMinutes(now()) >= $reuseMinutes;
    }

    private function isBotManagedConversation(Conversation $conversation): bool
    {
        return is_null($conversation->admin_id);
    }

    private function closeConversationForWhatsappReset(Conversation $conversation, string $content): void
    {
        Log::info('OpenClaw WhatsApp conversation direset.', [
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'last_message_at' => $conversation->last_message_at?->toDateTimeString(),
            'trigger' => $this->normalizeIncomingText($content),
        ]);

        $conversation->update([
            'status' => 'closed',
            'queue_position' => null,
            'admin_id' => null,
        ]);
    }

    private function normalizeIncomingText(string $content): string
    {
        return mb_strtolower(trim($content));
    }

    private function matchesRootMenuLabel(string $normalized): bool
    {
        return BotMenu::whereNull('parent_id')
            ->whereRaw('LOWER(label) = ?', [$normalized])
            ->exists();
    }
}
