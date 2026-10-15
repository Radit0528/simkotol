{{--
  [FRONTEND] Halaman Login Pengguna (US-01, S-01, BR-38, BR-39).
  Menyediakan form input Email dan Password, penanganan status lockout / rate limiting (5x gagal per menit),
  tampilan status peringatan akun nonaktif, serta informasi pengalihan otomatis berdasarkan peran (Owner / Kasir).
--}}

@extends('layouts.guest')

@section('content')
<div class="flex flex-col gap-6" x-data="{ showPassword: false, loading: false }">
    <!-- Header & Logo -->
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-[18px] bg-[#3C2317] text-[#D4A373] shadow-soft mb-3 border-2 border-[#8C6246]/30">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1A0F0A] tracking-tight font-sans">
            SIMKOTOL
        </h1>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#8C6246] mt-0.5">
            Sistem Manajemen Kopi Titik Nol
        </p>
    </div>

    <!-- Login Card Container -->
    <div class="p-6 sm:p-8 bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-[#1A0F0A]">Masuk ke Akun</h2>
            <p class="text-xs text-[#7A685D] mt-0.5">Silakan masukkan email dan kata sandi Anda</p>
        </div>

        <!-- Rate Limit Lockout Notification Banner (US-01) -->
        @if (session('status'))
            <div class="mb-5 p-3.5 rounded-[12px] bg-emerald-50 border border-[#2E7D32]/30 text-xs font-medium text-[#2E7D32] flex items-start gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->has('throttle') || session('lockout'))
            <div class="mb-5 p-4 rounded-[12px] bg-amber-50 border border-[#D97706]/40 text-xs text-[#B45309] flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-[#D97706]/15 flex items-center justify-center shrink-0 mt-0.5 text-[#B45309]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <strong class="font-bold block text-sm">Akses Terkunci Sementara (Rate Limit)</strong>
                    <span class="mt-0.5 block leading-relaxed">
                        {{ $errors->first('throttle') ?? session('lockout') ?? 'Terlalu banyak percobaan masuk yang gagal (maks 5 kali). Silakan coba lagi beberapa saat kemudian demi keamanan akun Anda.' }}
                    </span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login', [], false) }}" @submit="loading = true" class="space-y-4">
            @csrf

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1.5">
                    Alamat Email
                </label>
                <div class="relative">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="contoh: owner@titiknol.com"
                        class="w-full px-4 py-2.5 text-sm rounded-[12px] bg-white border @error('email') border-[#DC2626] focus:border-[#DC2626] focus:ring-[#DC2626]/20 @else border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-[#8C6246]/20 @enderror focus:outline-none focus:ring-3 text-[#1A0F0A] placeholder-[#7A685D]/60 transition-all shadow-xs"
                    >
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#7A685D]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                    </div>
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-[#DC2626] font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider">
                        Kata Sandi
                    </label>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Minimal 8 karakter"
                        class="w-full px-4 py-2.5 text-sm rounded-[12px] bg-white border @error('password') border-[#DC2626] focus:border-[#DC2626] focus:ring-[#DC2626]/20 @else border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-[#8C6246]/20 @enderror focus:outline-none focus:ring-3 text-[#1A0F0A] placeholder-[#7A685D]/60 transition-all shadow-xs pr-10"
                    >
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#7A685D] hover:text-[#3C2317] focus:outline-none"
                        aria-label="Lihat kata sandi"
                    >
                        <template x-if="!showPassword">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </template>
                        <template x-if="showPassword">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </template>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-[#DC2626] font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Remember Me & Help -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 rounded text-[#3C2317] border-[#8C6246]/40 focus:ring-[#8C6246] accent-[#3C2317]"
                    >
                    <span class="text-xs text-[#7A685D] font-medium">Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-2.5 px-4 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-sm shadow-soft transition-all duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50 disabled:opacity-75 disabled:cursor-not-allowed"
                >
                    <span x-show="!loading" class="flex items-center gap-2">
                        <span>Masuk ke SIMKOTOL</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </span>
                    <span x-show="loading" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memverifikasi...</span>
                    </span>
                </button>
            </div>
        </form>

        <!-- Role Redirection Notice (US-01) -->
        <div class="mt-6 pt-5 border-t border-[#8C6246]/15">
            <div class="flex items-start gap-2.5 text-[11px] text-[#7A685D] leading-relaxed">
                <svg class="w-4 h-4 text-[#8C6246] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <span>
                    <strong>Pengalihan Berdasarkan Peran:</strong> Akun <strong class="text-[#3C2317]">Owner</strong> otomatis diarahkan ke Dashboard Pemilik (S-02), sedangkan akun <strong class="text-[#3C2317]">Kasir</strong> diarahkan ke POS Kasir / Buka Shift (S-09).
                </span>
            </div>
        </div>
    </div>

    <!-- Security & Footer Notice -->
    <div class="text-center text-xs text-[#7A685D]">
        <p>Akses terbatas hanya untuk staf & pemilik terdaftar.</p>
        <p class="mt-1 text-[11px] text-[#7A685D]/75">&copy; {{ date('Y') }} SIMKOTOL &bull; Kopi Titik Nol</p>
    </div>
</div>
@endsection
