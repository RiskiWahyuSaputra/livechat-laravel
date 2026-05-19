<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation; // Assuming Conversation model exists

use App\Models\User;
use App\Models\Category;
use App\Models\ConversationRating;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $isAuthenticated = false;
        $token = $request->cookie('guest_chat_token');
        
        if ($token) {
            $user = User::where('email', $token)->first();
            if ($user) {
                $isAuthenticated = true;
            }
        }

        $featuredCategories = Category::where('is_featured', true)->get();
        $testimonials = ConversationRating::with(['user', 'conversation.customer'])
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->latest()
            ->take(12)
            ->get();

        return view('user.dashboard', compact('isAuthenticated', 'featuredCategories', 'testimonials'));
    }

    public function about(Request $request)
    {
        $isAuthenticated = false;
        $token = $request->cookie('guest_chat_token');
        if ($token && User::where('email', $token)->exists()) {
            $isAuthenticated = true;
        }

        return view('user.about', compact('isAuthenticated'));
    }

    public function contact(Request $request)
    {
        $isAuthenticated = false;
        $token = $request->cookie('guest_chat_token');
        if ($token && User::where('email', $token)->exists()) {
            $isAuthenticated = true;
        }

        return view('user.contact', compact('isAuthenticated'));
    }
}
