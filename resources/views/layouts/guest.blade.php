{{--
  [FRONTEND] Layout Tamu / Unauthenticated (F0.3, S-01).
  Digunakan untuk halaman login dan autentikasi dengan latar belakang khas Kopi Titik Nol.
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

    <!-- Vite Styles and Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="h-full font-sans antialiased bg-[#F8F5F0] text-[#1A0F0A] flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
        @if (isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </div>

    <!-- Global Toast Component -->
    <x-toast />

    @stack('scripts')
</body>
</html>
