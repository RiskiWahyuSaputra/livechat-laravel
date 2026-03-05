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
        $settings = $request->except('_token');

        foreach ($settings as $key => $value) {
            // Tentukan group berdasarkan prefix key (opsional)
            $group = 'general';
            if (str_starts_with($key, 'whapi_')) $group = 'whapi';
            if (str_starts_with($key, 'gemini_')) $group = 'gemini';

            Setting::set($key, $value, $group);
        }

        // Opsional: Clear cache atau restart service jika perlu
        Artisan::call('config:clear');

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
