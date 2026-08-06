<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memblokir akses role 'dev' jika environment adalah production.
 * Role dev hanya boleh aktif di development/staging.
 */
class DevOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Jika bukan dev role → tolak
        if (!$user->isDev()) {
            abort(403, 'Halaman ini hanya untuk Developer.');
        }

        // Jika dev role tapi di production → tolak
        if (app()->environment('production')) {
            abort(403, 'Dev access tidak diizinkan di environment production.');
        }

        return $next($request);
    }
}
