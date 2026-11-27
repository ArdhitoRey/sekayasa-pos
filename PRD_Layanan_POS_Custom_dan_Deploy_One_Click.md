# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Layanan Konsultasi, Pengembangan POS Custom, dan Deployment One-Click

**Nama inisiatif:** POS Custom Development Service  
**Versi:** 2.0  
**Tanggal:** 21 November 2026  
**Status:** Draft untuk validasi bisnis dan teknis  
**Model layanan:** Konsultasi + pengembangan software custom + kontrak layanan bulanan  
**Platform yang dikembangkan:** POS web/mobile sesuai kebutuhan masing-masing klien, ditambah portal internal untuk mengelola proyek dan deployment.

> **Keputusan produk utama:** Sistem POS tidak dijual sebagai template siap pakai. Setiap proyek dimulai dari konsultasi dan pemetaan proses bisnis klien. Arsitektur dan fitur kemudian dirancang serta dikembangkan berdasarkan kebutuhan yang disetujui klien.

---

## 1. Ringkasan Eksekutif

Usaha ini menyediakan jasa konsultasi dan pengembangan aplikasi Point of Sale (POS) secara khusus untuk setiap klien. Proses dimulai dari konsultasi, analisis proses bisnis, pemetaan kebutuhan, penyusunan proposal, penetapan ruang lingkup, dan persetujuan klien. Setelah ruang lingkup disetujui, klien menandatangani perjanjian pengembangan dan melakukan pembayaran biaya pengembangan secara lunas sebelum pekerjaan development dimulai.

Setelah aplikasi selesai, klien melakukan User Acceptance Testing (UAT). Jika hasilnya disetujui, aplikasi dipasang ke lingkungan produksi. Sejak layanan produksi aktif, klien menggunakan kontrak layanan bulanan untuk hosting, pemeliharaan, backup, pemantauan, dan dukungan teknis sesuai paket yang disepakati.

Di sisi penyedia layanan, diperlukan portal internal untuk mengelola lead, konsultasi, dokumen kebutuhan, proposal, kontrak, pembayaran, proyek, UAT, dan deployment. Portal ini harus memiliki tombol **Deploy to Staging** dan **Deploy to Production** agar deployment dapat dijalankan melalui satu tindakan setelah seluruh pemeriksaan lolos.

### 1.1 Prinsip produk

1. **Custom per klien:** alur kerja dan fitur mengikuti proses bisnis yang telah dianalisis, bukan memaksa klien mengikuti template generik.
2. **Scope tertulis:** kebutuhan, batasan, harga, jadwal, dan kriteria penerimaan disepakati sebelum development.
3. **Pembayaran development lunas di muka:** development tidak dimulai sebelum pembayaran penuh diverifikasi, kecuali ada pengecualian tertulis yang disetujui pemilik usaha.
4. **Layanan bulanan terpisah:** hosting, maintenance, backup, monitoring, dan support memiliki kontrak serta tagihan bulanan tersendiri.
5. **Deploy dengan pengaman:** tombol deploy tidak boleh melewati pemeriksaan pembayaran, kontrak, UAT, backup, dan konfigurasi produksi.
6. **Data klien terisolasi:** aplikasi dan data setiap klien dipisahkan secara teknis dan administratif.
7. **Gratis hanya untuk demo/staging:** hosting gratis dapat digunakan untuk demo atau pengujian terbatas, bukan untuk operasional POS produksi yang menyimpan transaksi dan data bisnis.

## 2. Tujuan Bisnis dan Produk

### 2.1 Tujuan bisnis

- Menawarkan solusi POS yang sesuai dengan proses kerja aktual setiap perusahaan.
- Mendapatkan pendapatan awal dari biaya konsultasi (jika diterapkan) dan biaya pengembangan satu kali.
- Mendapatkan pendapatan berulang melalui kontrak layanan bulanan.
- Mengurangi risiko proyek melebar melalui dokumen scope dan mekanisme change request.
- Mengurangi pekerjaan deployment manual melalui pipeline deployment yang terstandarisasi.
- Menjaga hubungan jangka panjang dengan klien melalui maintenance dan dukungan.

### 2.2 Tujuan produk

- Menyediakan alur kerja proyek dari calon klien sampai layanan aktif.
- Mengelola kebutuhan bisnis dan persetujuan klien dalam satu tempat.
- Menghasilkan dokumen proposal, estimasi biaya, kontrak, invoice, UAT, dan berita acara serah terima.
- Menyediakan project tracking untuk tim internal dan portal status untuk klien.
- Menyediakan deployment satu tombol yang aman dan dapat diaudit.
- Menyediakan monitoring status hosting, backup, versi aplikasi, dan masa kontrak bulanan.

### 2.3 Bukan tujuan (out of scope awal)

- Menjual satu aplikasi POS standar yang sama untuk semua pelanggan.
- Menjamin hosting produksi gratis tanpa batas.
- Mengotomatisasi tanda tangan elektronik yang sah tanpa penyedia e-signature yang dipilih dan dikonfigurasi.
- Menyediakan payment gateway tanpa akun merchant dan integrasi resmi.
- Menjamin integrasi semua printer, scanner, marketplace, atau perangkat sebelum pengujian kompatibilitas.
- Menyediakan akuntansi, payroll, CRM, ERP, atau modul lain secara otomatis di luar scope yang disetujui klien.

## 3. Model Layanan dan Kontrak

Layanan memiliki dua komponen komersial yang harus dibedakan.

