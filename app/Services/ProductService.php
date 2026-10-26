<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * [BACKEND] Service Manajemen Produk Menu (US-04, US-05, BR-14, BR-18, BR-19, BR-40, NFR-10).
 * Menangani CRUD produk menu, upload foto auto-resize 600x600 (maks 2MB),
 * snapshot harga, toggle menu habis manual (sold out), dan jejak audit log.
 */
class ProductService
{
    /**
     * Resolusi target auto-resize foto produk menu (US-04).
     */
    public const IMAGE_TARGET_WIDTH = 600;
    public const IMAGE_TARGET_HEIGHT = 600;

    /**
     * Batas maksimal ukuran upload gambar dalam Kilobytes (2MB) (US-04).
     */
    public const MAX_IMAGE_SIZE_KB = 2048;

    /**
     * [BACKEND] Mengambil daftar produk menu dengan filter dan relasi (US-04, US-14).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Product>|Collection<int, Product>
     */
    public function getProducts(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Product::query();

        // Filter kategori
        if (! empty($filters['category_id'])) {
            if (is_array($filters['category_id'])) {
                $query->whereIn('category_id', $filters['category_id']);
            } else {
                $query->where('category_id', $filters['category_id']);
            }
        }

        // Filter status aktif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Filter habis / sold out
        if (isset($filters['is_sold_out']) && $filters['is_sold_out'] !== '') {
            $query->where('is_sold_out', (bool) $filters['is_sold_out']);
        }

        // Pencarian nama atau SKU
        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search);
            });
        }

        // Eager loading
        if (! empty($filters['with_category'])) {
            $query->with('category');
        }

        if (! empty($filters['with_recipes'])) {
            $query->with('recipes.ingredient');
        }

        // Filter soft-delete
        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        } elseif (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        }

        // Urutan standar: sort_order asc, nama asc
        $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * [BACKEND] Menambah produk menu baru dengan upload foto auto-resize 600x600 (US-04, NFR-10).
     *
     * @param  array<string, mixed>  $data
     * @param  UploadedFile|null  $image
     * @return Product
     *
     * @throws ValidationException
     */
    public function createProduct(array $data, ?UploadedFile $image = null): Product
    {
        $imageFile = $image ?? ($data['image'] ?? null);
        if ($imageFile instanceof UploadedFile) {
            $data['image'] = $imageFile;
        }

        $validated = Validator::make($data, [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_sold_out' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:' . self::MAX_IMAGE_SIZE_KB,
            ],
        ], [
            'category_id.required' => 'Kategori menu wajib dipilih (US-04).',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'name.required' => 'Nama produk menu wajib diisi (US-04).',
            'price.required' => 'Harga jual menu wajib diisi (US-04).',
            'price.min' => 'Harga jual tidak boleh kurang dari 0 (US-04).',
            'image.max' => 'Ukuran foto maksimal ' . (self::MAX_IMAGE_SIZE_KB / 1024) . ' MB (US-04).',
            'image.mimes' => 'Format foto harus berupa JPG, PNG, atau WebP (US-04).',
            'sku.unique' => 'SKU produk menu sudah digunakan.',
        ])->validate();

        // 1. Proses upload foto dan auto-resize 600x600 (US-04)
        $storedImagePath = null;
        if (! empty($data['image']) && $data['image'] instanceof UploadedFile) {
            $storedImagePath = $this->processAndStoreImage($data['image']);
        }

        // 2. Set nilai default
        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null) {
            $maxOrder = Product::where('category_id', $validated['category_id'])->max('sort_order') ?? 0;
            $sortOrder = $maxOrder + 1;
        }

        return Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => (int) $validated['price'],
            'cost_price' => (int) ($validated['cost_price'] ?? 0),
            'image' => $storedImagePath,
            'is_active' => $validated['is_active'] ?? true,
            'is_sold_out' => $validated['is_sold_out'] ?? false,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * [BACKEND] Mengubah data produk menu beserta foto dan mencatat riwayat perubahan harga (US-04, NFR-12).
     *
     * @param  Product  $product
     * @param  array<string, mixed>  $data
     * @param  UploadedFile|null  $image
     * @param  int|null  $userId  User yang melakukan perubahan (untuk audit log)
     * @return Product
     *
     * @throws ValidationException
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $image = null, ?int $userId = null): Product
    {
        $imageFile = $image ?? ($data['image'] ?? null);
        if ($imageFile instanceof UploadedFile) {
            $data['image'] = $imageFile;
        }

        $validated = Validator::make($data, [
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product->id)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_sold_out' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:' . self::MAX_IMAGE_SIZE_KB,
            ],
        ], [
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'price.min' => 'Harga jual tidak boleh kurang dari 0 (US-04).',
            'image.max' => 'Ukuran foto maksimal ' . (self::MAX_IMAGE_SIZE_KB / 1024) . ' MB (US-04).',
            'image.mimes' => 'Format foto harus berupa JPG, PNG, atau WebP (US-04).',
            'sku.unique' => 'SKU produk menu sudah digunakan oleh menu lain.',
        ])->validate();

        return DB::transaction(function () use ($product, $data, $validated, $userId) {
            // 1. Audit Log jika harga menu diubah (US-04, NFR-12, BR-19)
            if (isset($validated['price']) && (int) $validated['price'] !== (int) $product->price) {
                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'product.price_changed',
                    'auditable_type' => Product::class,
                    'auditable_id' => $product->id,
                    'old_values' => ['price' => $product->price],
                    'new_values' => ['price' => (int) $validated['price']],
                    'created_at' => now(),
                ]);
            }

            // 2. Ganti foto jika foto baru diunggah (US-04)
            if (! empty($data['image']) && $data['image'] instanceof UploadedFile) {
                // Hapus file foto lama jika ada
                $this->deleteImageFile($product->image);

                // Proses resize 600x600 dan simpan
                $newPath = $this->processAndStoreImage($data['image']);
                $validated['image'] = $newPath;
            }

            $product->update($validated);

            return $product->fresh();
        });
    }

    /**
     * [BACKEND] Menghapus produk menu (Soft-Delete) atau Penghapusan Permanen jika memenuhi syarat (BR-40).
     * - Menu yang sudah digunakan pada transaksi order_items TIDAK BOLEH dihapus permanen (BR-40).
     *
     * @param  Product  $product
     * @param  bool  $force  Set true jika ingin menghapus permanen
     * @return bool
     *
     * @throws ValidationException
     */
    public function deleteProduct(Product $product, bool $force = false): bool
    {
        if ($force) {
            // Cek apakah produk sudah dipakai transaksi (BR-40)
            if ($product->hasTransactions()) {
                throw ValidationException::withMessages([
                    'product' => 'Produk menu "' . $product->name . '" sudah memiliki riwayat transaksi dan tidak boleh dihapus permanen (BR-40). Gunakan penonaktifan atau soft delete.',
                ]);
            }

            // Hapus file gambar jika ada
            $this->deleteImageFile($product->image);

            return (bool) $product->forceDelete();
        }

        // Soft-delete standar (histori transaksi tetap aman di database)
        return (bool) $product->delete();
    }

    /**
     * [BACKEND] Mengembalikan produk menu yang telah di-soft-delete (US-04).
     *
     * @param  int|string  $id
     * @return Product
     */
    public function restoreProduct(int|string $id): Product
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return $product->fresh();
    }

    /**
     * [BACKEND] Tandai menu habis manual (Sold Out) dengan pencatatan audit log (US-05, NFR-12).
     *
     * @param  Product  $product
     * @param  int|null  $userId  User yang melakukan aksi
     * @return Product
     */
    public function toggleSoldOut(Product $product, ?int $userId = null): Product
    {
        $oldState = (bool) $product->is_sold_out;
        $newState = ! $oldState;

        $product->update([
            'is_sold_out' => $newState,
        ]);

        // Catat ke Audit Log sesuai US-05
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'product.sold_out_toggled',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'old_values' => ['is_sold_out' => $oldState],
            'new_values' => ['is_sold_out' => $newState],
            'created_at' => now(),
        ]);

        return $product->fresh();
    }

    /**
     * [BACKEND] Toggle status aktif/nonaktif produk menu (US-04).
     *
     * @param  Product  $product
     * @return Product
     */
    public function toggleActive(Product $product): Product
    {
        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        return $product->fresh();
    }

    /**
     * [BACKEND] Hitung dan snapshot HPP produk berdasarkan resep BOM saat ini (US-04, BR-19).
     *
     * @param  Product  $product
     * @return Product
     */
    public function recalculateCostPrice(Product $product): Product
    {
        $hpp = (int) round($product->calculateHpp());

        $product->update([
            'cost_price' => $hpp,
        ]);

        return $product->fresh();
    }

    /**
     * [BACKEND] Memproses upload foto dan auto-resize menjadi 600x600 format WebP (US-04, NFR-10).
     *
     * @param  UploadedFile  $file
     * @return string  Relative path gambar di public storage (contoh: products/uuid.webp)
     *
     * @throws RuntimeException
     */
    public function processAndStoreImage(UploadedFile $file): string
    {
        $targetWidth = self::IMAGE_TARGET_WIDTH;
        $targetHeight = self::IMAGE_TARGET_HEIGHT;

        $filePath = $file->getRealPath();
        if (! $filePath || ! file_exists($filePath)) {
            throw new RuntimeException('File gambar tidak dapat dibaca dari server.');
        }

        $imageContent = file_get_contents($filePath);
        if ($imageContent === false) {
            throw new RuntimeException('Gagal membaca konten file gambar.');
        }

        $sourceImage = @imagecreatefromstring($imageContent);
        if (! $sourceImage) {
            throw new RuntimeException('Gagal memproses gambar. Format gambar tidak didukung atau korup.');
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Hitung crop tengah untuk mempertahankan rasio persegi (center crop)
        $origRatio = $origWidth / $origHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($origRatio > $targetRatio) {
            // Gambar lebih lebar: pertahankan tinggi, crop lebar
            $cropHeight = $origHeight;
            $cropWidth = (int) round($origHeight * $targetRatio);
            $cropX = (int) round(($origWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            // Gambar lebih tinggi: pertahankan lebar, crop tinggi
            $cropWidth = $origWidth;
            $cropHeight = (int) round($origWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($origHeight - $cropHeight) / 2);
        }

        $targetCanvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Pertahankan transparansi PNG / WebP
        imagealphablending($targetCanvas, false);
        imagesavealpha($targetCanvas, true);

        imagecopyresampled(
            $targetCanvas,
            $sourceImage,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        // Render buffer ke WebP (fallback ke JPEG jika WebP tidak tersedia)
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($targetCanvas, null, 85);
            $extension = 'webp';
        } else {
            imagejpeg($targetCanvas, null, 85);
            $extension = 'jpg';
        }
        $processedImageData = ob_get_clean();

        // Bersihkan memori GD
        imagedestroy($sourceImage);
        imagedestroy($targetCanvas);

        // Simpan ke disk public dengan nama unik yang di-generate (NFR-10)
        $fileName = 'products/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($fileName, $processedImageData);

        return $fileName;
    }

    /**
     * [BACKEND] Hapus file foto dari disk public jika ada.
     *
     * @param  string|null  $path
     * @return void
     */
    public function deleteImageFile(?string $path): void
    {
        if (! empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
