<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;

class MessageSearchService
{
    private const SNIPPET_LENGTH = 140;

    public function search(string $query, array $quickFilters = [], bool $unreadOnly = false): array
    {
        $keyword = trim($query);

        return [
            'contacts' => $this->searchContacts($keyword),
            'groups' => $this->searchGroups($keyword),
            'messages' => $this->searchMessages($keyword, $quickFilters, $unreadOnly),
        ];
    }

    private function searchContacts(string $keyword): array
    {
        if ($keyword === '') {
            return [];
        }

        $needle = '%' . mb_strtolower($keyword) . '%';

        return User::query()
            ->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(contact) LIKE ?', [$needle]);
            })
            ->whereHas('conversations', function ($query) {
                $query->whereIn('status', ['pending', 'queued', 'active']);
            })
            ->select(['id', 'name', 'contact', 'is_online'])
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'contact' => $user->contact,
                    'is_online' => (bool) $user->is_online,
                ];
            })
            ->toArray();
    }

    private function searchGroups(string $keyword): array
    {
        if ($keyword === '') {
            return [];
        }

        $query = Conversation::query()
            ->with(['customer:id,name,contact,is_online', 'admin:id,username'])
            ->whereIn('status', ['pending', 'queued', 'active']);

        $needle = '%' . mb_strtolower($keyword) . '%';
        $query->where(function ($conversationQuery) use ($needle) {
            $conversationQuery->whereHas('customer', function ($customerQuery) use ($needle) {
                $customerQuery->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(contact) LIKE ?', [$needle]);
            })->orWhereHas('messages', function ($messageQuery) use ($needle) {
                $messageQuery->whereRaw('LOWER(content) LIKE ?', [$needle]);
            });
        });

        return $query->orderByDesc('last_message_at')
            ->limit(20)
            ->get()
            ->map(function (Conversation $conversation) {
                return [
                    'id' => $conversation->id,
                    'customer_name' => $conversation->customer?->name,
                    'customer_contact' => $conversation->customer?->contact,
                    'is_online' => (bool) ($conversation->customer?->is_online ?? false),
                    'status' => $conversation->status,
                    'queue_position' => $conversation->queue_position,
                    'admin_name' => $conversation->admin?->username,
                    'last_message_at' => optional($conversation->last_message_at)->toISOString(),
                    'created_at' => optional($conversation->created_at)->toISOString(),
                ];
            })
            ->toArray();
    }

    private function searchMessages(string $keyword, array $quickFilters, bool $unreadOnly): array
    {
        $hasActiveQuickFilter = !empty($quickFilters) || $unreadOnly;
        if ($keyword === '' && !$hasActiveQuickFilter) {
            return [];
        }

        $messagesQuery = Message::query()
            ->with(['conversation.customer:id,name,contact'])
            ->whereHas('conversation', function ($query) {
                $query->whereIn('status', ['pending', 'queued', 'active']);
            });

        if ($keyword !== '') {
            $needle = '%' . mb_strtolower($keyword) . '%';
            $messagesQuery->whereRaw('LOWER(content) LIKE ?', [$needle]);
        }

        if ($unreadOnly) {
            $messagesQuery->where('is_read', false);
        }

        $messages = $messagesQuery
            ->orderByDesc('created_at')
            ->limit(250)
            ->get()
            ->filter(function (Message $message) use ($quickFilters) {
                if (empty($quickFilters)) {
                    return true;
                }

                $bucket = $this->inferMessageBucket($message);
                return in_array($bucket, $quickFilters, true);
            })
            ->values();

        $grouped = [
            'today' => [],
            'yesterday' => [],
            'last_week' => [],
            'last_month' => [],
            'older' => [],
        ];

        foreach ($messages as $message) {
            $groupKey = $this->resolveTimeGroup($message->created_at);
            $grouped[$groupKey][] = [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type,
                'message_type' => $message->message_type,
                'bucket' => $this->inferMessageBucket($message),
                'snippet' => $this->buildSnippet($message->content, $keyword),
                'is_read' => (bool) $message->is_read,
                'created_at' => optional($message->created_at)->toISOString(),
                'customer_name' => $message->conversation?->customer?->name,
            ];
        }

        $result = [];
        foreach (['today', 'yesterday', 'last_week', 'last_month', 'older'] as $key) {
            if (empty($grouped[$key])) {
                continue;
            }

            $result[] = [
                'time_group' => $key,
                'time_group_label' => $this->timeGroupLabel($key),
                'messages' => $grouped[$key],
            ];
        }

        return $result;
    }

    private function buildSnippet(string $content, string $keyword): string
    {
        $clean = trim(strip_tags($content));
        if ($clean === '') {
            return '';
        }

        if ($keyword === '') {
            return mb_strlen($clean) > self::SNIPPET_LENGTH
                ? mb_substr($clean, 0, self::SNIPPET_LENGTH) . ' ...'
                : $clean;
        }

        $position = mb_stripos($clean, $keyword);
        if ($position === false) {
            return mb_strlen($clean) > self::SNIPPET_LENGTH
                ? mb_substr($clean, 0, self::SNIPPET_LENGTH) . ' ...'
                : $clean;
        }

        $start = max(0, $position - 45);
        $slice = mb_substr($clean, $start, self::SNIPPET_LENGTH);
        $prefix = $start > 0 ? '... ' : '';
        $suffix = ($start + self::SNIPPET_LENGTH) < mb_strlen($clean) ? ' ...' : '';

        return $prefix . $slice . $suffix;
    }

    private function resolveTimeGroup(?Carbon $date): string
    {
        if (!$date) {
            return 'older';
        }

        if ($date->isToday()) {
            return 'today';
        }

        if ($date->isYesterday()) {
            return 'yesterday';
        }

        if ($date->greaterThanOrEqualTo(now()->subDays(7)->startOfDay())) {
            return 'last_week';
        }

        if ($date->greaterThanOrEqualTo(now()->subMonth()->startOfDay())) {
            return 'last_month';
        }

        return 'older';
    }

    private function timeGroupLabel(string $group): string
    {
        return match ($group) {
            'today' => 'HARI INI',
            'yesterday' => 'KEMARIN',
            'last_week' => 'MINGGU LALU',
            'last_month' => 'BULAN LALU',
            default => 'LEBIH LAMA',
        };
    }

    private function inferMessageBucket(Message $message): string
    {
        $content = mb_strtolower((string) $message->content);

        if (preg_match('/https?:\/\//', $content)) {
            return 'link';
        }

        if ($message->message_type === 'image') {
            return 'image';
        }

        if (preg_match('/\.(mp4|mov|mkv|avi|webm)(\?.*)?$/i', $content)) {
            return 'video';
        }

        if (preg_match('/\.(mp3|wav|ogg|m4a|aac|flac)(\?.*)?$/i', $content)) {
            return 'audio';
        }

        if ($message->message_type === 'file') {
            return 'file';
        }

        return 'text';
    }
}
