<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * [BACKEND] Service Manajemen Pengguna & Kasir (US-02, BR-38, BR-39).
 * Menangani operasi CRUD kasir dan staf, penonaktifan akun, soft-delete,
 * reset password & PIN, serta proteksi penghapusan akun yang bertransaksi.
 */
class UserManagementService
{
    /**
     * Panjang minimal password sesuai BR-38.
     */
    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * [BACKEND] Mengambil daftar pengguna/kasir dengan filter dan pagination (US-02).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>|Collection<int, User>
     */
    public function getUsers(array $filters = [], ?int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = User::query();

        // Filter role (owner / kasir)
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Filter status aktif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Filter pencarian teks (nama, email, no telepon)
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        // Tampilkan yang sudah di-soft-delete jika diminta
        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        } elseif (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        }

        // Urutkan role owner dulu, lalu berdasarkan nama
        $query->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc');

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Menambah akun kasir atau staf baru (US-02, BR-38).
     * Password minimal 8 karakter, PIN 6 digit angka di-hash jika diisi.
     *
     * @param  array<string, mixed>  $data
     * @return User
     *
     * @throws ValidationException
     */
    public function createUser(array $data): User
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:' . self::MIN_PASSWORD_LENGTH],
            'role' => ['required', Rule::in(['owner', 'kasir'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'pin_code' => [
                Rule::requiredIf(fn () => ($data['role'] ?? '') === 'owner'),
                'nullable',
                'digits:6',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'password.min' => 'Password minimal harus terdiri dari ' . self::MIN_PASSWORD_LENGTH . ' karakter (BR-38).',
            'pin_code.digits' => 'PIN harus terdiri dari 6 digit angka (BR-38).',
            'pin_code.required' => 'PIN 6 digit wajib diisi untuk akun dengan role Owner (US-02, BR-38).',
            'email.unique' => 'Email ini sudah terdaftar pada sistem.',
            'role.in' => 'Role pengguna hanya boleh owner atau kasir (US-02).',
        ])->validate();

        return DB::transaction(function () use ($validated) {
            $createPayload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'pin_failed_attempts' => 0,
                'pin_locked_until' => null,
            ];

            // Jika role owner atau pin_code disediakan, simpan sebagai hash (BR-38)
            if (! empty($validated['pin_code'])) {
                $createPayload['pin_code'] = Hash::make($validated['pin_code']);
            }

            return User::create($createPayload);
        });
    }

    /**
     * [BACKEND] Mengubah data profil dan informasi staf (US-02).
     * Menolak perubahan role atau penonaktifan jika kasir sedang memiliki shift aktif.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $data
     * @return User
     *
     * @throws ValidationException
     */
    public function updateUser(User $user, array $data): User
    {
        $validated = Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', 'required', Rule::in(['owner', 'kasir'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'email.unique' => 'Email ini sudah digunakan oleh pengguna lain.',
            'role.in' => 'Role pengguna hanya boleh owner atau kasir (US-02).',
        ])->validate();

        // Validasi aturan shift aktif saat menonaktifkan akun atau mengubah role (US-02)
        $isAttemptingToDeactivate = isset($validated['is_active']) && ! $validated['is_active'] && $user->is_active;
        $isAttemptingToChangeRole = isset($validated['role']) && $validated['role'] !== $user->role;

        if (($isAttemptingToDeactivate || $isAttemptingToChangeRole) && $user->hasOpenShift()) {
            throw ValidationException::withMessages([
                'user' => 'Tidak dapat mengubah role atau menonaktifkan kasir yang sedang memiliki shift aktif (open) sampai shift ditutup (US-02).',
            ]);
        }

        return DB::transaction(function () use ($user, $validated) {
            $user->update($validated);
            return $user->fresh();
        });
    }

    /**
     * [BACKEND] Menonaktifkan akun staf/kasir (US-02, BR-39).
     * Ditolak jika kasir sedang memiliki shift open (US-02).
     *
     * @param  User  $user
     * @return User
     *
     * @throws ValidationException
     */
    public function deactivateUser(User $user): User
    {
        if ($user->hasOpenShift()) {
            throw ValidationException::withMessages([
                'user' => 'Menonaktifkan kasir yang sedang memiliki shift aktif (open) ditolak sampai shift ditutup (US-02).',
            ]);
        }

        $user->update(['is_active' => false]);

        return $user->fresh();
    }

    /**
     * [BACKEND] Mengaktifkan kembali akun staf/kasir (US-02, BR-39).
     *
     * @param  User  $user
     * @return User
     */
    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user->fresh();
    }

    /**
     * [BACKEND] Reset password akun pengguna (US-02, BR-38).
     * Password baru wajib memiliki minimal 8 karakter dan di-hash (BR-38).
     *
     * @param  User  $user
     * @param  string  $newPassword
     * @return User
     *
     * @throws ValidationException
     */
    public function resetPassword(User $user, string $newPassword): User
    {
        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            throw ValidationException::withMessages([
                'password' => 'Password baru minimal harus terdiri dari ' . self::MIN_PASSWORD_LENGTH . ' karakter (BR-38).',
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $user->fresh();
    }

    /**
     * [BACKEND] Reset atau perbarui kode PIN 6 digit Owner/Staf (US-02, BR-38).
     * PIN wajib berupa 6 digit angka, disimpan dalam bentuk hash, dan mereset lockout counter (BR-37, BR-38).
     *
     * @param  User  $user
     * @param  string  $newPin
     * @return User
     *
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function resetPin(User $user, string $newPin): User
    {
        if (! preg_match('/^[0-9]{6}$/', $newPin)) {
            throw ValidationException::withMessages([
                'pin_code' => 'PIN approval harus berupa 6 digit angka (BR-38).',
            ]);
        }

        $user->update([
            'pin_code' => Hash::make($newPin),
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ]);

        return $user->fresh();
    }

    /**
     * [BACKEND] Menghapus pengguna (Soft-Delete) atau Penghapusan Permanen jika memenuhi syarat (BR-39).
     * - Pengguna dengan riwayat transaksi TIDAK BOLEH dihapus permanen (BR-39).
     * - Pengguna dengan shift aktif TIDAK BISA dihapus sampai shift ditutup (US-02).
     *
     * @param  User  $user
     * @param  bool  $force  Set true jika meminta penghapusan permanen (hanya jika belum punya transaksi)
     * @return bool
     *
     * @throws ValidationException
     */
    public function deleteUser(User $user, bool $force = false): bool
    {
        // 1. Cek shift aktif (US-02)
        if ($user->hasOpenShift()) {
            throw ValidationException::withMessages([
                'user' => 'Tidak dapat menghapus pengguna yang sedang memiliki shift aktif (open) sampai shift ditutup (US-02).',
            ]);
        }

        // 2. Cek transaksi untuk penghapusan permanen (BR-39)
        if ($force) {
            if ($user->hasTransactions()) {
                throw ValidationException::withMessages([
                    'user' => 'Akun yang sudah memiliki riwayat transaksi tidak boleh dihapus permanen (BR-39). Gunakan penonaktifan atau soft delete.',
                ]);
            }

            return (bool) $user->forceDelete();
        }

        // 3. Soft delete standar (histori transaksi tetap aman di database)
        return (bool) $user->delete();
    }

    /**
     * [BACKEND] Mengembalikan akun pengguna yang telah di-soft-delete (US-02).
     *
     * @param  int|string  $userId
     * @return User
     */
    public function restoreUser(int|string $userId): User
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();

        return $user->fresh();
    }
}

