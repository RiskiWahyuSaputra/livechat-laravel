<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation; // Assuming Conversation model exists

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $conversations = Conversation::where('user_id', $user->id)
                                    ->with('latestMessage') // Eager load the latest message for preview
                                    ->latest()
                                    ->get();

        return view('user.dashboard', compact('conversations'));
    }
}
