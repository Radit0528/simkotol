{{--
  [FRONTEND] Halaman Manajemen Resep (BOM) & Kalkulator HPP (US-09, S-08, BR-14, BR-18, BR-19).
  Interactive composition builder: menghubungkan produk menu dengan komposisi bahan baku,
  perhitungan takaran desimal (3 digit), kalkulasi real-time HPP, margin keuntungan,
  dan estimasi ketersediaan porsi menu berdasarkan stok fisik bahan (BR-14).
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
            <span>&bull;</span>
            <a href="{{ route('ingredients.index') }}" class="hover:underline">Inventaris</a>
            <span>&bull;</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/15 text-[#3C2317]">S-08</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Manajemen Resep (BOM) & HPP (S-08)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Susun formula komposisi takaran bahan per menu, pantau HPP otomatis, margin profitabilitas, dan porsi tersedia (US-09, BR-14)
        </p>
    </div>
    <div class="flex items-center gap-2.5">
        <a
            href="{{ route('products.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            <span>Katalog Menu (S-04)</span>
        </a>

        <a
            href="{{ route('ingredients.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <span>Bahan Baku (S-05)</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        searchProduct: '',
        categoryFilter: 'all',
        selectedProduct: null,

        // Daftar Produk Menu
        products: {{ Js::from($products ?? []) }},
        
        // Daftar Bahan Baku Tersedia
        availableIngredients: {{ Js::from($ingredients ?? []) }},

        // State builder resep aktif untuk produk terpilih
        activeRecipeItems: [],

        init() {
            if (this.products.length > 0) {
                this.selectProduct(this.products[0]);
            }
        },

        selectProduct(product) {
            this.selectedProduct = product;
            // Kloning resep yang ada ke activeRecipeItems
            this.activeRecipeItems = (product.recipes || []).map(r => ({
                ingredient_id: r.ingredient_id,
                amount_required: parseFloat(r.amount_required) || 0
            }));
        },

        get filteredProducts() {
            return this.products.filter(p => {
                const matchSearch = this.searchProduct === '' ||
                    p.name.toLowerCase().includes(this.searchProduct.toLowerCase()) ||
                    (p.sku && p.sku.toLowerCase().includes(this.searchProduct.toLowerCase()));

                const matchCat = this.categoryFilter === 'all' || p.category_id == this.categoryFilter;
                return matchSearch && matchCat;
            });
        },

        getIngredient(id) {
            return this.availableIngredients.find(i => i.id == id) || null;
        },

        addRecipeRow() {
            // Pilih bahan yang belum ada di resep (US-09: 1 bahan hanya sekali per menu)
            const usedIds = this.activeRecipeItems.map(r => r.ingredient_id);
            const nextIng = this.availableIngredients.find(i => !usedIds.includes(i.id));

            if (!nextIng) {
                if (this.$store?.toasts) {
                    this.$store.toasts.add('Seluruh bahan baku yang tersedia telah ditambahkan ke resep.', 'info');
                }
                return;
            }

            this.activeRecipeItems.push({
                ingredient_id: nextIng.id,
                amount_required: nextIng.unit === 'pcs' ? 1 : (nextIng.unit === 'gram' ? 15 : 50)
            });
        },

        removeRecipeRow(index) {
            this.activeRecipeItems.splice(index, 1);
        },

        // Total HPP Resep Aktif (BR-18, BR-19)
        get totalHpp() {
            return this.activeRecipeItems.reduce((sum, item) => {
                const ing = this.getIngredient(item.ingredient_id);
                if (!ing) return sum;
                const costPerUnit = parseFloat(ing.cost_per_unit) || 0;
                const amount = parseFloat(item.amount_required) || 0;
                return sum + (costPerUnit * amount);
            }, 0);
        },

        // Estimasi Margin Profit (%)
        get marginPercent() {
            if (!this.selectedProduct || this.selectedProduct.price <= 0) return 0;
            const price = parseFloat(this.selectedProduct.price);
            const profit = price - this.totalHpp;
            return Math.round((profit / price) * 1000) / 10;
        },

        // Laba Kotor per Porsi (Rp)
        get grossProfitPerPortion() {
            if (!this.selectedProduct) return 0;
            return Math.max(0, parseFloat(this.selectedProduct.price) - this.totalHpp);
        },

        // Estimasi Porsi Tersedia berdasarkan rumus BR-14: min( floor(stok / takaran) )
        get maxPortionsAvailable() {
            if (this.activeRecipeItems.length === 0) return 999; // Menu tanpa resep dianggap selalu tersedia

            let minPortions = Infinity;
            this.activeRecipeItems.forEach(item => {
                const ing = this.getIngredient(item.ingredient_id);
                if (!ing) return;
                const stock = parseFloat(ing.stock) || 0;
                const req = parseFloat(item.amount_required) || 0;
                if (req > 0) {
                    const portions = Math.floor(stock / req);
                    if (portions < minPortions) {
                        minPortions = portions;
                    }
                }
            });

            return minPortions === Infinity ? 0 : Math.max(0, minPortions);
        },

        // Cari bahan penentu habis / bottleneck (BR-14)
        get bottleneckIngredient() {
            if (this.activeRecipeItems.length === 0) return null;

            let lowest = Infinity;
            let lowestName = null;

            this.activeRecipeItems.forEach(item => {
                const ing = this.getIngredient(item.ingredient_id);
                if (!ing) return;
                const stock = parseFloat(ing.stock) || 0;
                const req = parseFloat(item.amount_required) || 0;
                if (req > 0) {
                    const portions = Math.floor(stock / req);
                    if (portions < lowest) {
                        lowest = portions;
                        lowestName = ing.name;
                    }
                }
            });

            return lowestName;
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num));
        },

        formatDecimal(num) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 }).format(num);
        },

        saveRecipe() {
            if (!this.selectedProduct) return;

            // Update ke objek lokal produk
            this.selectedProduct.recipes = JSON.parse(JSON.stringify(this.activeRecipeItems));
            this.selectedProduct.cost_price = Math.round(this.totalHpp);
            this.selectedProduct.recipes_count = this.activeRecipeItems.length;

            if (this.$store?.toasts) {
                this.$store.toasts.add(`Formula resep untuk "${this.selectedProduct.name}" berhasil disimpan. HPP dihitung Rp ${this.formatNumber(this.totalHpp)}.`, 'success');
            }
        }
    }"
    class="space-y-6"
