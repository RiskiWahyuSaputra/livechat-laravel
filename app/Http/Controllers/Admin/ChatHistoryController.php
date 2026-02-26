<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::onlyTrashed()->with(['customer', 'admin']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        $archives = $query->latest('deleted_at')->paginate(15)->withQueryString();

        return view('admin.history.index', compact('archives'));
    }

    public function show($id)
    {
        $conversation = Conversation::onlyTrashed()->with(['customer', 'admin', 'messages.sender'])->findOrFail($id);
        return view('admin.history.show', compact('conversation'));
    }
}
