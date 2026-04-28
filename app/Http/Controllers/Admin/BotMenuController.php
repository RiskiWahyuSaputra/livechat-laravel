<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotMenu;
use Illuminate\Http\Request;

class BotMenuController extends Controller
{
    public function index()
    {
        $flows = ['office_hour', 'outside_office_hour', 'closed'];
        $menus = [];
        foreach ($flows as $flow) {
            $menus[$flow] = BotMenu::with('children')
                ->whereNull('parent_id')
                ->where('flow_type', $flow)
                ->orderBy('order_index')
                ->get();
        }

        $greetings = [
            'office_hour'          => \App\Models\Setting::get('bot_greeting_office_hour', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?'),
            'outside_office_hour'  => \App\Models\Setting::get('bot_greeting_outside_office_hour', 'Mohon maaf, kami sedang di luar jam kerja. Silakan tinggalkan pesan, kami akan segera membalas.'),
            'closed'               => \App\Models\Setting::get('bot_greeting_closed', 'Mohon maaf, layanan chat kami sedang tidak tersedia. Silakan hubungi kami kembali nanti.'),
        ];

        return view('admin.bot-menus.index', compact('menus', 'greetings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'            => 'required|string|max:255',
            'parent_id'        => 'nullable|exists:bot_menus,id',
            'flow_type'        => 'required|in:office_hour,outside_office_hour,closed',
            'action_type'      => 'required|in:submenu,link,connect_cs',
            'message_response' => 'nullable|string',
            'action_value'     => 'nullable|string',
        ]);

        // Inherit flow_type from parent if creating a child node
        if ($request->parent_id) {
            $parent = BotMenu::find($request->parent_id);
            $flowType = $parent ? $parent->flow_type : $request->flow_type;
        } else {
            $flowType = $request->flow_type;
        }

        BotMenu::create(array_merge($request->all(), ['flow_type' => $flowType]));

        return redirect()->back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, BotMenu $botMenu)
    {
        $request->validate([
            'label'            => 'required|string|max:255',
            'action_type'      => 'required|in:submenu,link,connect_cs',
            'message_response' => 'nullable|string',
            'action_value'     => 'nullable|string',
        ]);

        $botMenu->update($request->all());

        return redirect()->back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function updateGreeting(Request $request)
    {
        $request->validate([
            'flow_type'            => 'required|in:office_hour,outside_office_hour,closed',
            'bot_greeting_message' => 'required|string',
        ]);

        $key = 'bot_greeting_' . $request->flow_type;
        \App\Models\Setting::set($key, $request->bot_greeting_message, 'bot');

        // Keep legacy key in sync for office_hour
        if ($request->flow_type === 'office_hour') {
            \App\Models\Setting::set('bot_greeting_message', $request->bot_greeting_message, 'bot');
        }

        return redirect()->back()->with('success', 'Pesan sapaan berhasil diperbarui.');
    }

    public function destroy(BotMenu $botMenu)
    {
        $botMenu->delete();
        return redirect()->back()->with('success', 'Menu berhasil dihapus.');
    }
}
