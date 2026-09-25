<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dibuat admin memakai kata sandi sementara. Sampai kata sandi diganti, pengguna hanya
 * boleh membuka halaman ganti kata sandi dan logout.
 */
class EnsurePasswordIsChanged
{
    private const ALLOWED_ROUTES = [
        'password.change',
        'password.change.update',
        'logout',
        'filament.admin.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->must_change_password || $request->routeIs(self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda wajib mengganti kata sandi sementara sebelum melanjutkan.',
            ], 403);
        }

        return redirect()->route('password.change');
    }
}