| Komponen | Jenis biaya | Waktu pembayaran | Cakupan |
|---|---|---|---|       
| Konsultasi awal | Gratis atau berbayar sesuai kebijakan usaha | Sebelum/saat konsultasi bila berbayar | Diskusi awal, pemahaman masalah, dan penentuan langkah discovery |
| Pengembangan POS custom | Biaya satu kali (one-time) | Lunas setelah proposal dan scope disetujui, sebelum development | Analisis rinci, desain, coding, testing, UAT, dokumentasi, dan go-live sesuai scope |
| Layanan bulanan | Berulang setiap bulan | Mulai saat layanan produksi aktif sesuai tanggal kontrak | Hosting, monitoring, backup, maintenance, support, dan SLA yang disepakati |
| Pekerjaan tambahan | Change request / quotation tambahan | Sesuai persetujuan perubahan | Fitur baru, perubahan proses, integrasi tambahan, atau pekerjaan di luar scope |
| Biaya pihak ketiga | Sesuai pemakaian/vendor | Sesuai tagihan pihak ketiga | Domain, hosting/VPS, email, SMS/WhatsApp, payment gateway, lisensi, dan perangkat |

### 3.1 Alur kontrak

1. Penyedia mengirim proposal dan dokumen ruang lingkup kerja (Statement of Work/SOW).
2. Klien meninjau fitur, batasan, jadwal, biaya, asumsi, dan kriteria penerimaan.
3. Setelah sepakat, kedua pihak menandatangani Perjanjian Pengembangan.
4. Klien membayar 100% biaya pengembangan yang disetujui.
5. Penyedia memverifikasi pembayaran dan mengubah proyek menjadi **Ready for Development**.
6. Setelah pengembangan selesai dan UAT disetujui, kedua pihak menandatangani Berita Acara Serah Terima (BAST) atau persetujuan go-live.
7. Perjanjian Layanan Bulanan ditandatangani sebelum atau saat aktivasi produksi. Masa layanan dan tagihan bulanan dimulai pada tanggal efektif yang tercantum dalam kontrak.

**Catatan:** Perjanjian pengembangan dan perjanjian layanan bulanan dapat menjadi dua dokumen terpisah atau satu perjanjian dengan lampiran berbeda. Bentuk final perlu diperiksa oleh penasihat hukum sesuai yurisdiksi dan model bisnis penyedia.

### 3.2 Ketentuan komersial yang harus ditentukan sebelum penjualan

- Apakah konsultasi awal gratis, dibatasi durasi, atau berbayar.
- Apakah pembayaran 100% dilakukan sebelum kickoff development atau setelah workshop discovery final.
- Apakah terdapat garansi perbaikan bug setelah go-live dan berapa lama.
- Apa saja yang termasuk paket bulanan dan apa yang dihitung terpisah.
- Siapa pemilik source code, desain, dokumentasi, dan komponen reusable.
- Siapa pemilik data bisnis klien dan bagaimana data diekspor saat kontrak berakhir.
- Ketentuan pembatalan, refund, keterlambatan pembayaran, suspend, dan terminasi.
- Batas layanan support, jam operasional, target response time, dan maintenance window.

## 4. Target Pengguna dan Peran

| Peran | Tanggung jawab | Akses utama |
|---|---|---|
| Calon klien | Menjelaskan kebutuhan dan mengikuti konsultasi | Form inquiry, booking konsultasi |
| PIC klien | Mengkoordinasikan keputusan bisnis dan teknis | Portal klien, dokumen scope, approval, UAT |
| Owner/Business Development | Mengelola calon klien, konsultasi, proposal, dan negosiasi | Portal internal seluruh pipeline |
| Business Analyst | Memetakan proses bisnis dan requirement | Discovery, process map, requirement register |
| Project Manager | Mengatur timeline, task, risiko, dan komunikasi | Project dashboard |
| UI/UX Designer | Merancang user flow dan antarmuka sesuai proses klien | Design review dan approval |
| Developer | Membangun fitur sesuai scope yang disetujui | Repository dan task proyek |
| QA/Tester | Menguji kualitas dan menyiapkan UAT | Test cases, bug tracking, release checklist |
| DevOps/Deployment Admin | Mengatur server, environment, backup, dan deployment | Deployment console dan secret management |
| Finance | Memverifikasi pembayaran dan tagihan berulang | Invoice, payment record, subscription status |
| Support/Maintenance | Menangani tiket dan pemeliharaan setelah go-live | Support portal, monitoring, incident log |

## 5. Alur Utama Layanan (End-to-End)

1. Calon klien menghubungi penyedia dan mengisi informasi awal.
2. Penyedia menjadwalkan konsultasi awal.
3. Business Analyst melakukan discovery dan memetakan proses bisnis.
4. Penyedia menyiapkan dokumen kebutuhan, user flow, estimasi teknis, proposal, harga, jadwal, dan SOW.
5. Klien meninjau proposal. Jika ada revisi, dokumen diperbarui dan dikirim kembali.
6. Klien menyetujui scope dan kedua pihak menandatangani Perjanjian Pengembangan.
7. Klien membayar 100% biaya pengembangan; Finance memverifikasi pembayaran.
8. Setelah pembayaran terverifikasi, development custom dimulai.
9. Tim melakukan demo berkala dan QA internal.
10. Klien menjalankan UAT berdasarkan test case yang disepakati.
11. Jika UAT belum lulus, penyedia memperbaiki defect dalam scope dan klien melakukan UAT ulang. Permintaan di luar scope masuk ke Change Request.
12. Jika UAT lulus, klien memberikan sign-off dan kedua pihak menyelesaikan BAST serta Perjanjian Layanan Bulanan.
13. Tim melakukan pre-deployment checks, backup, dan deployment ke production.
14. Sistem menjalankan health check dan smoke test. Jika gagal, deployment dihentikan dan prosedur rollback dijalankan bila aman.
15. Setelah pemeriksaan sukses, POS dinyatakan aktif dan layanan bulanan berjalan sesuai tanggal efektif kontrak.
16. Penyedia menjalankan monitoring, backup, maintenance, support, renewal, atau proses termination sesuai kontrak.

### 5.1 Status pipeline klien/proyek

