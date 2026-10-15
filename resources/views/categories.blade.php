{{--
  [FRONTEND] Halaman Manajemen Kategori Menu (US-03, S-03).
  Halaman mandiri (standalone page) untuk pengelolaan kategori produk kedai Kopi Titik Nol.
  Mencakup daftar kategori, pengurutan tampilan (sort order), modal tambah/edit kategori,
  serta aturan perlindungan penghapusan jika masih terdapat menu aktif terkait.
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('products.index') }}" class="hover:underline">Katalog Menu</a>
            <span>&bull;</span>
            <span class="text-[#3C2317]">Kategori</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Manajemen Kategori Menu (S-03)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Atur pengelompokan menu kopi dan kudapan beserta urutan tampil di POS kasir
        </p>
    </div>
    <div class="flex items-center gap-2.5">
        <a
            href="{{ route('products.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
            <span>Buka Katalog Menu (S-04)</span>
        </a>
        <button
            type="button"
            @click="openModalTambah()"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
        >
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Tambah Kategori Baru</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        search: '',
        modalTambahOpen: false,
        modalEditOpen: false,
        modalHapusOpen: false,
        selectedCategory: null,
        formData: {
            id: null,
            name: '',
            slug: '',
            icon: 'coffee',
            sort_order: 1,
            is_active: 1
        },
        categories: {{ Js::from($categories ?? []) }},

        generateSlug(text) {
            return text.toLowerCase()
                .trim()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        },

        get filteredCategories() {
            return this.categories
                .filter(c => this.search === '' || c.name.toLowerCase().includes(this.search.toLowerCase()) || c.slug.includes(this.search.toLowerCase()))
                .sort((a, b) => a.sort_order - b.sort_order);
        },

        openModalTambah() {
            this.formData = {
                id: null,
                name: '',
                slug: '',
                icon: 'coffee',
                sort_order: this.categories.length + 1,
                is_active: 1
            };
            this.modalTambahOpen = true;
        },

        openModalEdit(cat) {
            this.selectedCategory = cat;
            this.formData = {
                id: cat.id,
                name: cat.name,
                slug: cat.slug,
                icon: cat.icon || 'coffee',
                sort_order: cat.sort_order,
                is_active: cat.is_active
            };
            this.modalEditOpen = true;
        },

        openModalHapus(cat) {
            this.selectedCategory = cat;
            this.modalHapusOpen = true;
        },

        moveOrder(index, direction) {
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= this.filteredCategories.length) return;

            const current = this.filteredCategories[index];
            const target = this.filteredCategories[targetIndex];

            const tempOrder = current.sort_order;
            current.sort_order = target.sort_order;
            target.sort_order = tempOrder;

            window.toast(`Urutan kategori '${current.name}' diperbarui`, 'info', 2000);
        },

        submitTambah() {
            if (!this.formData.name) {
                window.toast('Nama kategori wajib diisi', 'warning');
                return;
            }
            if (!this.formData.slug) {
                this.formData.slug = this.generateSlug(this.formData.name);
            }

            const newCat = {
                id: Date.now(),
                name: this.formData.name,
                slug: this.formData.slug,
                icon: this.formData.icon,
                sort_order: parseInt(this.formData.sort_order) || (this.categories.length + 1),
                products_count: 0,
                is_active: this.formData.is_active ? 1 : 0
            };

            this.categories.push(newCat);
            this.modalTambahOpen = false;
            window.toast(`Kategori '${newCat.name}' berhasil dibuat`, 'success');
        },

        submitEdit() {
            if (!this.formData.name) {
                window.toast('Nama kategori wajib diisi', 'warning');
                return;
            }
            const idx = this.categories.findIndex(c => c.id === this.selectedCategory.id);
            if (idx !== -1) {
                this.categories[idx].name = this.formData.name;
                this.categories[idx].slug = this.formData.slug || this.generateSlug(this.formData.name);
                this.categories[idx].icon = this.formData.icon;
                this.categories[idx].sort_order = parseInt(this.formData.sort_order);
                this.categories[idx].is_active = this.formData.is_active ? 1 : 0;
                window.toast(`Kategori '${this.formData.name}' berhasil diperbarui`, 'success');
            }
            this.modalEditOpen = false;
        },

        confirmHapus() {
            if (!this.selectedCategory) return;
            if (this.selectedCategory.products_count > 0) {
                window.toast(`Gagal: Kategori '${this.selectedCategory.name}' masih memiliki ${this.selectedCategory.products_count} menu aktif (US-03)`, 'danger', 5000);
                this.modalHapusOpen = false;
                return;
            }

            this.categories = this.categories.filter(c => c.id !== this.selectedCategory.id);
            window.toast(`Kategori '${this.selectedCategory.name}' berhasil dihapus`, 'success');
            this.modalHapusOpen = false;
        }
    }"
    class="space-y-6"
