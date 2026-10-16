<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Form Request Autentikasi Login (US-01, BR-38, BR-39).
 * Menangani validasi input login, rate limiting 5x per menit, dan verifikasi status aktif akun.
 */
class LoginRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * [BACKEND] Dapatkan aturan validasi untuk permintaan login (US-01, BR-38).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * [BACKEND] Dapatkan pesan kustom untuk error validasi bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * [BACKEND] Autentikasi kredensial pengguna dengan proteksi rate limit dan verifikasi status aktif (US-01, BR-39).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // 1. Periksa apakah akun nonaktif sebelum memproses login lebih lanjut (BR-39)
        $user = User::where('email', $this->input('email'))->first();

        if ($user && ! $user->is_active && Hash::check($this->input('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi pemilik kedai untuk mengaktifkan kembali.',
            ]);
        }

        // 2. Coba autentikasi kredensial pengguna
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Kredensial yang dimasukkan tidak cocok dengan catatan kami.',
            ]);
        }

        // 3. Verifikasi ulang status aktif setelah sesi login terbentuk (BR-39)
        /** @var User $authenticatedUser */
        $authenticatedUser = Auth::user();

        if (! $authenticatedUser->is_active) {
            Auth::logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi pemilik kedai untuk mengaktifkan kembali.',
            ]);
        }

        // 4. Update waktu login terakhir (users.last_login_at)
        $authenticatedUser->update([
            'last_login_at' => now(),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * [BACKEND] Pastikan permintaan login belum melampaui ambang batas rate limit 5x per menit (US-01).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'throttle' => "Terlalu banyak percobaan masuk yang gagal (maksimal 5 kali). Silakan coba lagi dalam {$seconds} detik demi keamanan akun Anda.",
            'email' => "Terlalu banyak percobaan masuk yang gagal. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * [BACKEND] Dapatkan kunci throttle unik berdasarkan IP dan email (US-01).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
