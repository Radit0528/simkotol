{{--
  [FRONTEND] Halaman Stok Masuk (Pembelian) & Buku Besar Mutasi (US-07, US-08, S-06, S-07, BR-13, BR-19).
  Mencakup form pembelian bahan masuk dengan simulasi Moving Average Cost (BR-19),
  modal stok keluar manual (spill/expired/adjustment dengan alasan wajib), serta riwayat mutasi stok.
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
            <span>&bull;</span>
            <a href="{{ route('ingredients.index') }}" class="hover:underline">Bahan Baku</a>
            <span>&bull;</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/15 text-[#3C2317]">S-06 / S-07</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Mutasi Stok & Pembelian Bahan (S-06, S-07)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Pencatatan stok masuk pembelian dengan rekalkulasi moving average cost (BR-19) dan penyesuaian stok manual (US-07, US-08)
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <a
            href="{{ route('ingredients.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            <span>Daftar Bahan (S-05)</span>
        </a>

        <button
            type="button"
            @click="modalStockOutOpen = true"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-[#DC2626]/10 hover:bg-[#DC2626]/20 border border-[#DC2626]/30 text-xs font-bold text-[#DC2626] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
        >
            <svg class="w-4 h-4 text-[#DC2626]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"/>
            </svg>
            <span>Stok Keluar Manual (S-07)</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        activeTab: '{{ request('tab', 'purchase') }}',
        modalStockOutOpen: false,
        searchMutation: '',
        typeFilter: 'all',

        // Form Pembelian Baru (S-06)
        purchaseForm: {
            purchase_number: 'PO-' + new Date().toISOString().slice(2,10).replace(/-/g, '') + '-' + Math.floor(100 + Math.random() * 900),
            supplier_id: '{{ $suppliers[0]['id'] ?? '' }}',
            purchased_at: new Date().toISOString().slice(0, 10),
            notes: '',
            items: [
                {
                    ingredient_id: '{{ $ingredients[0]['id'] ?? '' }}',
                    quantity: 1000,
                    unit_cost: 190
                }
            ]
        },

        // Modal Stok Keluar (S-07)
        stockOutForm: {
            ingredient_id: '{{ $ingredients[0]['id'] ?? '' }}',
            type: 'spill',
            quantity: 50,
            notes: ''
        },

        ingredients: {{ Js::from($ingredients ?? []) }},
        suppliers: {{ Js::from($suppliers ?? []) }},
        mutations: {{ Js::from($mutations ?? []) }},

        // Cari data bahan baku berdasarkan ID
        getIngredient(id) {
            return this.ingredients.find(i => i.id == id) || null;
        },

        // Kalkulasi Moving Average Cost per baris (BR-19)
        calculateNewMovingAverage(item) {
            const ing = this.getIngredient(item.ingredient_id);
            if (!ing) return 0;

            const oldStock = Math.max(0, parseFloat(ing.stock) || 0);
            const oldCost = parseFloat(ing.cost_per_unit) || 0;
            const inQty = parseFloat(item.quantity) || 0;
            const inCost = parseFloat(item.unit_cost) || 0;

            if (oldStock + inQty <= 0) return inCost;

            const totalValue = (oldStock * oldCost) + (inQty * inCost);
            return Math.round(totalValue / (oldStock + inQty));
        },

        // Grand total PO pembelian
        get purchaseTotalCost() {
            return this.purchaseForm.items.reduce((total, item) => {
                const qty = parseFloat(item.quantity) || 0;
                const cost = parseFloat(item.unit_cost) || 0;
                return total + (qty * cost);
            }, 0);
        },

        addPurchaseRow() {
            const defaultId = this.ingredients.length > 0 ? this.ingredients[0].id : '';
            this.purchaseForm.items.push({
                ingredient_id: defaultId,
                quantity: 100,
                unit_cost: 0
            });
        },

        removePurchaseRow(index) {
            if (this.purchaseForm.items.length > 1) {
                this.purchaseForm.items.splice(index, 1);
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },

        formatDecimal(num) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 }).format(num);
        },

        // Simpan Pembelian Stok Masuk (S-06)
        savePurchase() {
            if (this.purchaseForm.items.length === 0) return;

            // Update stok dan moving average pada array lokal
            this.purchaseForm.items.forEach(item => {
                const ing = this.getIngredient(item.ingredient_id);
                if (ing) {
                    const newCost = this.calculateNewMovingAverage(item);
                    const oldStock = parseFloat(ing.stock) || 0;
                    const inQty = parseFloat(item.quantity) || 0;

                    ing.stock = oldStock + inQty;
                    ing.cost_per_unit = newCost;

                    // Buat rekaman mutasi stok 'in'
                    this.mutations.unshift({
                        id: this.mutations.length + 1,
                        ingredient_name: ing.name,
                        ingredient_unit: ing.unit,
                        type: 'in',
                        quantity: inQty,
                        stock_before: oldStock,
                        stock_after: ing.stock,
                        unit_cost: parseFloat(item.unit_cost),
                        notes: `Pembelian PO #${this.purchaseForm.purchase_number}`,
                        created_at: new Date().toISOString()
                    });
                }
            });

            if (this.$store?.toasts) {
                this.$store.toasts.add(`Pembelian #${this.purchaseForm.purchase_number} berhasil dicatat. Stok dan HPP telah diperbarui!`, 'success');
            }

            // Reset form PO
            this.purchaseForm.purchase_number = 'PO-' + new Date().toISOString().slice(2,10).replace(/-/g, '') + '-' + Math.floor(100 + Math.random() * 900);
            this.activeTab = 'mutations';
        },

        // Simpan Stok Keluar Manual (S-07)
        saveStockOut() {
            const ing = this.getIngredient(this.stockOutForm.ingredient_id);
            if (!ing) return;

            if (!this.stockOutForm.notes || this.stockOutForm.notes.trim().length < 5) {
                alert('Alasan/catatan penyesuaian stok wajib diisi minimal 5 karakter!');
                return;
            }

            const outQty = parseFloat(this.stockOutForm.quantity) || 0;
            const oldStock = parseFloat(ing.stock) || 0;
            const newStock = oldStock - outQty;

            ing.stock = newStock;

            this.mutations.unshift({
                id: this.mutations.length + 1,
                ingredient_name: ing.name,
                ingredient_unit: ing.unit,
                type: this.stockOutForm.type,
                quantity: -outQty,
                stock_before: oldStock,
                stock_after: newStock,
                unit_cost: ing.cost_per_unit,
                notes: this.stockOutForm.notes,
                created_at: new Date().toISOString()
            });

            this.modalStockOutOpen = false;
            this.stockOutForm.notes = '';

            if (this.$store?.toasts) {
                this.$store.toasts.add(`Penyesuaian stok manual (${this.stockOutForm.type}) berhasil dicatat.`, 'warning');
            }
        },

        get filteredMutations() {
            return this.mutations.filter(m => {
                const matchSearch = this.searchMutation === '' ||
                    m.ingredient_name.toLowerCase().includes(this.searchMutation.toLowerCase()) ||
                    (m.notes && m.notes.toLowerCase().includes(this.searchMutation.toLowerCase()));

                const matchType = this.typeFilter === 'all' || m.type === this.typeFilter;
                return matchSearch && matchType;
            });
        }
    }"
    class="space-y-6"
>
    <!-- Tab Navigation Bar -->
    <div class="bg-white rounded-[14px] p-1.5 border border-[#8C6246]/15 shadow-soft flex gap-2">
        <button
            type="button"
            @click="activeTab = 'purchase'"
            :class="activeTab === 'purchase' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[10px] text-xs font-bold transition-all duration-150"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Form Stok Masuk / Pembelian (S-06)</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'mutations'"
            :class="activeTab === 'mutations' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[10px] text-xs font-bold transition-all duration-150"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
            </svg>
            <span>Buku Besar Mutasi Stok & Log (S-07)</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: FORM STOK MASUK / PEMBELIAN DENGAN MOVING AVERAGE (S-06, BR-19) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'purchase'" x-cloak class="space-y-6">
        <form @submit.prevent="savePurchase()" class="space-y-6">
            
            <!-- Metadata PO Card -->
            <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-4">
                <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-[#1A0F0A]">Informasi Pembelian Bahan Baku (US-07)</h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Catat faktur masuk dari pemasok untuk menambah kuantitas dan memperbarui moving average cost</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#2E7D32]/15 text-[#2E7D32]">
                        Stok Masuk
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Nomor PO -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">No. Faktur / PO Pembelian</label>
                        <input
                            type="text"
                            x-model="purchaseForm.purchase_number"
                            required
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-mono font-bold bg-[#FCFAF7]"
                        />
                    </div>

                    <!-- Pemasok -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Mitra Supplier <span class="text-[#DC2626]">*</span></label>
                        <select
                            x-model="purchaseForm.supplier_id"
                            required
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold bg-white"
                        >
                            <template x-for="sup in suppliers" :key="sup.id">
                                <option :value="sup.id" x-text="sup.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Tanggal Pembelian (Max Hari Ini) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Tanggal Pembelian <span class="text-[#DC2626]">*</span></label>
                        <input
                            type="date"
                            x-model="purchaseForm.purchased_at"
                            :max="new Date().toISOString().slice(0, 10)"
                            required
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs bg-white"
                        />
                        <span class="text-[10px] text-[#7A685D] mt-0.5 block">Tanggal tidak boleh di masa depan (US-07)</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Catatan Tambahan (Opsional)</label>
                    <input
                        type="text"
                        x-model="purchaseForm.notes"
                        placeholder="Contoh: Pengiriman batch biji kopi sangrai minggu ke-4 Oktober 2026"
                        class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs bg-white"
                    />
                </div>
            </div>

            <!-- Item Pembelian & Moving Average Simulation Table -->
            <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-4">
                <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-[#1A0F0A] flex items-center gap-2">
                            <span>Daftar Bahan & Simulasi Moving Average Cost (BR-19)</span>
                        </h3>
                        <p class="text-xs text-[#7A685D] mt-0.5">
                            Sistem secara otomatis menghitung ulang harga pokok rata-rata per satuan dasar saat pembelian disimpan
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="addPurchaseRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[8px] bg-[#8C6246]/15 hover:bg-[#8C6246]/25 text-xs font-bold text-[#3C2317] transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span>Tambah Baris Bahan</span>
                    </button>
                </div>

                <!-- Table Rows -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FCFAF7] border-b border-[#8C6246]/20 text-[11px] font-bold text-[#3C2317] uppercase">
                                <th class="py-2.5 px-3">Bahan Baku</th>
                                <th class="py-2.5 px-3">Stok Saat Ini</th>
                                <th class="py-2.5 px-3">Kuantitas Masuk</th>
                                <th class="py-2.5 px-3">Harga Beli / Satuan</th>
                                <th class="py-2.5 px-3">Subtotal</th>
                                <th class="py-2.5 px-3 bg-amber-50/70 text-[#B45309]">Rekalkulasi HPP Baru (BR-19)</th>
                                <th class="py-2.5 px-2 text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#8C6246]/10 text-xs">
                            <template x-for="(row, idx) in purchaseForm.items" :key="idx">
                                <tr class="hover:bg-[#FCFAF7] transition-colors">
                                    <!-- Pilih Bahan -->
                                    <td class="py-3 px-3 min-w-[200px]">
                                        <select
                                            x-model="row.ingredient_id"
                                            required
                                            class="w-full px-2.5 py-1.5 rounded-[8px] border border-[#8C6246]/30 text-xs bg-white font-semibold"
                                        >
                                            <template x-for="ing in ingredients" :key="ing.id">
                                                <option :value="ing.id" x-text="`${ing.name} (${ing.unit})`"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <!-- Stok Saat Ini -->
                                    <td class="py-3 px-3 text-[#7A685D] whitespace-nowrap">
                                        <span class="font-bold text-[#1A0F0A]" x-text="formatDecimal(getIngredient(row.ingredient_id)?.stock || 0)"></span>
                                        <span x-text="getIngredient(row.ingredient_id)?.unit || ''"></span>
                                        <div class="text-[10px] text-[#7A685D]" x-text="`Cost Lama: Rp ${formatNumber(getIngredient(row.ingredient_id)?.cost_per_unit || 0)}`"></div>
                                    </td>

                                    <!-- Kuantitas Masuk -->
                                    <td class="py-3 px-3 min-w-[120px]">
                                        <div class="relative">
                                            <input
                                                type="number"
                                                step="0.001"
                                                min="0.001"
                                                x-model.number="row.quantity"
                                                required
                                                class="w-full pr-10 pl-2.5 py-1.5 rounded-[8px] border border-[#8C6246]/30 text-xs font-bold bg-white"
                                            />
                                            <span class="absolute inset-y-0 right-0 pr-2 flex items-center text-[10px] font-bold text-[#7A685D]" x-text="getIngredient(row.ingredient_id)?.unit || ''"></span>
                                        </div>
                                    </td>

                                    <!-- Harga Beli / Satuan Dasar -->
                                    <td class="py-3 px-3 min-w-[130px]">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-[10px] text-[#7A685D]">Rp</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                x-model.number="row.unit_cost"
                                                required
                                                placeholder="0"
                                                class="w-full pl-7 pr-2.5 py-1.5 rounded-[8px] border border-[#8C6246]/30 text-xs font-bold font-mono bg-white"
                                            />
                                        </div>
                                    </td>

                                    <!-- Subtotal Row -->
                                    <td class="py-3 px-3 font-bold text-[#1A0F0A] whitespace-nowrap font-mono">
                                        <span x-text="`Rp ${formatNumber((row.quantity || 0) * (row.unit_cost || 0))}`"></span>
                                    </td>

                                    <!-- Moving Average Simulator (BR-19) -->
                                    <td class="py-3 px-3 bg-amber-50/50 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[11px] text-[#7A685D] line-through" x-text="`Rp ${formatNumber(getIngredient(row.ingredient_id)?.cost_per_unit || 0)}`"></span>
                                            <span class="text-xs font-extrabold text-[#B45309]" x-text="`&rarr; Rp ${formatNumber(calculateNewMovingAverage(row))}`"></span>
                                            <span class="text-[10px] text-[#7A685D]" x-text="`/${getIngredient(row.ingredient_id)?.unit || ''}`"></span>
                                        </div>
                                        <span class="text-[9px] text-[#7A685D] block">Moving Average Cost</span>
                                    </td>

                                    <!-- Hapus Baris -->
                                    <td class="py-3 px-2 text-center">
                                        <button
                                            type="button"
                                            @click="removePurchaseRow(idx)"
                                            :disabled="purchaseForm.items.length <= 1"
                                            class="text-gray-400 hover:text-[#DC2626] disabled:opacity-30 disabled:cursor-not-allowed p-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- PO Summary Footer -->
                <div class="pt-4 border-t border-[#8C6246]/15 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-[#7A685D]">
                        Total item bahan yang dibeli: <strong class="text-[#1A0F0A]" x-text="purchaseForm.items.length"></strong> baris
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-xs text-[#7A685D] block">Total Biaya Pembelian:</span>
                            <span class="text-xl font-black text-[#3C2317] font-mono" x-text="`Rp ${formatNumber(purchaseTotalCost)}`"></span>
                        </div>
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs sm:text-sm font-semibold shadow-soft transition-all duration-200 hover:-translate-y-0.5"
                        >
                            Simpan Pembelian & Perbarui Stok
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: BUKU BESAR MUTASI STOK (S-07, US-08, BR-13) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'mutations'" x-cloak class="space-y-6">
        
        <!-- Filter Mutasi Bar -->
        <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#7A685D]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    x-model="searchMutation"
                    placeholder="Cari mutasi bahan, nomor PO, atau alasan penyesuaian..."
                    class="w-full pl-10 pr-4 py-2 rounded-[10px] border border-[#8C6246]/25 text-xs bg-white"
                />
            </div>

            <div class="flex items-center gap-2">
                <select
                    x-model="typeFilter"
                    class="px-3 py-2 rounded-[10px] border border-[#8C6246]/25 text-xs font-semibold bg-white"
                >
                    <option value="all">Semua Tipe Mutasi</option>
                    <option value="in">Stok Masuk (in)</option>
                    <option value="spill">Tumpah / Limbah (spill)</option>
                    <option value="expired">Basi / Kedaluwarsa (expired)</option>
                    <option value="adjustment">Stock Opname (adjustment)</option>
                    <option value="sales">Penjualan Kasir (sales)</option>
                    <option value="void_rollback">Rollback Void (void_rollback)</option>
                </select>

                <button
                    type="button"
                    @click="modalStockOutOpen = true"
                    class="px-3 py-2 rounded-[10px] bg-[#DC2626] text-white text-xs font-bold hover:bg-red-700 transition-colors"
                >
                    + Penyesuaian Manual
                </button>
            </div>
        </div>

        <!-- Tabel Riwayat Mutasi Stok -->
        <div class="bg-white rounded-[14px] border border-[#8C6246]/15 shadow-soft overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#3C2317] text-white text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Bahan Baku</th>
                            <th class="py-3 px-3">Tipe Mutasi</th>
                            <th class="py-3 px-3">Perubahan (Qty)</th>
                            <th class="py-3 px-4">Stok Sebelum &rarr; Sesudah</th>
                            <th class="py-3 px-3">Biaya Satuan</th>
                            <th class="py-3 px-4">Alasan / Referensi Wajib (US-08)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#8C6246]/10 text-xs">
                        <template x-for="mut in filteredMutations" :key="mut.id">
                            <tr class="hover:bg-[#FCFAF7] transition-colors">
                                <!-- Waktu -->
                                <td class="py-3 px-4 font-mono text-[11px] text-[#7A685D] whitespace-nowrap" x-text="new Date(mut.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })"></td>

                                <!-- Bahan -->
                                <td class="py-3 px-4 font-bold text-[#1A0F0A]" x-text="mut.ingredient_name"></td>

                                <!-- Tipe Badge -->
                                <td class="py-3 px-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-100 text-[#2E7D32]': mut.type === 'in',
                                            'bg-amber-100 text-[#B45309]': mut.type === 'spill',
                                            'bg-red-100 text-[#DC2626]': mut.type === 'expired',
                                            'bg-blue-100 text-blue-700': mut.type === 'adjustment',
                                            'bg-gray-100 text-gray-700': mut.type === 'sales',
                                            'bg-purple-100 text-purple-700': mut.type === 'void_rollback'
                                        }"
                                        x-text="mut.type"
                                    ></span>
                                </td>

                                <!-- Kuantitas -->
                                <td class="py-3 px-3 font-bold font-mono">
                                    <span
                                        :class="mut.quantity > 0 ? 'text-[#2E7D32]' : 'text-[#DC2626]'"
                                        x-text="`${mut.quantity > 0 ? '+' : ''}${formatDecimal(mut.quantity)} ${mut.ingredient_unit}`"
                                    ></span>
                                </td>

                                <!-- Sebelum -> Sesudah -->
                                <td class="py-3 px-4 text-[#7A685D] whitespace-nowrap">
                                    <span x-text="formatDecimal(mut.stock_before)"></span>
                                    <span class="text-[#3C2317] font-bold" x-text="` &rarr; ${formatDecimal(mut.stock_after)} ${mut.ingredient_unit}`"></span>
                                </td>

                                <!-- Biaya Satuan -->
                                <td class="py-3 px-3 font-mono text-[#1A0F0A]">
                                    <span x-text="`Rp ${formatNumber(mut.unit_cost || 0)}`"></span>
                                </td>

                                <!-- Alasan / Catatan Wajib -->
                                <td class="py-3 px-4 text-[#1A0F0A]">
                                    <span class="block" x-text="mut.notes || '-'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: STOK KELUAR MANUAL (SPILL/EXPIRED/ADJUSTMENT) (S-07, US-08) -->
    <!-- ========================================================================= -->
    <div
        x-show="modalStockOutOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="modalStockOutOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-[16px] max-w-md w-full p-6 shadow-xl border border-[#DC2626]/30 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#8C6246]/15">
                    <div>
                        <h3 class="text-base font-bold text-[#DC2626] flex items-center gap-1.5">
                            <svg class="w-5 h-5 text-[#DC2626]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <span>Stok Keluar Manual (S-07)</span>
                        </h3>
                        <p class="text-xs text-[#7A685D] mt-0.5">Pencatatan tumpah (spill), basi (expired), atau penyesuaian stock opname (US-08)</p>
                    </div>
                    <button type="button" @click="modalStockOutOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveStockOut()" class="space-y-4">
                    <!-- Pilih Bahan Baku -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Pilih Bahan Baku <span class="text-[#DC2626]">*</span></label>
                        <select
                            x-model="stockOutForm.ingredient_id"
                            required
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-semibold bg-white"
                        >
                            <template x-for="ing in ingredients" :key="ing.id">
                                <option :value="ing.id" x-text="`${ing.name} (Sisa: ${formatDecimal(ing.stock)} ${ing.unit})`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Jenis Pengurangan -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Jenis Pengurangan Stok <span class="text-[#DC2626]">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                @click="stockOutForm.type = 'spill'"
                                :class="stockOutForm.type === 'spill' ? 'border-[#D97706] bg-amber-50 text-[#B45309]' : 'border-gray-200 bg-white text-gray-700'"
                                class="p-2.5 rounded-[10px] border cursor-pointer text-center text-xs font-bold transition-all"
                            >
                                <input type="radio" name="type" value="spill" x-model="stockOutForm.type" class="sr-only"/>
                                <span>Spill / Tumpah</span>
                            </label>

                            <label
                                @click="stockOutForm.type = 'expired'"
                                :class="stockOutForm.type === 'expired' ? 'border-[#DC2626] bg-red-50 text-[#DC2626]' : 'border-gray-200 bg-white text-gray-700'"
                                class="p-2.5 rounded-[10px] border cursor-pointer text-center text-xs font-bold transition-all"
                            >
                                <input type="radio" name="type" value="expired" x-model="stockOutForm.type" class="sr-only"/>
                                <span>Expired / Basi</span>
                            </label>

                            <label
                                @click="stockOutForm.type = 'adjustment'"
                                :class="stockOutForm.type === 'adjustment' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700'"
                                class="p-2.5 rounded-[10px] border cursor-pointer text-center text-xs font-bold transition-all"
                            >
                                <input type="radio" name="type" value="adjustment" x-model="stockOutForm.type" class="sr-only"/>
                                <span>Adjustment +/-</span>
                            </label>
                        </div>
                    </div>

                    <!-- Jumlah Kuantitas Keluar -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Jumlah Kuantitas Pengurangan <span class="text-[#DC2626]">*</span></label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.001"
                                min="0.001"
                                x-model.number="stockOutForm.quantity"
                                required
                                class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs font-bold"
                            />
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-[#7A685D]" x-text="getIngredient(stockOutForm.ingredient_id)?.unit || ''"></span>
                        </div>
                    </div>

                    <!-- Alasan / Catatan Wajib (US-08) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] mb-1">Alasan Wajib (Minimal 5 Karakter) <span class="text-[#DC2626]">*</span></label>
                        <textarea
                            x-model="stockOutForm.notes"
                            rows="2"
                            required
                            minlength="5"
                            placeholder="Contoh: Susu pecah saat bongkar muatan kulkas bar"
                            class="w-full px-3 py-2 rounded-[10px] border border-[#8C6246]/30 text-xs bg-white"
                        ></textarea>
                        <span class="text-[10px] text-[#7A685D] mt-0.5 block">Dicatat pada audit log untuk verifikasi owner (US-08)</span>
                    </div>

                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalStockOutOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#DC2626] hover:bg-red-700 text-xs font-semibold text-white shadow-soft"
                        >
                            Konfirmasi Kurangi Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
