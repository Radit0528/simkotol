{{--
  [FRONTEND] Komponen Notifikasi Toast Global (F0.2, NFR-06).
  Menangani flash message dari server (Laravel session) serta dynamic toast
  sisi klien via Alpine.js store 'toasts'.
--}}
<div
    x-data="{
        init() {
            @if(session('success'))
                this.$store.toasts.add('{{ addslashes(session('success')) }}', 'success');
            @endif
            @if(session('error'))
                this.$store.toasts.add('{{ addslashes(session('error')) }}', 'danger');
            @endif
            @if(session('warning'))
                this.$store.toasts.add('{{ addslashes(session('warning')) }}', 'warning');
            @endif
            @if(session('info'))
                this.$store.toasts.add('{{ addslashes(session('info')) }}', 'info');
            @endif
            @if($errors->any())
                @foreach($errors->all() as $error)
                    this.$store.toasts.add('{{ addslashes($error) }}', 'danger');
                @endforeach
            @endif
        }
    }"
    class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none"
    role="region"
    aria-live="polite"
    aria-label="Pemberitahuan Sistem"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-start gap-3 p-4 rounded-[14px] shadow-soft border bg-[#FCFAF7] transition-all duration-200"
            :class="{
                'border-[#2E7D32]/40 bg-emerald-50/90 text-[#1A0F0A]': toast.type === 'success',
                'border-[#DC2626]/40 bg-red-50/90 text-[#1A0F0A]': toast.type === 'danger',
                'border-[#D97706]/40 bg-amber-50/90 text-[#1A0F0A]': toast.type === 'warning',
                'border-[#8C6246]/40 bg-[#FCFAF7] text-[#1A0F0A]': toast.type === 'info',
            }"
        >
            <!-- Icon status -->
            <div class="shrink-0 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <div class="w-6 h-6 rounded-full bg-[#2E7D32]/15 flex items-center justify-center text-[#2E7D32]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'danger'">
                    <div class="w-6 h-6 rounded-full bg-[#DC2626]/15 flex items-center justify-center text-[#DC2626]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'warning'">
                    <div class="w-6 h-6 rounded-full bg-[#D97706]/15 flex items-center justify-center text-[#B45309]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                </template>
                <template x-if="toast.type === 'info'">
                    <div class="w-6 h-6 rounded-full bg-[#8C6246]/15 flex items-center justify-center text-[#8C6246]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                        </svg>
                    </div>
                </template>
            </div>

            <!-- Content -->
            <div class="flex-1 text-sm font-medium leading-snug pt-0.5" x-text="toast.message"></div>

            <!-- Close button -->
            <button
                type="button"
                @click="$store.toasts.remove(toast.id)"
                class="shrink-0 p-1 text-[#7A685D] hover:text-[#1A0F0A] rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-[#8C6246]"
                aria-label="Tutup notifikasi"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
