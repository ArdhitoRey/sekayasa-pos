# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# Aplikasi Point of Sale (POS)

**Nama Produk:** POS Management System  
**Versi Dokumen:** 1.0  
**Status:** Draft – Initial Product Specification  
**Tanggal:** 20 November 2026  
**Platform:** Web Dashboard & Android Mobile Application  
**Model Bisnis:** SaaS (Software as a Service)

---

# 1. Product Overview

## 1.1. Deskripsi Produk

POS Management System merupakan aplikasi Point of Sale berbasis SaaS yang dirancang untuk membantu pemilik usaha mengelola kegiatan operasional bisnis, mulai dari transaksi penjualan, manajemen produk, inventaris, pengelolaan karyawan, hingga laporan penjualan.

Aplikasi memiliki dua platform utama:

1. **Mobile Application:** Digunakan oleh kasir untuk melakukan transaksi penjualan secara langsung.
2. **Web Dashboard:** Digunakan oleh owner dan admin untuk memantau aktivitas bisnis, mengelola produk, stok, karyawan, outlet, serta laporan keuangan.

Kedua platform terhubung melalui REST API dan menggunakan database terpusat sehingga data dapat diakses secara konsisten oleh pengguna yang memiliki hak akses.

## 1.2. Problem Statement

Banyak pemilik usaha masih menghadapi beberapa kendala dalam mengelola bisnis:

- Pencatatan transaksi masih dilakukan secara manual.
- Pemantauan stok barang tidak dilakukan secara real-time.
- Pemilik usaha kesulitan memantau penjualan harian.
- Kesalahan pencatatan transaksi dan perhitungan pembayaran.
- Tidak adanya laporan bisnis yang terstruktur.
- Sulit mengontrol aktivitas kasir dan karyawan.
- Data operasional tersebar di berbagai tempat.

## 1.3. Product Goals

Tujuan utama pengembangan aplikasi:

1. Mempermudah proses transaksi penjualan.
2. Memusatkan pengelolaan produk dan inventaris.
3. Menyediakan laporan bisnis secara otomatis.
4. Memudahkan owner memantau bisnis dari mana saja.
5. Meminimalkan kesalahan pencatatan transaksi.
6. Menyediakan sistem yang dapat dikembangkan untuk banyak outlet.
7. Menyediakan platform SaaS dengan sistem langganan bulanan.

# 2. Target Users & User Roles

Aplikasi memiliki beberapa jenis pengguna dengan tanggung jawab dan hak akses berbeda.

## 2.1. Super Admin

Super Admin merupakan pengelola utama platform SaaS.

Tanggung jawab:
- Mengelola seluruh akun bisnis.
- Mengelola paket langganan.
- Memantau status pembayaran subscription.
- Mengelola pengguna platform.
- Melihat statistik penggunaan platform.
- Mengelola konfigurasi aplikasi.
- Menangani pengaduan pelanggan.

## 2.2. Owner

Owner merupakan pemilik bisnis yang menggunakan aplikasi POS.

Tanggung jawab:
- Melihat dashboard bisnis.
- Mengelola outlet.
- Mengelola produk.
- Memantau stok.
- Mengelola karyawan.
- Melihat laporan penjualan.
- Melihat laporan keuntungan.
- Mengelola pengaturan bisnis.
- Mengelola langganan aplikasi.

## 2.3. Admin Toko

Admin bertanggung jawab terhadap operasional toko.

Hak akses:
- Mengelola produk dan kategori.
- Mengelola stok barang.
- Mengelola supplier.
- Memantau transaksi.
- Mengelola akun kasir.
- Melihat laporan sesuai izin yang diberikan.

## 2.4. Kasir

Kasir bertugas melakukan transaksi penjualan.

Hak akses:
- Login aplikasi POS.
- Membuka shift kasir.
- Melihat produk.
- Mencari produk.
- Memindai barcode.
- Menambahkan produk ke keranjang.
- Mengelola jumlah barang.
- Memberikan diskon sesuai izin.
- Memproses pembayaran.
- Mencetak struk.
- Melihat riwayat transaksi miliknya.

# 3. Platform & Technology Stack

## 3.1. Platform

| Platform | Pengguna | Fungsi |
|---|---|---|
| Web Dashboard | Owner | Monitoring bisnis |
| Web Dashboard | Admin | Operasional toko |
| Android App | Kasir | Transaksi penjualan |
| Web Super Admin | Platform Admin | Mengelola SaaS |

