<?php

namespace App\Services;

use App\Exceptions\PinLockedException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * [BACKEND] Service Verifikasi PIN Approval Owner & Penanganan Lockout (US-22, BR-37, BR-38, T-07).
 * Menangani validasi kode PIN 6 digit hash, pencatatan percobaan gagal,
 * serta penguncian otomatis selama 15 menit jika gagal 5 kali berturut-turut.
 */
class OwnerPinService
{
    /**
     * Batas maksimal percobaan PIN yang salah sebelum terkunci (BR-37).
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Durasi penguncian fitur approval dalam menit (BR-37, T-07).
     */
    public const LOCKOUT_MINUTES = 15;

    /**
     * [BACKEND] Verifikasi kode PIN Owner dengan pengecekan lockout dan penambahan counter gagal (BR-37, BR-38).
     *
     *
     * @throws PinLockedException
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function verify(User $owner, string $pin): bool
    {
        // 1. Validasi format PIN 6 digit angka (BR-38)
        if (! $this->isValidPinFormat($pin)) {
            throw new InvalidArgumentException('Format PIN tidak valid. PIN Owner harus terdiri dari 6 digit angka.');
        }

        // 2. Pastikan pengguna memiliki peran Owner dan berstatus aktif (BR-38, BR-39)
        if (! $owner->isOwner()) {
            throw ValidationException::withMessages([
                'pin' => 'Akses approval ditolak. Pengguna bukan akun Owner.',
            ]);
        }

        if (! $owner->is_active) {
            throw ValidationException::withMessages([
                'pin' => 'Akses approval ditolak. Akun Owner berstatus nonaktif.',
            ]);
        }

        // 3. Cek status penguncian (Lockout Check - BR-37)
        if ($this->isLocked($owner)) {
            $remainingSeconds = $this->getRemainingLockoutSeconds($owner);
            $remainingMinutes = (int) ceil($remainingSeconds / 60);

            throw new PinLockedException(
                "Fitur approval PIN terkunci selama 15 menit karena 5 kali kegagalan berturut-turut. Silakan coba lagi dalam {$remainingMinutes} menit.",
                $remainingSeconds
            );
        }

        // Jika masa penguncian telah berlalu, reset counter secara otomatis
        if ($owner->pin_locked_until !== null && $owner->pin_locked_until <= now()) {
            $this->resetLockout($owner);
        }

        // 4. Pastikan Owner telah mengatur kode PIN
        if (empty($owner->pin_code)) {
            throw ValidationException::withMessages([
                'pin' => 'Kode PIN approval belum diatur pada akun Owner ini.',
            ]);
        }

        // 5. Verifikasi kecocokan hash PIN (BR-38)
        if (Hash::check($pin, $owner->pin_code)) {
            // Berhasil: reset failed attempts dan lockout
            $this->resetLockout($owner);

            return true;
        }

        // 6. Gagal: inkremen counter percobaan gagal (BR-37)
        $failedAttempts = $owner->pin_failed_attempts + 1;

        if ($failedAttempts >= self::MAX_ATTEMPTS) {
            // Capai batas maksimal: kunci selama 15 menit (BR-37, T-07)
            $owner->update([
                'pin_failed_attempts' => $failedAttempts,
                'pin_locked_until' => now()->addMinutes(self::LOCKOUT_MINUTES),
            ]);

            throw new PinLockedException(
                'PIN salah. Batas percobaan maksimal (5 kali) telah tercapai. Fitur approval PIN terkunci selama 15 menit.',
                self::LOCKOUT_MINUTES * 60
            );
        }

        $owner->update([
            'pin_failed_attempts' => $failedAttempts,
        ]);

        $remainingAttempts = self::MAX_ATTEMPTS - $failedAttempts;

        throw ValidationException::withMessages([
            'pin' => "PIN Owner salah. Sisa kesempatan mencoba: {$remainingAttempts} kali sebelum terkunci 15 menit.",
        ]);
    }

    /**
     * [BACKEND] Cek verifikasi PIN tanpa melempar exception (mengembalikan boolean murni).
     */
    public function check(User $owner, string $pin): bool
    {
        try {
            return $this->verify($owner, $pin);
        } catch (PinLockedException|ValidationException|InvalidArgumentException) {
            return false;
        }
    }

    /**
     * [BACKEND] Periksa apakah fitur PIN approval pengguna sedang dalam status terkunci (BR-37).
     */
    public function isLocked(User $owner): bool
    {
        if ($owner->pin_locked_until === null) {
            return false;
        }

        return $owner->pin_locked_until > now();
    }

    /**
     * [BACKEND] Dapatkan sisa waktu penguncian dalam hitungan detik.
     */
    public function getRemainingLockoutSeconds(User $owner): int
    {
        if (! $this->isLocked($owner)) {
            return 0;
        }

        return (int) max(0, now()->diffInSeconds($owner->pin_locked_until, false));
    }

    /**
     * [BACKEND] Dapatkan sisa kesempatan mencoba PIN sebelum akun terkunci (BR-37).
     */
    public function getRemainingAttempts(User $owner): int
    {
        if ($this->isLocked($owner)) {
            return 0;
        }

        return (int) max(0, self::MAX_ATTEMPTS - $owner->pin_failed_attempts);
    }

    /**
     * [BACKEND] Reset counter kegagalan dan hapus status penguncian PIN Owner (BR-37).
     */
    public function resetLockout(User $owner): void
    {
        $owner->update([
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ]);
    }

    /**
     * [BACKEND] Atur atau ubah kode PIN 6 digit Owner dengan enkripsi hash (BR-38).
     *
     *
     * @throws InvalidArgumentException
     */
    public function setPin(User $owner, string $pin): void
    {
        if (! $this->isValidPinFormat($pin)) {
            throw new InvalidArgumentException('Format PIN tidak valid. PIN Owner harus terdiri dari 6 digit angka.');
        }

        $owner->update([
            'pin_code' => Hash::make($pin),
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ]);
    }

    /**
     * [BACKEND] Validasi format PIN harus tepat 6 digit angka (BR-38).
     */
    public function isValidPinFormat(string $pin): bool
    {
        return (bool) preg_match('/^\d{6}$/', $pin);
    }
}
