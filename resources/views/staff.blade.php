{{--
  [FRONTEND] Halaman Manajemen Pengguna & Staf Kasir (US-02, S-19, BR-38, BR-39).
  Halaman mandiri (standalone page) untuk pengelolaan staf kedai Kopi Titik Nol.
  Mencakup statistik ringkasan, pencarian & filter, tabel data staf,
  serta modal tambah/edit pengguna, reset kata sandi, dan toggle status akun (BR-39).
--}}

@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-[#1A0F0A] tracking-tight font-sans">
            Manajemen Staf & Pengguna (S-19)
        </h1>
        <p class="text-xs text-[#7A685D] mt-0.5">
            Kelola akun kasir dan hak akses owner sesuai standar keamanan Titik Nol
        </p>
    </div>
    <div>
        <button
            type="button"
            @click="openModalTambah()"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-[12px] bg-[#3C2317] hover:bg-[#3D241C] text-white font-semibold text-xs sm:text-sm shadow-soft transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-3 focus:ring-[#8C6246]/50"
        >
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Tambah Staf / Kasir Baru</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="{
        search: '',
        roleFilter: 'all',
        statusFilter: 'all',
        modalTambahOpen: false,
        modalEditOpen: false,
        modalResetPasswordOpen: false,
        modalToggleStatusOpen: false,
        selectedUser: null,
        showPassword: false,
        formData: {
            id: null,
            name: '',
            email: '',
            role: 'kasir',
            phone: '',
            pin_code: '',
            password: '',
            password_confirmation: '',
            is_active: 1
        },
        users: {{ Js::from($users ?? []) }},

        get filteredUsers() {
            return this.users.filter(user => {
                const matchSearch = this.search === '' || 
                    user.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    user.email.toLowerCase().includes(this.search.toLowerCase()) ||
                    (user.phone && user.phone.includes(this.search));
                
                const matchRole = this.roleFilter === 'all' || user.role === this.roleFilter;
                const matchStatus = this.statusFilter === 'all' || user.is_active == this.statusFilter;

                return matchSearch && matchRole && matchStatus;
            });
        },

        get stats() {
            return {
                total: this.users.length,
                kasir: this.users.filter(u => u.role === 'kasir').length,
                owner: this.users.filter(u => u.role === 'owner').length,
                active: this.users.filter(u => u.is_active == 1).length,
                inactive: this.users.filter(u => u.is_active == 0).length,
            };
        },

        openModalTambah() {
            this.formData = {
                id: null,
                name: '',
                email: '',
                role: 'kasir',
                phone: '',
                pin_code: '',
                password: '',
                password_confirmation: '',
                is_active: 1
            };
            this.showPassword = false;
            this.modalTambahOpen = true;
        },

        openModalEdit(user) {
            this.selectedUser = user;
            this.formData = {
                id: user.id,
                name: user.name,
                email: user.email,
                role: user.role,
                phone: user.phone || '',
                pin_code: '',
                is_active: user.is_active
            };
            this.modalEditOpen = true;
        },

        openModalResetPassword(user) {
            this.selectedUser = user;
            this.formData.password = '';
            this.formData.password_confirmation = '';
            this.showPassword = false;
            this.modalResetPasswordOpen = true;
        },

        openModalToggleStatus(user) {
            this.selectedUser = user;
            this.modalToggleStatusOpen = true;
        },

        submitTambah() {
            if (!this.formData.name || !this.formData.email || !this.formData.password) {
                window.toast('Harap lengkapi semua bidang wajib bertanda bintang (*)', 'warning');
                return;
            }
            if (this.formData.password.length < 8) {
                window.toast('Kata sandi minimal 8 karakter (BR-38)', 'danger');
                return;
            }
            if (this.formData.password !== this.formData.password_confirmation) {
                window.toast('Konfirmasi kata sandi tidak cocok', 'danger');
                return;
            }
            if (this.formData.role === 'owner' && this.formData.pin_code && !/^\d{6}$/.test(this.formData.pin_code)) {
                window.toast('PIN approval owner wajib tepat 6 digit angka (BR-38)', 'danger');
                return;
            }

            // Simulasi penambahan data sisi frontend
            const newUser = {
                id: Date.now(),
                name: this.formData.name,
                email: this.formData.email,
                role: this.formData.role,
                phone: this.formData.phone || '-',
                pin_set: this.formData.role === 'owner' && !!this.formData.pin_code,
                is_active: 1,
                last_login_at: 'Belum pernah',
                created_at: new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
            };
            this.users.unshift(newUser);
            this.modalTambahOpen = false;
            window.toast(`Akun staf '${newUser.name}' berhasil didaftarkan`, 'success');
        },

        submitEdit() {
            if (!this.formData.name || !this.formData.email) {
                window.toast('Nama dan email wajib diisi', 'warning');
                return;
            }
            const idx = this.users.findIndex(u => u.id === this.selectedUser.id);
            if (idx !== -1) {
                this.users[idx].name = this.formData.name;
                this.users[idx].email = this.formData.email;
                this.users[idx].role = this.formData.role;
                this.users[idx].phone = this.formData.phone || '-';
                window.toast(`Data pengguna '${this.formData.name}' berhasil diperbarui`, 'success');
            }
            this.modalEditOpen = false;
        },

        submitResetPassword() {
            if (!this.formData.password || this.formData.password.length < 8) {
                window.toast('Kata sandi baru minimal 8 karakter (BR-38)', 'danger');
                return;
            }
            if (this.formData.password !== this.formData.password_confirmation) {
                window.toast('Konfirmasi kata sandi tidak cocok', 'danger');
                return;
            }
            this.modalResetPasswordOpen = false;
            window.toast(`Kata sandi untuk '${this.selectedUser.name}' berhasil direset`, 'success');
        },

        confirmToggleStatus() {
            if (!this.selectedUser) return;
            const idx = this.users.findIndex(u => u.id === this.selectedUser.id);
            if (idx !== -1) {
                const newStatus = this.users[idx].is_active == 1 ? 0 : 1;
                this.users[idx].is_active = newStatus;
                const statusLabel = newStatus == 1 ? 'diaktifkan kembali' : 'dinonaktifkan (BR-39)';
                window.toast(`Status akun '${this.selectedUser.name}' telah ${statusLabel}`, newStatus == 1 ? 'success' : 'warning');
            }
            this.modalToggleStatusOpen = false;
        }
    }"
    class="space-y-6"
>
    <!-- 1. KARTU STATISTIK RINGKASAN STAF -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Total Staf -->
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <div class="flex items-center justify-between text-[#7A685D] text-xs font-semibold uppercase tracking-wider">
                <span>Total Staf</span>
                <div class="w-7 h-7 rounded-full bg-[#8C6246]/10 flex items-center justify-center text-[#8C6246]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-[#1A0F0A]" x-text="stats.total">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Semua akun terdaftar</p>
        </div>

        <!-- Kasir Aktif -->
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <div class="flex items-center justify-between text-[#7A685D] text-xs font-semibold uppercase tracking-wider">
                <span>Kasir Aktif</span>
                <div class="w-7 h-7 rounded-full bg-[#2E7D32]/10 flex items-center justify-center text-[#2E7D32]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-[#2E7D32]" x-text="stats.kasir">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Dapat membuka shift POS</p>
        </div>

        <!-- Owner / Administrator -->
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <div class="flex items-center justify-between text-[#7A685D] text-xs font-semibold uppercase tracking-wider">
                <span>Owner</span>
                <div class="w-7 h-7 rounded-full bg-[#3C2317]/10 flex items-center justify-center text-[#3C2317]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-[#3C2317]" x-text="stats.owner">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Otoritas PIN approval</p>
        </div>

        <!-- Akun Nonaktif -->
        <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft">
            <div class="flex items-center justify-between text-[#7A685D] text-xs font-semibold uppercase tracking-wider">
                <span>Nonaktif</span>
                <div class="w-7 h-7 rounded-full bg-[#DC2626]/10 flex items-center justify-center text-[#DC2626]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-[#DC2626]" x-text="stats.inactive">0</div>
            <p class="text-[11px] text-[#7A685D] mt-0.5">Akses login ditolak (BR-39)</p>
        </div>
    </div>

    <!-- 2. KONTROL PENCARIAN & FILTER -->
    <div class="p-4 rounded-[14px] bg-[#FCFAF7] border border-[#8C6246]/15 shadow-soft flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Input Pencarian -->
        <div class="relative flex-1 max-w-md">
            <input
                type="text"
                x-model="search"
                placeholder="Cari nama, email, atau no. telepon..."
                class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-[10px] bg-white border border-[#8C6246]/20 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all shadow-xs"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#7A685D]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
        </div>

        <!-- Pilihan Filter -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Filter Peran -->
            <div class="flex items-center gap-1.5">
                <label for="filter-role-staff" class="text-xs font-semibold text-[#7A685D]">Peran:</label>
                <select
                    id="filter-role-staff"
                    x-model="roleFilter"
                    class="px-2.5 py-1.5 text-xs rounded-[10px] bg-white border border-[#8C6246]/20 text-[#1A0F0A] focus:outline-none focus:ring-2 focus:ring-[#8C6246]/20"
                >
                    <option value="all">Semua Peran</option>
                    <option value="owner">Owner</option>
                    <option value="kasir">Kasir</option>
                </select>
            </div>

            <!-- Filter Status -->
            <div class="flex items-center gap-1.5">
                <label for="filter-status-staff" class="text-xs font-semibold text-[#7A685D]">Status:</label>
                <select
                    id="filter-status-staff"
                    x-model="statusFilter"
                    class="px-2.5 py-1.5 text-xs rounded-[10px] bg-white border border-[#8C6246]/20 text-[#1A0F0A] focus:outline-none focus:ring-2 focus:ring-[#8C6246]/20"
                >
                    <option value="all">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>

            <!-- Tombol Reset Filter -->
            <button
                type="button"
                x-show="search !== '' || roleFilter !== 'all' || statusFilter !== 'all'"
                @click="search = ''; roleFilter = 'all'; statusFilter = 'all'"
                class="px-2.5 py-1.5 text-xs text-[#DC2626] hover:bg-red-50 rounded-[8px] transition-colors font-medium"
                style="display: none;"
            >
                Reset Filter
            </button>
        </div>
    </div>

    <!-- 3. TABEL DATA STAF -->
    <div class="bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/15 shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#8C6246]/15 bg-[#F8F5F0]/60 text-[11px] font-bold text-[#7A685D] uppercase tracking-wider">
                        <th scope="col" class="py-3.5 px-4 sm:px-6">Pengguna / Akun</th>
                        <th scope="col" class="py-3.5 px-4">Peran</th>
                        <th scope="col" class="py-3.5 px-4">No. WhatsApp</th>
                        <th scope="col" class="py-3.5 px-4">PIN Approval</th>
                        <th scope="col" class="py-3.5 px-4">Status</th>
                        <th scope="col" class="py-3.5 px-4">Terakhir Masuk</th>
                        <th scope="col" class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#8C6246]/10 text-xs sm:text-sm">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <tr class="hover:bg-[#F8F5F0]/50 transition-colors">
                            <!-- Info Akun -->
                            <td class="py-3.5 px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-xs"
                                        :class="user.role === 'owner' ? 'bg-[#3C2317] text-[#D4A373]' : 'bg-[#8C6246] text-white'"
                                        x-text="user.name ? user.name.charAt(0).toUpperCase() : 'U'"
                                    ></div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-[#1A0F0A] truncate leading-tight" x-text="user.name"></p>
                                        <p class="text-xs text-[#7A685D] truncate mt-0.5" x-text="user.email"></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Lencana Peran -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <template x-if="user.role === 'owner'">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#3C2317] text-[#D4A373] shadow-2xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                        <span>Owner</span>
                                    </span>
                                </template>
                                <template x-if="user.role === 'kasir'">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#8C6246]/15 text-[#8C6246] border border-[#8C6246]/25">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                                        </svg>
                                        <span>Kasir</span>
                                    </span>
                                </template>
                            </td>

                            <!-- No. Telepon -->
                            <td class="py-3.5 px-4 text-[#7A685D] whitespace-nowrap font-mono text-xs" x-text="user.phone || '-'"></td>

                            <!-- Status PIN Approval Owner -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <template x-if="user.role === 'owner'">
                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-medium"
                                        :class="user.pin_set ? 'text-[#2E7D32]' : 'text-[#B45309]'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.pin_set ? 'bg-[#2E7D32]' : 'bg-[#D97706]'"></span>
                                        <span x-text="user.pin_set ? '6-Digit Aktif' : 'Belum Diatur'"></span>
                                    </span>
                                </template>
                                <template x-if="user.role !== 'owner'">
                                    <span class="text-xs text-[#7A685D]/60">-</span>
                                </template>
                            </td>

                            <!-- Status Aktif / Nonaktif -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="user.is_active == 1 ? 'bg-[#2E7D32]/10 text-[#2E7D32] border border-[#2E7D32]/30' : 'bg-red-50 text-[#DC2626] border border-[#DC2626]/30'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active == 1 ? 'bg-[#2E7D32]' : 'bg-[#DC2626]'"></span>
                                    <span x-text="user.is_active == 1 ? 'Aktif' : 'Nonaktif'"></span>
                                </span>
                            </td>

                            <!-- Terakhir Masuk -->
                            <td class="py-3.5 px-4 text-[#7A685D] text-xs whitespace-nowrap" x-text="user.last_login_at || 'Belum pernah'"></td>

                            <!-- Tombol Aksi -->
                            <td class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Tombol Ubah Profil -->
                                    <button
                                        type="button"
                                        @click="openModalEdit(user)"
                                        class="p-1.5 text-[#7A685D] hover:text-[#3C2317] hover:bg-[#F8F5F0] rounded-[8px] transition-colors focus:outline-none"
                                        title="Ubah Profil Pengguna"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Reset Kata Sandi -->
                                    <button
                                        type="button"
                                        @click="openModalResetPassword(user)"
                                        class="p-1.5 text-[#7A685D] hover:text-[#8C6246] hover:bg-[#8C6246]/10 rounded-[8px] transition-colors focus:outline-none"
                                        title="Reset Kata Sandi"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                        </svg>
                                    </button>

                                    <!-- Tombol Toggle Status -->
                                    <button
                                        type="button"
                                        @click="openModalToggleStatus(user)"
                                        class="p-1.5 rounded-[8px] transition-colors focus:outline-none"
                                        :class="user.is_active == 1 ? 'text-[#DC2626] hover:bg-red-50' : 'text-[#2E7D32] hover:bg-emerald-50'"
                                        :title="user.is_active == 1 ? 'Nonaktifkan Akun' : 'Aktifkan Akun Kembali'"
                                    >
                                        <template x-if="user.is_active == 1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </template>
                                        <template x-if="user.is_active != 1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- State Kosong -->
                    <tr x-show="filteredUsers.length === 0" style="display: none;">
                        <td colspan="7" class="py-12 px-4 text-center">
                            <div class="max-w-xs mx-auto">
                                <div class="w-12 h-12 rounded-full bg-[#8C6246]/10 text-[#8C6246] flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-[#1A0F0A]">Tidak Ada Staf Ditemukan</h3>
                                <p class="text-xs text-[#7A685D] mt-1">Coba periksa kembali kata kunci pencarian atau ganti filter peran & status.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. MODAL TAMBAH STAF / KASIR BARU -->
    <div
        x-show="modalTambahOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-tambah-title"
    >
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div
                x-show="modalTambahOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="modalTambahOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                x-show="modalTambahOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-lg w-full p-6 text-left overflow-hidden z-10"
            >
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h2 id="modal-tambah-title" class="text-base font-bold text-[#1A0F0A]">Daftarkan Staf / Kasir Baru</h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Lengkapi informasi akun untuk staf kedai Kopi Titik Nol</p>
                    </div>
                    <button
                        type="button"
                        @click="modalTambahOpen = false"
                        class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg transition-colors focus:outline-none"
                        aria-label="Tutup Modal"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitTambah()" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Nama Lengkap <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="formData.name"
                            required
                            placeholder="Contoh: Rian Anggara"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Alamat Email <span class="text-[#DC2626]">*</span>
                            </label>
                            <input
                                type="email"
                                x-model="formData.email"
                                required
                                placeholder="rian@titiknol.com"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                No. WhatsApp / HP
                            </label>
                            <input
                                type="tel"
                                x-model="formData.phone"
                                placeholder="081234567890"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1.5">
                            Peran Pengguna <span class="text-[#DC2626]">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="flex items-center gap-3 p-3 rounded-[10px] border cursor-pointer transition-all"
                                :class="formData.role === 'kasir' ? 'border-[#8C6246] bg-[#8C6246]/10 text-[#3C2317]' : 'border-[#8C6246]/20 bg-white text-[#7A685D] hover:bg-[#F8F5F0]'"
                            >
                                <input type="radio" value="kasir" x-model="formData.role" class="accent-[#3C2317]">
                                <div class="text-xs">
                                    <span class="font-bold block text-[#1A0F0A]">Kasir</span>
                                    <span class="text-[10px] text-[#7A685D]">Akses transaksi & shift</span>
                                </div>
                            </label>
                            <label
                                class="flex items-center gap-3 p-3 rounded-[10px] border cursor-pointer transition-all"
                                :class="formData.role === 'owner' ? 'border-[#3C2317] bg-[#3C2317]/10 text-[#3C2317]' : 'border-[#8C6246]/20 bg-white text-[#7A685D] hover:bg-[#F8F5F0]'"
                            >
                                <input type="radio" value="owner" x-model="formData.role" class="accent-[#3C2317]">
                                <div class="text-xs">
                                    <span class="font-bold block text-[#1A0F0A]">Owner</span>
                                    <span class="text-[10px] text-[#7A685D]">Akses penuh & approval</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Kata Sandi Awal <span class="text-[#DC2626]">*</span>
                            </label>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                x-model="formData.password"
                                required
                                placeholder="Min. 8 karakter"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                                Konfirmasi Sandi <span class="text-[#DC2626]">*</span>
                            </label>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                x-model="formData.password_confirmation"
                                required
                                placeholder="Ulangi kata sandi"
                                class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] placeholder-[#7A685D]/60 focus:outline-none transition-all"
                            >
                        </div>
                    </div>

                    <div x-show="formData.role === 'owner'" x-transition class="p-3.5 rounded-[12px] bg-[#3C2317]/5 border border-[#8C6246]/30 space-y-1.5">
                        <label class="block text-xs font-bold text-[#3C2317] uppercase tracking-wider">
                            PIN Approval Owner (6 Digit Angka)
                        </label>
                        <p class="text-[11px] text-[#7A685D]">
                            PIN digunakan untuk otorisasi pembatalan transaksi (void) dan aksi sensitif kasir (BR-37, BR-38).
                        </p>
                        <input
                            type="password"
                            maxlength="6"
                            inputmode="numeric"
                            x-model="formData.pin_code"
                            placeholder="Contoh: 123456"
                            class="w-full px-3.5 py-2 text-sm font-mono tracking-widest text-center rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#3C2317] focus:ring-2 focus:ring-[#3C2317]/20 text-[#1A0F0A] focus:outline-none transition-all"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="show-pass-tambah-staff" x-model="showPassword" class="rounded text-[#3C2317] accent-[#3C2317]">
                        <label for="show-pass-tambah-staff" class="text-xs text-[#7A685D] cursor-pointer">Perlihatkan teks kata sandi</label>
                    </div>

                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalTambahOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D] transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft transition-colors"
                        >
                            Simpan Staf Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. MODAL UBAH PROFIL STAF -->
    <div
        x-show="modalEditOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-edit-title"
    >
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div
                x-show="modalEditOpen"
                @click="modalEditOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                x-show="modalEditOpen"
                class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10"
            >
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div>
                        <h2 id="modal-edit-title" class="text-base font-bold text-[#1A0F0A]">Ubah Profil Staf</h2>
                        <p class="text-xs text-[#7A685D] mt-0.5">Perbarui nama, email, atau nomor kontak</p>
                    </div>
                    <button
                        type="button"
                        @click="modalEditOpen = false"
                        class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg transition-colors focus:outline-none"
                        aria-label="Tutup Modal"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="mt-4 space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Nama Lengkap
                        </label>
                        <input
                            type="text"
                            x-model="formData.name"
                            required
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Alamat Email
                        </label>
                        <input
                            type="email"
                            x-model="formData.email"
                            required
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            No. WhatsApp / Telepon
                        </label>
                        <input
                            type="tel"
                            x-model="formData.phone"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Peran Pengguna
                        </label>
                        <select
                            x-model="formData.role"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                            <option value="kasir">Kasir</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalEditOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#3C2317] hover:bg-[#3D241C] text-white text-xs font-semibold shadow-soft"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. MODAL RESET KATA SANDI -->
    <div
        x-show="modalResetPasswordOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-reset-title"
    >
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div
                x-show="modalResetPasswordOpen"
                @click="modalResetPasswordOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                x-show="modalResetPasswordOpen"
                class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10"
            >
                <div class="flex items-center justify-between pb-4 border-b border-[#8C6246]/15">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-[#8C6246]/15 text-[#8C6246] flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 id="modal-reset-title" class="text-base font-bold text-[#1A0F0A]">Reset Kata Sandi</h2>
                            <p class="text-xs text-[#7A685D] mt-0.5">Untuk pengguna: <strong class="text-[#1A0F0A]" x-text="selectedUser ? selectedUser.name : ''"></strong></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalResetPasswordOpen = false"
                        class="p-1.5 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg transition-colors focus:outline-none"
                        aria-label="Tutup Modal"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitResetPassword()" class="mt-4 space-y-3.5">
                    <div class="p-3 rounded-[10px] bg-amber-50 border border-[#D97706]/30 text-xs text-[#B45309] leading-relaxed">
                        Sandi lama akan langsung diganti. Staf yang bersangkutan harus masuk kembali menggunakan kata sandi baru.
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Kata Sandi Baru (Min. 8 Karakter) <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            x-model="formData.password"
                            required
                            placeholder="Masukkan sandi baru"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#1A0F0A] uppercase tracking-wider mb-1">
                            Konfirmasi Sandi Baru <span class="text-[#DC2626]">*</span>
                        </label>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            x-model="formData.password_confirmation"
                            required
                            placeholder="Ulangi sandi baru"
                            class="w-full px-3.5 py-2 text-sm rounded-[10px] bg-white border border-[#8C6246]/30 focus:border-[#8C6246] focus:ring-2 focus:ring-[#8C6246]/20 text-[#1A0F0A] focus:outline-none"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="show-pass-reset-staff" x-model="showPassword" class="rounded text-[#3C2317] accent-[#3C2317]">
                        <label for="show-pass-reset-staff" class="text-xs text-[#7A685D] cursor-pointer">Perlihatkan teks kata sandi</label>
                    </div>

                    <div class="pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="modalResetPasswordOpen = false"
                            class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-[10px] bg-[#8C6246] hover:bg-[#3C2317] text-white text-xs font-semibold shadow-soft"
                        >
                            Perbarui Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 7. MODAL KONFIRMASI STATUS (BR-39) -->
    <div
        x-show="modalToggleStatusOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-status-title"
    >
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div
                x-show="modalToggleStatusOpen"
                @click="modalToggleStatusOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                x-show="modalToggleStatusOpen"
                class="relative bg-[#FCFAF7] rounded-[14px] border border-[#8C6246]/20 shadow-soft max-w-md w-full p-6 text-left overflow-hidden z-10"
            >
                <div class="flex items-start gap-3.5">
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                        :class="selectedUser && selectedUser.is_active == 1 ? 'bg-red-50 text-[#DC2626]' : 'bg-emerald-50 text-[#2E7D32]'"
                    >
                        <template x-if="selectedUser && selectedUser.is_active == 1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </template>
                        <template x-if="selectedUser && selectedUser.is_active != 1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h2 id="modal-status-title" class="text-base font-bold text-[#1A0F0A]">
                            <span x-text="selectedUser && selectedUser.is_active == 1 ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Akun Kembali?'"></span>
                        </h2>
                        <p class="text-xs text-[#7A685D] mt-1 leading-relaxed">
                            <template x-if="selectedUser && selectedUser.is_active == 1">
                                <span>
                                    Akun <strong class="text-[#1A0F0A]" x-text="selectedUser.name"></strong> tidak akan bisa login ke sistem kasir. Sesuai <strong>BR-39</strong>, data transaksi historis tetap terjaga dan akun tidak dihapus permanen.
                                </span>
                            </template>
                            <template x-if="selectedUser && selectedUser.is_active != 1">
                                <span>
                                    Akun <strong class="text-[#1A0F0A]" x-text="selectedUser.name"></strong> akan dapat login kembali dan memulai shift POS.
                                </span>
                            </template>
                        </p>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-[#8C6246]/15 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="modalToggleStatusOpen = false"
                        class="px-4 py-2 rounded-[10px] bg-white hover:bg-[#F8F5F0] border border-[#8C6246]/30 text-xs font-semibold text-[#7A685D]"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmToggleStatus()"
                        class="px-4 py-2 rounded-[10px] text-white text-xs font-semibold shadow-soft"
                        :class="selectedUser && selectedUser.is_active == 1 ? 'bg-[#DC2626] hover:bg-red-700' : 'bg-[#2E7D32] hover:bg-emerald-700'"
                    >
                        <span x-text="selectedUser && selectedUser.is_active == 1 ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