## 3.2. Technology Stack

| Komponen | Teknologi |
|---|---|
| Backend | Laravel |
| API | Laravel REST API |
| Web Frontend | Vue.js / React.js |
| Mobile | Flutter |
| Database | MySQL |
| Authentication | Laravel Sanctum |
| API Format | JSON |
| Hosting | VPS / Cloud Server |
| Version Control | Git |
| UI Design | Figma |

## 3.3. System Architecture

Sistem menggunakan arsitektur centralized backend.

Web Dashboard dan Android Application akan mengakses backend yang sama melalui REST API.

Seluruh data bisnis disimpan pada database terpusat dengan pemisahan data berdasarkan tenant atau business account.

Setiap bisnis hanya dapat mengakses data miliknya sendiri.

# 4. Functional Requirements

Bagian ini menjelaskan fitur yang harus tersedia dalam aplikasi.

## 4.1. Authentication & Authorization

**FR-001 — Registration**  
Sistem menyediakan fitur pendaftaran akun bisnis baru.

Informasi:
- Nama pemilik.
- Nama bisnis.
- Email.
- Nomor telepon.
- Password.

**FR-002 — Login**  
Pengguna dapat login menggunakan email dan password.

**FR-003 — Role-Based Access Control**  
Sistem membatasi akses fitur berdasarkan role pengguna.

**FR-004 — Password Management**  
Pengguna dapat mengubah password dan melakukan reset password.

**FR-005 — Session Management**  
Sistem menyediakan pengelolaan sesi login dan logout.

## 4.2. Business Management

**FR-006 — Business Profile**  
Owner dapat mengelola informasi bisnis.

Informasi:
- Nama bisnis.
- Logo.
- Alamat.
- Nomor telepon.
- Email.
- Format mata uang.
- Zona waktu.
- Pengaturan pajak.

**FR-007 — Outlet Management**  
Owner dapat membuat dan mengelola beberapa outlet.

Informasi outlet:
- Nama outlet.
- Alamat.
- Nomor telepon.
- Status outlet.
- Karyawan yang ditugaskan.

**FR-008 — Outlet Selection**  
Pengguna yang memiliki akses ke lebih dari satu outlet dapat memilih outlet aktif.

## 4.3. Product Management

**FR-009 — Product List**  
Admin dapat melihat seluruh daftar produk.

Data produk:
- Nama produk.
- SKU.
- Barcode.
- Kategori.
- Harga beli.
- Harga jual.
- Stok.
- Satuan.
- Foto produk.
- Status produk.

**FR-010 — Create Product**  
Admin dapat menambahkan produk baru.

**FR-011 — Edit Product**  
Admin dapat memperbarui informasi produk.

**FR-012 — Delete Product**  
Admin dapat mengarsipkan produk yang sudah tidak digunakan.

**FR-013 — Product Category**  
Admin dapat membuat, mengubah, dan menghapus kategori produk.

**FR-014 — Product Search**  
Pengguna dapat mencari produk berdasarkan nama, SKU, atau barcode.

**FR-015 — Product Import & Export**  
Admin dapat melakukan impor dan ekspor produk melalui CSV atau Excel.

## 4.4. Inventory Management

**FR-016 — Stock Monitoring**  
Admin dapat melihat jumlah stok setiap produk.

**FR-017 — Stock Adjustment**  
Admin dapat melakukan penyesuaian stok.

Setiap perubahan stok harus mencatat:
- Produk.
- Stok sebelum perubahan.
- Jumlah perubahan.
- Stok setelah perubahan.
- Alasan perubahan.
- Pengguna yang melakukan perubahan.
- Waktu perubahan.

**FR-018 — Stock Movement**  
Sistem mencatat semua pergerakan stok.

Jenis pergerakan:
- Penambahan stok.
- Pengurangan stok.
- Penjualan.
- Retur pembelian.
- Retur penjualan.
- Transfer antar-outlet.
- Penyesuaian stok.

**FR-019 — Low Stock Notification**  
Sistem memberikan notifikasi ketika stok produk mencapai batas minimum.

**FR-020 — Stock Transfer**  
Owner atau admin yang memiliki izin dapat memindahkan stok antar-outlet.

## 4.5. Point of Sale / Cashier

Fitur ini merupakan bagian utama aplikasi mobile.

