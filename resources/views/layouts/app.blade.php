{{--
  [FRONTEND] Template Layout Induk Aplikasi SIMKOTOL (F0.2).
  Menyediakan kerangka utama antarmuka dengan Sidebar multi-role, Topbar profil,
  dan Notifikasi Toast global via Alpine.js.
  Sesuai dengan panduan ui_ux_design_system.md (PRD v2.0).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F5F0]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'SIMKOTOL') }} - Kopi Titik Nol</title>

    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts and Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body
    x-data="{ sidebarOpen: false }"
    class="h-full font-sans antialiased bg-[#F8F5F0] text-[#1A0F0A] selection:bg-[#D4A373] selection:text-[#3C2317]"
>
    <div class="min-h-full flex flex-col">
        <!-- Multi-Role Sidebar (Owner & Kasir) -->
        @include('layouts.partials.sidebar')

        <!-- Main Layout Area (Offset by sidebar width on lg screens) -->
        <div class="lg:pl-64 flex flex-col flex-1 min-h-screen transition-all duration-300">
            <!-- Global Topbar Header -->
            @include('layouts.partials.topbar')

            <!-- Main Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>

            <!-- Footer Note -->
            <footer class="py-4 px-6 text-center text-xs text-[#7A685D] border-t border-[#8C6246]/10">
                <p>&copy; {{ date('Y') }} SIMKOTOL &bull; Sistem Manajemen Kedai Kopi Titik Nol. Seluruh hak cipta dilindungi.</p>
            </footer>
        </div>
    </div>

    <!-- Global Toast Notifications Component -->
    <x-toast />

    @stack('scripts')
</body>
</html>
