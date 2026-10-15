{{--
  [FRONTEND] Halaman Manajemen Menu & Kalkulator Margin HPP (US-04, US-05, S-04).
  Halaman mandiri (standalone page) untuk katalog menu, kartu produk, switch status aktif/habis (sold out),
  serta kalkulator margin HPP real-time dan upload preview foto 600x600.
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('categories.index') }}" class="hover:underline">Kategori Menu</a>
            <span>&bull;</span>
            <span class="text-[#3C2317]">Katalog Produk</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Manajemen Menu & HPP (S-04)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Kelola resep harga jual, margin keuntungan HPP, dan ketersediaan menu kedai Kopi Titik Nol
        </p>
    </div>
    <div class="flex items-center gap-2.5">
        <a
            href="{{ route('categories.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            <span>Kelola Kategori (S-03)</span>
        </a>
        <button
            type="button"
            @click="openModalTambah()"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
        >
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Tambah Menu Baru</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        search: '',
        selectedCategory: 'all',
        statusFilter: 'all',
        modalTambahOpen: false,
        modalEditOpen: false,
        modalHapusOpen: false,
        selectedProduct: null,
        imagePreview: null,
        formData: {
            id: null,
            name: '',
            category_id: '',
            sku: '',
            description: '',
            price: 20000,
            cost_price: 7500,
            is_active: 1,
            is_sold_out: 0,
            image_url: ''
        },
        categories: {{ Js::from($categories ?? []) }},
        products: {{ Js::from($products ?? []) }},

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number || 0);
        },

        calculateMargin(price, hpp) {
            const p = parseInt(price) || 0;
            const h = parseInt(hpp) || 0;
            if (p <= 0) return 0;
            return Math.round(((p - h) / p) * 100);
        },

        calculateProfit(price, hpp) {
            const p = parseInt(price) || 0;
            const h = parseInt(hpp) || 0;
            return p - h;
        },

        get filteredProducts() {
            return this.products.filter(p => {
                const matchSearch = this.search === '' ||
                    p.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    (p.sku && p.sku.toLowerCase().includes(this.search.toLowerCase()));

                const matchCategory = this.selectedCategory === 'all' || p.category_id == this.selectedCategory;

                let matchStatus = true;
                if (this.statusFilter === 'active') matchStatus = p.is_active == 1 && p.is_sold_out == 0;
                else if (this.statusFilter === 'sold_out') matchStatus = p.is_sold_out == 1;
                else if (this.statusFilter === 'inactive') matchStatus = p.is_active == 0;

                return matchSearch && matchCategory && matchStatus;
            });
        },

        get stats() {
            return {
                total: this.products.length,
                active: this.products.filter(p => p.is_active == 1 && p.is_sold_out == 0).length,
                soldOut: this.products.filter(p => p.is_sold_out == 1).length,
                avgMargin: this.products.length > 0 ?
                    Math.round(this.products.reduce((acc, p) => acc + this.calculateMargin(p.price, p.cost_price), 0) / this.products.length) : 0
            };
        },

        openModalTambah() {
            this.formData = {
                id: null,
                name: '',
                category_id: this.categories.length > 0 ? this.categories[0].id : '',
                sku: 'SKT-' + Math.floor(100 + Math.random() * 900),
                description: '',
                price: 22000,
                cost_price: 8000,
                is_active: 1,
                is_sold_out: 0,
                image_url: ''
            };
            this.imagePreview = null;
            this.modalTambahOpen = true;
        },

        openModalEdit(product) {
            this.selectedProduct = product;
            this.formData = {
                id: product.id,
                name: product.name,
                category_id: product.category_id,
                sku: product.sku || '',
                description: product.description || '',
                price: product.price,
                cost_price: product.cost_price,
                is_active: product.is_active,
                is_sold_out: product.is_sold_out,
                image_url: product.image_url || ''
            };
            this.imagePreview = product.image_url || null;
            this.modalEditOpen = true;
        },

        openModalHapus(product) {
            this.selectedProduct = product;
            this.modalHapusOpen = true;
        },

        handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                window.toast('Ukuran file foto maksimal 2 MB (US-04)', 'danger');
                return;
            }
            const reader = new FileReader();
            reader.onload = (event) => {
                this.imagePreview = event.target.result;
                this.formData.image_url = event.target.result;
            };
            reader.readAsDataURL(file);
        },

        toggleSoldOut(product) {
            product.is_sold_out = product.is_sold_out == 1 ? 0 : 1;
            const statusText = product.is_sold_out == 1 ? 'ditandai HABIS (US-05)' : 'tersedia kembali';
            window.toast(`Menu '${product.name}' ${statusText}`, product.is_sold_out == 1 ? 'warning' : 'success');
        },

        toggleActive(product) {
            product.is_active = product.is_active == 1 ? 0 : 1;
            const statusText = product.is_active == 1 ? 'diaktifkan' : 'dinonaktifkan';
            window.toast(`Menu '${product.name}' ${statusText}`, product.is_active == 1 ? 'success' : 'info');
        },

        submitTambah() {
            if (!this.formData.name || !this.formData.price || !this.formData.category_id) {
                window.toast('Harap lengkapi nama menu, kategori, dan harga jual', 'warning');
                return;
            }

            const newProduct = {
                id: Date.now(),
                category_id: this.formData.category_id,
                category_name: (this.categories.find(c => c.id == this.formData.category_id) || {}).name || 'Kopi',
                name: this.formData.name,
                sku: this.formData.sku,
                description: this.formData.description,
                price: parseInt(this.formData.price),
                cost_price: parseInt(this.formData.cost_price) || 0,
                is_active: this.formData.is_active ? 1 : 0,
                is_sold_out: this.formData.is_sold_out ? 1 : 0,
                image_url: this.imagePreview || ''
            };

            this.products.unshift(newProduct);
            this.modalTambahOpen = false;
            window.toast(`Menu baru '${newProduct.name}' berhasil ditambahkan`, 'success');
        },

        submitEdit() {
            if (!this.formData.name || !this.formData.price) {
                window.toast('Harap lengkapi nama menu dan harga jual', 'warning');
                return;
            }

            const idx = this.products.findIndex(p => p.id === this.selectedProduct.id);
            if (idx !== -1) {
                this.products[idx].name = this.formData.name;
                this.products[idx].category_id = this.formData.category_id;
                this.products[idx].category_name = (this.categories.find(c => c.id == this.formData.category_id) || {}).name || 'Kopi';
                this.products[idx].sku = this.formData.sku;
                this.products[idx].description = this.formData.description;
                this.products[idx].price = parseInt(this.formData.price);
                this.products[idx].cost_price = parseInt(this.formData.cost_price) || 0;
                this.products[idx].is_active = this.formData.is_active ? 1 : 0;
                this.products[idx].is_sold_out = this.formData.is_sold_out ? 1 : 0;
                if (this.imagePreview) {
                    this.products[idx].image_url = this.imagePreview;
                }
                window.toast(`Perubahan menu '${this.formData.name}' berhasil disimpan`, 'success');
            }
            this.modalEditOpen = false;
        },

        confirmHapus() {
            if (!this.selectedProduct) return;
            this.products = this.products.filter(p => p.id !== this.selectedProduct.id);
            window.toast(`Menu '${this.selectedProduct.name}' berhasil dihapus`, 'success');
            this.modalHapusOpen = false;
        }
    }"
    class="space-y-6"