| Status | Arti | Syarat berpindah |
|---|---|---|
| New Lead | Calon klien baru | Form inquiry/lead masuk |
| Consultation Scheduled | Konsultasi dijadwalkan | Jadwal dan PIC tercatat |
| Consultation Completed | Konsultasi selesai | Catatan konsultasi disimpan |
| Discovery | Analisis proses bisnis berjalan | Klien menyediakan informasi yang diperlukan |
| Process Mapped | Proses bisnis dipetakan | Klien memeriksa process map |
| Proposal Draft | Proposal disiapkan | Requirement cukup jelas untuk estimasi |
| Proposal Sent | Proposal dikirim | Dokumen dan tanggal pengiriman tercatat |
| Revision Requested | Klien meminta perubahan | Daftar revisi tercatat |
| Scope Approved | Scope dan harga disetujui | Approval tertulis tersimpan |
| Contract Pending | Menunggu tanda tangan | Dokumen kontrak dikirim |
| Payment Pending | Menunggu pembayaran lunas | Invoice diterbitkan |
| Ready for Development | Siap dikerjakan | Kontrak signed dan pembayaran 100% verified |
| In Development | Pengembangan berlangsung | Project kickoff dilakukan |
| Internal QA | Pengujian internal | Build siap diuji |
| Client UAT | Pengujian oleh klien | Build UAT dan test cases tersedia |
| Change Request | Ada permintaan di luar scope | Dampak biaya/waktu dinilai dan disetujui |
| UAT Approved | Klien menyetujui hasil UAT | UAT sign-off tersimpan |
| Ready to Deploy | Menunggu deployment | BAST, kontrak bulanan, dan environment siap |
| Deploying | Pipeline berjalan | Deployment job dimulai |
| Live | Aplikasi produksi aktif | Health check dan smoke test lulus |
| Monthly Service Active | Layanan bulanan berjalan | Invoice/kontrak layanan aktif |
| Suspended | Layanan ditangguhkan | Kondisi suspend sesuai kontrak |
| Terminated | Layanan berakhir | Exit checklist dan ekspor data selesai |

## 6. Discovery dan Analisis Proses Bisnis Klien

Bagian ini menentukan bahwa produk adalah custom, bukan template.

### 6.1 Data yang dikumpulkan saat discovery

**Profil usaha**
- Nama badan usaha/brand dan bidang usaha.
- Jumlah outlet/cabang dan rencana ekspansi.
- Jumlah pengguna, role, dan struktur otorisasi.
- Jam operasional dan zona waktu.

**Proses penjualan**
- Jenis produk/jasa dan variasi.
- Alur kasir, order, keranjang, diskon, pajak, pembayaran, refund, dan struk.
- Metode pembayaran dan kebutuhan integrasi payment gateway.
- Proses transaksi ketika internet putus.

**Inventaris dan pengadaan**
- Satuan, SKU, barcode, batch/serial/expiry bila relevan.
- Supplier, purchase order, penerimaan barang, retur pembelian.
- Stock opname, penyesuaian, transfer antar-outlet.
- HPP dan metode perhitungan biaya yang diperlukan.

**Karyawan dan kontrol**
- Role dan permission.
- Shift kasir, kas awal, kas akhir, dan selisih kas.
- Approval diskon, void, refund, dan perubahan harga.
- Audit log aktivitas.

**Laporan dan integrasi**
- Laporan yang dibutuhkan owner, finance, dan supervisor.
- Export Excel/PDF.
- Integrasi akuntansi, marketplace, printer, scanner, display dapur, atau API pihak ketiga.

**Teknis dan operasional**
- Perangkat (Android, iOS, tablet, PC, printer, barcode scanner).
- Kualitas jaringan internet dan kebutuhan offline.
- Domain, hosting, email, backup, dan kebijakan akses.
- Volume transaksi, jumlah produk, jumlah pengguna simultan, serta data historis.

### 6.2 Output discovery wajib

1. Dokumen ringkasan proses bisnis saat ini (As-Is).
2. Dokumen proses bisnis yang diusulkan (To-Be).
3. User flow per role.
4. Daftar fitur dan requirement dengan prioritas.
5. Daftar integrasi dan dependensi perangkat.
6. Daftar laporan dan contoh format output.
7. Non-functional requirements dan target performa.
8. Daftar risiko, asumsi, dan hal yang tidak termasuk scope.
9. Estimasi effort, biaya, dan jadwal.

### 6.3 Aturan custom development

- Tidak ada fitur yang otomatis dianggap termasuk hanya karena ada di aplikasi POS lain.
- Requirement harus terhubung ke kebutuhan proses bisnis atau alasan bisnis yang tercatat.
- Komponen reusable boleh digunakan untuk efisiensi, tetapi alur, role, data, aturan transaksi, UI, dan integrasi dikustomisasi sesuai scope klien.
- Perubahan setelah scope disetujui wajib melalui Change Request.
- Penyedia wajib menjelaskan konsekuensi bila permintaan klien meningkatkan biaya, waktu, risiko, atau kompleksitas maintenance.

## 7. Functional Requirements — Portal Internal Penyedia

### 7.1 Lead dan konsultasi

| ID | Requirement | Acceptance criteria |
|---|---|---|
| OPS-001 | Form inquiry calon klien | Lead tersimpan dengan kontak, bisnis, sumber lead, dan kebutuhan awal |
| OPS-002 | Penjadwalan konsultasi | Jadwal, peserta, PIC, dan status konsultasi dapat dicatat |
| OPS-003 | Catatan konsultasi | Catatan, keputusan, dan tindak lanjut tersimpan pada profil lead |
| OPS-004 | Pengelolaan dokumen klien | File kebutuhan, contoh form, dan dokumen referensi dapat diunggah dengan akses terbatas |
| OPS-005 | Pipeline lead | Lead dapat dipindah antar-status dengan riwayat perubahan |

