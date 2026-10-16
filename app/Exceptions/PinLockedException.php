<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * [BACKEND] Exception Penguncian Fitur Approval PIN Owner (BR-37, US-22, T-07).
 * Dilemparkan ketika verifikasi PIN gagal 5 kali berturut-turut dan akun dikunci selama 15 menit.
 */
class PinLockedException extends Exception
{
    /**
     * Sisa waktu penguncian dalam detik.
     */
    public readonly int $remainingSeconds;

    /**
     * Konstruktor Exception Penguncian PIN.
     */
    public function __construct(
        string $message = 'Fitur approval PIN terkunci selama 15 menit karena 5 kali kegagalan berturut-turut.',
        int $remainingSeconds = 900,
        ?Throwable $previous = null
    ) {
        $this->remainingSeconds = max(0, $remainingSeconds);
        parent::__construct($message, 423, $previous);
    }

    /**
     * Dapatkan sisa waktu penguncian dalam menit pembulatan ke atas.
     */
    public function remainingMinutes(): int
    {
        return (int) ceil($this->remainingSeconds / 60);
    }
}
