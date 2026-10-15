/**
 * [FRONTEND] Tailwind CSS Configuration (F0.1).
 * SIMKOTOL Earthy Coffee Palette Tokens, Typography (Outfit & Inter), and Border Radius / Shadows.
 * Reference: ui_ux_design_system.md, dev_task_list.md (F0.1)
 *
 * @type {import('tailwindcss').Config}
 */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          primary: '#3C2317',       // Deep Coffee Espresso (Navbar/Sidebar/Primary Action Header)
          'primary-alt': '#3D241C', // Hover/Active state Sidebar
          secondary: '#8C6246',     // Warm Terracotta / Clay (Active states/Borders)
          accent: '#D4A373',        // Light Caramel (Hanya Latar/Badge - JANGAN dipakai sebagai Teks font!)
          canvas: '#F8F5F0',        // Cream White Canvas (Background Utama)
          card: '#FCFAF7',          // Soft Off-White (Card/Panel Container)
          dark: '#1A0F0A',          // Charcoal Coffee Text
          muted: '#7A685D',         // Warm Gray-Brown Text (WCAG AA ~4.8:1)
        },
        status: {
          success: '#2E7D32',       // Hijau Transaksi Sukses / Stok Aman
          warning: '#D97706',       // Amber Warning Stok Menipis (Latar Badge)
          'warning-text': '#B45309', // Amber Gelap Khusus Teks Tampil (WCAG AA Lolos)
          danger: '#DC2626',        // Merah Void / Stok Habis
        },
      },
      fontFamily: {
        sans: ['Outfit', 'Inter', 'sans-serif'],
      },
      borderRadius: {
        brand: '14px',             // Standard rounded-xl (12-16px) sesuai PRD v2.0
      },
      boxShadow: {
        soft: '0 4px 20px -2px rgba(60, 35, 23, 0.05)',
        'card-hover': '0 10px 25px -5px rgba(60, 35, 23, 0.1)',
      },
    },
  },
  plugins: [],
};