**FR-021 — Cashier Interface**  
Kasir memiliki halaman transaksi yang mudah digunakan.

Komponen:
- Pencarian produk.
- Daftar kategori.
- Daftar produk.
- Keranjang belanja.
- Jumlah barang.
- Total transaksi.
- Tombol pembayaran.

**FR-022 — Add Product to Cart**  
Kasir dapat menambahkan produk ke keranjang.

**FR-023 — Update Cart**  
Kasir dapat mengubah jumlah produk dan menghapus produk dari keranjang.

**FR-024 — Barcode Scanner**  
Kasir dapat memindai barcode produk menggunakan kamera atau barcode scanner eksternal yang kompatibel.

**FR-025 — Discount Management**  
Kasir dapat memberikan diskon berdasarkan izin yang diberikan.

Tipe diskon:
- Diskon nominal.
- Diskon persentase.
- Diskon per produk.
- Diskon keseluruhan transaksi.

**FR-026 — Tax Calculation**  
Sistem dapat menghitung pajak sesuai konfigurasi bisnis.

**FR-027 — Hold Transaction**  
Kasir dapat menahan transaksi sementara dan melanjutkannya kembali.

**FR-028 — Transaction Calculation**  
Sistem secara otomatis menghitung:
- Subtotal.
- Diskon.
- Pajak.
- Total pembayaran.
- Uang diterima.
- Kembalian.

**FR-029 — Payment Processing**  
Sistem mendukung metode pembayaran:
- Cash.
- Transfer bank.
- QRIS (pencatatan manual pada MVP).
- E-wallet (pencatatan manual pada MVP).

Transaksi dengan beberapa metode pembayaran dapat dikembangkan pada fase berikutnya.

**FR-030 — Transaction Completion**  
Transaksi dianggap selesai setelah pembayaran berhasil dicatat.

Sistem harus menyimpan transaksi dan mengurangi stok secara konsisten.

**FR-031 — Receipt Printing**  
Kasir dapat mencetak struk melalui printer thermal yang kompatibel.

**FR-032 — Transaction History**  
Kasir dapat melihat riwayat transaksi.

**FR-033 — Transaction Detail**  
Pengguna yang memiliki izin dapat melihat rincian transaksi.

**FR-034 — Refund & Void Transaction**  
Admin atau owner dapat membatalkan transaksi maupun memproses retur berdasarkan ketentuan dan otorisasi.

Setiap pembatalan dan retur harus memiliki alasan serta catatan audit.

## 4.6. Cashier Shift Management

**FR-035 — Open Cashier Shift**  
Kasir harus membuka shift sebelum melakukan transaksi.

Informasi:
- Kasir.
- Outlet.
- Waktu mulai.
- Modal awal.

**FR-036 — Close Cashier Shift**  
Kasir dapat menutup shift.

Informasi:
- Total transaksi.
- Total penjualan tunai.
- Total penjualan non-tunai.
- Kas aktual.
- Selisih kas.
- Waktu tutup.

**FR-037 — Shift History**  
Owner dapat melihat riwayat shift setiap kasir.

## 4.7. Customer Management

**FR-038 — Customer List**  
Admin dapat mengelola daftar pelanggan.

Data pelanggan:
- Nama.
- Nomor telepon.
- Email.
- Alamat.
- Catatan.

**FR-039 — Customer Transaction History**  
Sistem menyimpan riwayat pembelian pelanggan.

**FR-040 — Customer Selection**  
Kasir dapat memilih pelanggan ketika melakukan transaksi.

Fitur ini opsional dalam transaksi biasa.

## 4.8. Supplier & Purchasing

**FR-041 — Supplier Management**  
Admin dapat mengelola data supplier.

**FR-042 — Purchase Order**  
Admin dapat membuat pesanan pembelian kepada supplier.

**FR-043 — Purchase Receiving**  
Admin dapat mencatat barang yang diterima dari supplier.

**FR-044 — Purchase History**  
Sistem menyimpan riwayat pembelian dan penerimaan barang.

Stok bertambah setelah penerimaan barang dikonfirmasi.

## 4.9. Dashboard & Analytics

**FR-045 — Business Dashboard**  
Owner dapat melihat ringkasan bisnis.

Informasi:
- Total penjualan hari ini.
- Total transaksi.
- Rata-rata nilai transaksi.
- Produk terlaris.
- Stok menipis.
- Grafik penjualan.
- Ringkasan metode pembayaran.