>
    <!-- TOP NOTIFICATION BANNER (BR-14 & BR-18 INFO) -->
    <div class="p-4 rounded-[14px] bg-white border border-[#8C6246]/20 shadow-soft flex items-start gap-3.5">
        <div class="w-9 h-9 rounded-full bg-[#8C6246]/15 text-[#3C2317] flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
        </div>
        <div class="text-xs text-[#1A0F0A] space-y-1">
            <div class="flex items-center gap-2">
                <span class="font-bold text-[#3C2317]">Prinsip Kalkulasi Resep & Ketersediaan POS (US-09, BR-14, BR-18):</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/10 text-[#8C6246]">Otomatis</span>
            </div>
            <p class="text-[#7A685D] leading-relaxed">
                Setiap menu terhubung dengan takaran bahan baku (gram/ml/pcs). Ketersediaan porsi dihitung otomatis dari <code class="text-[#3C2317] font-bold">min(floor(stok bahan &divide; takaran))</code> (BR-14). Mengubah komposisi resep hanya berlaku untuk pesanan baru dan tidak akan mengubah transaksi masa lalu (BR-18).
            </p>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SPLIT SCREEN BUILDER -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: DAFTAR MENU KATALOG (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            
            <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-[#1A0F0A]">Pilih Menu Produk</h2>
                    <span class="text-xs text-[#7A685D]" x-text="`${filteredProducts.length} Menu`"></span>
                </div>

                <!-- Search Input -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-[#7A685D]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        x-model="searchProduct"
                        placeholder="Cari nama menu kopi / kudapan..."
                        class="w-full pl-9 pr-3 py-2 rounded-[8px] border border-[#8C6246]/25 text-xs bg-[#FCFAF7]"
                    />
                </div>

                <!-- Filter Kategori Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-[11px]">
                    <button
                        type="button"
                        @click="categoryFilter = 'all'"
                        :class="categoryFilter === 'all' ? 'bg-[#3C2317] text-white' : 'bg-[#F8F5F0] text-[#7A685D] hover:bg-[#8C6246]/15'"
                        class="px-2.5 py-1 rounded-full font-semibold transition-all whitespace-nowrap"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="categoryFilter = '1'"
                        :class="categoryFilter === '1' ? 'bg-[#3C2317] text-white' : 'bg-[#F8F5F0] text-[#7A685D] hover:bg-[#8C6246]/15'"
                        class="px-2.5 py-1 rounded-full font-semibold transition-all whitespace-nowrap"
                    >
                        Espresso
                    </button>
                    <button
                        type="button"
                        @click="categoryFilter = '2'"
                        :class="categoryFilter === '2' ? 'bg-[#3C2317] text-white' : 'bg-[#F8F5F0] text-[#7A685D] hover:bg-[#8C6246]/15'"
                        class="px-2.5 py-1 rounded-full font-semibold transition-all whitespace-nowrap"
                    >
                        Manual Brew
                    </button>
                    <button
                        type="button"
                        @click="categoryFilter = '3'"
                        :class="categoryFilter === '3' ? 'bg-[#3C2317] text-white' : 'bg-[#F8F5F0] text-[#7A685D] hover:bg-[#8C6246]/15'"
                        class="px-2.5 py-1 rounded-full font-semibold transition-all whitespace-nowrap"
                    >
                        Non-Coffee
                    </button>
                </div>
            </div>

            <!-- List Menu Cards -->
            <div class="space-y-2.5 max-h-[620px] overflow-y-auto pr-1">
                <template x-for="prod in filteredProducts" :key="prod.id">
                    <div
                        @click="selectProduct(prod)"
                        :class="selectedProduct?.id === prod.id ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/40 shadow-md' : 'border-[#8C6246]/15 bg-white hover:border-[#8C6246]/40'"
                        class="p-4 rounded-[12px] border cursor-pointer transition-all duration-150 space-y-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-bold text-xs sm:text-sm text-[#1A0F0A] block" x-text="prod.name"></span>
                                <span class="text-[11px] text-[#8C6246] font-medium" x-text="prod.category_name"></span>
                            </div>
                            <span class="font-bold text-xs text-[#1A0F0A] font-mono whitespace-nowrap" x-text="`Rp ${formatNumber(prod.price)}`"></span>
                        </div>

                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-[#8C6246]/10">
                            <!-- Status Resep (Berapa bahan) -->
                            <div class="flex items-center gap-1 text-[#7A685D]">
                                <svg class="w-3.5 h-3.5 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                <span x-text="`${(prod.recipes || []).length} Bahan Baku`"></span>
                            </div>

                            <!-- HPP & Margin Badge -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#7A685D]" x-text="`HPP: Rp ${formatNumber(prod.cost_price || 0)}`"></span>
                                <span
                                    class="px-1.5 py-0.2 rounded font-bold text-[10px]"
                                    :class="prod.margin >= 60 ? 'bg-emerald-100 text-[#2E7D32]' : (prod.margin >= 40 ? 'bg-amber-100 text-[#B45309]' : 'bg-red-100 text-[#DC2626]')"
                                    x-text="`${prod.margin || 0}%`"
                                ></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        </div>

        <!-- RIGHT COLUMN: INTERACTIVE BOM BUILDER (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <template x-if="selectedProduct">
                <div class="bg-white rounded-[16px] p-6 border border-[#8C6246]/15 shadow-soft space-y-6">
                    
                    <!-- Selected Product Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-[#8C6246]/15 gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/15 text-[#3C2317] uppercase" x-text="selectedProduct.category_name"></span>
                                <span class="text-[11px] text-[#7A685D] font-mono" x-text="selectedProduct.sku"></span>
                            </div>
                            <h2 class="text-lg font-black text-[#1A0F0A] mt-1" x-text="selectedProduct.name"></h2>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-[#7A685D] block">Harga Jual Menu (POS)</span>
                            <span class="text-lg font-black text-[#3C2317] font-mono" x-text="`Rp ${formatNumber(selectedProduct.price)}`"></span>
                        </div>
                    </div>

                    <!-- Composition Rows Table -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-[#1A0F0A] uppercase tracking-wider">
                                Formula Komposisi Bahan Baku (BOM)
                            </h3>
                            <button
                                type="button"
                                @click="addRecipeRow()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[8px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft transition-all"
                            >
                                <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                <span>Tambah Bahan (+)</span>
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto border border-[#8C6246]/15 rounded-[12px]">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#FCFAF7] border-b border-[#8C6246]/15 text-[11px] font-bold text-[#3C2317]">
                                        <th class="py-2.5 px-3">Bahan Baku</th>
                                        <th class="py-2.5 px-3">Takaran / Porsi <span class="text-[#DC2626]">*</span></th>
                                        <th class="py-2.5 px-3">Cost / Unit</th>
                                        <th class="py-2.5 px-3">Subtotal HPP</th>
                                        <th class="py-2.5 px-2 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#8C6246]/10 text-xs">
                                    <template x-for="(item, idx) in activeRecipeItems" :key="idx">
                                        <tr class="hover:bg-[#FCFAF7]">
                                            <!-- Pilih Bahan -->
                                            <td class="py-2.5 px-3 min-w-[200px]">
                                                <select
                                                    x-model="item.ingredient_id"
                                                    class="w-full px-2.5 py-1.5 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white font-semibold"
                                                >
                                                    <template x-for="ing in availableIngredients" :key="ing.id">
                                                        <option :value="ing.id" x-text="`${ing.name} (${ing.unit})`"></option>
                                                    </template>
                                                </select>
                                                <span class="text-[10px] text-[#7A685D] mt-0.5 block" x-text="`Sisa stok: ${formatDecimal(getIngredient(item.ingredient_id)?.stock || 0)} ${getIngredient(item.ingredient_id)?.unit || ''}`"></span>
                                            </td>

                                            <!-- Input Takaran -->
                                            <td class="py-2.5 px-3 min-w-[120px]">
                                                <div class="relative">
                                                    <input
                                                        type="number"
                                                        step="0.001"
                                                        min="0.001"
                                                        x-model.number="item.amount_required"
                                                        required
                                                        class="w-full pr-10 pl-2.5 py-1.5 rounded-[8px] border border-[#8C6246]/30 text-xs font-bold font-mono"
                                                    />
                                                    <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[11px] font-bold text-[#7A685D]" x-text="getIngredient(item.ingredient_id)?.unit || ''"></span>
                                                </div>
                                            </td>

                                            <!-- Cost Satuan -->
                                            <td class="py-2.5 px-3 font-mono text-[#7A685D]">
                                                <span x-text="`Rp ${formatNumber(getIngredient(item.ingredient_id)?.cost_per_unit || 0)}`"></span>
                                            </td>

                                            <!-- Subtotal Biaya HPP Takaran -->
                                            <td class="py-2.5 px-3 font-bold text-[#1A0F0A] font-mono">
                                                <span x-text="`Rp ${formatNumber((item.amount_required || 0) * (getIngredient(item.ingredient_id)?.cost_per_unit || 0))}`"></span>
                                            </td>

                                            <!-- Tombol Hapus Baris -->
                                            <td class="py-2.5 px-2 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeRecipeRow(idx)"
                                                    class="p-1 rounded text-gray-400 hover:text-[#DC2626] hover:bg-red-50"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty Recipe Warning -->
                                    <template x-if="activeRecipeItems.length === 0">
                                        <tr>
                                            <td colspan="5" class="text-center py-6 text-xs text-[#7A685D]">
                                                Menu ini belum memiliki komposisi bahan baku (BOM). Klik "+ Tambah Bahan" di atas.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- REAL-TIME HPP & PROFIT MARGIN CALCULATOR CARD -->
                    <div class="p-4 rounded-[14px] bg-gradient-to-br from-[#FCFAF7] to-[#F8F5F0] border border-[#8C6246]/25 space-y-4">
                        <div class="flex items-center justify-between border-b border-[#8C6246]/15 pb-2">
                            <span class="text-xs font-bold text-[#3C2317] uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span>Kalkulator HPP & Profitabilitas Resep</span>
                            </span>
                            <span class="text-[11px] text-[#7A685D]">Otomatis Terkalkulasi</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Total HPP -->
                            <div class="bg-white p-3 rounded-[10px] border border-[#8C6246]/15 shadow-2xs">
                                <span class="text-[10px] text-[#7A685D] block">Total HPP Resep</span>
                                <span class="text-sm font-black text-[#1A0F0A] font-mono mt-0.5 block" x-text="`Rp ${formatNumber(totalHpp)}`"></span>
                            </div>

                            <!-- Laba Kotor / Porsi -->
                            <div class="bg-white p-3 rounded-[10px] border border-[#8C6246]/15 shadow-2xs">
                                <span class="text-[10px] text-[#7A685D] block">Laba Kotor / Porsi</span>
                                <span class="text-sm font-black text-[#2E7D32] font-mono mt-0.5 block" x-text="`Rp ${formatNumber(grossProfitPerPortion)}`"></span>
                            </div>

                            <!-- Margin % -->
                            <div class="bg-white p-3 rounded-[10px] border border-[#8C6246]/15 shadow-2xs">
                                <span class="text-[10px] text-[#7A685D] block">Margin Keuntungan</span>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="text-sm font-black font-mono" :class="marginPercent >= 60 ? 'text-[#2E7D32]' : (marginPercent >= 40 ? 'text-[#B45309]' : 'text-[#DC2626]')" x-text="`${marginPercent}%`"></span>
                                    <span class="text-[9px] font-bold" :class="marginPercent >= 60 ? 'text-[#2E7D32]' : 'text-[#B45309]'" x-text="marginPercent >= 60 ? 'Sehat' : 'Cukup'"></span>
                                </div>
                            </div>

                            <!-- Porsi Siap Jual (BR-14) -->
                            <div class="bg-white p-3 rounded-[10px] border border-[#8C6246]/15 shadow-2xs">
                                <span class="text-[10px] text-[#7A685D] block">Porsi Siap Jual (BR-14)</span>
                                <div class="mt-0.5 flex items-baseline gap-1">
                                    <span class="text-sm font-black" :class="maxPortionsAvailable <= 0 ? 'text-[#DC2626]' : 'text-[#3C2317]'" x-text="formatNumber(maxPortionsAvailable)"></span>
                                    <span class="text-[10px] text-[#7A685D]">Porsi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottleneck info -->
                        <template x-if="bottleneckIngredient">
                            <div class="flex items-center justify-between text-[11px] text-[#7A685D] bg-white/70 px-3 py-1.5 rounded-[8px]">
                                <span>Bahan Penentu Batas Stok: <strong class="text-[#3C2317]" x-text="bottleneckIngredient"></strong></span>
                                <span :class="maxPortionsAvailable <= 0 ? 'text-[#DC2626] font-bold' : 'text-[#2E7D32]'" x-text="maxPortionsAvailable <= 0 ? '❌ Menu Habis di POS' : 'Ready Dijual di Kasir'"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Action Save Button -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="saveRecipe()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs sm:text-sm font-semibold shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
                        >
                            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Simpan Formula Resep (BOM)</span>
                        </button>
                    </div>

                </div>
            </template>

        </div>

    </div>
</div>
@endsection
