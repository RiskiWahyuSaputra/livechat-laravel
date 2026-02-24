<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    // Tampilkan halaman login user
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }
        return view('auth.user-login');
    }

    // Tampilkan halaman register user
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }
        return view('auth.user-register');
    }

    // Proses login user
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Cek apakah user diblokir
        $user = User::where('email', $credentials['email'])->first();
        if ($user && $user->is_blocked) {
            return back()->withErrors([
                'email' => 'Akun Anda telah diblokir. Silakan hubungi administrator.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            // Set user online
            Auth::user()->update(['is_online' => true]);
            return redirect()->intended(route('chat.index'));
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Proses register user baru
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'unique:users'],
            'password'              => ['required', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $user->update(['is_online' => true]);

        return redirect()->route('chat.index');
    }

    // Logout user
    public function logout(Request $request)
    {
        Auth::user()->update(['is_online' => false]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login');
    }
}
