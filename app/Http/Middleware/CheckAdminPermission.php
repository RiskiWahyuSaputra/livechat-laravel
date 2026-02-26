<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth('admin')->user();

        if (!$admin || !$admin->hasPermission($permission)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
