{{--
  [FRONTEND] Komponen Topbar Navigasi & Profil (F0.2, US-10, S-22).
  Menampilkan tombol hamburger mobile, breadcrumb/judul halaman, status shift aktif,
  lencana notifikasi stok kritis (S-22), dan ringkasan profil pengguna.
--}}

@php
    $user = auth()->user() ?? (object)[
        'name' => 'Owner Titik Nol',
        'role' => 'owner',
        'email' => 'owner@titiknol.com',
    ];
    $role = $user->role ?? 'owner';
    // Critical low stock count placeholder / dynamic query
    $criticalStockCount = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('v_ingredient_stock_status')) {
            $criticalStockCount = \Illuminate\Support\Facades\DB::table('v_ingredient_stock_status')
                ->whereIn('stock_status', ['low', 'out'])
                ->count();
        }
    } catch (\Throwable $e) {
        $criticalStockCount = 0;
    }
@endphp

<header class="sticky top-0 z-30 flex items-center justify-between h-20 px-4 sm:px-6 lg:px-8 bg-[#FCFAF7] border-b border-[#8C6246]/15 shadow-soft">
    <!-- Left: Mobile Toggle & Page Title / Breadcrumb -->
    <div class="flex items-center gap-3 sm:gap-4">
        <!-- Mobile Sidebar Toggle -->
        <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 -ml-2 text-[#7A685D] hover:text-[#3C2317] hover:bg-[#F8F5F0] rounded-xl lg:hidden focus:outline-none focus:ring-2 focus:ring-[#8C6246]"
            aria-label="Buka Menu Navigasi"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        <!-- Dynamic Title or Header Slot -->
        <div>
            @hasSection('header')
                @yield('header')
            @else
                <h1 class="text-lg sm:text-xl font-bold text-[#1A0F0A] tracking-tight font-sans">
                    {{ $title ?? 'SIMKOTOL POS & Dashboard' }}
                </h1>
                <p class="text-xs text-[#7A685D] hidden sm:block">Sistem Manajemen Kedai Kopi Titik Nol</p>
            @endif
        </div>
    </div>

    <!-- Right: Shift Status, Stock Notifications, and Profile -->
    <div class="flex items-center gap-2 sm:gap-4">
        <!-- Shift Status Indicator -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#F8F5F0] border border-[#8C6246]/20 text-xs font-medium text-[#3C2317]">
            <span class="w-2 h-2 rounded-full bg-[#2E7D32] animate-pulse"></span>
            <span>Shift: <strong class="font-semibold">Aktif</strong></span>
        </div>

        @if($role === 'owner')
            <!-- Low Stock Alert Drawer Trigger (S-22, US-10) -->
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="relative p-2 text-[#7A685D] hover:text-[#3C2317] hover:bg-[#F8F5F0] rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-[#8C6246]"
                    aria-label="Notifikasi Stok Bahan Kritis"
                    title="Notifikasi Stok Bahan"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                    @if($criticalStockCount > 0)
                        <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#DC2626] text-[10px] font-bold text-white shadow-xs">
                            {{ $criticalStockCount > 9 ? '9+' : $criticalStockCount }}
                        </span>
                    @endif
                </button>

                <!-- Stock Alert Dropdown Panel -->
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-3 w-80 sm:w-96 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/20 shadow-soft p-4 z-50 text-[#1A0F0A]"
                    style="display: none;"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-[#8C6246]/15">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#D97706]"></span>
                            <h3 class="text-sm font-bold text-[#1A0F0A]">Peringatan Stok Kritis (S-22)</h3>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-[#D97706]/15 text-[#B45309] font-bold">
                            {{ $criticalStockCount }} Bahan
                        </span>
                    </div>

                    <div class="py-3 space-y-2.5 max-h-64 overflow-y-auto custom-scrollbar">
                        @if($criticalStockCount == 0)
                            <div class="text-center py-6 text-xs text-[#7A685D]">
                                <svg class="w-8 h-8 mx-auto mb-2 text-[#2E7D32]/60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Seluruh stok bahan baku dalam batas aman.
                            </div>
                        @else
                            <p class="text-xs text-[#7A685D]">Bahan berikut telah mencapai atau di bawah batas minimum:</p>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-[#8C6246]/15 text-center">
                        <a
                            href="{{ route('ingredients.index', [], false) }}"
                            class="inline-block text-xs font-semibold text-[#8C6246] hover:text-[#3C2317] hover:underline"
                        >
                            Lihat Semua Stok Bahan &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Profile Dropdown & Info -->
        <div class="flex items-center gap-3 pl-2 sm:pl-3 border-l border-[#8C6246]/20">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#3C2317] text-[#D4A373] flex items-center justify-center font-bold text-sm shadow-soft">
                {{ strtoupper(substr($user->name ?? 'O', 0, 1)) }}
            </div>
            <div class="hidden sm:block text-left">
                <div class="text-xs font-bold text-[#1A0F0A] leading-tight">{{ $user->name ?? 'Owner' }}</div>
                <div class="text-[10px] text-[#7A685D] uppercase tracking-wider font-semibold">{{ $role }}</div>
            </div>
        </div>
    </div>
</header>
