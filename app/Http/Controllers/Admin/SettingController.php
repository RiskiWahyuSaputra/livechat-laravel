<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = $request->except(['_token', '_method']);

        foreach ($settings as $key => $value) {
            // Tentukan group berdasarkan prefix key (opsional)
            $group = 'general';
            if (str_starts_with($key, 'gemini_')) $group = 'gemini';
            if (str_starts_with($key, 'openclaw_')) $group = 'openclaw';
            if (str_starts_with($key, 'ai_')) $group = 'ai';
            if (str_starts_with($key, 'messaging_')) $group = 'messaging';

            Setting::set($key, $value, $group);
        }

        // Opsional: Clear cache atau restart service jika perlu
        Artisan::call('config:clear');

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function runCleanup()
    {
        $exitCode = Artisan::call('chat:cleanup-stale-data', ['--force' => true]);
        $output = Artisan::output();

        return redirect()->back()->with('success', 'Pembersihan berhasil dijalankan.' . ($output ? "\n" . trim($output) : ''));
    }
}