**FR-046 — Sales Report**  
Owner dapat melihat laporan penjualan berdasarkan:
- Hari.
- Minggu.
- Bulan.
- Rentang tanggal.
- Outlet.
- Kasir.

**FR-047 — Product Report**  
Owner dapat melihat produk paling laku dan produk dengan penjualan terendah.

**FR-048 — Profit Report**  
Sistem menampilkan estimasi laba kotor berdasarkan harga jual dan harga pokok produk.

**FR-049 — Payment Report**  
Owner dapat melihat ringkasan transaksi berdasarkan metode pembayaran.

**FR-050 — Export Report**  
Laporan dapat diunduh dalam format Excel atau PDF.

## 4.10. Employee Management

**FR-051 — Employee List**  
Owner dapat mengelola daftar karyawan.

**FR-052 — Employee Account**  
Owner dapat membuat akun pengguna.

**FR-053 — Role Assignment**  
Owner dapat menetapkan role untuk setiap karyawan.

**FR-054 — Permission Management**  
Owner dapat mengatur izin akses setiap role.

**FR-055 — Activity Log**  
Sistem mencatat aktivitas penting pengguna.

Contoh:
- Login.
- Penambahan produk.
- Perubahan harga.
- Penyesuaian stok.
- Pembatalan transaksi.
- Perubahan konfigurasi bisnis.

## 4.11. Subscription Management

Fitur ini digunakan untuk menjalankan model bisnis SaaS.

**FR-056 — Subscription Plans**  
Platform menyediakan beberapa paket langganan.

Contoh:
- Starter.
- Business.
- Pro.

**FR-057 — Subscription Status**  
Sistem menyimpan status langganan:
- Trial.
- Active.
- Past Due.
- Expired.
- Suspended.
- Cancelled.

**FR-058 — Subscription Billing**  
Owner dapat melihat informasi tagihan dan masa berlaku langganan.

**FR-059 — Plan Upgrade & Downgrade**  
Owner dapat melakukan perubahan paket langganan sesuai ketentuan.

**FR-060 — Feature Limitation**  
Sistem membatasi fitur berdasarkan paket langganan.

Contoh:
- Jumlah outlet.
- Jumlah akun karyawan.
- Jumlah produk.
- Fitur laporan.
- Fitur multi-outlet.

**FR-061 — Payment History**  
Owner dapat melihat riwayat pembayaran langganan.

Pada MVP, pembayaran subscription dapat dilakukan melalui pencatatan pembayaran manual oleh admin platform. Integrasi payment gateway dikembangkan pada fase berikutnya.

# 5. User Flow

## 5.1. Business Registration Flow

1. Pengguna membuka website.
2. Pengguna memilih menu registrasi.
3. Pengguna mengisi informasi bisnis.
4. Pengguna memasukkan email dan password.
5. Sistem melakukan validasi data.
6. Sistem membuat akun bisnis.
7. Sistem membuat outlet pertama.
8. Pengguna diarahkan ke dashboard.
9. Pengguna dapat mulai mengatur produk dan karyawan.

## 5.2. Cashier Transaction Flow

1. Kasir membuka aplikasi Android.
2. Kasir melakukan login.
3. Kasir memilih outlet jika diperlukan.
4. Kasir membuka shift.
5. Kasir memilih produk atau memindai barcode.
6. Produk masuk ke keranjang.
7. Kasir memeriksa jumlah dan total transaksi.
8. Kasir memilih metode pembayaran.
9. Kasir memasukkan jumlah uang yang diterima jika menggunakan pembayaran tunai.
10. Sistem memvalidasi pembayaran.
11. Sistem menyimpan transaksi.
12. Sistem mengurangi stok produk.
13. Sistem menampilkan transaksi berhasil.
14. Kasir mencetak atau membagikan struk.

## 5.3. Owner Monitoring Flow

1. Owner membuka website.
2. Owner melakukan login.
3. Owner masuk ke dashboard.
4. Sistem menampilkan ringkasan penjualan.
5. Owner memilih rentang tanggal.
6. Sistem mengambil data penjualan.
7. Owner melihat grafik dan laporan transaksi.
8. Owner dapat mengunduh laporan.

## 5.4. Subscription Flow

