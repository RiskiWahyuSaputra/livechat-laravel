<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DebugAiSettings extends Command
{
    protected $signature = 'ai:debug';
    protected $description = 'Debug AI provider settings';

    public function handle()
    {
        $this->info('=== AI Settings Debug ===');
        $this->newLine();

        $provider = Setting::get('ai_provider', 'not set');
        $this->info("AI Provider: {$provider}");

        $geminiKey = Setting::get('gemini_api_key');
        $this->info('Gemini API Key: ' . ($geminiKey ? 'SET (length: ' . strlen($geminiKey) . ')' : 'NOT SET'));

        $groqKey = Setting::get('groq_api_key');
        $this->info('Groq API Key: ' . ($groqKey ? 'SET (length: ' . strlen($groqKey) . ')' : 'NOT SET'));

        $groqModel = Setting::get('groq_model', 'not set');
        $this->info("Groq Model: {$groqModel}");

        $this->newLine();
        $this->info('=== Cache Status ===');
        
        $backoffKeys = [
            'openclaw_backoff_chat',
            'openclaw_backoff_knowledge',
            'openclaw_backoff_summary_customer',
        ];

        foreach ($backoffKeys as $key) {
            $exists = Cache::has($key);
            $this->info("{$key}: " . ($exists ? 'BLOCKED' : 'OK'));
        }

        $this->newLine();
        $this->info('=== Recommendations ===');
        
        if ($provider === 'groq' && !$groqKey) {
            $this->error('⚠️  Provider is "groq" but Groq API Key is not set!');
            $this->info('   → Go to Settings and add your Groq API Key');
        }

        if ($provider === 'groq' && $groqKey) {
            $this->info('✓ Groq is configured correctly');
            $this->info('  Run: php artisan cache:clear');
            $this->info('  Run: php artisan config:clear');
        }

        return 0;
    }
}
