@extends('layouts.site')

@section('title', 'Seka Yasa POS — Jasa Sistem Kasir Custom & Managed Deployment')

@section('content')

<!-- =====================================================================
     LAYAR 1: FULL-SCREEN DARK HERO (100vh — Pola A)
     ===================================================================== -->
<section class="relative min-h-[calc(100vh-5rem)] bg-[#1A1208] text-[#F7F3EC] flex items-center overflow-hidden py-16">
    <!-- Warm Radial Glow behind right visual -->
    <div class="pointer-events-none absolute right-[-5%] top-1/2 -translate-y-1/2 w-[780px] h-[780px] rounded-full" style="background: radial-gradient(circle, rgba(119, 82, 41, 0.32) 0%, rgba(26, 18, 8, 0) 70%);"></div>

    <!-- Subtle Javanese Geometric Silhouette (opacity: 0.055, 480px) -->
    <svg class="pointer-events-none absolute right-[2%] top-1/2 -translate-y-1/2 w-[480px] h-[480px] text-[#F7B05F] opacity-[0.055]" viewBox="0 0 200 200" fill="none">
        <rect x="20" y="20" width="160" height="160" stroke="currentColor" stroke-width="1.5"/>
        <path d="M100 10L190 100L100 190L10 100L100 10Z" stroke="currentColor" stroke-width="1.5"/>
        <circle cx="100" cy="100" r="42" stroke="currentColor" stroke-width="1.5"/>
        <path d="M100 35C120 65 135 80 165 100C135 120 120 135 100 165C80 135 65 120 35 100C65 80 80 65 100 35Z" stroke="currentColor" stroke-width="1.2"/>
    </svg>

    <div class="sy-container relative z-10 w-full">
        <div class="sy-hero-grid">
            <!-- Left Column (1.08fr) -->
            <div>
                <div class="sy-index text-[#F7B05F] mb-5">00 / Layanan POS Custom & Infrastruktur Terkelola</div>
                <h1 class="sy-h1 text-[#F7F3EC]">
                    Sistem Kasir Dibangun Mengikuti Cara Kerja Bisnis Anda
                </h1>
                <p class="mt-6 text-[1.12rem] leading-[1.75] text-[#C4B5A2] max-w-[680px]">
                    Kami tidak menjual template POS yang memaksa toko Anda mengubah SOP. Setiap sistem diawali dengan pemetaan proses bisnis, dikembangkan secara khusus dengan isolasi data per klien, dan dirilis melalui pipeline satu tombol yang teraudit.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="/contact" class="px-6 py-3.5 rounded-md bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] text-sm font-bold transition">
                        Konsultasi Kebutuhan Usaha
                    </a>
                    <a href="/portal" class="px-6 py-3.5 rounded-md border border-[rgba(247,176,95,0.35)] hover:border-[#F7B05F] text-[#F7F3EC] text-sm font-semibold transition">
                        Lihat Portal & Console Deploy
                    </a>
                </div>
            </div>

            <!-- Right Column (0.92fr) — Flat, Clean, Complex System Visual (NO 3D Tilt, NO Floating Badges) -->
            <div class="bg-[#23190D] border border-[rgba(247,176,95,0.22)] rounded-lg p-5 shadow-none">
                <!-- Window Header -->
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-[rgba(247,176,95,0.14)] text-xs">
                    <span class="font-semibold text-[#F7F3EC]">Orchestrator & Ledger Kontrol — Kopi Senopati Artisan</span>
                    <span class="font-mono text-[#F7B05F]">PROD • v1.0.4</span>
                </div>

                <!-- Realistic Dense Operational Grid -->
                <div class="grid grid-cols-3 gap-3 mb-4 text-xs">
                    <div class="bg-[#1A1208] border border-[rgba(247,176,95,0.14)] p-3 rounded">
                        <span class="text-[11px] text-[#C4B5A2] block">Transaksi Shift #04</span>
                        <span class="text-base font-bold text-[#F7F3EC] mt-1 block">Rp 14.820.000</span>
                    </div>
                    <div class="bg-[#1A1208] border border-[rgba(247,176,95,0.14)] p-3 rounded">
                        <span class="text-[11px] text-[#C4B5A2] block">Selisih Kas Fisik</span>
                        <span class="text-base font-bold text-[#F7B05F] mt-1 block">Rp 0 (Cocok)</span>
                    </div>
                    <div class="bg-[#1A1208] border border-[rgba(247,176,95,0.14)] p-3 rounded">
                        <span class="text-[11px] text-[#C4B5A2] block">Payment Gate</span>
                        <span class="text-base font-bold text-[#F7F3EC] mt-1 block">100% Lunas</span>
                    </div>
                </div>

                <!-- Realistic Multi-Row System Ledger -->
                <div class="bg-[#1A1208] border border-[rgba(247,176,95,0.14)] rounded overflow-hidden">
                    <div class="px-3.5 py-2 border-b border-[rgba(247,176,95,0.12)] flex justify-between text-[11px] font-semibold text-[#C4B5A2]">
                        <span>PEMERIKSAAN PRA-DEPLOY & MUTASI STOK</span>
                        <span>STATUS</span>
                    </div>
                    <div class="divide-y divide-[rgba(247,176,95,0.10)] text-xs">
                        <div class="px-3.5 py-2.5 flex justify-between items-center">
                            <span class="text-[#F7F3EC]">Row-Lock Atomic Stock Deduction (MySQL InnoDB)</span>
                            <span class="font-mono text-[#F7B05F]">VERIFIED</span>
                        </div>
                        <div class="px-3.5 py-2.5 flex justify-between items-center">
                            <span class="text-[#F7F3EC]">UAT Sign-off & BAST Digital (#BAST-2026-001)</span>
                            <span class="font-mono text-[#F7B05F]">5/5 PASS</span>
                        </div>
                        <div class="px-3.5 py-2.5 flex justify-between items-center">
                            <span class="text-[#F7F3EC]">Snapshot Database Terenkripsi (Offsite Backup)</span>
                            <span class="font-mono text-[#F7B05F]">READY</span>
                        </div>
                        <div class="px-3.5 py-2.5 flex justify-between items-center">
                            <span class="text-[#F7F3EC]">Production Health Check Endpoint (/up)</span>
                            <span class="font-mono text-[#F7B05F]">HTTP 200</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Left Minimal Scroll Cue -->
    <div class="hidden lg:flex items-center gap-3 absolute bottom-8 left-[4%] text-xs text-[#C4B5A2]">
        <span class="w-9 h-[1.5px] bg-[#F7B05F] inline-block"></span>
        <span>Gulir ke bawah untuk menelaah masalah lapangan</span>
    </div>
</section>

<!-- =====================================================================
     LAYAR 2: MASALAH DI LAPANGAN (Warm Alabaster #FAF7F2 — Pola B Sticky Split)
     ===================================================================== -->
<section class="bg-[#FAF7F2] py-24 lg:py-32">
    <div class="sy-container">
        <div class="sy-split">
            <!-- Left Sticky Column (0.72fr, max-width 440px) -->
            <div class="sy-split-left">
                <div class="sy-index text-[#775229] mb-3">01 / Masalah di Lapangan</div>
                <h2 class="sy-h2 text-[#1A1208]">
                    Mengapa Aplikasi Kasir Template Sering Gagal di Toko Nyata
                </h2>
                <p class="mt-4 text-[0.98rem] leading-[1.65] text-[#524538]">
                    Bisnis yang bertumbuh memiliki aturan stok, diskon, shift kasir, dan laporan yang tidak bisa dipaksakan ke dalam satu cetakan generik.
                </p>
            </div>

            <!-- Right Editorial Row List (1.08fr, max-width 720px, hairline 1px dividers) -->
            <div class="sy-split-right divide-y divide-[rgba(119,82,41,0.18)] border-t border-b border-[rgba(119,82,41,0.18)]">
                <!-- Row 01 -->
                <div class="py-8 grid grid-cols-[48px_1fr] gap-4 items-start">
                    <span class="text-sm font-bold text-[#775229] pt-1">01</span>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="sy-h3 text-[#1A1208]">Selisih Stok dan Kebocoran Kas Shift</h3>
                            <span class="px-2.5 py-1 bg-[#F2EBE1] text-[#775229] text-xs font-semibold rounded-[6px]">Kontrol Inventori & Shift</span>
                        </div>
                        <p class="sy-body-point text-[#524538]">
                            Tanpa penguncian baris database dan rekonsiliasi kas fisik wajib saat tutup shift, transaksi bersamaan memicu stok minus dan selisih uang laci tanpa jejak audit.
                        </p>
                    </div>
                </div>

                <!-- Row 02 -->
                <div class="py-8 grid grid-cols-[48px_1fr] gap-4 items-start">
                    <span class="text-sm font-bold text-[#775229] pt-1">02</span>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="sy-h3 text-[#1A1208]">Alur Kasir Dipaksa Mengikuti Template</h3>
                            <span class="px-2.5 py-1 bg-[#F2EBE1] text-[#775229] text-xs font-semibold rounded-[6px]">Custom Discovery SOW</span>
                        </div>
                        <p class="sy-body-point text-[#524538]">
                            Kasir terpaksa mencatat resep, batch apotek, atau split payment di kertas terpisah karena sistem jadi-jadian tidak mendukung aturan transaksi spesifik usaha Anda.
                        </p>
                    </div>
                </div>

                <!-- Row 03 -->
                <div class="py-8 grid grid-cols-[48px_1fr] gap-4 items-start">
                    <span class="text-sm font-bold text-[#775229] pt-1">03</span>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="sy-h3 text-[#1A1208]">Hosting Gratis Berhenti saat Jam Ramai</h3>
                            <span class="px-2.5 py-1 bg-[#F2EBE1] text-[#775229] text-xs font-semibold rounded-[6px]">Managed VPS & Deploy</span>
                        </div>
                        <p class="sy-body-point text-[#524538]">
                            Server gratisan yang tertidur otomatis dan penyimpanan file sementara berisiko menghentikan operasional kasir serta menghilangkan data transaksi harian Anda.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================================
     LAYAR 3: SHOWCASE KARYA SISTEM (Warm Sand #F3EDE4 — Pola C Wide Alternating)
     ===================================================================== -->
<section class="bg-[#F3EDE4] py-24 lg:py-32">
    <div class="sy-container space-y-24">
        <div class="max-w-xl">
            <div class="sy-index text-[#775229] mb-3">02 / Karya Sistem yang Dapat Diuji Langsung</div>
            <h2 class="sy-h2 text-[#1A1208]">
                Tiga Pilar Aplikasi Operasional dan Kontrol Deployment
            </h2>
        </div>

        <!-- Showcase 1: Image/UI Left (1.15fr), Copy Right (0.85fr) -->
        <div class="sy-showcase">
            <div class="sy-showcase-media bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] rounded-lg p-6">
                <div class="flex justify-between items-center pb-3 mb-4 border-b border-[rgba(119,82,41,0.18)] text-xs font-semibold text-[#775229]">
                    <span>MODUL KASIR POS • OUTLET PUSAT SENOPATI</span>
                    <span class="font-mono text-[#1A1208]">SHIFT AKTIF: RP 200.000</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="space-y-2">
                        <div class="p-3 bg-[#F3EDE4] border border-[rgba(119,82,41,0.15)] rounded flex justify-between">
                            <div>
                                <div class="font-bold text-[#1A1208]">Kopi Susu Gula Aren</div>
                                <div class="text-[11px] text-[#524538] font-mono">8991001002 • Stok: 198</div>
                            </div>
                            <span class="font-bold text-[#775229]">Rp 22.000</span>
                        </div>
                        <div class="p-3 bg-[#F3EDE4] border border-[rgba(119,82,41,0.15)] rounded flex justify-between">
                            <div>
                                <div class="font-bold text-[#1A1208]">French Croissant Butter</div>
                                <div class="text-[11px] text-[#524538] font-mono">8991001006 • Stok: 34</div>
                            </div>
                            <span class="font-bold text-[#775229]">Rp 24.000</span>
                        </div>
                        <div class="p-3 bg-[#F3EDE4] border border-[rgba(119,82,41,0.15)] rounded flex justify-between">
                            <div>
                                <div class="font-bold text-[#1A1208]">Arabica Gayo Beans 250g</div>
                                <div class="text-[11px] text-[#524538] font-mono">8991001009 • Stok: 25</div>
                            </div>
                            <span class="font-bold text-[#775229]">Rp 85.000</span>
                        </div>
                    </div>
                    <div class="bg-[#1A1208] text-[#F7F3EC] p-4 rounded flex flex-col justify-between">
                        <div class="space-y-1.5 font-mono text-[11px]">
                            <div class="text-[#F7B05F] font-bold border-b border-[rgba(247,176,95,0.2)] pb-1">STRUK #INV-1-20261121-A4F1</div>
                            <div class="flex justify-between"><span>2x Kopi Susu Aren</span><span>44.000</span></div>
                            <div class="flex justify-between"><span>1x Croissant</span><span>24.000</span></div>
                            <div class="flex justify-between text-[#C4B5A2]"><span>PPN (11%)</span><span>7.480</span></div>
                            <div class="flex justify-between font-bold text-[#F7B05F] pt-1 border-t border-[rgba(247,176,95,0.2)]"><span>TOTAL</span><span>Rp 75.480</span></div>
                        </div>
                        <div class="mt-4 pt-2 border-t border-[rgba(247,176,95,0.15)] text-[10px] text-[#C4B5A2] flex justify-between">
                            <span>TUNAI: Rp 100.000</span>
                            <span>KEMBALI: Rp 24.520</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sy-showcase-copy">
                <h3 class="sy-h2 text-[#1A1208]">Terminal Kasir Cepat dengan Kontrol Shift & Barcode</h3>
                <p class="mt-4 sy-body-point text-[#524538]">
                    Kasir memproses transaksi dalam hitungan detik dengan scanner barcode, penahanan transaksi sementara, diskon terotorisasi, cetak struk thermal 80mm, dan penguncian stok atomik di database.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Atomic Row Lock</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Shift Cash Variance</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">WebAPK Android</span>
                </div>
                <div class="mt-7">
                    <a href="/pos" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229] transition">
                        Buka Layar Kasir POS Sekarang →
                    </a>
                </div>
            </div>
        </div>

        <!-- Showcase 2 (.reverse): Copy Left, UI Right -->
        <div class="sy-showcase reverse pt-12 border-t border-[rgba(119,82,41,0.18)]">
            <div class="sy-showcase-media bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] rounded-lg p-6">
                <div class="flex justify-between items-center pb-3 mb-4 border-b border-[rgba(119,82,41,0.18)] text-xs font-semibold text-[#775229]">
                    <span>AUDIT INVENTORI & ANALITIK LABA KOTOR</span>
                    <span class="font-mono text-[#1A1208]">MULTI-OUTLET</span>
                </div>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-[rgba(119,82,41,0.18)] text-[#524538]">
                            <th class="py-2 font-semibold">Mutasi / Produk</th>
                            <th class="py-2 font-semibold">Jenis</th>
                            <th class="py-2 text-right font-semibold">Perubahan</th>
                            <th class="py-2 text-right font-semibold">Margin Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(119,82,41,0.12)] text-[#1A1208]">
                        <tr>
                            <td class="py-2.5 font-semibold">Kopi Susu Gula Aren</td>
                            <td class="py-2.5 font-mono text-[11px] text-[#775229]">SALE</td>
                            <td class="py-2.5 text-right font-mono">-2 Cup</td>
                            <td class="py-2.5 text-right font-bold">56.8%</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">Arabica Gayo 250g</td>
                            <td class="py-2.5 font-mono text-[11px] text-[#775229]">TRANSFER_OUT</td>
                            <td class="py-2.5 text-right font-mono">-5 Pack</td>
                            <td class="py-2.5 text-right font-bold">35.3%</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">French Croissant Butter</td>
                            <td class="py-2.5 font-mono text-[11px] text-[#775229]">ADJUSTMENT_ADD</td>
                            <td class="py-2.5 text-right font-mono">+20 Pcs</td>
                            <td class="py-2.5 text-right font-bold">43.7%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sy-showcase-copy">
                <h3 class="sy-h2 text-[#1A1208]">Dashboard Pemilik Usaha & Jejak Audit Pergerakan Stok</h3>
                <p class="mt-4 sy-body-point text-[#524538]">
                    Pemilik usaha memantau omset per cabang, harga pokok penjualan (HPP), estimasi laba kotor harian, serta setiap riwayat penyesuaian stok beserta alasan dan pelakunya.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Stock Audit Trail</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Laporan HPP & Laba</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Transfer Antar-Cabang</span>
                </div>
                <div class="mt-7">
                    <a href="/dashboard" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229] transition">
                        Buka Dashboard Owner & Laporan →
                    </a>
                </div>
            </div>
        </div>

        <!-- Showcase 3: UI Left, Copy Right -->
        <div class="sy-showcase pt-12 border-t border-[rgba(119,82,41,0.18)]">
            <div class="sy-showcase-media bg-[#1A1208] border border-[rgba(247,176,95,0.22)] rounded-lg p-6 text-[#F7F3EC]">
                <div class="flex justify-between items-center pb-3 mb-4 border-b border-[rgba(247,176,95,0.18)] text-xs font-semibold text-[#F7B05F]">
                    <span>PORTAL INTERNAL • ONE-CLICK DEPLOYMENT CONSOLE</span>
                    <span class="font-mono text-[#C4B5A2]">TARGET: PRODUCTION VPS</span>
                </div>
                <div class="space-y-2 text-xs font-mono">
                    <div class="p-2.5 bg-[#23190D] border border-[rgba(247,176,95,0.16)] rounded flex justify-between">
                        <span>[GATE 1] Verifikasi Pembayaran Development 100%</span>
                        <span class="text-[#F7B05F]">PASS</span>
                    </div>
                    <div class="p-2.5 bg-[#23190D] border border-[rgba(247,176,95,0.16)] rounded flex justify-between">
                        <span>[GATE 2] Client UAT Sign-off & BAST Tertulis</span>
                        <span class="text-[#F7B05F]">PASS</span>
                    </div>
                    <div class="p-2.5 bg-[#23190D] border border-[rgba(247,176,95,0.16)] rounded flex justify-between">
                        <span>[GATE 3] Pre-Migration Database Snapshot Backup</span>
                        <span class="text-[#F7B05F]">PASS</span>
                    </div>
                    <div class="p-3 bg-black/50 border border-[rgba(247,176,95,0.16)] rounded text-[11px] text-[#C4B5A2]">
                        <div>&gt; php artisan migrate --force && php artisan optimize</div>
                        <div class="text-[#F7B05F] mt-1">&gt; Health Check /up: HTTP 200 OK (42ms) — Aplikasi Live.</div>
                    </div>
                </div>
            </div>

            <div class="sy-showcase-copy">
                <h3 class="sy-h2 text-[#1A1208]">Portal Internal Pengelolaan Proyek & Deploy Satu Tombol</h3>
                <p class="mt-4 sy-body-point text-[#524538]">
                    Mengunci pengerjaan development sebelum pembayaran 100% terverifikasi, melacak UAT klien, dan menjalankan deployment staging maupun produksi dengan pengaman pre-check serta rollback.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Payment Gate 100%</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">7 Pre-Deploy Checks</span>
                    <span class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] text-[#775229] text-xs font-semibold rounded-[8px]">Auto Rollback</span>
                </div>
                <div class="mt-7">
                    <a href="/portal" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229] transition">
                        Buka Portal Layanan & Deploy Console →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================================
     LAYAR 4: CARA KERJA (Warm Alabaster #FAF7F2 — Pola D Vertical Bordered Timeline)
     ===================================================================== -->
<section class="bg-[#FAF7F2] py-24 lg:py-32">
    <div class="sy-container">
        <div class="sy-split">
            <!-- Left Sticky Column -->
            <div class="sy-split-left">
                <div class="sy-index text-[#775229] mb-3">03 / Alur Kerja Proyek Custom</div>
                <h2 class="sy-h2 text-[#1A1208]">
                    Tiga Tahap Terukur dari Konsultasi hingga Go-Live
                </h2>
                <p class="mt-4 text-[0.98rem] leading-[1.65] text-[#524538]">
                    Ruang lingkup, biaya pengembangan, dan tanggung jawab layanan bulanan disepakati di muka tanpa biaya tersembunyi.
                </p>
            </div>

            <!-- Right Column: Vertical Bordered Step Timeline (border-left: 2px solid #775229; padding-left: 2.25rem) -->
            <div class="sy-split-right">
                <div class="border-l-2 border-[#775229] pl-9 max-w-[680px] space-y-12">
                    <div>
                        <div class="text-xs font-bold text-[#775229] uppercase tracking-wider mb-1">Langkah 01 — Discovery & SOW</div>
                        <h3 class="sy-h3 text-[#1A1208]">Pemetaan Proses As-Is, To-Be, dan Persetujuan Scope</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Analis bisnis memetakan alur kasir, gudang, serta perangkat keras toko Anda untuk menyusun Statement of Work (SOW) dan proposal biaya tetap satu kali.
                        </p>
                    </div>

                    <div>
                        <div class="text-xs font-bold text-[#775229] uppercase tracking-wider mb-1">Langkah 02 — Payment Gate & Development</div>
                        <h3 class="sy-h3 text-[#1A1208]">Verifikasi Pelunasan 100% dan Pengujian UAT Klien</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Setelah kontrak ditandatangani dan pembayaran lunas diverifikasi, tim membangun sistem di repository terisolasi hingga klien menandatangani BAST UAT di staging.
                        </p>
                    </div>

                    <div>
                        <div class="text-xs font-bold text-[#775229] uppercase tracking-wider mb-1">Langkah 03 — Production Deploy & Managed Service</div>
                        <h3 class="sy-h3 text-[#1A1208]">Rilis Satu Tombol ke VPS Berbayar & Pemeliharaan Bulanan</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Aplikasi dirilis ke lingkungan produksi terenkripsi HTTPS dengan backup harian otomatis, pemantauan uptime, dan kontrak dukungan bulanan yang transparan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================================
     LAYAR 5: PENUTUP & AKSI (Deep Espresso #1A1208)
     ===================================================================== -->
<section class="bg-[#1A1208] text-[#F7F3EC] py-24 lg:py-28">
    <div class="sy-container">
        <div class="max-w-3xl">
            <div class="sy-index text-[#F7B05F] mb-4">04 / Mulai Pemetaan Bisnis Anda</div>
            <h2 class="sy-h2 text-[#F7F3EC]">
                Siap Memetakan Sistem Kasir yang Sesuai dengan Operasional Toko Anda?
            </h2>
            <p class="mt-5 text-[1.05rem] leading-[1.7] text-[#C4B5A2] max-w-[600px]">
                Gunakan widget konsultasi 3 langkah untuk merangkum kebutuhan cabang, perangkat, dan target alur kerja bisnis Anda tanpa komitmen biaya di awal.
            </p>
            <div class="mt-9 flex flex-wrap gap-4">
                <a href="/contact" class="px-7 py-3.5 rounded-md bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] text-sm font-bold transition">
                    Jadwalkan Konsultasi Sekarang
                </a>
                <a href="/projects" class="px-7 py-3.5 rounded-md border border-[rgba(247,176,95,0.3)] hover:border-[#F7B05F] text-[#F7F3EC] text-sm font-semibold transition">
                    Bedah Detail Studi Kasus
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
