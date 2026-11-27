@extends('layouts.site')

@section('title', 'Fitur POS — Tandha by SEKA GROUP')

@section('content')

<!-- PAGE HEADER -->
<section class="inner-header">
    <div class="container inner-header-grid reveal-on-scroll is-visible">
        <div>
            <span class="section-index section-index-light">Fitur Tandha POS</span>
            <h1>Setiap modul dirancang untuk meja kasir, kontrol stok cabang, dan ketenangan pemilik.</h1>
        </div>
        <p>
            Fokus pada ketertiban pencatatan operasional retail, kafe, restoran, dan usaha multi-cabang—cepat dipahami staf baru dan bebas dari selisih kas.
        </p>
    </div>
</section>

<!-- FEATURE MODULES -->
<section class="inner-content">
    <div class="container">
        <div class="case-filter-bar reveal-on-scroll">
            <button type="button" class="case-filter-btn active" data-case-filter="all">Semua Modul (3)</button>
            <button type="button" class="case-filter-btn" data-case-filter="kasir">Kasir &amp; Shift</button>
            <button type="button" class="case-filter-btn" data-case-filter="stok">Stok &amp; Laba Kotor</button>
            <button type="button" class="case-filter-btn" data-case-filter="akun">Akun Sewa &amp; Custom</button>
        </div>

        <!-- Modul 01 -->
        <article class="project-row-block reveal-on-scroll" id="kasir" data-case-cat="kasir">
            <div class="showcase-media bg-[#0D2620] p-4 sm:p-6 text-white">
                <div class="flex flex-wrap justify-between gap-2 text-xs pb-3 mb-4 border-b border-[rgba(52,211,153,0.22)] text-[#34D399] font-semibold">
                    <span>TERMINAL KASIR &amp; REKONSILIASI SHIFT</span>
                    <span class="tabular-nums">STOK TERKUNCI OTOMATIS</span>
                </div>
                <div class="space-y-2.5 tabular-nums text-xs">
                    <div class="p-3 bg-[#143B32] border border-white/10 rounded-[8px] flex justify-between gap-2">
                        <span class="text-[#B8D0C9]">Shift Dibuka (Modal Awal Laci):</span>
                        <span class="font-bold text-white shrink-0">Rp 200.000</span>
                    </div>
                    <div class="p-3 bg-[#143B32] border border-white/10 rounded-[8px] flex justify-between gap-2">
                        <span class="text-[#B8D0C9]">Total Penjualan Tunai Shift:</span>
                        <span class="font-bold text-white shrink-0">Rp 1.450.000</span>
                    </div>
                    <div class="p-3 bg-[#143B32] border border-white/10 rounded-[8px] flex justify-between gap-2">
                        <span class="text-[#B8D0C9]">Kas Seharusnya (Expected Cash):</span>
                        <span class="font-bold text-[#34D399] shrink-0">Rp 1.650.000</span>
                    </div>
                    <div class="p-3 bg-[#143B32] border border-white/10 rounded-[8px] flex justify-between gap-2">
                        <span class="text-[#B8D0C9]">Selisih Kas Fisik (Variance):</span>
                        <span class="font-bold text-[#34D399] shrink-0">Rp 0 (MATCH)</span>
                    </div>
                </div>
            </div>

            <div>
                <span class="section-index">Modul 01 — Frontliner Kasir</span>
                <h2 class="split-title">POS Kasir Cepat, Hold Struk &amp; Tutup Shift Presisi</h2>

                <div class="project-detail-table">
                    <div class="detail-line">
                        <strong>Masalah Toko</strong>
                        <span>Stok minus saat dua kasir checkout bersamaan, antrean macet saat menunda bayar, dan setoran akhir shift sulit dicocokkan.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Fitur Utama</strong>
                        <span>Pessimistic lock stok database, scan barcode SKU otomatis, Hold/Resume keranjang, diskon nominal/persen, cetak struk 80mm, dan wajib hitung uang laci saat tutup shift.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Dampak</strong>
                        <span>Nol stok minus pada jam sibuk serta rekonsiliasi laci shift tercatat objektif.</span>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="/pos" class="link-arrow">Buka &amp; uji layar kasir Tandha →</a>
                </div>
            </div>
        </article>

        <!-- Modul 02 -->
        <article class="project-row-block reveal-on-scroll" id="stok" data-case-cat="stok">
            <div>
                <span class="section-index">Modul 02 — Stok &amp; Analitik Pemilik</span>
                <h2 class="split-title">Kontrol Stok Multi-Cabang, Mutasi &amp; Laba Kotor</h2>

                <div class="project-detail-table">
                    <div class="detail-line">
                        <strong>Masalah Toko</strong>
                        <span>Pemilik harus menunggu akhir bulan merekap spreadsheet tiap cabang dan sulit melacak penyebab selisih opname stok.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Fitur Utama</strong>
                        <span>Kartu riwayat mutasi keluar-masuk, transfer antar-outlet, kalkulasi HPP &amp; laba kotor per produk, serta peringatan stok menipis.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Dampak</strong>
                        <span>Omzet harian, margin kotor, dan pergerakan stok semua cabang terpantau langsung.</span>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="/dashboard" class="link-arrow">Buka dashboard pemilik &amp; stok →</a>
                </div>
            </div>

            <div class="showcase-media bg-white p-4 sm:p-6">
                <div class="flex flex-wrap justify-between gap-2 text-xs pb-3 mb-4 border-b border-[rgba(13,38,32,0.12)] text-[#0D7A5F] font-semibold">
                    <span>JEJAK MUTASI STOK &amp; HPP CABANG</span>
                    <span class="text-[#0D2620] tabular-nums">RIWAYAT TERCATAT</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-left text-xs tabular-nums">
                    <thead class="text-[#3D544E] border-b border-[rgba(13,38,32,0.12)]">
                        <tr>
                            <th class="py-2">Nama Produk</th>
                            <th class="py-2">Harga Pokok</th>
                            <th class="py-2">Harga Jual</th>
                            <th class="py-2 text-right">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(13,38,32,0.08)] text-[#0D2620]">
                        <tr>
                            <td class="py-2.5 font-semibold">Caramel Macchiato Ice</td>
                            <td class="py-2.5">Rp 12.500</td>
                            <td class="py-2.5">Rp 28.000</td>
                            <td class="py-2.5 text-right font-bold text-[#0D7A5F]">Rp 15.500</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">Japanese Matcha Latte</td>
                            <td class="py-2.5">Rp 11.000</td>
                            <td class="py-2.5">Rp 25.000</td>
                            <td class="py-2.5 text-right font-bold text-[#0D7A5F]">Rp 14.000</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">Pain Au Chocolat</td>
                            <td class="py-2.5">Rp 15.000</td>
                            <td class="py-2.5">Rp 27.000</td>
                            <td class="py-2.5 text-right font-bold text-[#0D7A5F]">Rp 12.000</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </article>

        <!-- Modul 03 -->
        <article class="project-row-block reveal-on-scroll" id="akun" data-case-cat="akun">
            <div class="internal-system-preview">
                <div class="preview-panel">
                    <div class="preview-panel-head">
                        <span>Portal Akun Bisnis Tandha</span>
                        <span class="text-xs font-semibold text-[#0D7A5F]">DATA TERISOLASI PER BISNIS</span>
                    </div>
                    <div class="preview-grid-2x2">
                        <div class="preview-stat">
                            <small>Langganan Tandha POS</small>
                            <strong>Sewa Mulai Rp 99rb/bln</strong>
                        </div>
                        <div class="preview-stat">
                            <small>Konsultasi Update Fitur</small>
                            <strong>Terintegrasi WhatsApp SEKA GROUP</strong>
                        </div>
                        <div class="preview-stat">
                            <small>Status Tagihan Sewa</small>
                            <strong>Midtrans / Verifikasi Admin</strong>
                        </div>
                        <div class="preview-stat">
                            <small>Keamanan Akses</small>
                            <strong>Cloudflare + NIK Unik</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <span class="section-index">Modul 03 — Akun Terisolasi &amp; Konsultasi</span>
                <h2 class="split-title">Berlangganan Paket &amp; Request Penambahan Fitur dalam Satu Akun</h2>

                <div class="project-detail-table">
                    <div class="detail-line">
                        <strong>Isolasi Data</strong>
                        <span>Pendaftaran 3 langkah lengkap dengan validasi anti-duplikasi NIK/email/WhatsApp memisahkan penuh outlet, staf, dan transaksi tiap akun usaha.</span>
                    </div>
                    <div class="detail-line">
                        <strong>Fleksibilitas</strong>
                        <span>Pemilik dapat berlangganan paket bulanan sekaligus mengajukan konsultasi penambahan fitur kapan saja — baik sebelum maupun setelah berlangganan.</span>
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap gap-5 items-center">
                    <a href="/harga" class="link-arrow">Lihat pilihan paket sewa →</a>
                    <a href="/contact" class="link-arrow">Ajukan konsultasi fitur →</a>
                </div>
            </div>
        </article>
    </div>
</section>

@endsection
