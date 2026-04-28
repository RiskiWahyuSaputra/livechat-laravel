<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OpenClawWhatsappOutboundQueue
{
    private const QUEUE_KEY = 'openclaw_whatsapp_outbound_queue';
    private const LEASED_KEY = 'openclaw_whatsapp_outbound_leased';

    public function enqueue(array $payload): string
    {
        $id = (string) Str::uuid();
        $job = [
            'id' => $id,
            'attempts' => 0,
            'created_at' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        Cache::forever($this->jobKey($id), $job);

        $queue = $this->getQueue();
        $queue[] = $id;
        $this->putQueue($queue);

        return $id;
    }

    public function claim(int $limit = 10, int $leaseSeconds = 60): array
    {
        $this->requeueExpiredLeases();

        $queue = $this->getQueue();
        if ($queue === []) {
            return [];
        }

        $claimedIds = array_splice($queue, 0, $limit);
        $this->putQueue($queue);

        $leased = $this->getLeased();
        $expiresAt = now()->addSeconds($leaseSeconds);
        $jobs = [];

        foreach ($claimedIds as $id) {
            $job = Cache::get($this->jobKey($id));
            if (!is_array($job)) {
                continue;
            }

            $job['attempts'] = (int) ($job['attempts'] ?? 0) + 1;
            $job['leased_at'] = now()->toIso8601String();
            Cache::forever($this->jobKey($id), $job);

            $leased[$id] = $expiresAt->timestamp;
            $jobs[] = [
                'id' => $id,
                'attempts' => $job['attempts'],
                'payload' => $job['payload'] ?? [],
            ];
        }

        $this->putLeased($leased);

        return $jobs;
    }

    public function acknowledge(string $id, bool $success, ?string $error = null): void
    {
        $leased = $this->getLeased();
        unset($leased[$id]);
        $this->putLeased($leased);

        $job = Cache::get($this->jobKey($id));
        if (!is_array($job)) {
            return;
        }

        if ($success) {
            Cache::forget($this->jobKey($id));
            return;
        }

        $job['last_error'] = trim((string) $error);
        Cache::forever($this->jobKey($id), $job);

        $maxAttempts = max(1, (int) env('OPENCLAW_OUTBOUND_MAX_ATTEMPTS', 5));
        if ((int) ($job['attempts'] ?? 0) >= $maxAttempts) {
            Cache::forget($this->jobKey($id));
            return;
        }

        $queue = $this->getQueue();
        $queue[] = $id;
        $this->putQueue($queue);
    }

    private function requeueExpiredLeases(): void
    {
        $leased = $this->getLeased();
        if ($leased === []) {
            return;
        }

        $queue = $this->getQueue();
        $now = now()->timestamp;

        foreach ($leased as $id => $expiresAt) {
            if ((int) $expiresAt > $now) {
                continue;
            }

            unset($leased[$id]);

            if (is_array(Cache::get($this->jobKey($id)))) {
                $queue[] = $id;
            }
        }

        $this->putLeased($leased);
        $this->putQueue($queue);
    }

    private function getQueue(): array
    {
        $queue = Cache::get(self::QUEUE_KEY, []);
        if (!is_array($queue)) {
            return [];
        }

        return array_values(array_filter($queue, static fn ($id) => is_string($id) && $id !== ''));
    }

    private function putQueue(array $queue): void
    {
        Cache::forever(self::QUEUE_KEY, array_values($queue));
    }

    private function getLeased(): array
    {
        $leased = Cache::get(self::LEASED_KEY, []);
        return is_array($leased) ? $leased : [];
    }

    private function putLeased(array $leased): void
    {
        Cache::forever(self::LEASED_KEY, $leased);
    }

    private function jobKey(string $id): string
    {
        return 'openclaw_whatsapp_outbound_job_' . $id;
    }
}
