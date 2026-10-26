<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Manajemen Supplier / Pemasok Bahan Baku (US-06, US-07, BR-40).
 * Mengelola operasi CRUD supplier, pencarian, filter status aktif, dan proteksi penghapusan riwayat pembelian.
 */
class SupplierService
{
    /**
     * [BACKEND] Mengambil daftar supplier dengan filter, pencarian, dan pagination (US-06).
     *
     * @param  array<string, mixed>  $filters
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<Supplier>|Collection<int, Supplier>
     */
    public function getSuppliers(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Supplier::query();

        // Filter status aktif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Pencarian nama, nomor telepon, atau alamat
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('address', 'like', $search);
            });
        }

        // Sertakan hitungan riwayat pembelian jika diminta
        if (! empty($filters['with_purchases_count'])) {
            $query->withCount('purchases');
        }

        $query->orderBy('name', 'asc');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Membuat data supplier baru (US-06).
     *
     * @param  array<string, mixed>  $data
     * @param  int|null  $userId
     * @return Supplier
     *
     * @throws ValidationException
     */
    public function createSupplier(array $data, ?int $userId = null): Supplier
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.max' => 'Nama supplier maksimal 120 karakter.',
            'phone.max' => 'Nomor telepon supplier maksimal 20 digit.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated, $userId) {
            $supplier = Supplier::create([
                'name' => trim($validated['name']),
                'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
                'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
                'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'supplier.created',
                'auditable_type' => Supplier::class,
                'auditable_id' => $supplier->id,
                'old_values' => null,
                'new_values' => $supplier->only(['name', 'phone', 'address', 'notes', 'is_active']),
                'created_at' => now(),
            ]);

            return $supplier;
        });
    }

    /**
     * [BACKEND] Memperbarui data supplier (US-06).
     *
     * @param  Supplier  $supplier
     * @param  array<string, mixed>  $data
     * @param  int|null  $userId
     * @return Supplier
     *
     * @throws ValidationException
     */
    public function updateSupplier(Supplier $supplier, array $data, ?int $userId = null): Supplier
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'sometimes|required|boolean',
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.max' => 'Nama supplier maksimal 120 karakter.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($supplier, $validated, $userId) {
            $oldValues = $supplier->only(['name', 'phone', 'address', 'notes', 'is_active']);

            if (isset($validated['name'])) {
                $validated['name'] = trim($validated['name']);
            }
            if (array_key_exists('phone', $validated)) {
                $validated['phone'] = ! empty($validated['phone']) ? trim($validated['phone']) : null;
            }
            if (array_key_exists('address', $validated)) {
                $validated['address'] = ! empty($validated['address']) ? trim($validated['address']) : null;
            }
            if (array_key_exists('notes', $validated)) {
                $validated['notes'] = ! empty($validated['notes']) ? trim($validated['notes']) : null;
            }

            $supplier->update($validated);
            $newValues = $supplier->only(['name', 'phone', 'address', 'notes', 'is_active']);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'supplier.updated',
                'auditable_type' => Supplier::class,
                'auditable_id' => $supplier->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => now(),
            ]);

            return $supplier;
        });
    }

    /**
     * [BACKEND] Menghapus supplier dengan proteksi riwayat transaksi pembelian (BR-40).
     *
     * @param  Supplier  $supplier
     * @return bool
     *
     * @throws ValidationException
     */
    public function deleteSupplier(Supplier $supplier): bool
    {
        if ($supplier->hasPurchases()) {
            throw ValidationException::withMessages([
                'supplier' => 'Supplier tidak dapat dihapus karena memiliki riwayat transaksi pembelian (BR-40). Nonaktifkan supplier jika sudah tidak bermitra.',
            ]);
        }

        $id = $supplier->id;
        $name = $supplier->name;
        $supplier->delete();

        AuditLog::create([
            'action' => 'supplier.deleted',
            'auditable_type' => Supplier::class,
            'auditable_id' => $id,
            'old_values' => ['name' => $name],
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * [BACKEND] Mengubah status aktif/nonaktif supplier (US-06).
     *
     * @param  Supplier  $supplier
     * @return Supplier
     */
    public function toggleActive(Supplier $supplier): Supplier
    {
        $supplier->update([
            'is_active' => ! $supplier->is_active,
        ]);

        return $supplier;
    }
}

