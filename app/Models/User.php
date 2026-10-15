<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * [BACKEND] Model Pengguna SIMKOTOL (US-01, US-02, BR-38, BR-39).
 * Mendukung role Owner & Kasir, hash PIN 6 digit untuk approval, serta penguncian PIN dan soft delete.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'pin_code',
        'pin_failed_attempts',
        'pin_locked_until',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin_locked_until' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'pin_failed_attempts' => 'integer',
        ];
    }

    /**
     * Cek apakah user memiliki role Owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Cek apakah user memiliki role Kasir.
     */
    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }
}
