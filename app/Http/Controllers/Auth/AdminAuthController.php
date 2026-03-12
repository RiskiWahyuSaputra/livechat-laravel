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
            
            $admin = Auth::guard('admin')->user();
            // Set admin online
            $admin->update(['status' => 'online']);
            
            // Tentukan durasi cookie berdasarkan role
            // Agent: 30 menit, Superadmin: 1 minggu (10080 menit)
            $duration = $admin->is_superadmin ? 10080 : 30;
            
            return redirect()->intended(route('admin.dashboard'))
                ->withCookie(cookie('agent_session', $admin->id, $duration));
        }

        return back()->withErrors([
            'login' => 'Email/Username atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    }

    // Logout admin
    public function logout(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            $admin->update(['status' => 'offline']);
        }
        
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Hapus cookie saat logout
        return redirect()->route('admin.login')
            ->withoutCookie('agent_session');
    }
}
