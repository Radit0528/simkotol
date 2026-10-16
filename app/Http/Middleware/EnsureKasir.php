<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * [BACKEND] Middleware Memastikan Pengguna Memiliki Hak Akses Kasir/POS (US-01, BR-38, BR-39).
 * Mengizinkan Kasir dan Owner (sesuai matriks hak akses POS di PRD Bab 5).
 */
class EnsureKasir
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

        // Pengguna harus memiliki role kasir atau owner (akses POS penuh)
        if (! $user->isKasir() && ! $user->isOwner()) {
            abort(403, 'Akses ditolak. Fitur ini hanya dapat diakses oleh Kasir atau Owner SIMKOTOL.');
        }

        return $next($request);
    }
}