1. Owner mendaftar akun.
2. Owner memilih paket langganan.
3. Sistem mencatat subscription.
4. Owner melakukan pembayaran sesuai metode yang tersedia.
5. Pembayaran diverifikasi.
6. Status subscription berubah menjadi Active.
7. Sistem memberikan akses sesuai paket yang dipilih.
8. Ketika masa langganan berakhir, sistem memberikan notifikasi perpanjangan.

# 6. Non-Functional Requirements

## 6.1. Performance

- Halaman dashboard harus dapat dimuat secara wajar pada koneksi internet normal.
- Proses transaksi harus memberikan respons secepat mungkin.
- Query database harus dioptimalkan.
- Sistem harus mendukung pertumbuhan jumlah transaksi dan pengguna.

## 6.2. Security

- Password harus disimpan menggunakan hashing yang aman.
- API harus menggunakan autentikasi.
- Hak akses harus divalidasi pada backend.
- Setiap bisnis hanya boleh mengakses data miliknya sendiri.
- Semua endpoint sensitif harus dilindungi.
- Input harus divalidasi untuk mencegah data tidak valid.
- Aktivitas penting harus memiliki audit log.
- Koneksi menggunakan HTTPS.

## 6.3. Reliability

- Sistem harus menjaga konsistensi data transaksi dan stok.
- Kegagalan pembayaran tidak boleh menghasilkan transaksi yang dianggap berhasil.
- Backup database dilakukan secara rutin.
- Sistem harus memiliki mekanisme pemulihan data.

## 6.4. Scalability

Sistem harus dapat dikembangkan untuk:
- Banyak bisnis atau tenant.
- Banyak outlet.
- Banyak kasir.
- Transaksi dalam jumlah besar.
- Penambahan fitur di masa mendatang.

## 6.5. Usability

- Antarmuka kasir harus sederhana dan cepat dipahami.
- Dashboard harus mudah dipahami oleh owner.
- Navigasi konsisten antarhalaman.
- Aplikasi mobile harus responsif terhadap berbagai ukuran layar.
- Proses transaksi harus meminimalkan jumlah langkah.

## 6.6. Synchronization

Web dan mobile harus menampilkan data yang konsisten.

Perubahan data pada backend harus tercermin pada platform lain ketika data berhasil diperbarui.

Untuk MVP, aplikasi membutuhkan koneksi internet aktif saat menyelesaikan transaksi.

Dukungan transaksi offline dan sinkronisasi otomatis akan menjadi pengembangan lanjutan.

# 7. Database Overview

Berikut gambaran entitas utama yang dibutuhkan.

| Tabel | Fungsi |
|---|---|
| businesses | Data bisnis atau tenant |
| users | Data akun pengguna |
| roles | Data role |
| permissions | Hak akses |
| business_users | Relasi pengguna dengan bisnis |
| outlets | Data outlet |
| products | Data produk |
| categories | Kategori produk |
| units | Satuan produk |
| inventory | Jumlah stok produk per outlet |
| stock_movements | Riwayat pergerakan stok |
| customers | Data pelanggan |
| suppliers | Data supplier |
| transactions | Data transaksi |
| transaction_items | Detail item transaksi |
| payments | Data pembayaran |
| refunds | Data retur |
| cashier_shifts | Data shift kasir |
| purchases | Data pembelian |
| purchase_items | Detail pembelian |
| subscriptions | Data langganan |
| plans | Paket langganan |
| subscription_payments | Riwayat pembayaran langganan |
| activity_logs | Catatan aktivitas pengguna |

Catatan: daftar ini merupakan rancangan awal. Struktur database final perlu dibuat berdasarkan ERD dan aturan bisnis yang sudah divalidasi.

# 8. Business Rules

## 8.1. Transaction Rules

1. Setiap transaksi memiliki nomor transaksi unik.
2. Setiap transaksi terhubung dengan bisnis, outlet, dan kasir.
3. Transaksi yang sudah selesai tidak boleh diubah secara langsung.
4. Pembatalan transaksi harus memiliki alasan.
5. Pengembalian barang harus tercatat sebagai transaksi retur.
6. Perubahan stok harus tercatat dalam stock movements.

## 8.2. Inventory Rules

1. Stok dikelola berdasarkan produk dan outlet.
2. Penjualan akan mengurangi stok secara otomatis.
3. Pembelian yang sudah diterima menambah stok.
4. Penyesuaian stok harus memiliki alasan.
5. Sistem harus mencegah pengurangan stok yang tidak valid berdasarkan konfigurasi bisnis.
6. Transfer antar-outlet harus mencatat stok keluar dan stok masuk.

