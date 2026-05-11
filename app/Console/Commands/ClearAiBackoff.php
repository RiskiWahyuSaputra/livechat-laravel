<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAiBackoff extends Command
{
    protected $signature = 'ai:clear-backoff';
    protected $description = 'Clear AI provider backoff cache';

    public function handle()
    {
        $patterns = [
            'best_ai_model_backoff_*',
            'openclaw_backoff_*',
        ];

        $cleared = 0;
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                $cleared++;
            }
        }

        $this->info("Cleared {$cleared} AI backoff cache entries.");
        return 0;
    }
}
