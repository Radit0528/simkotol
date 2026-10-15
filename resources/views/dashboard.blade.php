{{--
  [FRONTEND] Halaman Dashboard Owner & Placeholder Layar (US-26, S-02).
  Menampilkan KPI Cards (Penjualan Hari Ini, Transaksi, Laba Kotor, Bahan Kritis)
  serta demonstrasi integrasi Layout Utama, Topbar, Sidebar, dan Toast Notification (Phase F0).
--}}

@extends('layouts.app')

@section('header')
    <div class="flex flex-col">
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            {{ $title ?? 'Dashboard Owner (S-02)' }}
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Selamat datang kembali di Sistem Manajemen Kopi Titik Nol
        </p>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Quick Status Banner / Welcome Alert -->
    <div class="p-4 sm:p-5 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/20 shadow-soft flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-[12px] bg-[#3C2317] text-[#D4A373] flex items-center justify-center shrink-0 shadow-soft">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-[#1A0F0A]">Fondasi Antarmuka Frontend — Phase F0 Berhasil Dikonfigurasi</h2>
                <p class="text-xs text-[#7A685D] mt-0.5">Desain Token Earthy Coffee, Tipografi Outfit/Inter, Layout Induk Multi-Role, & Notifikasi Toast aktif.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
            <button
                type="button"
                onclick="window.toast('Tes notifikasi sukses: Keranjang siap digunakan!', 'success')"
                class="px-3 py-1.5 rounded-[10px] bg-[#2E7D32]/10 hover:bg-[#2E7D32]/20 text-[#2E7D32] border border-[#2E7D32]/30 text-xs font-semibold transition-colors"
            >
                Uji Toast Sukses
            </button>
            <button
                type="button"
                onclick="window.toast('Peringatan: Stok Susu UHT tersisa 2 Liter (≤ batas minimum)', 'warning')"
                class="px-3 py-1.5 rounded-[10px] bg-[#D97706]/10 hover:bg-[#D97706]/20 text-[#B45309] border border-[#D97706]/30 text-xs font-semibold transition-colors"
            >
                Uji Toast Peringatan
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards Row (S-02 Blueprint) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- KPI 1: Penjualan Hari Ini -->
        <div class="p-5 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
            <div class="flex items-center justify-between text-[#7A685D]">
                <span class="text-xs font-semibold uppercase tracking-wider">Penjualan Hari Ini</span>
                <div class="w-8 h-8 rounded-full bg-[#8C6246]/10 flex items-center justify-center text-[#8C6246]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-[#1A0F0A] tracking-tight">Rp 1.450.000</span>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-[#2E7D32] font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/>
                    </svg>
                    <span>+12.5% vs kemarin</span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Total Transaksi -->
        <div class="p-5 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
            <div class="flex items-center justify-between text-[#7A685D]">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Transaksi</span>
                <div class="w-8 h-8 rounded-full bg-[#8C6246]/10 flex items-center justify-center text-[#8C6246]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-[#1A0F0A] tracking-tight">64 Struk</span>
                <p class="text-xs text-[#7A685D] mt-1">Rata-rata: Rp 22.650 / struk</p>
            </div>
        </div>

        <!-- KPI 3: Laba Kotor -->
        <div class="p-5 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
            <div class="flex items-center justify-between text-[#7A685D]">
                <span class="text-xs font-semibold uppercase tracking-wider">Laba Kotor (Est.)</span>
                <div class="w-8 h-8 rounded-full bg-[#2E7D32]/10 flex items-center justify-center text-[#2E7D32]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-[#1A0F0A] tracking-tight">Rp 890.000</span>
                <p class="text-xs text-[#2E7D32] font-semibold mt-1">Margin: ~61.4% (HPP Rp 560k)</p>
            </div>
        </div>

        <!-- KPI 4: Bahan Kritis -->
        <div class="p-5 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
            <div class="flex items-center justify-between text-[#7A685D]">
                <span class="text-xs font-semibold uppercase tracking-wider">Bahan Kritis</span>
                <div class="w-8 h-8 rounded-full bg-[#D97706]/10 flex items-center justify-center text-[#B45309]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-[#B45309] tracking-tight">2 Bahan</span>
                <p class="text-xs text-[#B45309] font-medium mt-1">Susu UHT & Sirup Karamel</p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation / Blueprint Action Links -->
    <div class="p-6 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/20 shadow-soft">
        <h3 class="text-sm font-bold text-[#1A0F0A] mb-3">Tautan Cepat Fase Selanjutnya</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <a href="{{ route('pos.index') }}" class="p-3.5 rounded-[12px] bg-[#F8F5F0] hover:bg-[#8C6246]/10 border border-[#8C6246]/20 text-xs font-semibold text-[#3C2317] flex items-center justify-between group transition-colors">
                <span>Buka Layar POS Kasir (S-10)</span>
                <span class="text-[#8C6246] group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
            <a href="{{ route('login') }}" class="p-3.5 rounded-[12px] bg-[#F8F5F0] hover:bg-[#8C6246]/10 border border-[#8C6246]/20 text-xs font-semibold text-[#3C2317] flex items-center justify-between group transition-colors">
                <span>Pratinjau Halaman Login (S-01)</span>
                <span class="text-[#8C6246] group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
            <a href="{{ route('products.index') }}" class="p-3.5 rounded-[12px] bg-[#F8F5F0] hover:bg-[#8C6246]/10 border border-[#8C6246]/20 text-xs font-semibold text-[#3C2317] flex items-center justify-between group transition-colors">
                <span>Manajemen Menu & HPP (S-04)</span>
                <span class="text-[#8C6246] group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
        </div>
    </div>
</div>
@endsection
