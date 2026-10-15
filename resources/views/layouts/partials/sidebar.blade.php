{{--
  [FRONTEND] Komponen Sidebar Navigasi Multi-Role (F0.2, US-01, US-02).
  Menampilkan menu navigasi yang disesuaikan berdasarkan peran pengguna (Owner atau Kasir).
  Menggunakan palet warna Earthy Coffee (#3C2317, #3D241C, #8C6246).
--}}

@php
    $user = auth()->user() ?? (object)[
        'name' => 'Demo User',
        'role' => 'owner',
        'email' => 'owner@titiknol.com',
    ];
    $role = $user->role ?? 'owner';
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<!-- Mobile Sidebar Backdrop -->
<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden"
    aria-hidden="true"
></div>

<!-- Main Sidebar Container -->
<aside
    id="main-sidebar"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-[#3C2317] text-[#F8F5F0] transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none"
    aria-label="Navigasi Utama"
>
    <!-- Brand Header -->
    <div class="flex items-center justify-between px-6 h-20 border-b border-[#3D241C] bg-[#3C2317]">
        <a href="{{ route('dashboard', [], false) }}" class="flex items-center gap-3 group focus:outline-none">
            <div class="w-10 h-10 rounded-[12px] bg-[#8C6246] flex items-center justify-center text-white shadow-soft group-hover:bg-[#D4A373] group-hover:text-[#3C2317] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-bold text-lg tracking-wide text-white font-sans">SIMKOTOL</span>
                <span class="text-[10px] text-[#D4A373] uppercase tracking-wider font-semibold">Kopi Titik Nol</span>
            </div>
        </a>

        <!-- Close Button (Mobile Only) -->
        <button
            type="button"
            @click="sidebarOpen = false"
            class="p-2 -mr-2 text-[#F8F5F0]/70 hover:text-white rounded-lg lg:hidden focus:outline-none"
            aria-label="Tutup Menu"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation Links Scrollable Area -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
        @if($role === 'owner')
            <!-- OWNER MENU SECTION -->
            <div class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-[#D4A373]/80 uppercase">
                Ringkasan & Kasir
            </div>

            <!-- Dashboard -->
            <a
                href="{{ route('dashboard', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
                <span>Dashboard (S-02)</span>
            </a>

            <!-- POS Layar Kasir -->
            <a
                href="{{ route('pos.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
                <span>Layar POS Kasir</span>
            </a>

            <!-- SECTION PRODUK & INVENTORY -->
            <div class="pt-5 px-3 pb-2 text-[11px] font-semibold tracking-wider text-[#D4A373]/80 uppercase">
                Menu & Inventaris
            </div>

            <!-- Kategori & Menu -->
            <a
                href="{{ route('products.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('products*') || request()->routeIs('categories*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
                <span>Menu & Kategori</span>
            </a>

            <!-- Bahan Baku & BOM -->
            <a
                href="{{ route('ingredients.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('ingredients*') || request()->routeIs('recipes*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                <span>Bahan & Resep (BOM)</span>
            </a>

            <!-- Stok Masuk & Keluar -->
            <a
                href="{{ route('stock.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock*') || request()->routeIs('purchases*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                </svg>
                <span>Mutasi & Pembelian</span>
            </a>

            <!-- SECTION TRANSAKSI & MARKETING -->
            <div class="pt-5 px-3 pb-2 text-[11px] font-semibold tracking-wider text-[#D4A373]/80 uppercase">
                Operasional & Finansial
            </div>

            <!-- Riwayat Transaksi -->
            <a
                href="{{ route('orders.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('orders*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
                <span>Riwayat Transaksi</span>
            </a>

            <!-- Laporan & Ekspor -->
            <a
                href="{{ route('reports.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('reports*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <span>Laporan & Laba</span>
            </a>

            <!-- Voucher & Promo -->
            <a
                href="{{ route('vouchers.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('vouchers*') || request()->routeIs('banners*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
                </svg>
                <span>Voucher & Promo</span>
            </a>

            <!-- SECTION SISTEM & PENGATURAN -->
            <div class="pt-5 px-3 pb-2 text-[11px] font-semibold tracking-wider text-[#D4A373]/80 uppercase">
                Sistem & Kontrol
            </div>

            <!-- Kelola Staf -->
            <a
                href="{{ route('users.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('users*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <span>Kelola Staf (S-19)</span>
            </a>

            <!-- Pengaturan Toko -->
            <a
                href="{{ route('settings.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('settings*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Pengaturan Toko (S-20)</span>
            </a>

            <!-- Audit Log -->
            <a
                href="{{ route('audit-logs.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('audit-logs*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <span>Audit Log (S-21)</span>
            </a>
        @else
            <!-- KASIR MENU SECTION -->
            <div class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-[#D4A373]/80 uppercase">
                Kasir POS
            </div>

            <!-- POS Layar Kasir -->
            <a
                href="{{ route('pos.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
                <span>POS Kasir (S-10)</span>
            </a>

            <!-- Shift Kasir -->
            <a
                href="{{ route('shifts.current', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('shifts*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Shift Kasir (S-09 / S-15)</span>
            </a>

            <!-- Riwayat Transaksi Shift -->
            <a
                href="{{ route('orders.index', [], false) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-[12px] text-sm font-medium transition-all duration-200 {{ request()->routeIs('orders*') ? 'bg-[#8C6246] text-white shadow-soft' : 'text-[#F8F5F0]/80 hover:bg-[#3D241C] hover:text-white' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
                <span>Pesanan Shift Saya</span>
            </a>
        @endif
    </nav>

    <!-- User Profile & Session Footer -->
    <div class="p-4 border-t border-[#3D241C] bg-[#3C2317]">
        <div class="flex items-center gap-3 p-2 rounded-[12px] bg-[#3D241C]/60">
            <div class="w-9 h-9 rounded-full bg-[#8C6246] flex items-center justify-center font-bold text-sm text-white shrink-0">
                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-white truncate">{{ $user->name ?? 'Kasir / Staf' }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#2E7D32]"></span>
                    <span class="text-[10px] uppercase font-bold text-[#D4A373] tracking-wide">{{ $role }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout', [], false) }}">
                @csrf
                <button
                    type="submit"
                    title="Keluar dari sistem"
                    class="p-1.5 text-[#F8F5F0]/70 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors focus:outline-none"
                    aria-label="Logout"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
