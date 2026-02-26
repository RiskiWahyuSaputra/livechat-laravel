<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation; // Assuming Conversation model exists

class UserDashboardController extends Controller
{
    public function index()
    {
        return view('user.dashboard');
    }
}
