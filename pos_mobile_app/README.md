# POS Mobile App (Android APK) - POS Management System

Aplikasi Mobile Kasir berbasis **Flutter** untuk POS Management System (sesuai spesifikasi PRD Bab 3.2 & 9.2).

---

## 📱 Fitur Utama Kasir Mobile:
1. **Login Kasir Multi-Outlet** (autentikasi Laravel Sanctum REST API).
2. **Katalog Produk & Grid:** Pencarian instan dan filter kategori.
3. **Validasi Ketersediaan Stok:** Mencegah penjualan melebihi stok fisik outlet.
4. **Keranjang Belanja Interaktif:** Tambah/kurang kuantitas item, kalkulasi otomatis PPN 11%.
5. **Kalkulator Pembayaran Tunai & Kembalian:** Validasi jumlah uang tunai yang diterima kasir.
6. **Checkout Atomik:** Memanggil API `/pos/checkout` dengan *pessimistic row locking* di database backend.
7. **Pemberitahuan Struk:** Nomor faktur unik (`INV-OUTLET-Ymd-XXXX`) dan rincian transaksi.

---

## ⚙️ Konfigurasi Base URL API
Buka file `lib/services/api_service.dart`:
```dart
// Jika menggunakan HP Fisik Android (dalam satu jaringan Wi-Fi):
static String baseUrl = 'http://192.168.1.10:8000/api';

// Jika menggunakan Android Studio Emulator di PC:
static String baseUrl = 'http://10.0.2.2:8000/api';
```

---

## 🚀 Cara Menghasilkan File `.apk` (Compile APK):

### Langkah 1: Persiapan di Komputer yang Memiliki Flutter SDK
Pastikan Flutter SDK dan Android SDK sudah terinstal di komputer.
```bash
flutter doctor
```

### Langkah 2: Unduh Dependensi
```bash
cd "D:\Semester 8\AplikasiPOS\pos_mobile_app"
flutter pub get
```

### Langkah 3: Build APK Release
Jalankan perintah berikut di terminal:
```bash
flutter build apk --release
```

File `.apk` yang sudah siap diinstal ke HP Android akan otomatis dihasilkan di:
📂 **`pos_mobile_app/build/app/outputs/flutter-apk/app-release.apk`**

Salin file `app-release.apk` tersebut ke HP Android Anda lalu klik **Install**.
