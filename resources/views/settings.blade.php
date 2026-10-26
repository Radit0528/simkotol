{{--
  [FRONTEND] Halaman Pengaturan Toko & Format Struk (US-32, BR-04, BR-05, S-20).
  Halaman pengelolaan identitas kedai Kopi Titik Nol, konfigurasi pajak restoran (PB1),
  service charge, aturan pembulatan tunai, serta simulator live preview struk thermal (58mm/80mm).
--}}

@extends('layouts.app')

@push('styles')
<style>
    /* Styling Thermal Paper Receipt Preview */
    .thermal-receipt {
        background: #FFFFFF;
        box-shadow: 0 4px 25px -4px rgba(60, 35, 23, 0.12), 0 2px 10px -2px rgba(60, 35, 23, 0.08);
        position: relative;
    }
    
    /* Efek Gerigi Kertas Struk Thermal (Perforated Edge) */
    .receipt-edge-top {
        background: radial-gradient(circle, transparent, transparent 3px, #FFFFFF 3.5px, #FFFFFF);
        background-size: 10px 10px;
        background-position: 5px -5px;
        height: 8px;
    }
    .receipt-edge-bottom {
        background: radial-gradient(circle, transparent, transparent 3px, #FFFFFF 3.5px, #FFFFFF);
        background-size: 10px 10px;
        background-position: 5px 3px;
        height: 8px;
    }

    /* Print Specific Rules for Thermal Printing */
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-thermal-receipt, #printable-thermal-receipt * {
            visibility: visible;
        }
        #printable-thermal-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <div class="flex items-center gap-2 text-xs text-[#8C6246] font-semibold mb-1">
            <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
            <span>&bull;</span>
            <span class="text-[#3C2317]">Pengaturan Toko</span>
            <span>&bull;</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#8C6246]/15 text-[#3C2317]">S-20</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Pengaturan Toko & Format Struk (S-20)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Kelola profil kedai, persentase pajak PB1, service charge, pembulatan tunai, dan kustomisasi struk thermal Kopi Titik Nol (US-32, BR-04, BR-05)
        </p>
    </div>
    <div class="flex items-center gap-2.5">
        <button
            type="button"
            @click="window.print()"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-[12px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#3C2317] shadow-soft transition-all duration-200 hover:-translate-y-0.5"
            title="Cetak struk contoh langsung ke printer sistem"
        >
            <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.056.867-1.829 1.89-1.829h6.78c1.023 0 2.13.773 1.89 1.829l-1.042 4.593A2.25 2.25 0 0113.84 21H10.16a2.25 2.25 0 01-2.198-1.578L6.72 13.83zM6 9V3.75A2.25 2.25 0 018.25 1.5h7.5A2.25 2.25 0 0118 3.75V9m-12 0h12m-12 0H4.5A2.25 2.25 0 002.25 11.25v6.5A2.25 2.25 0 004.5 20H6"/>
            </svg>
            <span class="hidden sm:inline">Cetak Contoh Struk</span>
            <span class="sm:hidden">Cetak</span>
        </button>
        <button
            type="submit"
            form="form-settings"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
        >
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <span>Simpan Pengaturan</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        activeTab: 'profile',
        copySuccess: false,
        previewWidth: '{{ $settings['paper_size'] ?? 80 }}',
        formData: {
            store_name: '{{ addslashes($settings['store_name'] ?? 'Kopi Titik Nol') }}',
            store_address: '{{ addslashes($settings['store_address'] ?? 'Jl. Kaliurang KM 5, Sleman, D.I. Yogyakarta') }}',
            store_phone: '{{ addslashes($settings['store_phone'] ?? '081234567890') }}',
            store_logo: '{{ addslashes($settings['store_logo'] ?? '') }}',
            receipt_header: '{{ addslashes($settings['receipt_header'] ?? 'KOPI TITIK NOL • COFFEE & ROASTERY') }}',
            receipt_footer: '{{ addslashes($settings['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda, sampai jumpa lagi!') }}',
            paper_size: {{ (int)($settings['paper_size'] ?? 80) }},
            show_logo_on_receipt: {{ ($settings['show_logo_on_receipt'] ?? 1) ? 'true' : 'false' }},
            show_queue_number: {{ ($settings['show_queue_number'] ?? 1) ? 'true' : 'false' }},
            tax_percent: {{ (float)($settings['tax_percent'] ?? 0) }},
            service_percent: {{ (float)($settings['service_percent'] ?? 0) }},
            tax_inclusive: {{ ($settings['tax_inclusive'] ?? 0) ? 'true' : 'false' }},
            cash_rounding: {{ (int)($settings['cash_rounding'] ?? 100) }},
            qris_expiry_minutes: {{ (int)($settings['qris_expiry_minutes'] ?? 15) }},
            payment_provider: '{{ addslashes($settings['payment_provider'] ?? 'midtrans') }}',
            allow_negative_stock_on_paid: {{ ($settings['allow_negative_stock_on_paid'] ?? 1) ? 'true' : 'false' }}
        },

        // Mock items struk untuk live preview kalkulasi
        mockCart: {
            subtotal: 45000,
            discount: 5000,
            get netSubtotal() {
                return Math.max(0, this.subtotal - this.discount);
            }
        },

        // Kalkulasi Pajak PB1 sesuai mode inklusif / eksklusif (BR-04)
        get calculatedTax() {
            const rate = parseFloat(this.formData.tax_percent) || 0;
            if (rate <= 0) return 0;
            if (this.formData.tax_inclusive) {
                // Pajak sudah termasuk dalam harga: Net * rate / (100 + rate)
                return Math.round((this.mockCart.netSubtotal * rate) / (100 + rate));
            } else {
                // Pajak eksklusif ditambahkan ke subtotal
                return Math.round((this.mockCart.netSubtotal * rate) / 100);
            }
        },

        // Kalkulasi Service Charge
        get calculatedService() {
            const rate = parseFloat(this.formData.service_percent) || 0;
            if (rate <= 0) return 0;
            return Math.round((this.mockCart.netSubtotal * rate) / 100);
        },

        // Grand total sebelum pembulatan
        get rawGrandTotal() {
            if (this.formData.tax_inclusive) {
                return this.mockCart.netSubtotal + this.calculatedService;
            } else {
                return this.mockCart.netSubtotal + this.calculatedTax + this.calculatedService;
            }
        },

        // Pembulatan Tunai ke kelipatan terdekat ke bawah (BR-05)
        get roundingAmount() {
            const rounding = parseInt(this.formData.cash_rounding) || 0;
            if (rounding <= 0) return 0;
            const remainder = this.rawGrandTotal % rounding;
            return remainder > 0 ? -remainder : 0;
        },

        // Grand Total Akhir
        get finalGrandTotal() {
            return this.rawGrandTotal + this.roundingAmount;
        },

        // Kembalian contoh uang Rp 50.000 / kelipatan
        get changeAmount() {
            const cashPaid = this.finalGrandTotal > 50000 ? 100000 : 50000;
            return Math.max(0, cashPaid - this.finalGrandTotal);
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },

        // Salin teks struk ringkas untuk WhatsApp (US-20)
        copyWhatsAppReceipt() {
            const text = `*${this.formData.store_name.toUpperCase()}*\n` +
                `${this.formData.store_address}\n` +
                `No. Telp: ${this.formData.store_phone}\n` +
                `--------------------------------\n` +
                `No: #SKT-261026-001 | Meja/Antrean: A-012\n` +
                `Waktu: 26 Okt 2026 14:30 | Kasir: Andi\n` +
                `--------------------------------\n` +
                `1x Es Kopi Susu Titik Nol  Rp 20.000\n` +
                `1x Croissant Butter        Rp 25.000\n` +
                `--------------------------------\n` +
                `Subtotal:        Rp ${this.formatRupiah(this.mockCart.subtotal)}\n` +
                `Voucher (HEMAT): -Rp ${this.formatRupiah(this.mockCart.discount)}\n` +
                (this.calculatedTax > 0 ? `Pajak (${this.formData.tax_percent}%):    Rp ${this.formatRupiah(this.calculatedTax)}\n` : '') +
                (this.calculatedService > 0 ? `Service (${this.formData.service_percent}%):  Rp ${this.formatRupiah(this.calculatedService)}\n` : '') +
                (this.roundingAmount !== 0 ? `Pembulatan:      -Rp ${this.formatRupiah(Math.abs(this.roundingAmount))}\n` : '') +
                `*TOTAL:          Rp ${this.formatRupiah(this.finalGrandTotal)}*\n` +
                `--------------------------------\n` +
                `Metode: TUNAI | Lunas\n` +
                `${this.formData.receipt_footer}`;

            navigator.clipboard.writeText(text).then(() => {
                this.copySuccess = true;
                setTimeout(() => this.copySuccess = false, 2500);
            });
        }
    }"
    class="space-y-6"
>
    <!-- Navigation Tabs & Form Header -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: SETTINGS FORM (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Tab Selector Bar -->
            <div class="bg-white rounded-[14px] p-1.5 border border-[#8C6246]/15 shadow-soft flex flex-wrap gap-1">
                <button
                    type="button"
                    @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
                    class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-[10px] text-xs font-semibold transition-all duration-150"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.651V9.35m0 0a3.001 3.001 0 003.75-.614A2.993 2.993 0 009 9.35c.828 0 1.58-.334 2.13-.876a3.001 3.001 0 004.74 0c.55.542 1.302.876 2.13.876.828 0 1.58-.334 2.13-.876a3.001 3.001 0 003.75.614z"/>
                    </svg>
                    <span>Profil Kedai</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'receipt'"
                    :class="activeTab === 'receipt' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
                    class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-[10px] text-xs font-semibold transition-all duration-150"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <span>Format Struk</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'tax'"
                    :class="activeTab === 'tax' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
                    class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-[10px] text-xs font-semibold transition-all duration-150"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Pajak & Biaya</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'payment'"
                    :class="activeTab === 'payment' ? 'bg-[#3C2317] text-white shadow-sm' : 'text-[#7A685D] hover:text-[#1A0F0A] hover:bg-[#F8F5F0]'"
                    class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-[10px] text-xs font-semibold transition-all duration-150"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 002.25 19.5z"/>
                    </svg>
                    <span>Pembayaran & Stok</span>
                </button>
            </div>

            <!-- FORM START -->
            <form id="form-settings" method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                @csrf

                <!-- ========================================================= -->
                <!-- SEKSI 1: PROFIL KEDAI (US-32) -->
                <!-- ========================================================= -->
                <div x-show="activeTab === 'profile'" x-cloak class="space-y-6">
                    <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-5">
                        <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#1A0F0A] flex items-center gap-2">
                                    <span>Identitas Kedai Kopi (US-32)</span>
                                </h2>
                                <p class="text-xs text-[#7A685D] mt-0.5">
                                    Data ini dicantumkan pada kepala struk kasir, laporan PDF, dan komunikasi WhatsApp pelanggan
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#8C6246]/10 text-[#3C2317]">
                                Profil Toko
                            </span>
                        </div>

                        <!-- Nama Toko -->
                        <div>
                            <label for="store_name" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                Nama Toko / Kedai <span class="text-[#DC2626]">*</span>
                            </label>
                            <input
                                type="text"
                                id="store_name"
                                name="store_name"
                                x-model="formData.store_name"
                                required
                                maxlength="100"
                                placeholder="Contoh: Kopi Titik Nol"
                                class="w-full px-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                            />
                            <p class="text-[11px] text-[#7A685D] mt-1">Nama resmi yang dicetak paling atas pada setiap struk thermal POS</p>
                        </div>

                        <!-- Alamat Lengkap Toko -->
                        <div>
                            <label for="store_address" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                Alamat Lengkap Kedai <span class="text-[#DC2626]">*</span>
                            </label>
                            <textarea
                                id="store_address"
                                name="store_address"
                                x-model="formData.store_address"
                                rows="2"
                                required
                                maxlength="255"
                                placeholder="Contoh: Jl. Kaliurang KM 5, Sleman, D.I. Yogyakarta 55281"
                                class="w-full px-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                            ></textarea>
                            <p class="text-[11px] text-[#7A685D] mt-1">Cantumkan jalan, nomor, dan kota kedai agar terbaca jelas oleh pembeli</p>
                        </div>

                        <!-- Nomor Telepon / WhatsApp -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="store_phone" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                    No. Telepon / WhatsApp Kasir <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs text-[#7A685D]">
                                        <svg class="w-4 h-4 text-[#8C6246]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        id="store_phone"
                                        name="store_phone"
                                        x-model="formData.store_phone"
                                        required
                                        maxlength="30"
                                        placeholder="081234567890"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                                    />
                                </div>
                                <p class="text-[11px] text-[#7A685D] mt-1">Dipakai untuk pengiriman struk digital via wa.me (US-20)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                    Logo Kedai untuk Thermal
                                </label>
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-[10px] bg-[#3C2317] flex items-center justify-center text-white shrink-0 shadow-sm border border-[#8C6246]/30">
                                        <svg class="w-6 h-6 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                    </div>
                                    <div class="text-[11px] text-[#7A685D]">
                                        <p class="font-medium text-[#1A0F0A]">Monokrom 1-Bit Hitam-Putih</p>
                                        <p>Rekomendasi rasio 1:1 (PNG transparan) agar hasil cetak thermal tajam tanpa abu-abu.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEKSI 2: FORMAT STRUK THERMAL (US-20, US-32) -->
                <!-- ========================================================= -->
                <div x-show="activeTab === 'receipt'" x-cloak class="space-y-6">
                    <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-5">
                        <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#1A0F0A] flex items-center gap-2">
                                    <span>Konfigurasi Struk Thermal (US-20, US-32)</span>
                                </h2>
                                <p class="text-xs text-[#7A685D] mt-0.5">
                                    Atur lebar cetak printer POS, pesan sapaan, dan footer penutup struk pelanggan
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#8C6246]/10 text-[#3C2317]">
                                Cetak Thermal
                            </span>
                        </div>

                        <!-- Pilihan Ukuran Kertas Default -->
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-2">
                                Pilihan Ukuran Kertas Thermal Default <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Card 58mm -->
                                <label
                                    @click="formData.paper_size = 58; previewWidth = 58"
                                    :class="formData.paper_size == 58 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="relative flex items-start gap-3 p-4 rounded-[12px] border cursor-pointer transition-all duration-150"
                                >
                                    <input
                                        type="radio"
                                        name="paper_size"
                                        value="58"
                                        x-model="formData.paper_size"
                                        class="mt-0.5 text-[#3C2317] focus:ring-[#8C6246]"
                                    />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-[#1A0F0A]">58 mm</span>
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-[#8C6246]/15 text-[#3C2317]">Kompak (~32 Chars)</span>
                                        </div>
                                        <p class="text-xs text-[#7A685D] mt-1">
                                            Format ramping untuk printer thermal mobile Bluetooth portabel di meja bar kasir.
                                        </p>
                                    </div>
                                </label>

                                <!-- Card 80mm -->
                                <label
                                    @click="formData.paper_size = 80; previewWidth = 80"
                                    :class="formData.paper_size == 80 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="relative flex items-start gap-3 p-4 rounded-[12px] border cursor-pointer transition-all duration-150"
                                >
                                    <input
                                        type="radio"
                                        name="paper_size"
                                        value="80"
                                        x-model="formData.paper_size"
                                        class="mt-0.5 text-[#3C2317] focus:ring-[#8C6246]"
                                    />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-[#1A0F0A]">80 mm</span>
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-[#2E7D32]/15 text-[#2E7D32]">Standar POS (~48 Chars)</span>
                                        </div>
                                        <p class="text-xs text-[#7A685D] mt-1">
                                            Format lebar standar untuk printer kasir desktop USB / Auto-Cutter LAN berkecepatan tinggi.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Header Sapaan Struk -->
                        <div>
                            <label for="receipt_header" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                Header Sapaan Tambahan (Opsional)
                            </label>
                            <input
                                type="text"
                                id="receipt_header"
                                name="receipt_header"
                                x-model="formData.receipt_header"
                                maxlength="100"
                                placeholder="KOPI TITIK NOL • COFFEE & ROASTERY"
                                class="w-full px-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                            />
                            <p class="text-[11px] text-[#7A685D] mt-1">Teks sub-header di bawah nama toko</p>
                        </div>

                        <!-- Pesan Footer Struk (receipt_footer) -->
                        <div>
                            <label for="receipt_footer" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                Pesan Footer Penutup Struk <span class="text-[#DC2626]">*</span>
                            </label>
                            <textarea
                                id="receipt_footer"
                                name="receipt_footer"
                                x-model="formData.receipt_footer"
                                rows="2"
                                required
                                maxlength="255"
                                placeholder="Terima kasih atas kunjungan Anda, sampai jumpa lagi!"
                                class="w-full px-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                            ></textarea>
                            <p class="text-[11px] text-[#7A685D] mt-1">Ucapan terima kasih, info media sosial Instagram, atau promo kunjungan berikutnya</p>
                        </div>

                        <!-- Toggles Tampilan Struk -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <!-- Toggle Logo -->
                            <label class="flex items-center justify-between p-3.5 rounded-[10px] bg-[#FCFAF7] border border-[#8C6246]/20 cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-[#1A0F0A] block">Cetak Logo Toko di Struk</span>
                                    <span class="text-[11px] text-[#7A685D]">Tampilkan grafis logo di bagian paling atas struk</span>
                                </div>
                                <input
                                    type="checkbox"
                                    name="show_logo_on_receipt"
                                    value="1"
                                    x-model="formData.show_logo_on_receipt"
                                    class="rounded border-[#8C6246]/40 text-[#3C2317] focus:ring-[#8C6246] w-4 h-4 ml-3"
                                />
                            </label>

                            <!-- Toggle Nomor Antrean -->
                            <label class="flex items-center justify-between p-3.5 rounded-[10px] bg-[#FCFAF7] border border-[#8C6246]/20 cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-[#1A0F0A] block">Cetak No. Antrean Besar</span>
                                    <span class="text-[11px] text-[#7A685D]">Tampilkan font besar nomor antrean (mis: A-012)</span>
                                </div>
                                <input
                                    type="checkbox"
                                    name="show_queue_number"
                                    value="1"
                                    x-model="formData.show_queue_number"
                                    class="rounded border-[#8C6246]/40 text-[#3C2317] focus:ring-[#8C6246] w-4 h-4 ml-3"
                                />
                            </label>
                        </div>

                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEKSI 3: PAJAK, SERVICE & PEMBULATAN (BR-04, BR-05) -->
                <!-- ========================================================= -->
                <div x-show="activeTab === 'tax'" x-cloak class="space-y-6">
                    <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-5">
                        <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#1A0F0A] flex items-center gap-2">
                                    <span>Pajak Restoran, Service & Pembulatan (BR-04, BR-05)</span>
                                </h2>
                                <p class="text-xs text-[#7A685D] mt-0.5">
                                    Atur persentase PB1/PPN daerah, service charge kafe, dan aturan pembulatan transaksi tunai kasir
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#8C6246]/10 text-[#3C2317]">
                                Kalkulasi Kasir
                            </span>
                        </div>

                        <!-- Rule Explanation Alert (BR-04 & BR-05) -->
                        <div class="p-3.5 rounded-[12px] bg-[#FCFAF7] border border-[#8C6246]/25 flex items-start gap-3 text-xs text-[#3C2317]">
                            <svg class="w-5 h-5 text-[#8C6246] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            <div class="space-y-1">
                                <p class="font-semibold text-[#1A0F0A]">Ketentuan Aturan Bisnis SIMKOTOL:</p>
                                <ul class="list-disc list-inside text-[11px] text-[#7A685D] space-y-0.5">
                                    <li><strong class="text-[#3C2317]">BR-04:</strong> Pajak (PB1/PPN) & service charge dikonfigurasi di sini (default 0%). Harga menu dianggap belum termasuk pajak (*tax exclusive*).</li>
                                    <li><strong class="text-[#3C2317]">BR-05:</strong> Pembulatan tunai otomatis ke kelipatan Rp100 terdekat ke bawah. Selisih dicatat ke kolom <code class="bg-[#8C6246]/15 px-1 py-0.5 rounded text-[10px] text-[#1A0F0A]">rounding_amount</code>.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Persentase Pajak & Service Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Input Pajak Restoran (PB1) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="tax_percent" class="text-xs font-semibold text-[#1A0F0A]">
                                        Pajak Restoran / PB1 (%) <span class="text-[#DC2626]">*</span>
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="formData.tax_percent = 0" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">0%</button>
                                        <button type="button" @click="formData.tax_percent = 10" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">10%</button>
                                        <button type="button" @click="formData.tax_percent = 11" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">11%</button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="tax_percent"
                                        name="tax_percent"
                                        x-model.number="formData.tax_percent"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        required
                                        placeholder="0"
                                        class="w-full pl-3.5 pr-8 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all font-semibold"
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-[#7A685D]">%</span>
                                </div>
                                <p class="text-[11px] text-[#7A685D] mt-1">Dihitung dari subtotal setelah voucher diskon</p>
                            </div>

                            <!-- Input Service Charge -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="service_percent" class="text-xs font-semibold text-[#1A0F0A]">
                                        Service Charge Kafe (%) <span class="text-[#DC2626]">*</span>
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="formData.service_percent = 0" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">0%</button>
                                        <button type="button" @click="formData.service_percent = 5" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">5%</button>
                                        <button type="button" @click="formData.service_percent = 10" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F8F5F0] hover:bg-[#8C6246]/20 text-[#7A685D]">10%</button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="service_percent"
                                        name="service_percent"
                                        x-model.number="formData.service_percent"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        required
                                        placeholder="0"
                                        class="w-full pl-3.5 pr-8 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all font-semibold"
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-[#7A685D]">%</span>
                                </div>
                                <p class="text-[11px] text-[#7A685D] mt-1">Biaya layanan operasional meja / dine-in kafe</p>
                            </div>
                        </div>

                        <!-- Mode Perhitungan Pajak: Inklusif vs Eksklusif (BR-04) -->
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-2">
                                Mode Perhitungan Pajak (BR-04) <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label
                                    @click="formData.tax_inclusive = false"
                                    :class="!formData.tax_inclusive ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex items-start gap-3 p-3.5 rounded-[12px] border cursor-pointer transition-all duration-150"
                                >
                                    <input
                                        type="radio"
                                        name="tax_inclusive"
                                        value="0"
                                        :checked="!formData.tax_inclusive"
                                        class="mt-0.5 text-[#3C2317] focus:ring-[#8C6246]"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-[#1A0F0A] block">Pajak Eksklusif (Default BR-04)</span>
                                        <span class="text-[11px] text-[#7A685D]">Harga menu belum termasuk pajak. Pajak ditambahkan pada total di kasir.</span>
                                    </div>
                                </label>

                                <label
                                    @click="formData.tax_inclusive = true"
                                    :class="formData.tax_inclusive ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex items-start gap-3 p-3.5 rounded-[12px] border cursor-pointer transition-all duration-150"
                                >
                                    <input
                                        type="radio"
                                        name="tax_inclusive"
                                        value="1"
                                        :checked="formData.tax_inclusive"
                                        class="mt-0.5 text-[#3C2317] focus:ring-[#8C6246]"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-[#1A0F0A] block">Pajak Inklusif</span>
                                        <span class="text-[11px] text-[#7A685D]">Harga menu di papan daftar menu sudah mencakup pajak restoran.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Aturan Pembulatan Tunai (BR-05) -->
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] mb-2">
                                Aturan Pembulatan Transaksi Tunai (BR-05) <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <label
                                    @click="formData.cash_rounding = 100"
                                    :class="formData.cash_rounding == 100 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex flex-col items-center justify-center p-3 rounded-[12px] border cursor-pointer text-center transition-all duration-150"
                                >
                                    <input type="radio" name="cash_rounding" value="100" x-model="formData.cash_rounding" class="sr-only"/>
                                    <span class="text-xs font-bold text-[#1A0F0A]">Rp 100</span>
                                    <span class="text-[10px] text-[#2E7D32] font-semibold mt-0.5">Default BR-05</span>
                                    <span class="text-[10px] text-[#7A685D] mt-0.5">Ke bawah</span>
                                </label>

                                <label
                                    @click="formData.cash_rounding = 50"
                                    :class="formData.cash_rounding == 50 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex flex-col items-center justify-center p-3 rounded-[12px] border cursor-pointer text-center transition-all duration-150"
                                >
                                    <input type="radio" name="cash_rounding" value="50" x-model="formData.cash_rounding" class="sr-only"/>
                                    <span class="text-xs font-bold text-[#1A0F0A]">Rp 50</span>
                                    <span class="text-[10px] text-[#7A685D] mt-0.5">Ke bawah</span>
                                </label>

                                <label
                                    @click="formData.cash_rounding = 500"
                                    :class="formData.cash_rounding == 500 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex flex-col items-center justify-center p-3 rounded-[12px] border cursor-pointer text-center transition-all duration-150"
                                >
                                    <input type="radio" name="cash_rounding" value="500" x-model="formData.cash_rounding" class="sr-only"/>
                                    <span class="text-xs font-bold text-[#1A0F0A]">Rp 500</span>
                                    <span class="text-[10px] text-[#7A685D] mt-0.5">Ke bawah</span>
                                </label>

                                <label
                                    @click="formData.cash_rounding = 0"
                                    :class="formData.cash_rounding == 0 ? 'border-[#3C2317] bg-[#FCFAF7] ring-2 ring-[#8C6246]/30' : 'border-[#8C6246]/20 bg-white hover:border-[#8C6246]/50'"
                                    class="flex flex-col items-center justify-center p-3 rounded-[12px] border cursor-pointer text-center transition-all duration-150"
                                >
                                    <input type="radio" name="cash_rounding" value="0" x-model="formData.cash_rounding" class="sr-only"/>
                                    <span class="text-xs font-bold text-[#1A0F0A]">Rp 0</span>
                                    <span class="text-[10px] text-[#7A685D] mt-0.5">Tanpa Pembulatan</span>
                                </label>
                            </div>
                            <p class="text-[11px] text-[#7A685D] mt-1.5">
                                Pembulatan ke bawah memudahkan kasir tanpa perlu menyiapkan pecahan koin receh kecil saat transaksi tunai.
                            </p>
                        </div>

                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEKSI 4: PEMBAYARAN & KEBIJAKAN STOK (US-32, BR-16) -->
                <!-- ========================================================= -->
                <div x-show="activeTab === 'payment'" x-cloak class="space-y-6">
                    <div class="bg-white rounded-[14px] p-6 border border-[#8C6246]/15 shadow-soft space-y-5">
                        <div class="flex items-center justify-between border-b border-[#8C6246]/10 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#1A0F0A] flex items-center gap-2">
                                    <span>Integrasi Pembayaran & Kebijakan Stok (BR-16)</span>
                                </h2>
                                <p class="text-xs text-[#7A685D] mt-0.5">
                                    Atur masa berlaku QRIS dinamis serta kebijakan operasional stok saat pembayaran
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#8C6246]/10 text-[#3C2317]">
                                Gateway & Kebijakan
                            </span>
                        </div>

                        <!-- QRIS Expiry & Payment Provider -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Masa Berlaku QRIS -->
                            <div>
                                <label for="qris_expiry_minutes" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                    Masa Berlaku QRIS Dinamis (Menit) <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="qris_expiry_minutes"
                                        name="qris_expiry_minutes"
                                        x-model.number="formData.qris_expiry_minutes"
                                        min="1"
                                        max="60"
                                        required
                                        placeholder="15"
                                        class="w-full pl-3.5 pr-14 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all font-semibold"
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs text-[#7A685D]">Menit</span>
                                </div>
                                <p class="text-[11px] text-[#7A685D] mt-1">Timer hitung mundur pada modal pembayaran kasir (S-11, BR-11)</p>
                            </div>

                            <!-- Provider Payment Gateway -->
                            <div>
                                <label for="payment_provider" class="block text-xs font-semibold text-[#1A0F0A] mb-1.5">
                                    Provider Payment Gateway <span class="text-[#DC2626]">*</span>
                                </label>
                                <select
                                    id="payment_provider"
                                    name="payment_provider"
                                    x-model="formData.payment_provider"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-[10px] border border-[#8C6246]/30 text-xs text-[#1A0F0A] bg-white focus:outline-none focus:ring-2 focus:ring-[#8C6246]/50 focus:border-[#8C6246] transition-all"
                                >
                                    <option value="midtrans">Midtrans Snap / Core API (Produksi)</option>
                                    <option value="mock">Sandbox Mock Simulator (Pengujian Lokal)</option>
                                </select>
                                <p class="text-[11px] text-[#7A685D] mt-1">Sistem webhook dan auto polling QRIS real-time</p>
                            </div>
                        </div>

                        <!-- Kebijakan Stok Minus Saat Bayar (BR-16) -->
                        <div class="p-4 rounded-[12px] bg-[#FCFAF7] border border-[#8C6246]/20">
                            <label class="flex items-start justify-between cursor-pointer">
                                <div class="pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#1A0F0A]">Izinkan Stok Minus Saat Bayar (BR-16)</span>
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#2E7D32]/15 text-[#2E7D32]">Rekomendasi Operasional</span>
                                    </div>
                                    <p class="text-xs text-[#7A685D] mt-1 leading-relaxed">
                                        Saat order dibayar (<code class="text-[#3C2317] font-semibold">status = 'paid'</code>), stok bahan baku tetap dikurangkan meskipun tercatat kurang atau bernilai negatif di sistem. Ini mencegah antrean kasir terhenti jika belum sempat input stok masuk manual.
                                    </p>
                                </div>
                                <input
                                    type="checkbox"
                                    name="allow_negative_stock_on_paid"
                                    value="1"
                                    x-model="formData.allow_negative_stock_on_paid"
                                    class="rounded border-[#8C6246]/40 text-[#3C2317] focus:ring-[#8C6246] w-4 h-4 mt-1"
                                />
                            </label>
                        </div>

                    </div>
                </div>

                <!-- BOTTOM ACTION BAR -->
                <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft flex items-center justify-between">
                    <span class="text-xs text-[#7A685D]">
                        Perubahan akan langsung aktif untuk sesi POS kasir berikutnya
                    </span>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
                    >
                        <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <span>Simpan Pengaturan Toko & Struk</span>
                    </button>
                </div>
            </form>
            <!-- FORM END -->

        </div>

        <!-- RIGHT COLUMN: INTERACTIVE THERMAL RECEIPT PREVIEW (5 Cols) -->
        <div class="lg:col-span-5 sticky top-24 space-y-4">
            
            <!-- Preview Controls Header -->
            <div class="bg-white rounded-[14px] p-4 border border-[#8C6246]/15 shadow-soft flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-[#1A0F0A] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#2E7D32] animate-pulse"></span>
                        <span>Live Preview Struk Thermal</span>
                    </h3>
                    <p class="text-[11px] text-[#7A685D] mt-0.5">Tampilan real-time sesuai preferensi Anda</p>
                </div>
                <!-- Width Switcher (58mm vs 80mm) -->
                <div class="flex items-center p-1 bg-[#F8F5F0] rounded-[10px] border border-[#8C6246]/20">
                    <button
                        type="button"
                        @click="previewWidth = 58"
                        :class="previewWidth == 58 ? 'bg-white text-[#3C2317] shadow-sm font-bold' : 'text-[#7A685D] hover:text-[#1A0F0A]'"
                        class="px-2.5 py-1 text-[11px] rounded-[7px] transition-all"
                    >
                        58 mm
                    </button>
                    <button
                        type="button"
                        @click="previewWidth = 80"
                        :class="previewWidth == 80 ? 'bg-white text-[#3C2317] shadow-sm font-bold' : 'text-[#7A685D] hover:text-[#1A0F0A]'"
                        class="px-2.5 py-1 text-[11px] rounded-[7px] transition-all"
                    >
                        80 mm
                    </button>
                </div>
            </div>

            <!-- THERMAL RECEIPT CONTAINER -->
            <div class="flex justify-center p-2 sm:p-4 bg-[#EFECE6] rounded-[18px] border border-[#8C6246]/20 overflow-hidden">
                
                <!-- The Actual Paper Element (Printable) -->
                <div
                    id="printable-thermal-receipt"
                    class="thermal-receipt transition-all duration-300 font-mono text-[#1A0F0A]"
                    :style="previewWidth == 58 ? 'width: 270px; max-width: 100%; font-size: 11px;' : 'width: 350px; max-width: 100%; font-size: 12px;'"
                >
                    <!-- Perforated Tear Edge (Top) -->
                    <div class="receipt-edge-top"></div>

                    <!-- Inner Receipt Body -->
                    <div class="p-4 sm:p-5 space-y-3">
                        
                        <!-- Header Kedai & Logo -->
                        <div class="text-center space-y-1">
                            <template x-if="formData.show_logo_on_receipt">
                                <div class="flex justify-center mb-1">
                                    <div class="w-10 h-10 rounded-full bg-[#1A0F0A] flex items-center justify-center text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                    </div>
                                </div>
                            </template>

                            <h4 class="font-extrabold text-sm sm:text-base tracking-wide uppercase" x-text="formData.store_name || 'KOPI TITIK NOL'"></h4>
                            
                            <template x-if="formData.receipt_header">
                                <p class="text-[10px] sm:text-[11px] font-semibold text-[#3C2317]" x-text="formData.receipt_header"></p>
                            </template>
                            
                            <p class="text-[10px] text-[#7A685D] leading-tight" x-text="formData.store_address"></p>
                            <p class="text-[10px] text-[#7A685D]">Telp: <span x-text="formData.store_phone"></span></p>
                        </div>

                        <!-- Divider Line -->
                        <div class="border-t border-dashed border-[#1A0F0A]/40 my-2"></div>

                        <!-- Antrean Besar (Jika Diaktifkan) -->
                        <template x-if="formData.show_queue_number">
                            <div class="text-center py-1">
                                <span class="text-[10px] text-[#7A685D] uppercase tracking-wider block">Nomor Antrean</span>
                                <span class="text-xl sm:text-2xl font-black tracking-widest text-[#1A0F0A]">A-012</span>
                            </div>
                        </template>

                        <!-- Order Info Meta -->
                        <div class="text-[10px] sm:text-[11px] space-y-0.5 text-[#1A0F0A]/90">
                            <div class="flex justify-between">
                                <span>No: #SKT-261026-001</span>
                                <span>26/10/26 14:30</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Kasir: Andi Pratama</span>
                                <span>DINE IN (Meja 04)</span>
                            </div>
                        </div>

                        <!-- Divider Line -->
                        <div class="border-t border-dashed border-[#1A0F0A]/40 my-2"></div>

                        <!-- Item Lines -->
                        <div class="space-y-1.5 text-[11px]">
                            <div>
                                <div class="flex justify-between font-bold">
                                    <span>1x Es Kopi Susu Titik Nol</span>
                                    <span>20.000</span>
                                </div>
                                <div class="text-[9px] text-[#7A685D] pl-2">&bull; Less Sugar 50%</div>
                            </div>
                            <div>
                                <div class="flex justify-between font-bold">
                                    <span>1x Croissant Butter Artisan</span>
                                    <span>25.000</span>
                                </div>
                                <div class="text-[9px] text-[#7A685D] pl-2">&bull; Hangatkan</div>
                            </div>
                        </div>

                        <!-- Divider Line -->
                        <div class="border-t border-dashed border-[#1A0F0A]/40 my-2"></div>

                        <!-- Calculation Summary -->
                        <div class="space-y-1 text-[11px]">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>Rp 45.000</span>
                            </div>
                            <div class="flex justify-between text-[#2E7D32]">
                                <span>Diskon Voucher (HEMAT10):</span>
                                <span>-Rp 5.000</span>
                            </div>

                            <!-- Pajak PB1 (BR-04) -->
                            <template x-if="calculatedTax > 0">
                                <div class="flex justify-between">
                                    <span x-text="`Pajak PB1 (${formData.tax_percent}%${formData.tax_inclusive ? ' Inklusif' : ''}):`"></span>
                                    <span x-text="`Rp ${formatRupiah(calculatedTax)}`"></span>
                                </div>
                            </template>

                            <!-- Service Charge -->
                            <template x-if="calculatedService > 0">
                                <div class="flex justify-between">
                                    <span x-text="`Service Charge (${formData.service_percent}%):`"></span>
                                    <span x-text="`Rp ${formatRupiah(calculatedService)}`"></span>
                                </div>
                            </template>

                            <!-- Pembulatan (BR-05) -->
                            <template x-if="roundingAmount !== 0">
                                <div class="flex justify-between text-[#7A685D]">
                                    <span x-text="`Pembulatan (Rp ${formData.cash_rounding}):`"></span>
                                    <span x-text="`-Rp ${formatRupiah(Math.abs(roundingAmount))}`"></span>
                                </div>
                            </template>

                            <!-- GRAND TOTAL -->
                            <div class="border-t border-solid border-[#1A0F0A] pt-1.5 flex justify-between font-black text-xs sm:text-sm">
                                <span>TOTAL:</span>
                                <span x-text="`Rp ${formatRupiah(finalGrandTotal)}`"></span>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="border-t border-dashed border-[#1A0F0A]/40 pt-2 space-y-0.5 text-[10px] sm:text-[11px]">
                            <div class="flex justify-between">
                                <span>Metode Bayar:</span>
                                <span class="font-bold">TUNAI (LUNAS)</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tunai Diterima:</span>
                                <span x-text="`Rp ${formatRupiah(finalGrandTotal > 50000 ? 100000 : 50000)}`"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Kembalian:</span>
                                <span x-text="`Rp ${formatRupiah(changeAmount)}`"></span>
                            </div>
                        </div>

                        <!-- Divider Line -->
                        <div class="border-t border-dashed border-[#1A0F0A]/40 my-2"></div>

                        <!-- Footer Text & Barcode Simulation -->
                        <div class="text-center space-y-2 pt-1">
                            <p class="text-[10px] text-[#1A0F0A] leading-tight font-medium" x-text="formData.receipt_footer"></p>
                            
                            <!-- Barcode SVG Simulation -->
                            <div class="flex justify-center items-center py-1 opacity-80">
                                <div class="h-8 flex items-center space-x-0.5">
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1.5 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1 h-full bg-black"></div>
                                    <div class="w-2 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1 h-full bg-black"></div>
                                    <div class="w-1.5 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-2 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                    <div class="w-1 h-full bg-black"></div>
                                    <div class="w-1.5 h-full bg-black"></div>
                                    <div class="w-0.5 h-full bg-black"></div>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#7A685D] tracking-wider block">SIMKOTOL POS &bull; www.kopititiknol.id</span>
                        </div>

                    </div>

                    <!-- Perforated Tear Edge (Bottom) -->
                    <div class="receipt-edge-bottom"></div>
                </div>

            </div>

            <!-- Receipt Actions (Cetak & Salin WhatsApp) -->
            <div class="bg-white rounded-[14px] p-3.5 border border-[#8C6246]/15 shadow-soft flex items-center justify-between gap-2">
                <button
                    type="button"
                    @click="window.print()"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-sm transition-all"
                >
                    <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.056.867-1.829 1.89-1.829h6.78c1.023 0 2.13.773 1.89 1.829l-1.042 4.593A2.25 2.25 0 0113.84 21H10.16a2.25 2.25 0 01-2.198-1.578L6.72 13.83zM6 9V3.75A2.25 2.25 0 018.25 1.5h7.5A2.25 2.25 0 0118 3.75V9m-12 0h12m-12 0H4.5A2.25 2.25 0 002.25 11.25v6.5A2.25 2.25 0 004.5 20H6"/>
                    </svg>
                    <span>Cetak Struk</span>
                </button>

                <button
                    type="button"
                    @click="copyWhatsAppReceipt()"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-[10px] bg-[#2E7D32] hover:bg-[#256828] text-white text-xs font-semibold shadow-sm transition-all"
                >
                    <template x-if="!copySuccess">
                        <div class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.865 5.25 5.25 0 00.832-2.842C3.818 15.65 3 13.91 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                            </svg>
                            <span>Salin Teks WA</span>
                        </div>
                    </template>
                    <template x-if="copySuccess">
                        <span class="text-white font-bold text-xs">Tersalin! &check;</span>
                    </template>
                </button>
            </div>

        </div>

    </div>
</div>
@endsection
