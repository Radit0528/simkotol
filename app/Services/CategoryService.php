<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * [BACKEND] Service Manajemen Kategori Produk Menu (US-03, BR-40).
 * Menangani operasi CRUD kategori menu, pembuatan slug otomatis yang unik,
 * pengaturan urutan tampil (drag/sort_order), serta penolakan penghapusan jika ada menu aktif.
 */
class CategoryService
{
    /**
     * [BACKEND] Mengambil daftar kategori menu dengan filter dan pengurutan (US-03).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Category>|Collection<int, Category>
     */
    public function getCategories(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Category::query();

        // Filter status aktif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Pencarian nama atau slug
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search);
            });
        }

        // Sertakan hitungan produk jika diminta
        if (! empty($filters['with_counts'])) {
            $query->withCount([
                'products',
                'activeProducts',
            ]);
        }

        // Filter soft-delete
        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        } elseif (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        }

        // Urutan standar: sort_order asc, lalu nama asc (US-03)
        $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Menambah kategori produk baru dengan nama unik dan slug otomatis (US-03).
     *
     * @param  array<string, mixed>  $data
     * @return Category
     *
     * @throws ValidationException
     */
    public function createCategory(array $data): Category
    {
        // Generate slug otomatis dari nama jika slug kosong atau tidak diisi (US-03)
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug((string) $data['name']);
        }

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
            'slug' => ['required', 'string', 'max:100', 'unique:categories,slug'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Nama kategori wajib diisi (US-03).',
            'name.unique' => 'Nama kategori ini sudah terdaftar.',
            'slug.unique' => 'Slug kategori ini sudah digunakan.',
        ])->validate();

        // Tentukan urutan otomatis di akhir jika tidak diisi
        if (! isset($validated['sort_order'])) {
            $maxOrder = Category::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        return Category::create($validated);
    }

    /**
     * [BACKEND] Mengubah data kategori (nama unik, slug otomatis/manual, urutan, ikon) (US-03).
     *
     * @param  Category  $category
     * @param  array<string, mixed>  $data
     * @return Category
     *
     * @throws ValidationException
     */
    public function updateCategory(Category $category, array $data): Category
    {
        // Jika nama diubah namun slug tidak disediakan eksplisit, sesuaikan slug
        if (! empty($data['name']) && empty($data['slug']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug((string) $data['name'], $category->id);
        }

        $validated = Validator::make($data, [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.unique' => 'Nama kategori ini sudah digunakan oleh kategori lain.',
            'slug.unique' => 'Slug kategori ini sudah digunakan oleh kategori lain.',
        ])->validate();

        $category->update($validated);

        return $category->fresh();
    }

    /**
     * [BACKEND] Menghapus kategori menu (US-03, BR-40).
     * Menolak penghapusan jika kategori masih memiliki menu/produk aktif.
     *
     * @param  Category  $category
     * @param  bool  $force  Set true jika penghapusan permanen (hanya jika tidak ada produk terkait)
     * @return bool
     *
     * @throws ValidationException
     */
    public function deleteCategory(Category $category, bool $force = false): bool
    {
        // 1. Validasi aturan US-03: Tolak hapus jika masih ada menu/produk yang aktif
        if ($category->hasActiveProducts()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori "' . $category->name . '" tidak dapat dihapus karena masih memiliki menu aktif (US-03). Nonaktifkan atau pindahkan menu tersebut terlebih dahulu.',
            ]);
        }

        // 2. Jika force delete diminta, pastikan tidak ada produk yang terikat sama sekali (foreign key restrict)
        if ($force) {
            if ($category->products()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori yang memiliki riwayat produk tidak dapat dihapus permanen. Gunakan soft-delete (BR-40).',
                ]);
            }

            return (bool) $category->forceDelete();
        }

        // 3. Soft-delete kategori (BR-40)
        return (bool) $category->delete();
    }

    /**
     * [BACKEND] Mengembalikan kategori menu yang telah di-soft-delete (US-03).
     *
     * @param  int|string  $id
     * @return Category
     */
    public function restoreCategory(int|string $id): Category
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return $category->fresh();
    }

    /**
     * [BACKEND] Toggle status aktif/nonaktif kategori menu (US-03).
     *
     * @param  Category  $category
     * @return Category
     */
    public function toggleActive(Category $category): Category
    {
        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        return $category->fresh();
    }

    /**
     * [BACKEND] Memperbarui urutan tampil kategori secara massal (US-03, S-03).
     * Mendukung fitur reorder / drag urutan kategori di antarmuka katalog POS.
     *
     * @param  array<int, int|string>  $orderedIds  Array ID kategori berurutan [id1, id2, ...]
     * @return void
     */
    public function reorderCategories(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Category::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });
    }

    /**
     * [BACKEND] Helper pembuat slug unik otomatis (US-03).
     *
     * @param  string  $name
     * @param  int|null  $ignoreId
     * @return string
     */
    public function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);

        if (empty($baseSlug)) {
            $baseSlug = 'kategori';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->withTrashed()
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
