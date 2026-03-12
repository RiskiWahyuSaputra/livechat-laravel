<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'Silakan login sebagai admin terlebih dahulu.');
        }

        // Cek apakah cookie agent_session masih ada (untuk pengetesan 2 menit)
        if (!$request->cookie('agent_session')) {
            $admin = Auth::guard('admin')->user();
            if ($admin) {
                $admin->update(['status' => 'offline']);
            }

            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'Sesi agent (2 menit) telah berakhir. Silakan login kembali.');
        }

        return $next($request);
    }
}
