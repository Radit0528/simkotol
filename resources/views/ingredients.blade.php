{{--
  [FRONTEND] Halaman Manajemen Bahan Baku & Pemasok (US-06, US-10, S-05, BR-20).
  Halaman pengelolaan inventaris bahan baku kedai Kopi Titik Nol:
  pemantauan stok fisik, satuan dasar (gram/ml/pcs), peringatan stok minimum,
  indikator status stok (Aman/Menipis/Habis), modal CRUD bahan & supplier.
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
            <span>&bull;</span>
            <span class="text-[#3C2317]">Inventaris</span>
            <span>&bull;</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/15 text-[#3C2317]">S-05</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Manajemen Bahan Baku & Pemasok (S-05)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Pantau ketersediaan stok fisik, takaran resep, peringatan stok kritis, dan kelola mitra supplier kedai Kopi Titik Nol (US-06, BR-20)
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <button
            type="button"
            @click="modalSupplierOpen = true"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
            </svg>
            <span>Mitra Supplier</span>
        </button>

        <a
            href="{{ route('stock.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#2E7D32]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Stok Masuk (S-06)</span>
        </a>

        <button
            type="button"
            @click="openModalTambah()"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
        >
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Tambah Bahan Baru</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        search: '',
        unitFilter: 'all',
        statusFilter: 'all',
        modalTambahOpen: false,
        modalEditOpen: false,
        modalHapusOpen: false,
        modalSupplierOpen: false,
        selectedIngredient: null,

        formData: {
            id: null,
            sku: '',
            name: '',
            unit: 'gram',
            stock: 0,
            min_stock: 0,
            cost_per_unit: 0,
            is_active: 1
        },

        supplierForm: {
            name: '',
            phone: '',
            address: '',
            notes: ''
        },

        ingredients: {{ Js::from($ingredients ?? []) }},
        suppliers: {{ Js::from($suppliers ?? []) }},

        get filteredIngredients() {
            return this.ingredients.filter(item => {
                const matchSearch = this.search === '' ||
                    item.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    (item.sku && item.sku.toLowerCase().includes(this.search.toLowerCase()));

                const matchUnit = this.unitFilter === 'all' || item.unit === this.unitFilter;
                
                let matchStatus = true;
                if (this.statusFilter === 'out') {
                    matchStatus = parseFloat(item.stock) <= 0;
                } else if (this.statusFilter === 'low') {
                    matchStatus = parseFloat(item.stock) > 0 && parseFloat(item.stock) <= parseFloat(item.min_stock);
                } else if (this.statusFilter === 'ok') {
                    matchStatus = parseFloat(item.stock) > parseFloat(item.min_stock);
                }

                return matchSearch && matchUnit && matchStatus;
            });
        },

        get stats() {
            let total = this.ingredients.length;
            let safe = 0;
            let low = 0;
            let out = 0;
            let totalAsset = 0;

            this.ingredients.forEach(i => {
                const stock = parseFloat(i.stock) || 0;
                const min = parseFloat(i.min_stock) || 0;
                const cost = parseFloat(i.cost_per_unit) || 0;

                if (stock <= 0) {
                    out++;
                } else if (stock <= min) {
                    low++;
                } else {
                    safe++;
                }

                if (stock > 0) {
                    totalAsset += (stock * cost);
                }
            });

            return { total, safe, low, out, totalAsset };
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },

        formatDecimal(num) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 }).format(num);
        },

        openModalTambah() {
            this.formData = {
                id: null,
                sku: 'ING-' + Math.floor(1000 + Math.random() * 9000),
                name: '',
                unit: 'gram',
                stock: 0,
                min_stock: 100,
                cost_per_unit: 0,
                is_active: 1
            };
            this.modalTambahOpen = true;
        },

        openModalEdit(item) {
            this.selectedIngredient = item;
            this.formData = {
                id: item.id,
                sku: item.sku || '',
                name: item.name,
                unit: item.unit,
                stock: item.stock,
                min_stock: item.min_stock,
                cost_per_unit: item.cost_per_unit,
                is_active: item.is_active ? 1 : 0
            };
            this.modalEditOpen = true;
        },

        confirmDelete(item) {
            this.selectedIngredient = item;
            this.modalHapusOpen = true;
        },

        saveIngredient() {
            if (!this.formData.name.trim()) return;

            if (this.formData.id) {
                // Update
                const index = this.ingredients.findIndex(i => i.id === this.formData.id);
                if (index !== -1) {
                    this.ingredients[index].name = this.formData.name;
                    this.ingredients[index].min_stock = this.formData.min_stock;
                    this.ingredients[index].is_active = this.formData.is_active;
                }
                this.modalEditOpen = false;
                if (this.$store?.toasts) {
                    this.$store.toasts.add('Data bahan ' + this.formData.name + ' berhasil diperbarui', 'success');
                }
            } else {
                // Tambah baru
                const newId = this.ingredients.length > 0 ? Math.max(...this.ingredients.map(i => i.id)) + 1 : 1;
                this.ingredients.unshift({
                    id: newId,
                    sku: this.formData.sku,
                    name: this.formData.name,
                    unit: this.formData.unit,
                    stock: parseFloat(this.formData.stock) || 0,
                    min_stock: parseFloat(this.formData.min_stock) || 0,
                    cost_per_unit: parseFloat(this.formData.cost_per_unit) || 0,
                    is_active: 1,
                    recipes_count: 0
                });
                this.modalTambahOpen = false;
                if (this.$store?.toasts) {
                    this.$store.toasts.add('Bahan baku baru berhasil didaftarkan', 'success');
                }
            }
        },

        deleteIngredient() {
            if (!this.selectedIngredient) return;
            // Cegah hapus jika ada resep terkait
            if (this.selectedIngredient.recipes_count > 0) {
                if (this.$store?.toasts) {
                    this.$store.toasts.add('Gagal: Bahan baku terhubung dengan resep aktif menu. Hapus dari resep terlebih dahulu.', 'danger');
                }
                this.modalHapusOpen = false;
                return;
            }

            this.ingredients = this.ingredients.filter(i => i.id !== this.selectedIngredient.id);
            this.modalHapusOpen = false;
            if (this.$store?.toasts) {
                this.$store.toasts.add('Bahan baku berhasil dinonaktifkan/dihapus', 'success');
            }
        },

        addSupplier() {
            if (!this.supplierForm.name.trim()) return;
            const newId = this.suppliers.length > 0 ? Math.max(...this.suppliers.map(s => s.id)) + 1 : 1;
            this.suppliers.push({
                id: newId,
                name: this.supplierForm.name,
                phone: this.supplierForm.phone,
                address: this.supplierForm.address,
                notes: this.supplierForm.notes,
                is_active: 1
            });
            this.supplierForm = { name: '', phone: '', address: '', notes: '' };
            if (this.$store?.toasts) {
                this.$store.toasts.add('Pemasok baru berhasil disimpan', 'success');
            }
        }
    }"
    class="space-y-6"