## 8.3. Multi-Tenant Rules

1. Setiap bisnis memiliki identitas tenant tersendiri.
2. Setiap data bisnis memiliki relasi terhadap business_id.
3. Pengguna tidak boleh mengakses data tenant lain.
4. Akses outlet dibatasi berdasarkan penugasan dan izin pengguna.
5. Super Admin memiliki akses administratif sesuai kewenangannya.

## 8.4. Subscription Rules

1. Setiap bisnis memiliki status langganan.
2. Fitur yang dapat digunakan bergantung pada paket.
3. Sistem harus memeriksa batas jumlah outlet dan pengguna.
4. Pemilik bisnis mendapat notifikasi sebelum langganan berakhir.
5. Jika langganan berakhir, akses fitur dapat dibatasi sesuai kebijakan platform.
6. Data bisnis tidak langsung dihapus ketika subscription berakhir.

# 9. UI/UX Requirements

## 9.1. Web Dashboard

Menu navigasi utama:

1. Dashboard
2. Outlet
3. Produk
4. Kategori
5. Inventaris
6. Transaksi
7. Pelanggan
8. Supplier
9. Pembelian
10. Karyawan
11. Laporan
12. Pengaturan
13. Subscription

Karakteristik desain:
- Modern dan minimalis.
- Navigasi sidebar.
- Dashboard menggunakan visualisasi grafik.
- Tabel memiliki fitur pencarian, filter, dan pagination.
- Mendukung desktop dan tablet.
- Memiliki desain responsif.

## 9.2. Android Application

Menu utama:

1. Login
2. Pilih Outlet
3. Buka Shift
4. Kasir / Penjualan
5. Riwayat Transaksi
6. Detail Transaksi
7. Tutup Shift
8. Profil Pengguna

Karakteristik desain:
- Fokus pada kecepatan transaksi.
- Tombol utama mudah dijangkau.
- Produk ditampilkan menggunakan grid.
- Keranjang belanja mudah diakses.
- Mendukung scanner barcode.
- Mendukung printer thermal yang kompatibel.
- Memiliki konfirmasi sebelum tindakan penting.

# 10. MVP Scope

Agar proses pengembangan tidak terlalu kompleks, fitur dibagi menjadi beberapa fase.

## Phase 1 — MVP

Fokus utama adalah membuat aplikasi POS yang dapat digunakan untuk transaksi dasar.

Fitur yang wajib tersedia:
- Registrasi bisnis.
- Login dan autentikasi.
- Role pengguna.
- Manajemen outlet dasar.
- Manajemen produk.
- Manajemen kategori.
- Manajemen stok.
- Aplikasi Android kasir.
- Pencarian produk.
- Barcode scanner.
- Keranjang belanja.
- Diskon sederhana.
- Perhitungan total transaksi.
- Pembayaran tunai dan metode non-tunai manual.
- Penyimpanan transaksi.
- Pengurangan stok otomatis.
- Cetak struk.
- Riwayat transaksi.
- Dashboard owner.
- Laporan penjualan sederhana.
- Manajemen karyawan dasar.

## Phase 2 — Business Management

Pengembangan fitur manajemen bisnis:
- Multi-outlet.
- Transfer stok.
- Customer management.
- Supplier management.
- Purchase order.
- Penerimaan barang.
- Laporan keuntungan.
- Laporan produk.
- Refund dan retur lanjutan.
- Cashier shift management yang lebih lengkap.
- Export laporan.

## Phase 3 — SaaS & Advanced Features

Pengembangan fitur komersial dan lanjutan:
- Subscription management.
- Payment gateway.
- Paket berlangganan.
- Multi-tenant management lanjutan.
- Offline transaction.
- Automatic data synchronization.
- Push notification.
- Integrasi QRIS.
- Integrasi pembayaran otomatis.
- Integrasi akuntansi.
- Loyalty program.
- Multi-platform mobile.

# 11. Acceptance Criteria

Fitur dinyatakan selesai apabila memenuhi kriteria berikut.

