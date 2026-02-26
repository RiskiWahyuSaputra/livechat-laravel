<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class AutoGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            $guest = User::create([
                'name' => 'Guest_' . mt_rand(1000, 9999),
                'email' => 'guest_' . uniqid() . '@example.com',
                'password' => bcrypt(Str::random(16)),
            ]);
            Auth::login($guest);
        }

        return $next($request);
    }
}