>
    <!-- TOP KPI STATS SUMMARY (5 Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- Total Bahan -->
        <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold text-[#7A685D] block">Total Bahan Baku</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-[#1A0F0A]" x-text="stats.total"></span>
                <span class="text-xs text-[#7A685D]">Item</span>
            </div>
            <span class="text-[11px] text-[#8C6246] mt-1 block">Inventaris Dasar (US-06)</span>
        </div>

        <!-- Stok Aman -->
        <div class="bg-white rounded-[14px] p-4 border border-[#2E7D32]/25 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#2E7D32]">Stok Aman</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#2E7D32]"></span>
            </div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-[#2E7D32]" x-text="stats.safe"></span>
                <span class="text-xs text-[#7A685D]">Item</span>
            </div>
            <span class="text-[11px] text-[#7A685D] mt-1 block">&gt; Stok Minimum</span>
        </div>

        <!-- Stok Menipis (Alert Amber) -->
        <div class="bg-white rounded-[14px] p-4 border border-[#D97706]/30 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#B45309]">Stok Menipis</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#D97706] animate-pulse"></span>
            </div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-[#B45309]" x-text="stats.low"></span>
                <span class="text-xs text-[#7A685D]">Item</span>
            </div>
            <span class="text-[11px] text-[#B45309] font-medium mt-1 block">&le; Min. Stok (BR-20)</span>
        </div>

        <!-- Stok Habis (Alert Merah) -->
        <div class="bg-white rounded-[14px] p-4 border border-[#DC2626]/30 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-[#DC2626]">Stok Habis</span>
                <span class="w-2.5 h-2.5 rounded-full bg-[#DC2626]"></span>
            </div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-[#DC2626]" x-text="stats.out"></span>
                <span class="text-xs text-[#7A685D]">Item</span>
            </div>
            <span class="text-[11px] text-[#DC2626] font-medium mt-1 block">Kritis &le; 0 (S-22)</span>
        </div>

        <!-- Total Valuasi Aset Stok -->
        <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-[#3C2317] to-[#3D241C] rounded-[14px] p-4 text-white shadow-soft">
            <span class="text-xs font-medium text-[#D4A373] block">Valuasi Aset Stok</span>
            <div class="mt-1">
                <span class="text-lg sm:text-xl font-black text-white" x-text="`Rp ${formatNumber(stats.totalAsset)}`"></span>
            </div>
            <span class="text-[10px] text-white/70 mt-1 block">Moving Average Cost (BR-19)</span>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <!-- Search Input -->
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#7A685D]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <input
                type="text"
                x-model="search"
                placeholder="Cari nama bahan, kode SKU (misal: ING-KOP01)..."
                class="w-full pl-10 pr-4 py-2.5 rounded-[10px] border border-[#8C6246]/25 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/40 focus:border-[#8C6246]"
            />
        </div>

        <!-- Filters (Unit & Status) -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Satuan -->
            <select
                x-model="unitFilter"
                class="px-3 py-2 rounded-[10px] border border-[#8C6246]/25 text-xs font-semibold text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/40"
            >
                <option value="all">Semua Satuan</option>
                <option value="gram">Gram (gr)</option>
                <option value="ml">Mililiter (ml)</option>
                <option value="pcs">Pieces (pcs)</option>
            </select>

            <!-- Filter Status Stok -->
            <select
                x-model="statusFilter"
                class="px-3 py-2 rounded-[10px] border border-[#8C6246]/25 text-xs font-semibold text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/40"
            >
                <option value="all">Semua Status Stok</option>
                <option value="ok">Stok Aman</option>
                <option value="low">⚠️ Stok Menipis</option>
                <option value="out">❌ Stok Habis</option>
            </select>

            <!-- Reset Filter -->
            <template x-if="search !== '' || unitFilter !== 'all' || statusFilter !== 'all'">
                <button
                    type="button"
                    @click="search = ''; unitFilter = 'all'; statusFilter = 'all'"
                    class="px-2.5 py-2 text-xs font-semibold text-[#DC2626] hover:bg-red-50 rounded-[10px] transition-colors"
                >
                    Reset Filter
                </button>
            </template>
        </div>
    </div>

    <!-- MAIN INGREDIENT INVENTORY TABLE -->
    <div class="bg-white rounded-[14px] border border-[#8C6246]/15 shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#3C2317] text-white text-[11px] font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">SKU & Bahan Baku</th>
                        <th class="py-3 px-3">Satuan</th>
                        <th class="py-3 px-4">Stok Fisik Saat Ini</th>
                        <th class="py-3 px-3">Min. Stok</th>
                        <th class="py-3 px-4">Harga Satuan (Cost)</th>
                        <th class="py-3 px-4">Nilai Aset</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#8C6246]/10 text-xs">
                    <template x-for="item in filteredIngredients" :key="item.id">
                        <tr class="hover:bg-[#FCFAF7] transition-colors">
                            <!-- Nama & SKU -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-8 h-8 rounded-[8px] flex items-center justify-center font-bold text-xs"
                                        :class="{
                                            'bg-[#8C6246]/15 text-[#3C2317]': item.unit === 'gram',
                                            'bg-blue-100 text-blue-800': item.unit === 'ml',
                                            'bg-amber-100 text-amber-800': item.unit === 'pcs'
                                        }"
                                    >
                                        <span x-text="item.unit.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <span class="font-bold text-[#1A0F0A] block" x-text="item.name"></span>
                                        <span class="text-[10px] text-[#7A685D] font-mono" x-text="item.sku || '-'"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Satuan Dasar -->
                            <td class="py-3.5 px-3">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-[#8C6246]/15 text-[#3C2317]': item.unit === 'gram',
                                        'bg-blue-50 text-blue-700': item.unit === 'ml',
                                        'bg-amber-50 text-amber-700': item.unit === 'pcs'
                                    }"
                                    x-text="item.unit"
                                ></span>
                            </td>

                            <!-- Stok Fisik -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm" :class="item.stock <= 0 ? 'text-[#DC2626]' : (item.stock <= item.min_stock ? 'text-[#B45309]' : 'text-[#1A0F0A]')" x-text="formatDecimal(item.stock)"></span>
                                        <span class="text-[11px] text-[#7A685D]" x-text="item.unit"></span>
                                    </div>
                                    <!-- Progress indicator bar -->
                                    <div class="w-28 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="item.stock <= 0 ? 'bg-[#DC2626] w-full' : (item.stock <= item.min_stock ? 'bg-[#D97706] w-2/5' : 'bg-[#2E7D32] w-full')"
                                        ></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Min Stok -->
                            <td class="py-3.5 px-3">
                                <span class="font-semibold text-[#7A685D]" x-text="formatDecimal(item.min_stock)"></span>
                                <span class="text-[10px] text-[#7A685D]" x-text="item.unit"></span>
                            </td>

                            <!-- Cost Per Unit -->
                            <td class="py-3.5 px-4 font-mono font-medium text-[#1A0F0A]">
                                <span x-text="`Rp ${formatNumber(item.cost_per_unit)}`"></span>
                                <span class="text-[10px] text-[#7A685D]" x-text="`/${item.unit}`"></span>
                            </td>

                            <!-- Nilai Aset -->
                            <td class="py-3.5 px-4 font-bold text-[#3C2317]">
                                <span x-text="`Rp ${formatNumber(Math.max(0, item.stock * item.cost_per_unit))}`"></span>
                            </td>

                            <!-- Status Badge (Aman/Menipis/Habis) -->
                            <td class="py-3.5 px-3">
                                <template x-if="item.stock <= 0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-[#DC2626] border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#DC2626]"></span>
                                        <span>Habis</span>
                                    </span>
                                </template>
                                <template x-if="item.stock > 0 && item.stock <= item.min_stock">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-[#B45309] border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#D97706] animate-pulse"></span>
                                        <span>Menipis</span>
                                    </span>
                                </template>
                                <template x-if="item.stock > item.min_stock">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-[#2E7D32] border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32]"></span>
                                        <span>Aman</span>
                                    </span>
                                </template>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Tombol Edit -->
                                    <button
                                        type="button"
                                        @click="openModalEdit(item)"
                                        class="p-1.5 rounded-[8px] text-[#8C6246] hover:bg-[#8C6246]/10 hover:text-[#3C2317] transition-colors"
                                        title="Edit Bahan Baku"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Mutasi Cepat -->
                                    <a
                                        :href="`{{ route('stock.index') }}?ingredient_id=${item.id}`"
                                        class="p-1.5 rounded-[8px] text-[#2E7D32] hover:bg-emerald-50 transition-colors"
                                        title="Catat Mutasi / Beli Bahan Ini"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                        </svg>
                                    </a>

                                    <!-- Tombol Hapus -->
                                    <button
                                        type="button"
                                        @click="confirmDelete(item)"
                                        class="p-1.5 rounded-[8px] text-gray-400 hover:text-[#DC2626] hover:bg-red-50 transition-colors"
                                        title="Hapus Bahan Baku"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <template x-if="filteredIngredients.length === 0">
                        <tr>
                            <td colspan="8" class="text-center py-12 text-[#7A685D]">
                                <svg class="w-12 h-12 mx-auto mb-2 text-[#8C6246]/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                </svg>
                                <p class="text-sm font-bold text-[#1A0F0A]">Tidak ada data bahan baku</p>
                                <p class="text-xs text-[#7A685D] mt-0.5">Coba ubah kata kunci pencarian atau reset filter satuan dan status.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH BAHAN BAKU BARU (US-06) -->
    <!-- ========================================================================= -->
    <div
        x-show="modalTambahOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="modalTambahOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-[16px] max-w-lg w-full p-6 shadow-xl border border-[#8C6246]/20">
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h3 class="text-base font-bold text-[#1A0F0A]">Tambah Bahan Baku Baru (US-06)</h3>
                        <p class="text-xs text-[#7A685D] mt-0.5">Daftarkan bahan baku baru beserta satuan dasar dan stok awal</p>
                    </div>
                    <button type="button" @click="modalTambahOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveIngredient()" class="space-y-4 pt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Kode SKU</label>
                            <input
                                type="text"
                                x-model="formData.sku"
                                placeholder="ING-KOP01"
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-mono uppercase bg-[#FCFAF7]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Satuan Dasar <span class="text-[#DC2626]">*</span></label>
                            <select
                                x-model="formData.unit"
                                required
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            >
                                <option value="gram">gram (Biji Kopi, Bubuk, Powder)</option>
                                <option value="ml">ml (Susu, Sirup, Air, Ekstrak)</option>
                                <option value="pcs">pcs (Cup, Sedotan, Pastry Frozen)</option>
                            </select>
                            <span class="text-[10px] text-[#B45309] mt-0.5 block">Satuan terkunci setelah ada mutasi (US-06)</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Nama Bahan Baku <span class="text-[#DC2626]">*</span></label>
                        <input
                            type="text"
                            x-model="formData.name"
                            required
                            placeholder="Contoh: Biji Kopi Arabika House Blend"
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Stok Awal</label>
                            <input
                                type="number"
                                step="0.001"
                                min="0"
                                x-model.number="formData.stock"
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Min. Stok (Alert)</label>
                            <input
                                type="number"
                                step="0.001"
                                min="0"
                                x-model.number="formData.min_stock"
                                required
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Harga Awal / Satuan</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model.number="formData.cost_per_unit"
                                required
                                placeholder="Rp/unit"
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalTambahOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-xs font-semibold text-white shadow-soft"
                        >
                            Simpan Bahan Baku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDIT BAHAN BAKU -->
    <!-- ========================================================================= -->
    <div
        x-show="modalEditOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="modalEditOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-[16px] max-w-lg w-full p-6 shadow-xl border border-[#8C6246]/20">
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h3 class="text-base font-bold text-[#1A0F0A]">Edit Bahan Baku</h3>
                        <p class="text-xs text-[#7A685D] mt-0.5">Perbarui nama atau batas minimum stok bahan</p>
                    </div>
                    <button type="button" @click="modalEditOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveIngredient()" class="space-y-4 pt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Kode SKU</label>
                            <input
                                type="text"
                                x-model="formData.sku"
                                readonly
                                class="w-full px-3 py-2 rounded-[10px] border border-gray-200 text-xs font-mono uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Satuan Dasar (Terkunci)</label>
                            <input
                                type="text"
                                x-model="formData.unit"
                                readonly
                                class="w-full px-3 py-2 rounded-[10px] border border-gray-200 text-xs font-bold uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Nama Bahan Baku <span class="text-[#DC2626]">*</span></label>
                        <input
                            type="text"
                            x-model="formData.name"
                            required
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Batas Minimum Stok (Alert)</label>
                            <input
                                type="number"
                                step="0.001"
                                min="0"
                                x-model.number="formData.min_stock"
                                required
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Status Keaktifan</label>
                            <select
                                x-model.number="formData.is_active"
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold"
                            >
                                <option :value="1">Aktif Digunakan</option>
                                <option :value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalEditOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-xs font-semibold text-white shadow-soft"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: CRUD SUPPLIER / MITRA PEMASOK -->
    <!-- ========================================================================= -->
    <div
        x-show="modalSupplierOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="modalSupplierOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-[16px] max-w-2xl w-full p-6 shadow-xl border border-[#8C6246]/20 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#8C6246]/15">
                    <div>
                        <h3 class="text-base font-bold text-[#1A0F0A]">Manajemen Mitra Supplier (US-06)</h3>
                        <p class="text-xs text-[#7A685D] mt-0.5">Daftar kontak pemasok bahan baku kopi, susu, sirup, dan kemasan</p>
                    </div>
                    <button type="button" @click="modalSupplierOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form Tambah Supplier Cepat -->
                <form @submit.prevent="addSupplier()" class="bg-[#FCFAF7] p-4 rounded-[12px] border border-[#8C6246]/20 space-y-3">
                    <span class="text-xs font-bold text-[#3C2317] block">Tambah Supplier Baru</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input
                            type="text"
                            x-model="supplierForm.name"
                            required
                            placeholder="Nama Supplier / Badan Usaha *"
                            class="px-3 py-2 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white"
                        />
                        <input
                            type="text"
                            x-model="supplierForm.phone"
                            placeholder="No. WhatsApp / Telepon"
                            class="px-3 py-2 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white"
                        />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input
                            type="text"
                            x-model="supplierForm.address"
                            placeholder="Alamat Supplier"
                            class="px-3 py-2 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white"
                        />
                        <input
                            type="text"
                            x-model="supplierForm.notes"
                            placeholder="Catatan / Spesialisasi Bahan"
                            class="px-3 py-2 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white"
                        />
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="px-3.5 py-1.5 rounded-[8px] bg-[#3C2317] text-white text-xs font-semibold hover:bg-[#3D241C]"
                        >
                            + Simpan Supplier
                        </button>
                    </div>
                </form>

                <!-- Daftar Supplier -->
                <div class="space-y-2.5 max-h-60 overflow-y-auto">
                    <template x-for="sup in suppliers" :key="sup.id">
                        <div class="p-3 rounded-[10px] border border-[#8C6246]/15 hover:bg-[#FCFAF7] flex items-center justify-between transition-colors">
                            <div>
                                <span class="font-bold text-xs text-[#1A0F0A] block" x-text="sup.name"></span>
                                <div class="flex items-center gap-2 text-[11px] text-[#7A685D] mt-0.5">
                                    <span x-text="`Telp: ${sup.phone || '-'}`"></span>
                                    <span>&bull;</span>
                                    <span x-text="sup.address || 'Alamat tidak dicantumkan'"></span>
                                </div>
                                <template x-if="sup.notes">
                                    <span class="text-[10px] text-[#8C6246] block mt-0.5" x-text="`Catatan: ${sup.notes}`"></span>
                                </template>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#2E7D32]/10 text-[#2E7D32]">Aktif</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: KONFIRMASI HAPUS BAHAN BAKU -->
    <!-- ========================================================================= -->
    <div
        x-show="modalHapusOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="modalHapusOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-[16px] max-w-sm w-full p-6 shadow-xl border border-red-200 text-center space-y-4">
                <div class="w-12 h-12 rounded-full bg-red-100 text-[#DC2626] mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#1A0F0A]">Hapus Bahan Baku?</h3>
                    <p class="text-xs text-[#7A685D] mt-1">
                        Apakah Anda yakin ingin menghapus <strong class="text-[#1A0F0A]" x-text="selectedIngredient?.name"></strong>?
                    </p>
                    <p class="text-[11px] text-[#DC2626] mt-2 font-medium">
                        Perhatian: Sistem menolak penghapusan jika bahan ini masih terikat dengan resep menu aktif (US-06).
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="modalHapusOpen = false"
                        class="px-4 py-2 rounded-[10px] bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteIngredient()"
                        class="px-4 py-2 rounded-[10px] bg-[#DC2626] hover:bg-red-700 text-xs font-semibold text-white shadow-soft"
                    >
                        Ya, Hapus Bahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
