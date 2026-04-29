<?php

namespace App\Http\Controllers;

use App\Models\BotMenu;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationFlowService;
use App\Services\OpenClawWhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

            // Handle rejected mode (e.g. closed) — send the rejection message to WhatsApp
            if (!empty($result['rejected'])) {
                $rejectText = $result['reject_message'] ?? '';
                if ($rejectText !== '') {
                    $this->openClawWhatsappService->sendText($user, $rejectText);
                }
                return response()->json(['status' => 'ok', 'rejected' => true]);
            }

            $conversation = $result['conversation'];
            $isNewConversation = true;

            if (!$this->shouldProcessInitialInbound($conversation, $message['content'])) {
                $this->sendBotMessagesToWhatsapp($user, $result['bot_messages']);

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

        $this->sendBotMessagesToWhatsapp($user, $result['bot_messages']);

        return response()->json([
            'status' => 'ok',
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    private function sendBotMessagesToWhatsapp(User $user, array $messages): void
    {
        $total = count($messages);

        for ($index = 0; $index < $total; $index++) {
            $current = $messages[$index];
            $next = $messages[$index + 1] ?? null;
            $afterNext = $messages[$index + 2] ?? null;

            if (
                $current->message_type === 'text' &&
                $next &&
                in_array($next->message_type, ['image', 'file'], true) &&
                $afterNext &&
                $afterNext->message_type === 'text' &&
                !empty($afterNext->whatsapp_buttons ?? []) &&
                empty($current->whatsapp_buttons ?? []) &&
                trim((string) $current->content) !== ''
            ) {
                $captionParts = [trim((string) $current->content), trim((string) $afterNext->content)];
                $caption = trim(implode("\n\n", array_filter($captionParts)));
                $this->openClawWhatsappService->sendMedia(
                    $user,
                    $next->content,
                    $caption,
                    $afterNext->whatsapp_buttons ?? []
                );
                $index += 2;
                continue;
            }

            if (
                $current->message_type === 'text' &&
                $next &&
                in_array($next->message_type, ['image', 'file'], true) &&
                empty($current->whatsapp_buttons ?? []) &&
                trim((string) $current->content) !== ''
            ) {
                $buttons = isset($next->whatsapp_buttons) ? $next->whatsapp_buttons : [];
                $this->openClawWhatsappService->sendMedia($user, $next->content, $current->content, $buttons);
                $index++;
                continue;
            }

            $this->sendBotMessageToWhatsapp($user, $current);
        }
    }

    private function sendBotMessageToWhatsapp(User $user, $message): void
    {
        $buttons = $this->filterWhatsappButtonsForMessage($message);

        if ($message->message_type === 'image' || $message->message_type === 'file') {
            $this->openClawWhatsappService->sendMedia($user, $message->content, '', $buttons);
            return;
        }

        $this->openClawWhatsappService->sendText($user, $message->content, $buttons);
    }

    private function filterWhatsappButtonsForMessage($message): array
    {
        return isset($message->whatsapp_buttons) ? $message->whatsapp_buttons : [];
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
            'context.fileUrl',
            'mediaUrl',
            'fileUrl',
            'context.attachment.url',
            'context.attachment.mediaUrl',
            'context.attachment.fileUrl',
            'context.attachment.downloadUrl',
            'attachment.url',
            'attachment.mediaUrl',
            'attachment.fileUrl',
            'attachment.downloadUrl',
            'context.media.url',
            'context.media.mediaUrl',
            'context.media.fileUrl',
            'media.url',
            'media.mediaUrl',
            'media.fileUrl',
            'message.mediaUrl',
            'message.fileUrl',
            'message.attachment.url',
            'message.attachment.mediaUrl',
            'message.attachment.fileUrl',
            'message.attachment.downloadUrl',
            'context.attachments.0.url',
            'context.attachments.0.mediaUrl',
            'context.attachments.0.fileUrl',
            'context.attachments.0.downloadUrl',
            'context.message.attachments.0.url',
            'context.message.attachments.0.mediaUrl',
            'context.message.attachments.0.fileUrl',
            'context.message.attachments.0.downloadUrl',
            'context.metadata.attachment.url',
            'context.metadata.attachment.mediaUrl',
            'context.metadata.attachment.fileUrl',
            'context.metadata.attachment.downloadUrl',
            'context.metadata.attachments.0.url',
            'context.metadata.attachments.0.mediaUrl',
            'context.metadata.attachments.0.fileUrl',
            'context.metadata.attachments.0.downloadUrl',
            'context.metadata.mediaUrl',
            'context.metadata.fileUrl',
        ]) ?? '');

        $mediaPath = (string) ($this->extractValue($payload, [
            'context.mediaPath',
            'context.filePath',
            'mediaPath',
            'filePath',
            'context.attachment.path',
            'context.attachment.filePath',
            'context.attachment.localPath',
            'context.attachment.savedPath',
            'attachment.path',
            'attachment.filePath',
            'attachment.localPath',
            'attachment.savedPath',
            'context.media.path',
            'context.media.filePath',
            'media.path',
            'media.filePath',
            'message.attachment.path',
            'message.attachment.filePath',
            'context.attachments.0.path',
            'context.attachments.0.filePath',
            'context.message.attachments.0.path',
            'context.message.attachments.0.filePath',
            'context.metadata.attachment.path',
            'context.metadata.attachment.filePath',
            'context.metadata.attachments.0.path',
            'context.metadata.attachments.0.filePath',
        ]) ?? '');

        $mediaMarkerType = $this->extractMediaMarkerType($content);

        if ($mediaUrl !== '') {
            $content = $mediaUrl;
            $messageType = $this->normalizeInboundMessageType($messageType, $mediaMarkerType, true, $mediaUrl);
        } elseif ($mediaPath !== '') {
            $importedMediaUrl = $this->importInboundMediaFromLocalPath($mediaPath);
            if ($importedMediaUrl !== null) {
                $content = $importedMediaUrl;
                $messageType = $this->normalizeInboundMessageType($messageType, $mediaMarkerType, true, $importedMediaUrl);
            } elseif ($mediaMarkerType !== null) {
                $content = $this->buildMissingMediaPlaceholder($mediaMarkerType);
                $messageType = $this->normalizeInboundMessageType($messageType, $mediaMarkerType, false);
            }
        } elseif ($mediaMarkerType !== null) {
            $content = $this->buildMissingMediaPlaceholder($mediaMarkerType);
            $messageType = $this->normalizeInboundMessageType($messageType, $mediaMarkerType, false);
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

    private function extractMediaMarkerType(string $content): ?string
    {
        $normalized = trim(mb_strtolower($content));

        if (preg_match('/^<media:([a-z0-9_-]+)>$/', $normalized, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? null;
    }

    private function normalizeInboundMessageType(string $messageType, ?string $mediaMarkerType, bool $hasMediaUrl, ?string $mediaUrl = null): string
    {
        $normalizedType = trim(mb_strtolower($messageType));
        $markerType = trim(mb_strtolower((string) $mediaMarkerType));

        if (
            str_contains($normalizedType, 'image')
            || $markerType === 'image'
            || $markerType === 'photo'
            || $this->isImageMediaUrl($mediaUrl)
        ) {
            return 'image';
        }

        if ($hasMediaUrl || $markerType !== '') {
            return 'file';
        }

        return 'text';
    }

    private function isImageMediaUrl(?string $mediaUrl): bool
    {
        $url = trim(mb_strtolower((string) $mediaUrl));
        if ($url === '') {
            return false;
        }

        return preg_match('/\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/', $url) === 1;
    }

    private function importInboundMediaFromLocalPath(string $mediaPath): ?string
    {
        $realPath = realpath($mediaPath);
        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        $allowedRoots = array_values(array_filter(array_map(
            fn ($path) => $path ? realpath($path) : false,
            [
                env('OPENCLAW_STATE_DIR'),
                storage_path('app'),
            ]
        )));

        $normalizedPath = str_replace('\\', '/', $realPath);
        $isAllowed = collect($allowedRoots)->contains(function ($root) use ($normalizedPath) {
            $normalizedRoot = str_replace('\\', '/', (string) $root);

            return $normalizedRoot !== '' && str_starts_with($normalizedPath, rtrim($normalizedRoot, '/') . '/');
        });

        if (!$isAllowed) {
            Log::warning('OpenClaw inbound media path diabaikan karena berada di luar root yang diizinkan.', [
                'path' => $realPath,
            ]);

            return null;
        }

        $extension = strtolower((string) pathinfo($realPath, PATHINFO_EXTENSION));
        if ($extension === '') {
            $mime = @mime_content_type($realPath) ?: '';
            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'bin',
            };
        }

        $relativePath = 'whatsapp-inbound/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;

        try {
            Storage::disk('public')->put($relativePath, file_get_contents($realPath));

            return Storage::disk('public')->url($relativePath);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengimpor media inbound WhatsApp dari path lokal.', [
                'path' => $realPath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildMissingMediaPlaceholder(string $mediaMarkerType): string
    {
        $type = trim(mb_strtolower($mediaMarkerType));

        return 'whatsapp-media-placeholder:' . ($type !== '' ? $type : 'file');
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
