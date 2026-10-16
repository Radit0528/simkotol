<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * [BACKEND] Middleware Memastikan Pengguna Memiliki Role Owner (US-01, BR-38, BR-39).
 * Menolak akses pengguna non-owner atau yang belum login ke area administratif (HTTP 403).
 */
class EnsureOwner
{
    /**
     * Menangani permintaan HTTP yang masuk.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // BR-39: Pastikan akun tetap berstatus aktif
        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi pemilik kedai.',
            ]);
        }

        // T-12: Kasir mencoba membuka halaman owner via URL -> HTTP 403
        if (! $user->isOwner()) {
            abort(403, 'Akses ditolak. Halaman ini hanya dapat diakses oleh Owner SIMKOTOL.');
        }

        return $next($request);
    }
}
