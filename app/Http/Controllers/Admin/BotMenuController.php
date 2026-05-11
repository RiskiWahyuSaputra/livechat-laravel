<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotMenu;
use Illuminate\Http\Request;

class BotMenuController extends Controller
{
    public function index()
    {
        $flow = 'office_hour';
        $menus = [];
        $menus[$flow] = BotMenu::with('children')
            ->whereNull('parent_id')
            ->where('flow_type', $flow)
            ->orderBy('order_index')
            ->get();

        $greetings = [
            'office_hour' => \App\Models\Setting::get('bot_greeting_office_hour', 'Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?'),
        ];

        return view('admin.bot-menus.index', compact('menus', 'greetings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'            => 'required|string|max:255',
            'parent_id'        => 'nullable|exists:bot_menus,id',
            'flow_type'        => 'required|in:office_hour',
            'action_type'      => 'required|in:submenu,link,connect_cs',
            'message_response' => 'nullable|string',
            'action_value'     => 'nullable|string',
        ]);

        // Inherit flow_type from parent if creating a child node
        if ($request->parent_id) {
            $parent = BotMenu::find($request->parent_id);
            $flowType = $parent ? $parent->flow_type : 'office_hour';
        } else {
            $flowType = 'office_hour';
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
            'flow_type'            => 'required|in:office_hour',
            'bot_greeting_message' => 'required|string',
        ]);

        $key = 'bot_greeting_office_hour';
        \App\Models\Setting::set($key, $request->bot_greeting_message, 'bot');

        // Keep legacy key in sync for office_hour
        \App\Models\Setting::set('bot_greeting_message', $request->bot_greeting_message, 'bot');

        return redirect()->back()->with('success', 'Pesan sapaan berhasil diperbarui.');
    }

    public function destroy(BotMenu $botMenu)
    {
        $botMenu->delete();
        return redirect()->back()->with('success', 'Menu berhasil dihapus.');
    }
}
