<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    // Tampilkan halaman login admin
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.admin-login');
    }

    // Proses login admin
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $authCredentials = [
            $loginField => $request->login,
            'password'  => $request->password,
        ];

        if (Auth::guard('admin')->attempt($authCredentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Set admin online
            Auth::guard('admin')->user()->update(['status' => 'online']);
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'login' => 'Email/Username atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    }

    // Logout admin
    public function logout(Request $request)
    {
        Auth::guard('admin')->user()->update(['status' => 'offline']);
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
