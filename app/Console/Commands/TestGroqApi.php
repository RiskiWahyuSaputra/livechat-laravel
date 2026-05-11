<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGroqApi extends Command
{
    protected $signature = 'ai:test-groq';
    protected $description = 'Test Groq API connection';

    public function handle()
    {
        $this->info('=== Testing Groq API ===');
        $this->newLine();

        $apiKey = Setting::get('groq_api_key');
        $model = Setting::get('groq_model', 'llama-3.3-70b-versatile');

        if (!$apiKey) {
            $this->error('❌ Groq API Key not set!');
            return 1;
        }

        $this->info("API Key: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5));
        $this->info("Model: {$model}");
        $this->newLine();

        $this->info('Sending test request...');

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => 'Say "Hello, Groq is working!" in Indonesian.'],
                    ],
                    'max_tokens' => 100,
                ]);

            $this->newLine();
            $this->info("Status: {$response->status()}");

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                $this->info('✅ SUCCESS!');
                $this->newLine();
                $this->info('Response:');
                $this->line($text);
                $this->newLine();
                $this->info('🎉 Groq API is working correctly!');
                return 0;
            } else {
                $this->error('❌ FAILED!');
                $this->newLine();
                $this->error('Error Response:');
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
                
                if ($response->status() === 401) {
                    $this->newLine();
                    $this->error('⚠️  API Key is invalid or expired!');
                    $this->info('   → Generate a new API Key at https://console.groq.com');
                }
                
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('❌ EXCEPTION!');
            $this->newLine();
            $this->error($e->getMessage());
            return 1;
        }
    }
}