>
    <!-- Statistik Singkat Kategori -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Total Kategori</span>
            <div class="mt-2 text-2xl font-bold text-[#1A0F0A]" x-text="categories.length">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Pengelompokan menu POS</p>
        </div>
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Kategori Aktif</span>
            <div class="mt-2 text-2xl font-bold text-[#2E7D32]" x-text="categories.filter(c => c.is_active == 1).length">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Tampil pada tab kasir POS</p>
        </div>
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Total Menu Terhubung</span>
            <div class="mt-2 text-2xl font-bold text-[#3C2317]" x-text="categories.reduce((acc, c) => acc + (c.products_count || 0), 0)">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Item menu terdaftar di sistem</p>
        </div>
    </div>

    <!-- Bar Pencarian -->
    <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft flex items-center justify-between gap-3">
        <div class="relative flex-1 max-w-md">
            <input
                type="text"
                x-model="search"
                placeholder="Cari nama atau slug kategori..."
                class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-[10px] bg-white border border-[#8C6246]/20 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all shadow-xs"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#7A685D]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
        </div>
        <div class="text-xs text-[#7A685D] hidden sm:block">
            Gunakan tombol panah <strong>↑ ↓</strong> untuk mengubah urutan tab kasir POS
        </div>
    </div>

    <!-- Tabel Data Kategori (S-03) -->
    <div class="bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/15 shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#8C6246]/15 bg-[#F8F5F0]/60 text-[11px] font-bold text-[#7A685D] uppercase tracking-wider">
                        <th scope="col" class="py-3.5 px-4 sm:px-6 w-16 text-center">Urutan</th>
                        <th scope="col" class="py-3.5 px-4">Nama Kategori & Slug</th>
                        <th scope="col" class="py-3.5 px-4">Ikon</th>
                        <th scope="col" class="py-3.5 px-4">Jumlah Menu</th>
                        <th scope="col" class="py-3.5 px-4">Status Tampil</th>
                        <th scope="col" class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#8C6246]/10 text-xs sm:text-sm">
                    <template x-for="(cat, index) in filteredCategories" :key="cat.id">
                        <tr class="hover:bg-[#F8F5F0]/50 transition-colors">
                            <!-- Urutan & Reorder buttons -->
                            <td class="py-3.5 px-4 sm:px-6 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="w-6 h-6 rounded-full bg-[#8C6246]/10 text-[#3C2317] font-mono text-xs font-bold flex items-center justify-center" x-text="cat.sort_order"></span>
                                    <div class="flex flex-col">
                                        <button
                                            type="button"
                                            @click="moveOrder(index, -1)"
                                            :disabled="index === 0"
                                            class="p-0.5 text-[#7A685D] hover:text-[#3C2317] disabled:opacity-25 disabled:cursor-not-allowed focus:outline-none"
                                            title="Naikkan Urutan"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="moveOrder(index, 1)"
                                            :disabled="index === filteredCategories.length - 1"
                                            class="p-0.5 text-[#7A685D] hover:text-[#3C2317] disabled:opacity-25 disabled:cursor-not-allowed focus:outline-none"
                                            title="Turunkan Urutan"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <!-- Nama Kategori & Slug -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-[#1A0F0A]" x-text="cat.name"></div>
                                <div class="text-xs text-[#7A685D] font-mono mt-0.5" x-text="'/' + cat.slug"></div>
                            </td>

                            <!-- Ikon -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="w-8 h-8 rounded-[8px] bg-[#3C2317]/10 text-[#3C2317] flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693l-1.57-.393m15.6 0A2.25 2.25 0 0121 17.25v.75a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-.75a2.25 2.25 0 011.2-1.95"/>
                                    </svg>
                                </div>
                            </td>

                            <!-- Jumlah Produk Aktif -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#8C6246]/10 text-[#8C6246]">
                                    <span x-text="cat.products_count">0</span>
                                    <span>Menu</span>
                                </span>
                            </td>

                            <!-- Status Aktif / Nonaktif -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="cat.is_active == 1 ? 'bg-[#2E7D32]/10 text-[#2E7D32] border border-[#2E7D32]/30' : 'bg-stone-100 text-[#7A685D] border border-stone-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active == 1 ? 'bg-[#2E7D32]' : 'bg-[#7A685D]'"></span>
                                    <span x-text="cat.is_active == 1 ? 'Tampil di POS' : 'Disembunyikan'"></span>
                                </span>
                            </td>

                            <!-- Tombol Aksi -->
                            <td class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="openModalEdit(cat)"
                                        class="p-1.5 text-[#7A685D] hover:text-[#3C2317] hover:bg-[#F8F5F0] rounded-[8px] transition-colors focus:outline-none"
                                        title="Ubah Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openModalHapus(cat)"
                                        class="p-1.5 text-[#DC2626] hover:bg-red-50 rounded-[8px] transition-colors focus:outline-none"
                                        title="Hapus Kategori"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH KATEGORI (S-03) -->
    <div x-show="modalTambahOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div x-show="modalTambahOpen" @click="modalTambahOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>
            <div x-show="modalTambahOpen" class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10">
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]">Tambah Kategori Baru</h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Kategori akan muncul sebagai tab pilihan pada POS</p>
                    </div>
                    <button type="button" @click="modalTambahOpen = false" class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitTambah()" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Nama Kategori <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="formData.name"
                            @input="formData.slug = generateSlug(formData.name)"
                            required
                            placeholder="Contoh: Espresso Based"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Slug URL (Otomatis)
                        </label>
                        <input
                            type="text"
                            x-model="formData.slug"
                            required
                            class="w-full px-3.5 py-2 text-sm font-mono rounded-[10px] bg-[#F8F5F0] border border-[#8C6246]/20 text-[#7A685D] focus:outline-none"
                        >
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Urutan Tampil
                            </label>
                            <input
                                type="number"
                                min="1"
                                x-model="formData.sort_order"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Status Aktif
                            </label>
                            <select
                                x-model="formData.is_active"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                                <option value="1">Tampil di POS</option>
                                <option value="0">Disembunyikan</option>
                            </select>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button type="button" @click="modalTambahOpen = false" class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT KATEGORI (S-03) -->
    <div x-show="modalEditOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div x-show="modalEditOpen" @click="modalEditOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>
            <div x-show="modalEditOpen" class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10">
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]">Ubah Kategori Menu</h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Perbarui nama atau urutan kategori</p>
                    </div>
                    <button type="button" @click="modalEditOpen = false" class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit()" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Nama Kategori <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="formData.name"
                            required
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Slug URL
                        </label>
                        <input
                            type="text"
                            x-model="formData.slug"
                            required
                            class="w-full px-3.5 py-2 text-sm font-mono rounded-[10px] bg-[#F8F5F0] border border-[#8C6246]/20 text-[#7A685D] focus:outline-none"
                        >
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Urutan Tampil
                            </label>
                            <input
                                type="number"
                                min="1"
                                x-model="formData.sort_order"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Status Aktif
                            </label>
                            <select
                                x-model="formData.is_active"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                                <option value="1">Tampil di POS</option>
                                <option value="0">Disembunyikan</option>
                            </select>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button type="button" @click="modalEditOpen = false" class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS KATEGORI (US-03) -->
    <div x-show="modalHapusOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div x-show="modalHapusOpen" @click="modalHapusOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>
            <div x-show="modalHapusOpen" class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-red-50 text-[#DC2626] flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]">Hapus Kategori?</h2>
                        <template x-if="selectedCategory && selectedCategory.products_count > 0">
                            <div class="mt-2 p-3 rounded-[10px] bg-red-50 border border-red-200 text-xs text-[#DC2626] leading-relaxed">
                                <strong>Ditolak Sistem (US-03):</strong> Kategori <strong x-text="selectedCategory.name"></strong> masih memiliki <strong x-text="selectedCategory.products_count"></strong> menu aktif. Hapus atau pindahkan menu tersebut terlebih dahulu sebelum kategori dapat dihapus.
                            </div>
                        </template>
                        <template x-if="selectedCategory && selectedCategory.products_count === 0">
                            <p class="text-xs text-[#7A685D] mt-1 leading-relaxed">
                                Anda yakin ingin menghapus kategori <strong class="text-[#1A0F0A]" x-text="selectedCategory ? selectedCategory.name : ''"></strong>? Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </template>
                    </div>
                </div>
                <div class="mt-5 pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                    <button type="button" @click="modalHapusOpen = false" class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]">Batal</button>
                    <button
                        type="button"
                        @click="confirmHapus()"
                        :disabled="selectedCategory && selectedCategory.products_count > 0"
                        class="px-4 py-2 rounded-[10px] bg-[#DC2626] hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-soft"
                    >
                        Ya, Hapus Kategori
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
