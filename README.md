# Seka Yasa POS — Layanan Konsultasi & Sistem Kasir Custom

Sistem **Point of Sale (POS)** multi-tenant, Halaman Akun Klien & Konsultasi Custom (`/client`), Terminal Kasir (`/pos`), Dashboard Pemilik & Super Admin (`/dashboard`), serta Aplikasi Kasir Android (PWA WebAPK & Flutter).

## Struktur Repositori

- `app-pos/` — Aplikasi Backend Laravel 13 (REST API Sanctum, Multi-Tenant POS, Kasir Terminal, Dashboard Owner & Verifikasi Super Admin, Akun Klien `/client`, serta alur langganan siap integrasi Midtrans).
- `pos_mobile_app/` — Source code aplikasi mobile kasir berbasis Flutter.
- `PRD_Aplikasi_POS.md` — Spesifikasi produk POS SaaS v1.0.
- `PRD_Layanan_POS_Custom_dan_Deploy_One_Click.md` — Spesifikasi layanan konsultasi & custom POS v2.0.
- `DESIGN_SYSTEM.md` — Sistem Desain *Seka Yasa — Warm Javanese Modern Studio (Anti-AI Slop)*.
- `jalankan-aplikasi.bat` — Pintasan untuk menjalankan server lokal di Windows.

## Menjalankan Secara Lokal

1. Masuk ke folder `app-pos` dan salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi MySQL (`DB_DATABASE=pos_system`).
2. Jalankan instalasi & migrasi database:
   ```bash
   cd app-pos
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```
3. Jalankan server:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
4. Akses di browser: `http://localhost:8000`
5. Jalankan pengujian otomatis:
   ```bash
   php artisan test
   ```
