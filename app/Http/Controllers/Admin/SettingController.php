<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ConversationFlowService;
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
        $rules = [
            'system_mode'        => 'sometimes|in:office_hour,outside_office_hour,closed',
            'office_hours_start' => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}$/'],
            'office_hours_end'   => ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}$/'],
            'office_hours_timezone' => 'sometimes|string',
        ];

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $rules["office_hours_{$day}_start"] = ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}$/'];
            $rules["office_hours_{$day}_end"] = ['sometimes', 'nullable', 'regex:/^\d{2}:\d{2}$/'];
        }

        $request->validate($rules, [
            'system_mode.in'              => 'Nilai system_mode tidak valid. Pilih salah satu: office_hour, outside_office_hour, atau closed.',
            'office_hours_start.regex'    => 'Format office_hours_start tidak valid. Gunakan format HH:MM (contoh: 08:00).',
            'office_hours_end.regex'      => 'Format office_hours_end tidak valid. Gunakan format HH:MM (contoh: 17:00).',
        ]);

        $settings = $request->except(['_token', '_method']);

        // Handle checkboxes (if not in request, set to 0)
        foreach ($days as $day) {
            $key = "office_hours_{$day}_active";
            if (!$request->has($key)) {
                $settings[$key] = '0';
            }
        }

        // Capture old system_mode before saving
        $newMode = $settings['system_mode'] ?? null;
        $oldMode = Setting::get('system_mode', 'office_hour');

        foreach ($settings as $key => $value) {
            // Tentukan group berdasarkan prefix key (opsional)
            $group = 'general';
            if (str_starts_with($key, 'gemini_')) $group = 'gemini';
            if (str_starts_with($key, 'openclaw_')) $group = 'openclaw';
            if (str_starts_with($key, 'ai_')) $group = 'ai';
            if (str_starts_with($key, 'messaging_')) $group = 'messaging';
            if (str_starts_with($key, 'office_hours_')) $group = 'operational';

            Setting::set($key, $value, $group);
        }

        // Notify queued conversations if mode changed to outside_office_hour or closed
        if ($newMode && $newMode !== $oldMode && in_array($newMode, ['outside_office_hour', 'closed'])) {
            app(ConversationFlowService::class)->notifyQueuedConversationsOfModeChange($newMode);
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
