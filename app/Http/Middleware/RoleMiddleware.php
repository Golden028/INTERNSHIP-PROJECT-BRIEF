<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check() || Auth::user()->role !== $role) {
            // Jika bukan admin, tendang kembali ke dashboard user
            return redirect('/')->with('error', 'Akses ditolak! Anda tidak memiliki izin administratif.');
        }

        return $next($request);
    }
}