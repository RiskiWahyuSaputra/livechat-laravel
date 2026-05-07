<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConversationSummaryService
{
    public function __construct(protected GeminiService $geminiService)
    {
    }

    public function summarizeConversation(Conversation $conversation): ?array
    {
        $summarySource = $this->buildConversationSummarySource(
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('message_type', '!=', 'whisper')
                ->latest('created_at')
                ->limit(80)
                ->get()
        );

        if (!$summarySource['available']) {
            return null;
        }

        $summary = $this->geminiService->summarizeConversationForCustomer($summarySource['history']);

        if (is_array($summary)) {
            return $summary;
        }

        return $this->buildDeterministicConversationSummary($summarySource['lines']);
    }

    public function formatWhatsappSummary(array $summary): string
    {
        $sentiment = strtoupper((string) ($summary['sentiment'] ?? 'Neutral'));
        $text = trim((string) ($summary['summary'] ?? ''));

        return trim(implode("\n", array_filter([
            'Ringkasan percakapan:',
            $text,
            '',
            'Sentimen: ' . $sentiment,
        ])));
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
                'history' => '',
                'lines' => [],
            ];
        }

        $historyLines = $lines->slice(-40)->values();

        return [
            'available' => true,
            'history' => $historyLines->implode("\n"),
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
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

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
}
