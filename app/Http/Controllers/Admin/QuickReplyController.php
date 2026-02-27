<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickReply;
use Illuminate\Http\Request;

class QuickReplyController extends Controller
{
    public function index()
    {
        $replies = QuickReply::orderBy('created_at', 'desc')->get();
        return view('admin.quick-replies.index', compact('replies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        QuickReply::create($request->all());

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil ditambahkan.');
    }

    public function update(Request $request, QuickReply $quickReply)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $quickReply->update($request->all());

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil diperbarui.');
    }

    public function destroy(QuickReply $quickReply)
    {
        $quickReply->delete();
        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil dihapus.');
    }
}
