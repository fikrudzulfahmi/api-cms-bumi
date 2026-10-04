<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route hanya untuk akun ber-peran 'admin'.
 * Akun 'penulis' hanya boleh mengelola berita miliknya sendiri.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak — hanya untuk admin.'], 403);
        }

        return $next($request);
    }
}
