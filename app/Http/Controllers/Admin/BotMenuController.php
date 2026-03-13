<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotMenu;
use Illuminate\Http\Request;

class BotMenuController extends Controller
{
    public function index()
    {
        $menus = BotMenu::with('children')->whereNull('parent_id')->orderBy('order_index')->get();
        return view('admin.bot-menus.index', compact('menus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:bot_menus,id',
            'action_type' => 'required|in:submenu,link,connect_cs',
            'message_response' => 'nullable|string',
            'action_value' => 'nullable|string',
        ]);

        BotMenu::create($request->all());

        return redirect()->back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, BotMenu $botMenu)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'action_type' => 'required|in:submenu,link,connect_cs',
            'message_response' => 'nullable|string',
            'action_value' => 'nullable|string',
        ]);

        $botMenu->update($request->all());

        return redirect()->back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(BotMenu $botMenu)
    {
        $botMenu->delete();
        return redirect()->back()->with('success', 'Menu berhasil dihapus.');
    }
}
