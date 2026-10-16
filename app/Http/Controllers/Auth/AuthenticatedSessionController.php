<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * [BACKEND] Controller Sesi Autentikasi Pengguna (US-01, BR-38, BR-39).
 * Menangani tampilan form login, proses login dengan role-based redirect, dan proses logout.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * [BACKEND] Menampilkan formulir login pengguna (US-01, S-01).
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * [BACKEND] Memproses permintaan login dan mengarahkan sesuai peran pengguna (US-01, BR-38, BR-39).
     * Pengguna dengan role 'owner' diarahkan ke Dashboard Pemilik (/dashboard),
     * sedangkan role 'kasir' diarahkan ke Layar Kasir POS (/pos).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->isOwner()) {
            return redirect()->intended(route('dashboard', [], false));
        }

        return redirect()->intended(route('pos.index', [], false));
    }

    /**
     * [BACKEND] Menghancurkan sesi terautentikasi pengguna / Logout (US-01).
     * Menghapus sesi aktif, meregenerasi CSRF token, dan mengalihkan kembali ke form login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