### 7.2 Discovery dan requirement

| ID | Requirement | Acceptance criteria |
|---|---|---|
| OPS-006 | Form discovery terstruktur | Form dapat disesuaikan per jenis bisnis |
| OPS-007 | Process map | BA dapat mengunggah atau menghubungkan diagram As-Is dan To-Be |
| OPS-008 | Requirement register | Kebutuhan memiliki ID, deskripsi, prioritas, status, dan sumber kebutuhan |
| OPS-009 | Traceability | Requirement dapat ditautkan ke desain, task, test case, dan UAT |
| OPS-010 | Scope exclusion | Fitur yang tidak termasuk scope ditampilkan eksplisit dalam proposal |

### 7.3 Proposal, kontrak, dan pembayaran

| ID | Requirement | Acceptance criteria |
|---|---|---|
| OPS-011 | Proposal builder | Proposal memuat solusi, deliverables, timeline, harga, asumsi, dan scope |
| OPS-012 | Versioning proposal | Revisi proposal tersimpan dengan nomor versi dan tanggal |
| OPS-013 | Client approval | Persetujuan scope dapat dicatat dengan identitas, tanggal, dan versi dokumen |
| OPS-014 | Development agreement | Dokumen kontrak dapat diunggah/dikirim dan status tanda tangan dicatat |
| OPS-015 | Invoice development | Invoice memuat total biaya dan instruksi pembayaran |
| OPS-016 | Verifikasi pembayaran penuh | Status Ready for Development hanya aktif jika pembayaran 100% sudah diverifikasi |
| OPS-017 | Recurring service agreement | Kontrak bulanan mencakup paket layanan, biaya, tanggal efektif, dan renewal |
| OPS-018 | Payment history | Riwayat pembayaran one-time dan bulanan dapat dilihat dan diekspor |
| OPS-019 | Reminder tagihan | Pengingat tagihan dan kontrak berakhir dapat dijadwalkan |

### 7.4 Project management

| ID | Requirement | Acceptance criteria |
|---|---|---|
| OPS-020 | Project workspace | Setiap klien memiliki workspace dan anggota tim |
| OPS-021 | Task board | Task memiliki assignee, prioritas, status, estimasi, dan due date |
| OPS-022 | Milestone | Kickoff, design approval, feature complete, QA, UAT, dan go-live dapat dilacak |
| OPS-023 | Demo/review | Catatan demo dan feedback klien tersimpan |
| OPS-024 | Change request | Perubahan scope menampilkan dampak biaya, waktu, dan approval |
| OPS-025 | Issue/bug tracker | Bug memiliki severity, langkah reproduksi, status, dan bukti |
| OPS-026 | Client portal | Klien dapat melihat milestone, dokumen yang dibagikan, dan status proyek tanpa melihat data internal lain |

### 7.5 UAT dan serah terima

| ID | Requirement | Acceptance criteria |
|---|---|---|
| OPS-027 | UAT test cases | Test case terhubung ke requirement yang disetujui |
| OPS-028 | UAT result | Klien dapat mencatat Pass, Fail, atau Blocked beserta bukti |
| OPS-029 | UAT sign-off | Go-live tidak dapat disetujui sebelum seluruh blocker diselesaikan atau dikecualikan tertulis |
| OPS-030 | Handover documents | Manual pengguna, admin guide, daftar akun, dan dokumentasi deployment diserahkan sesuai kontrak |
| OPS-031 | BAST | Persetujuan serah terima tersimpan dengan tanggal dan pihak yang menyetujui |

## 8. Functional Requirements — Aplikasi POS Custom Klien

Fitur aplikasi POS **ditentukan berdasarkan discovery**. Daftar berikut adalah katalog opsi, bukan template wajib.

| Area | Contoh kemampuan yang dapat dipilih |
|---|---|
| Penjualan | POS kasir, order, diskon, pajak, pembayaran, split payment, refund, void |
| Produk | SKU, barcode, varian, bundling, satuan, harga khusus |
| Inventaris | Stok per lokasi, stock movement, stock opname, min/max stock, batch/serial/expiry |
| Pembelian | Supplier, purchase order, penerimaan, retur pembelian |
| Pelanggan | Profil, riwayat transaksi, membership, loyalty bila diperlukan |
| Outlet | Cabang, transfer stok, harga per outlet, laporan konsolidasi |
| Karyawan | Role, permission, shift, kas awal/akhir, approval, audit log |
| Laporan | Penjualan, HPP, laba kotor, stok, pembelian, kas, kinerja kasir |
| Hardware | Printer thermal, barcode scanner, cash drawer, customer display, kitchen display |
| Integrasi | QRIS/payment gateway, accounting, marketplace, API eksternal |
| Offline | Transaksi offline, queue sinkronisasi, konflik data, indikator status sync |
| Pengaturan | Pajak, mata uang, format struk, jam kerja, zona waktu, multi-gudang |

### 8.1 Aturan custom per proyek

- Requirement wajib disetujui di SOW.
- Data model mengikuti karakteristik bisnis klien.
- Proses kasir dan persetujuan menyesuaikan SOP klien.
- Laporan dibuat sesuai definisi metrik yang disepakati.
- Integrasi pihak ketiga bergantung pada API, akun, biaya, dan akses dari penyedia eksternal.
- Hardware hanya dinyatakan kompatibel setelah diuji dengan model dan koneksi yang spesifik.

## 9. UAT, Perubahan Scope, dan Garansi

### 9.1 Proses UAT