| Fitur | Acceptance Criteria |
|---|---|
| Login | Pengguna dapat login dengan akun valid |
| Registration | Bisnis baru berhasil dibuat |
| Product Management | Produk dapat dibuat, diperbarui, dan diarsipkan |
| Inventory | Perubahan stok tercatat dengan benar |
| Cashier | Kasir dapat membuat transaksi |
| Barcode | Produk dapat ditemukan melalui barcode |
| Payment | Sistem dapat menghitung pembayaran dan kembalian |
| Transaction | Transaksi tersimpan dengan nomor unik |
| Stock Reduction | Stok berkurang setelah transaksi berhasil |
| Receipt | Struk dapat dicetak pada perangkat kompatibel |
| Dashboard | Data penjualan sesuai transaksi tersimpan |
| Reports | Laporan dapat difilter berdasarkan rentang tanggal |
| Authorization | Pengguna tidak dapat mengakses fitur di luar izin |
| Multi-Tenant | Data satu bisnis tidak dapat dilihat bisnis lain |

# 12. Product Success Metrics

Keberhasilan produk dapat diukur melalui beberapa metrik.

## Business Metrics

- Jumlah bisnis yang mendaftar.
- Jumlah bisnis berlangganan.
- Monthly Recurring Revenue (MRR).
- Customer Churn Rate.
- Conversion Rate dari trial ke paid subscription.
- Jumlah outlet aktif.

## Product Metrics

- Jumlah transaksi harian.
- Jumlah pengguna aktif.
- Tingkat keberhasilan transaksi.
- Rata-rata waktu penyelesaian transaksi.
- Jumlah error transaksi.
- Tingkat penggunaan fitur.

## Customer Metrics

- Tingkat kepuasan pelanggan.
- Jumlah tiket support.
- Tingkat perpanjangan subscription.
- Tingkat retensi pengguna.

# 13. Risks & Constraints

## Technical Risks

**1. Koneksi Internet**  
Transaksi bergantung pada koneksi internet pada MVP.

Mitigasi: gunakan API yang efisien dan rencanakan fitur offline pada fase selanjutnya.

**2. Data Synchronization**  
Ketidaksesuaian data antara mobile dan website dapat mengakibatkan laporan tidak akurat.

Mitigasi: backend menjadi sumber data utama dan proses perubahan stok serta transaksi harus menggunakan database transaction.

**3. Concurrent Transactions**  
Dua kasir dapat menjual produk yang sama pada waktu bersamaan.

Mitigasi: implementasikan database locking atau atomic stock updates serta validasi ketersediaan stok.

**4. Data Security**  
Sistem multi-tenant memiliki risiko kebocoran data antarbisnis.

Mitigasi: implementasikan tenant isolation, authorization middleware, dan pengujian keamanan.

**5. Hardware Compatibility**  
Tidak semua printer thermal dan barcode scanner memiliki kompatibilitas yang sama.

Mitigasi: lakukan pengujian terhadap perangkat yang didukung.

## Business Risks

- Persaingan dengan penyedia POS lain.
- Biaya server meningkat.
- Tingkat penggunaan aplikasi rendah.
- Pelanggan berhenti berlangganan.
- Biaya customer support meningkat.

# 14. Out of Scope

Fitur berikut belum menjadi bagian dari pengembangan MVP:

- Aplikasi iOS.
- Sistem akuntansi lengkap.
- Payroll karyawan.
- Manajemen pajak kompleks.
- Integrasi marketplace.
- Program loyalty tingkat lanjut.
- Manajemen franchise kompleks.
- Pembayaran otomatis melalui berbagai payment gateway.
- Transaksi offline penuh.

Fitur-fitur tersebut dapat dipertimbangkan berdasarkan kebutuhan pengguna dan prioritas pengembangan.

# 15. Final Product Vision

POS Management System diharapkan menjadi platform manajemen bisnis yang membantu pemilik usaha menjalankan operasional toko secara lebih efisien.

Platform ini tidak hanya berfungsi sebagai aplikasi kasir, tetapi juga sebagai sistem terintegrasi untuk mengelola transaksi, produk, inventaris, karyawan, outlet, dan performa bisnis.

Dengan menggabungkan aplikasi mobile dan web dashboard dalam satu ekosistem, pemilik usaha dapat mengontrol kegiatan bisnis dari mana saja, sementara kasir dapat melakukan transaksi secara cepat melalui perangkat mobile.

**Core Product Principle:**

> One Business Platform, Multiple Devices, Centralized Management.

---

**End of Document**  
Product Requirements Document — POS Management System | Version 1.0