>
    <!-- 4 KARTU STATISTIK KATALOG -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Total Menu</span>
            <div class="mt-2 text-2xl font-bold text-[#1A0F0A]" x-text="stats.total">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Semua produk terdaftar</p>
        </div>
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Menu Aktif</span>
            <div class="mt-2 text-2xl font-bold text-[#2E7D32]" x-text="stats.active">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Siap dijual di POS</p>
        </div>
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Menu Habis (Sold Out)</span>
            <div class="mt-2 text-2xl font-bold text-[#D97706]" x-text="stats.soldOut">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Ditandai habis manual / bahan 0</p>
        </div>
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <span class="text-xs font-semibold uppercase tracking-wider text-[#7A685D]">Rata-rata Margin HPP</span>
            <div class="mt-2 text-2xl font-bold text-[#3C2317]" x-text="stats.avgMargin + '%'">0%</div>
            <p class="text-[11px] text-[#2E7D32] font-semibold mt-0.5">Profitabilitas sehat</p>
        </div>
    </div>

    <!-- TAB FILTER KATEGORI (Touch-Optimized) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar">
        <button
            type="button"
            @click="selectedCategory = 'all'"
            class="px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap shrink-0 shadow-2xs"
            :class="selectedCategory === 'all' ? 'bg-[#3C2317] text-white shadow-soft' : 'bg-[#FCFAF7] text-[#7A685D] hover:bg-[#F8F5F0] hover:text-[#3C2317] border border-[#8C6246]/20'"
        >
            Semua Menu (<span x-text="products.length"></span>)
        </button>
        <template x-for="cat in categories" :key="cat.id">
            <button
                type="button"
                @click="selectedCategory = cat.id"
                class="px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap shrink-0"
                :class="selectedCategory == cat.id ? 'bg-[#3C2317] text-white shadow-soft' : 'bg-[#FCFAF7] text-[#7A685D] hover:bg-[#F8F5F0] hover:text-[#3C2317] border border-[#8C6246]/20'"
            >
                <span x-text="cat.name"></span>
                <span class="opacity-75 font-normal ml-1" x-text="'(' + products.filter(p => p.category_id == cat.id).length + ')'"></span>
            </button>
        </template>
    </div>

    <!-- BAR PENCARIAN & STATUS FILTER -->
    <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative flex-1 max-w-md">
            <input
                type="text"
                x-model="search"
                placeholder="Cari nama menu, SKU..."
                class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-[10px] bg-white border border-[#8C6246]/20 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all shadow-xs"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#7A685D]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <label for="filter-status-product" class="text-xs font-semibold text-[#7A685D]">Status:</label>
            <select
                id="filter-status-product"
                x-model="statusFilter"
                class="px-2.5 py-1.5 text-xs rounded-[10px] bg-white border border-[#8C6246]/20 text-[#1A0F0A] focus:outline-none focus:ring-2 focus:ring-[#8C6246]/20"
            >
                <option value="all">Semua Status</option>
                <option value="active">Siap Jual</option>
                <option value="sold_out">Habis (Sold Out)</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>
    </div>

    <!-- GRID KARTU MENU (S-04) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <template x-for="product in filteredProducts" :key="product.id">
            <div
                class="rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft overflow-hidden flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover group"
                :class="product.is_sold_out == 1 ? 'opacity-90 ring-1 ring-[#D97706]/40' : (product.is_active == 0 ? 'opacity-60 bg-stone-50' : '')"
            >
                <!-- Image & Status Overlay -->
                <div class="relative w-full h-44 bg-[#F8F5F0] overflow-hidden flex items-center justify-center border-b border-[#8C6246]/10">
                    <template x-if="product.image_url">
                        <img :src="product.image_url" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </template>
                    <template x-if="!product.image_url">
                        <div class="flex flex-col items-center justify-center text-[#8C6246]/60">
                            <svg class="w-12 h-12 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693l-1.57-.393m15.6 0A2.25 2.25 0 0121 17.25v.75a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-.75a2.25 2.25 0 011.2-1.95"/>
                            </svg>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-[#7A685D]">Foto 600×600</span>
                        </div>
                    </template>

                    <!-- Sold Out Badge Overlay (US-05) -->
                    <template x-if="product.is_sold_out == 1">
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-2xs flex items-center justify-center">
                            <span class="px-3 py-1 rounded-full bg-[#DC2626] text-white text-xs font-extrabold uppercase tracking-widest shadow-md">
                                HABIS
                            </span>
                        </div>
                    </template>

                    <!-- Category Badge -->
                    <div class="absolute top-2.5 left-2.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#3C2317]/85 text-[#F8F5F0] backdrop-blur-xs" x-text="product.category_name"></span>
                    </div>

                    <!-- SKU Tag -->
                    <template x-if="product.sku">
                        <div class="absolute top-2.5 right-2.5">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-white/85 text-[#1A0F0A] shadow-2xs" x-text="product.sku"></span>
                        </div>
                    </template>
                </div>

                <!-- Product Body Details -->
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-[#1A0F0A] leading-snug line-clamp-1 group-hover:text-[#8C6246] transition-colors" x-text="product.name"></h3>
                        <p class="text-xs text-[#7A685D] mt-1 line-clamp-2 leading-relaxed" x-text="product.description || 'Menu racikan khas Kopi Titik Nol.'"></p>
                    </div>

                    <!-- Price & HPP/Margin Section -->
                    <div class="mt-4 pt-3 border-t border-[#8C6246]/10 space-y-2">
                        <!-- Harga Jual -->
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-[#7A685D] font-medium">Harga Jual:</span>
                            <span class="text-base font-extrabold text-[#1A0F0A]" x-text="formatRupiah(product.price)"></span>
                        </div>

                        <!-- HPP & Margin Preview Pill (S-04) -->
                        <div class="p-2 rounded-[10px] bg-[#F8F5F0] border border-[#8C6246]/15 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] text-[#7A685D] uppercase font-semibold block">HPP Resep</span>
                                <span class="font-bold text-[#3C2317]" x-text="formatRupiah(product.cost_price)"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-[#7A685D] uppercase font-semibold block">Margin</span>
                                <span
                                    class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-extrabold"
                                    :class="calculateMargin(product.price, product.cost_price) >= 50 ? 'bg-[#2E7D32]/15 text-[#2E7D32]' : (calculateMargin(product.price, product.cost_price) >= 25 ? 'bg-[#D97706]/15 text-[#B45309]' : 'bg-[#DC2626]/15 text-[#DC2626]')"
                                    x-text="calculateMargin(product.price, product.cost_price) + '%'"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Quick Toggle & Actions -->
                <div class="px-4 py-3 bg-[#F8F5F0]/60 border-t border-[#8C6246]/15 flex items-center justify-between">
                    <!-- Toggle Habis (US-05) -->
                    <button
                        type="button"
                        @click="toggleSoldOut(product)"
                        class="px-2.5 py-1 rounded-[8px] text-[11px] font-bold border transition-all"
                        :class="product.is_sold_out == 1 ? 'bg-amber-50 border-[#D97706]/40 text-[#B45309]' : 'bg-white border-[#8C6246]/30 text-[#7A685D] hover:text-[#1A0F0A]'"
                    >
                        <span x-text="product.is_sold_out == 1 ? 'Buka Stok' : 'Tandai Habis'"></span>
                    </button>

                    <!-- Edit & Actions -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openModalEdit(product)"
                            class="p-1.5 text-[#7A685D] hover:text-[#3C2317] hover:bg-white rounded-lg transition-colors"
                            title="Ubah Menu"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </button>
                        <button
                            type="button"
                            @click="openModalHapus(product)"
                            class="p-1.5 text-[#DC2626] hover:bg-red-50 rounded-lg transition-colors"
                            title="Hapus Menu"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty state -->
    <div x-show="filteredProducts.length === 0" style="display: none;" class="py-16 text-center bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/15 p-6">
        <div class="w-12 h-12 rounded-full bg-[#8C6246]/10 text-[#8C6246] flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
        </div>
        <h3 class="text-sm font-bold text-[#1A0F0A]">Tidak Ada Menu Ditemukan</h3>
        <p class="text-xs text-[#7A685D] mt-1">Coba pilih tab kategori lain atau sesuaikan kata kunci pencarian.</p>
    </div>

    <!-- MODAL TAMBAH / EDIT MENU (S-04) -->
    <div x-show="modalTambahOpen || modalEditOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div x-show="modalTambahOpen || modalEditOpen" @click="modalTambahOpen = false; modalEditOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>
            <div x-show="modalTambahOpen || modalEditOpen" class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-xl w-full p-6 text-left overflow-hidden z-10">
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]" x-text="modalTambahOpen ? 'Tambah Menu Produk Baru' : 'Ubah Data Menu Produk'"></h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Konfigurasi harga jual dan kalkulator margin HPP otomatis (US-04)</p>
                    </div>
                    <button type="button" @click="modalTambahOpen = false; modalEditOpen = false" class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="modalTambahOpen ? submitTambah() : submitEdit()" class="mt-4 space-y-4">
                    <!-- Nama & Kategori -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Nama Menu <span class="text-[#DC2626]">*</span>
                            </label>
                            <input
                                type="text"
                                x-model="formData.name"
                                required
                                placeholder="Contoh: Es Kopi Susu Titik Nol"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Kategori <span class="text-[#DC2626]">*</span>
                            </label>
                            <select
                                x-model="formData.category_id"
                                required
                                class="w-full px-3 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- SKU & Deskripsi -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Kode SKU
                            </label>
                            <input
                                type="text"
                                x-model="formData.sku"
                                placeholder="SKT-001"
                                class="w-full px-3.5 py-2 text-sm font-mono rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Deskripsi Singkat Menu
                            </label>
                            <input
                                type="text"
                                x-model="formData.description"
                                placeholder="Double shot espresso, fresh milk, gula aren..."
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                            >
                        </div>
                    </div>

                    <!-- KALKULATOR HARGA JUAL & MARGIN HPP (S-04) -->
                    <div class="p-4 rounded-[12px] bg-[#3C2317]/5 border border-[#8C6246]/30 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#3C2317] uppercase tracking-wider">Kalkulator Margin & Profitabilitas HPP</span>
                            <span class="text-[10px] text-[#7A685D]">Otomatis dihitung real-time</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                    Harga Jual (Rp) <span class="text-[#DC2626]">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="500"
                                    x-model="formData.price"
                                    required
                                    class="w-full px-3.5 py-2 text-sm font-bold rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                    Estimasi HPP Bahan (Rp)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="500"
                                    x-model="formData.cost_price"
                                    class="w-full px-3.5 py-2 text-sm font-bold rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                                >
                            </div>
                        </div>

                        <!-- Live Calculation Output -->
                        <div class="p-3 rounded-[10px] bg-white border border-[#8C6246]/20 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-[#7A685D] block">Laba Kotor Per Porsi:</span>
                                <span class="text-sm font-extrabold text-[#2E7D32]" x-text="formatRupiah(calculateProfit(formData.price, formData.cost_price))"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-[#7A685D] block">Persentase Margin:</span>
                                <span
                                    class="text-sm font-extrabold px-2 py-0.5 rounded-full"
                                    :class="calculateMargin(formData.price, formData.cost_price) >= 50 ? 'bg-[#2E7D32]/15 text-[#2E7D32]' : (calculateMargin(formData.price, formData.cost_price) >= 20 ? 'bg-[#D97706]/15 text-[#B45309]' : 'bg-[#DC2626]/15 text-[#DC2626]')"
                                    x-text="calculateMargin(formData.price, formData.cost_price) + '%'"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <!-- UPLOAD FOTO PRODUK (600x600 preview, maks 2MB, US-04) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1.5">
                            Foto Menu Produk (JPG/PNG/WebP, Maks 2MB &bull; Auto 600×600)
                        </label>
                        <div class="flex items-center gap-4">
                            <!-- Preview Box -->
                            <div class="w-20 h-20 rounded-[12px] bg-[#F8F5F0] border-2 border-dashed border-[#8C6246]/30 overflow-hidden flex items-center justify-center shrink-0">
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="Preview Foto" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!imagePreview">
                                    <svg class="w-7 h-7 text-[#7A685D]/60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input
                                    type="file"
                                    accept="image/png, image/jpeg, image/webp"
                                    @change="handleImageUpload($event)"
                                    class="block w-full text-xs text-[#7A685D] file:mr-3 file:py-2 file:px-3 file:rounded-[8px] file:border-0 file:text-xs file:font-semibold file:bg-[#8C6246]/15 file:text-[#3C2317] hover:file:bg-[#8C6246]/25 file:cursor-pointer"
                                >
                                <p class="text-[11px] text-[#7A685D] mt-1">Sistem otomatis menyesuaikan rasio menjadi 600×600 px saat diunggah.</p>
                            </div>
                        </div>
                    </div>

                    <!-- TOGGLE AKTIF & HABIS -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <label class="flex items-center gap-2 p-2.5 rounded-[10px] bg-white border border-[#8C6246]/20 cursor-pointer">
                            <input type="checkbox" x-model="formData.is_active" class="rounded text-[#3C2317] accent-[#3C2317]">
                            <span class="text-xs font-semibold text-[#1A0F0A]">Status Aktif (Tampil POS)</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-[10px] bg-white border border-[#8C6246]/20 cursor-pointer">
                            <input type="checkbox" x-model="formData.is_sold_out" class="rounded text-[#D97706] accent-[#D97706]">
                            <span class="text-xs font-semibold text-[#B45309]">Tandai Habis (Sold Out)</span>
                        </label>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button type="button" @click="modalTambahOpen = false; modalEditOpen = false" class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft">
                            <span x-text="modalTambahOpen ? 'Simpan Menu Baru' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS MENU -->
    <div x-show="modalHapusOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div x-show="modalHapusOpen" @click="modalHapusOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>
            <div x-show="modalHapusOpen" class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-red-50 text-[#DC2626] flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]">Hapus Menu Produk?</h2>
                        <p class="text-xs text-[#7A685D] mt-1 leading-relaxed">
                            Anda yakin ingin menghapus menu <strong class="text-[#1A0F0A]" x-text="selectedProduct ? selectedProduct.name : ''"></strong>? Sesuai <strong>BR-40</strong>, jika menu pernah digunakan dalam transaksi lampau, transaksi lama tetap utuh karena tersimpan dalam snapshot.
                        </p>
                    </div>
                </div>
                <div class="mt-5 pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                    <button type="button" @click="modalHapusOpen = false" class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]">Batal</button>
                    <button
                        type="button"
                        @click="confirmHapus()"
                        class="px-4 py-2 rounded-[10px] bg-[#DC2626] hover:bg-red-700 text-white text-xs font-semibold shadow-soft"
                    >
                        Ya, Hapus Menu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