1. Penyedia menyiapkan staging/UAT dengan data dummy atau data uji yang diotorisasi.
2. Penyedia memberikan test case berdasarkan SOW.
3. Klien menjalankan test case menggunakan role yang relevan.
4. Hasil Pass/Fail/Blocked dicatat dengan bukti.
5. Bug yang menyimpang dari scope diperbaiki dan diuji ulang.
6. Permintaan fitur baru atau perubahan proses dibuat sebagai Change Request.
7. Klien menyetujui UAT dan menandatangani BAST atau persetujuan go-live.

### 9.2 Klasifikasi perubahan

| Jenis | Contoh | Penanganan |
|---|---|---|
| Defect | Hasil transaksi berbeda dari requirement yang disetujui | Diperbaiki sesuai ketentuan garansi/kontrak |
| Clarification | Penjelasan yang tidak mengubah perilaku dan effort secara material | Dicatat dan disepakati |
| Change Request | Menambah alur, role, laporan, integrasi, atau aturan transaksi | Estimasi biaya dan waktu baru; perlu approval |
| Third-party change | API vendor berubah atau layanan eksternal terganggu | Dianalisis sebagai dependensi eksternal dan dapat menjadi pekerjaan tambahan |

### 9.3 Garansi dan maintenance

Periode garansi bug setelah go-live harus ditentukan dalam perjanjian pengembangan. Kontrak bulanan tidak otomatis berarti pengembangan fitur baru tanpa batas. Perubahan fitur, tambahan integrasi, migrasi data baru, dan perubahan proses bisnis harus mengikuti mekanisme quotation/change request.

## 10. Hosting dan Deployment One-Click

### 10.1 Jawaban atas kebutuhan hosting gratis

Hosting gratis dapat disediakan untuk **demo, preview, dan staging terbatas**. Namun, hosting gratis tidak disarankan untuk aplikasi POS produksi yang menyimpan transaksi, stok, data pelanggan, atau data keuangan.

Alasan penting berdasarkan dokumentasi penyedia per 21 November 2026:

- Render Free Web Service akan spin down setelah 15 menit tanpa trafik masuk; request pertama setelah tidur memerlukan waktu untuk bangun. Filesystem bersifat ephemeral, sehingga file lokal seperti upload dan SQLite hilang saat restart/redeploy/spin down. Render Free Postgres berakhir setelah 30 hari dan tidak menyediakan backup. Render menyatakan free instance bukan untuk aplikasi produksi. (Sumber: https://render.com/docs/free)
- Railway Free memberikan kredit pemakaian $1 per bulan, layanan tidur setelah sekitar 5–10 menit tanpa trafik keluar, dan layanan dapat berhenti ketika kredit habis. Paket gratis memiliki batas storage/compute dan tidak menyediakan custom domain pada tier Free menurut tabel perbandingan paket. (Sumber: https://docs.railway.com/pricing/free-plan)

**Keputusan:** sediakan free staging/demo dengan data dummy. Untuk produksi, gunakan hosting/VPS berbayar yang disepakati bersama klien atau infrastruktur milik klien. Biaya hosting, domain, email, backup tambahan, dan layanan pihak ketiga harus terlihat jelas di proposal serta kontrak bulanan.

### 10.2 Lingkungan yang disediakan

| Environment | Tujuan | Data | Hosting yang disarankan |
|---|---|---|---|
| Local/Development | Coding dan pengujian developer | Data lokal/dummy | Laptop developer/container |
| Preview/Free Staging | Demo internal dan review awal | Hanya data dummy/anonymized | Free tier yang memenuhi batasan demo |
| UAT/Staging | Uji penerimaan klien | Data dummy atau data uji yang disetujui | Staging terisolasi; idealnya resource terpisah |
| Production | Operasional bisnis nyata | Data bisnis aktual | VPS/managed hosting berbayar atau server milik klien |

### 10.3 Arsitektur deployment yang direkomendasikan

```text
Internal Admin Portal
        │
        │ tombol Deploy + project_id + environment
        ▼
Deployment Orchestrator
        │
        ├── Validasi scope, pembayaran, UAT, kontrak
        ├── Ambil konfigurasi target dan secret yang aman
        ├── Trigger CI/CD workflow
        ▼
Private Git Repository (kode per proyek/klien)
        │
        ▼
CI/CD Pipeline (GitHub Actions atau runner setara)
        │
        ├── Install dependencies
        ├── Run automated tests
        ├── Build release artifact/container
        ├── Backup database sebelum production deploy
        ├── Deploy ke target hosting
        ├── Run migration dan Laravel optimize
        ├── Restart queue/scheduler bila digunakan
        ├── Health check dan smoke test
        ▼
Client Environment (isolated per client)
        ├── POS Web / API
        ├── Database
        ├── File/Object Storage
        └── Backup & Monitoring
```

### 10.4 Tombol deployment

Portal internal menyediakan tombol:

- **Deploy to Staging** — mengirim build ke lingkungan UAT/staging.
- **Deploy to Production** — mengirim release yang telah disetujui ke production.
- **Rollback** — mengembalikan versi aplikasi ke release sebelumnya jika rollback aman dan didukung.

Tombol tidak langsung menjalankan deployment tanpa pemeriksaan. Sistem harus menampilkan checklist sebelum tombol aktif.

### 10.5 Pre-deployment checklist

| Check | Syarat |
|---|---|
| Client/project | Project terdaftar dan statusnya benar |
| Scope/payment | SOW final dan pembayaran pengembangan 100% terverifikasi |
| Contract | Kontrak pengembangan tersedia; kontrak layanan bulanan efektif sebelum production go-live |
| UAT | UAT disetujui atau pengecualian tertulis disimpan |
| Repository | Repository private dan release/tag tersedia |
| Environment | APP_KEY, database, storage, mail, queue, dan environment variables terpasang aman |
| Database | Backup terbaru tersedia dan dapat diverifikasi |
| Domain/TLS | Domain diarahkan dan sertifikat TLS/SSL aktif untuk production |
| Tests | Automated tests dan pre-deploy checks lulus |
| Operator | Pengguna memiliki hak deployment dan melakukan konfirmasi |

### 10.6 Proses saat tombol ditekan

1. Operator memilih klien, environment, dan versi release.
2. Orchestrator menjalankan pre-deployment checklist.
3. Sistem menampilkan estimasi dampak dan meminta konfirmasi untuk production.
4. Pipeline membuat deployment lock agar dua deployment tidak berjalan bersamaan pada proyek yang sama.
5. Build dan test dijalankan.
6. Backup database dibuat sebelum perubahan production.
7. Release dikirim ke server/hosting target.
8. Migration dijalankan dengan prosedur yang aman dan disetujui.
9. Cache/optimisasi Laravel diterapkan dan worker direstart bila perlu.
10. Health check dan smoke test dijalankan.
11. Jika sukses, portal menyimpan URL, release ID, waktu, operator, dan status.
12. Jika gagal, pipeline menghentikan proses, menampilkan log, dan menjalankan rollback aplikasi bila aman.
13. Database rollback tidak boleh dilakukan otomatis jika dapat menghilangkan transaksi baru; pemulihan database harus mengikuti prosedur backup/restore yang teruji.

GitHub Actions mendukung workflow manual melalui `workflow_dispatch`, sehingga workflow dapat dijalankan dari tombol **Run workflow** di tab Actions. Workflow tersebut harus ada di default branch dan pelaksana memerlukan write access. Untuk pengalaman satu tombol dari portal internal, backend portal dapat memanggil GitHub Actions API dengan token yang disimpan di secret manager. (Sumber: https://docs.github.com/en/actions/how-tos/manage-workflow-runs/manually-run-a-workflow)

### 10.7 Status deployment

`Not Configured → Ready → Queued → Building → Testing → Backing Up → Deploying → Migrating → Health Check → Successful`

Status gagal: `Failed`, `Cancelled`, `Rollback Required`, atau `Rolled Back`.

Setiap deployment menyimpan:
- client/project ID;
- environment;
- release/version/commit SHA;
- operator;
- waktu mulai dan selesai;
- hasil setiap tahapan;
- log yang telah disanitasi;
- URL deployment;
- referensi backup;
- status rollback.

### 10.8 Setup satu kali sebelum tombol dapat digunakan

Tombol deploy menjadi sederhana untuk penggunaan harian, tetapi bukan berarti tidak memerlukan konfigurasi awal. Sebelum tombol pertama kali digunakan, penyedia harus:

1. Membuat akun pada provider hosting yang dipilih dan menentukan siapa pemilik akun/tagihannya.
2. Menyiapkan private repository untuk proyek klien.
3. Menghubungkan portal internal dengan GitHub Actions atau runner deployment melalui token/API credential dengan hak minimum.
4. Membuat server/service, database, storage, domain/subdomain, DNS, dan sertifikat TLS/SSL.
5. Menyimpan secret seperti database password dan APP_KEY di secret manager, bukan di repository.
6. Menguji deployment ke staging sampai health check dan rollback berhasil.
7. Mendaftarkan deployment target ke profil proyek klien.

Setelah setup satu kali tersebut selesai, operator dapat memilih proyek, memilih environment, memeriksa checklist, dan menekan tombol deploy. Jika klien memakai server miliknya sendiri, diperlukan akses dan konfigurasi awal dari klien.

## 11. Hosting Production: Persyaratan Teknis Minimum

Untuk Laravel, hosting produksi harus memenuhi versi PHP dan extension sesuai versi Laravel yang digunakan, web server, permission storage/cache, environment variables, database, scheduler/queue jika dipakai, HTTPS, logging, dan backup. Laravel 13 saat ini membutuhkan PHP 8.3 atau lebih tinggi; versi aktual harus disesuaikan dengan versi framework proyek. Dokumentasi Laravel juga menyarankan `php artisan optimize`, `APP_DEBUG=false` di production, dan menyediakan health route default `/up`. Sumber: https://laravel.com/framework/docs/deployment

### 11.1 Rekomendasi hosting

- **Demo/staging gratis:** Render Free atau Railway Free dapat dipertimbangkan hanya untuk demo tanpa data bisnis nyata, setelah batasan tier diperiksa.
- **Production:** VPS/managed hosting berbayar dengan storage persisten, database yang dibackup, resource memadai, HTTPS, monitoring, dan akses deployment terkontrol.
- **Server klien:** jika klien memiliki infrastruktur sendiri, deployment dapat diarahkan ke server tersebut setelah audit kompatibilitas.
- **Shared hosting:** hanya digunakan jika kebutuhan aplikasi sederhana dan provider menyediakan versi PHP, extension, cron, database, storage, dan akses deployment yang memadai. Queue/worker dan proses background perlu diverifikasi.

### 11.2 Biaya hosting dan kepemilikan

- Domain sebaiknya didaftarkan atas nama klien atau pihak yang disepakati secara tertulis.
- Biaya hosting production bukan dianggap gratis dan harus masuk ke kontrak bulanan atau dibayar langsung oleh klien.
- SSL/TLS dapat menggunakan sertifikat gratis dari penyedia yang mendukung, tetapi domain tetap memiliki biaya registrasi/perpanjangan.
- Backup harus disimpan di lokasi terpisah dari server aplikasi.
- Kapasitas hosting ditinjau berdasarkan volume transaksi, ukuran database, jumlah pengguna aktif, file upload, dan target ketersediaan.

## 12. Layanan Bulanan (Managed POS Service)

Kontrak bulanan dapat mencakup item berikut sesuai paket yang ditawarkan:

| Layanan | Basic | Standard | Premium |
|---|---|---|---|
| Hosting production | Sesuai resource yang disepakati | Sesuai resource yang disepakati | Resource lebih besar/opsi dedicated |
| SSL/TLS | Ya | Ya | Ya |
| Monitoring uptime | Dasar | Lebih sering | Dengan alert dan laporan |
| Backup database | Terjadwal | Harian/retensi lebih panjang | Harian + salinan eksternal/retensi khusus |
| Maintenance keamanan | Patch terjadwal | Patch dan review berkala | Review lebih sering |
| Support | Jam kerja | Prioritas standar | Prioritas lebih tinggi |
| Bug fixing | Bug sesuai scope | Bug sesuai scope | Bug sesuai scope |
| Feature baru | Quotation terpisah | Quotation terpisah | Quotation/retainer terpisah |
| Laporan layanan | Ringkasan | Bulanan | Bulanan + review |

Nama paket, batas resource, frekuensi backup, durasi retensi, jam support, response time, serta biaya harus ditentukan secara komersial. Tabel ini adalah kerangka, bukan penawaran harga final.

### 12.1 Isi minimum kontrak bulanan

- Nama aplikasi/klien dan environment yang dikelola.
- Komponen yang termasuk hosting.
- Ukuran resource dan batas penggunaan wajar.
- Backup schedule dan retention.
- Monitoring dan prosedur incident.
- Jam support dan target response time.
- Jadwal maintenance.
- Biaya bulanan, tanggal jatuh tempo, pajak, dan metode pembayaran.
- Ketentuan telat bayar, suspend, perpanjangan, dan terminasi.
- Proses ekspor data dan serah terima saat kontrak berakhir.
- Biaya pihak ketiga yang tidak termasuk.

## 13. Security, Privacy, dan Data Ownership

1. Setiap klien memiliki database atau batas isolasi data yang terdokumentasi. Untuk custom POS, default yang direkomendasikan adalah deployment dan database terpisah per klien jika anggaran/infrastruktur memungkinkan.
2. Repository kode klien bersifat private.
3. Secret seperti APP_KEY, password database, API key, token deploy, dan kredensial provider tidak boleh disimpan di source code atau file log.
4. Akses berbasis role dan prinsip least privilege.
5. Data in transit wajib menggunakan HTTPS/TLS.
6. Production menggunakan `APP_DEBUG=false`.
7. Aktivitas administratif, akses data sensitif, perubahan harga/stok, refund, dan deployment dicatat dalam audit log.
8. Backup dienkripsi bila didukung provider dan akses backup dibatasi.
9. Data bisnis milik klien sesuai perjanjian; penyedia memproses data hanya untuk menyediakan layanan.
10. Kontrak harus mengatur ekspor, retensi, dan penghapusan data setelah terminasi.
11. Data nyata tidak boleh ditempatkan pada free staging/demo.
12. Akses developer ke production dibatasi, dicatat, dan diberikan hanya ketika diperlukan.

## 14. Model Data Portal Internal (High-Level)

| Entitas | Fungsi |
|---|---|
| leads | Calon klien dan sumber lead |
| consultations | Jadwal dan catatan konsultasi |
| client_organizations | Profil perusahaan klien |
| client_contacts | PIC dan kontak |
| discovery_sessions | Sesi discovery |
| business_processes | Proses As-Is dan To-Be |
| requirements | Daftar kebutuhan yang dapat ditelusuri |
| proposals | Proposal dan versi dokumen |
| statements_of_work | Scope, deliverables, asumsi, exclusions |
| contracts | Perjanjian pengembangan dan layanan bulanan |
| invoices | Tagihan |
| payments | Pembayaran dan verifikasi |
| projects | Proyek pengembangan |
| milestones | Milestone dan due date |
| tasks | Tugas tim |
| change_requests | Perubahan scope |
| test_cases | Test case QA/UAT |
| uat_results | Hasil pengujian klien |
| handovers | BAST dan dokumen serah terima |
| client_applications | Metadata aplikasi custom per klien |
| deployment_targets | Hosting/environment target |
| deployment_runs | Riwayat deployment dan log |
| backup_records | Catatan backup |
| service_subscriptions | Kontrak layanan bulanan |
| support_tickets | Tiket support |
| audit_logs | Audit aktivitas portal |

Data transaksi POS klien tidak harus disimpan di database portal internal. Portal cukup menyimpan metadata proyek dan informasi deployment; data penjualan tetap berada di lingkungan aplikasi klien yang terisolasi.

## 15. Non-Functional Requirements

| Area | Persyaratan |
|---|---|
| Security | Role-based access, secret management, HTTPS, audit log, isolated client data |
| Reliability | Deployment dapat dilacak, backup teruji, health check, prosedur rollback |
| Performance | Target ditentukan per proyek berdasarkan volume dan perangkat klien |
| Availability | Target uptime hanya dicantumkan jika kapasitas hosting/monitoring mendukung dan disepakati kontrak |
| Scalability | Penambahan klien tidak menyebabkan akses lintas proyek atau resource collision |
| Maintainability | Versioning, automated tests, dokumentasi deployment, dan change log |
| Usability | Portal internal mudah dipakai oleh sales, BA, PM, QA, finance, dan DevOps |
| Observability | Log, metrics, uptime, backup result, dan deployment history dapat ditinjau |

## 16. Acceptance Criteria Utama

| Area | Kriteria diterima |
|---|---|
| Consultation | Lead dan konsultasi dapat direkam dan dilacak |
| Discovery | Process map, requirement, asumsi, dan exclusion terdokumentasi |
| Proposal | Harga, jadwal, deliverables, dan scope memiliki versi serta approval |
| Payment gate | Developer tidak dapat memulai proyek sebelum pembayaran 100% diverifikasi |
| Custom development | Fitur POS yang dibangun terhubung ke requirement klien yang disetujui |
| Change request | Perubahan di luar scope harus disetujui sebelum dikerjakan |
| UAT | Klien dapat menguji dan memberikan sign-off |
| Monthly contract | Kontrak layanan aktif sebelum production berjalan atau sesuai tanggal efektif yang disepakati |
| Deploy staging | Deployment dapat dilakukan setelah pre-check staging lulus |
| Deploy production | Deployment terkunci jika UAT, kontrak, backup, domain/TLS, atau konfigurasi belum siap |
| Deployment log | Operator, versi, environment, waktu, hasil, dan log tersimpan |
| Rollback | Prosedur rollback aplikasi diuji pada staging sebelum production dipakai |
| Client isolation | Klien tidak dapat melihat data klien lain |
| Exit | Klien dapat memperoleh ekspor data sesuai kontrak saat layanan dihentikan |

## 17. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Scope creep | Jadwal dan biaya membengkak | SOW rinci dan change request berbayar |
| Klien belum siap mengambil keputusan | Development tertunda | Tetapkan PIC dan approval deadline |
| Kebutuhan berubah saat development | Rework | Discovery, prototype, demo milestone, change control |
| Pembayaran belum lunas | Risiko arus kas | Payment gate wajib sebelum development |
| Hosting gratis tidak stabil | POS tidak dapat diakses | Free hanya untuk staging; production di hosting berbayar |
| Salah konfigurasi deployment | Downtime atau data rusak | Pre-check, backup, staging, health check, rollback |
| Kebocoran data antarklien | Risiko hukum dan reputasi | Isolasi repo/database, RBAC, secrets, audit log |
| Provider pihak ketiga berubah | Integrasi gagal | Dokumentasi dependensi dan change request |
| Backup gagal | Kehilangan data | Monitoring backup dan uji restore berkala |
| Klien menunggak bulanan | Biaya infrastruktur ditanggung penyedia | Reminder, grace period, suspend sesuai kontrak, ekspor data sesuai ketentuan |

## 18. Roadmap Implementasi Portal dan Deployment

### Phase 1 — Operational Foundation
- Form lead dan konsultasi.
- Profil klien dan catatan discovery.
- Requirement register dan file repository.
- Proposal, SOW, kontrak, invoice, dan payment verification.
- Status proyek serta task board dasar.

### Phase 2 — Custom Project Delivery
- Project workspace dan milestone.
- Demo feedback.
- Change request.
- QA issue tracker.
- UAT test case, UAT result, dan sign-off.
- BAST/handover.

### Phase 3 — Deployment Automation
- Integrasi private Git repository.
- Deployment target registry.
- Secret management.
- Tombol Deploy to Staging.
- Tombol Deploy to Production dengan gate.
- Deployment logs, health check, notification, dan rollback.

### Phase 4 — Monthly Managed Service
- Kontrak dan invoice berulang.
- Reminder jatuh tempo dan renewal.
- Uptime monitoring.
- Backup dashboard dan restore testing.
- Support ticket.
- Client service portal.

### Phase 5 — Scale and Optimization
- Provider adapters untuk beberapa hosting.
- Otomatisasi provisioning domain/database.
- Laporan biaya hosting per klien.
- Usage monitoring dan rekomendasi kapasitas.
- Client self-service untuk dokumen, tiket, dan status layanan.

## 19. Keputusan yang Masih Harus Ditetapkan Pemilik Usaha

1. Nama brand dan badan usaha penyedia.
2. Konsultasi gratis atau berbayar.
3. Apakah pembayaran 100% dilakukan sebelum kickoff atau setelah discovery final.
4. Durasi garansi bug setelah go-live.
5. Paket bulanan dan komponen yang termasuk.
6. Jam support dan target response time.
7. Kebijakan domain dan kepemilikan hosting.
8. Apakah source code menjadi milik klien atau lisensi penggunaan; tentukan melalui kontrak.
9. Retensi backup dan prosedur restore.
10. Provider production pilihan pertama dan siapa yang membayar infrastrukturnya.
11. Apakah tiap klien mendapat repository, database, dan hosting terpisah.
12. Penyedia e-signature dan payment gateway yang akan digunakan.

## 20. Rekomendasi Akhir

Model bisnis yang paling konsisten dengan kebutuhan ini adalah **jasa pengembangan POS custom dengan recurring managed service**. Penyedia mendapatkan pendapatan proyek dari pengembangan yang dibayar lunas, kemudian mendapatkan pendapatan berulang dari hosting dan layanan bulanan setelah aplikasi digunakan.

Untuk deployment, buat satu portal internal sebagai control panel. Setiap proyek memiliki repository dan environment sendiri. Operator cukup memilih proyek, memilih staging atau production, memeriksa checklist, kemudian menekan tombol deploy. Sistem menjalankan pipeline, menyimpan log, mengecek kesehatan aplikasi, dan memberi tahu hasilnya.

**Jangan menjanjikan hosting production gratis.** Sediakan staging/demo gratis dengan data dummy, lalu gunakan hosting production berbayar yang biayanya transparan dalam kontrak bulanan. Ini lebih aman untuk data transaksi klien dan lebih sehat untuk keberlanjutan usaha.

---

## Referensi Teknis

1. Render — Free Instances and Limitations: https://render.com/docs/free (diakses 21 November 2026).
2. Render — FAQ, ephemeral filesystem dan perilaku service: https://render.com/docs/faq (diakses 21 November 2026).
3. Railway — Free Plan: https://docs.railway.com/pricing/free-plan (diakses 21 November 2026).
4. GitHub — Manually running a workflow (`workflow_dispatch`): https://docs.github.com/en/actions/how-tos/manage-workflow-runs/manually-run-a-workflow (diakses 21 November 2026).
5. GitHub — Deployment environments: https://docs.github.com/en/actions/concepts/workflows-and-actions/deployment-environments (diakses 21 November 2026).
6. Laravel — Deployment: https://laravel.com/framework/docs/deployment (diakses 21 November 2026).

**Akhir dokumen — PRD POS Custom Development Service v2.0**
