@extends('layouts.site')

@section('title', 'Tandha by SEKA GROUP — Aplikasi Kasir, Stok Cabang & Solusi POS Bisnis')

@section('content')

<!-- ==================================================================
     1. HERO: TANDHA POS + SPACIOUS LIVE TEMPLATE PREVIEW
     ================================================================== -->
<section class="full-stage hero-stage" id="hero-stage">
    <div class="hero-blueprint-grid" aria-hidden="true"></div>
    <div class="hero-cursor-glow" id="hero-cursor-glow" aria-hidden="true"></div>
    <div class="hero-signal-tracks" aria-hidden="true">
        <span class="signal-line line-h1"><i></i></span>
        <span class="signal-line line-h2"><i></i></span>
        <span class="signal-line line-v1"><i></i></span>
    </div>

    <div class="container hero-grid">
        <div class="hero-copy reveal-on-scroll is-visible">
            <span class="hero-pill">
                <i class="live-pulse-dot" aria-hidden="true"></i>
                Tandha POS · by SEKA GROUP
            </span>

            <h1 class="hero-headline">
                Kasir Cepat, Stok Terkunci,
                <span class="hero-headline-accent">dan Kontrol Cabang yang Rapi.</span>
            </h1>

            <p class="hero-lead">
                Gunakan paket sewa <strong>Tandha POS</strong> siap pakai untuk langsung berjualan hari ini, atau konsultasikan penyesuaian khusus apabila alur kasir, resep, maupun skema stok toko Anda berbeda.
            </p>

            <div class="hero-checks">
                <span><i data-lucide="check-circle-2" class="w-4 h-4 text-[#34D399]"></i> Checkout Lock Stok DB</span>
                <span><i data-lucide="check-circle-2" class="w-4 h-4 text-[#34D399]"></i> Tutup Shift Hitung Laci</span>
                <span><i data-lucide="check-circle-2" class="w-4 h-4 text-[#34D399]"></i> Multi-Outlet Terisolasi</span>
            </div>

            <div class="hero-cta-row">
                <a href="/harga" class="btn btn-mint btn-lg">Lihat Paket &amp; Harga →</a>
                <a href="/contact" class="btn btn-ghost-light btn-lg">Konsultasi &amp; Request Fitur</a>
            </div>
        </div>

        <!-- Hero Right: Fixed-Height Interactive Scan Barcode -> Stock Deduct -> In-Frame Thermal Receipt -->
        <div class="reveal-on-scroll delay-1 is-visible" x-data="heroTemplateDemo()">
            <div class="hero-image-box" id="hero-image-box">
                <div class="hero-frame-bar">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="hero-frame-dots shrink-0" aria-hidden="true"><span></span><span></span><span></span></div>
                        <span class="font-semibold text-[#F4FAF8] truncate">Simulasi Kasir Tandha POS</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[#34D399] font-semibold text-[11px] shrink-0">
                        <i class="live-pulse-dot"></i> TERHUBUNG LANGSUNG KE STOK
                    </span>
                </div>

                <!-- Fixed-Dimension Light POS Surface -->
                <div class="pos-light-terminal text-left">
                    <!-- 1. Interactive Barcode Scanner Bar -->
                    <div class="pos-scanner-bar">
                        <div x-show="laserActive" class="pos-laser-beam" aria-hidden="true"></div>
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-md bg-[#E6F4EF] text-[#0D7A5F] flex items-center justify-center shrink-0">
                                <i data-lucide="scan-barcode" class="w-4 h-4"></i>
                            </span>
                            <div class="leading-tight min-w-0">
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-[#647C76]">Scanner Barcode</span>
                                <span class="block tabular-nums text-xs font-bold text-[#0D2620] truncate" x-text="lastScanText"></span>
                            </div>
                        </div>
                        <button type="button" @click="scanNextSample()" class="px-3 py-1.5 rounded-lg bg-[#0D7A5F] hover:bg-[#095C47] text-white text-xs font-bold shrink-0 transition">
                            Scan Barcode
                        </button>
                    </div>

                    <!-- 2. Catalog Grid with Live Stock Counter -->
                    <div class="mini-pos-catalog mb-3.5">
                        <template x-for="item in catalog" :key="item.sku">
                            <button type="button"
                                @click="scanItem(item)"
                                :disabled="item.stock <= 0"
                                :class="{
                                    'mini-pos-card': true,
                                    'is-scanned': lastScannedSku === item.sku,
                                    'is-out': item.stock <= 0
                                }">
                                <div class="flex items-center justify-between gap-2 w-full">
                                    <span class="text-[10px] tabular-nums font-bold text-[#647C76]" x-text="item.barcode"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="lastScannedSku === item.sku" x-transition.opacity class="stock-delta-pop">-1</span>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                            :class="item.stock <= 0 ? 'bg-rose-100 text-rose-700' : (item.stock <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-[#E6F4EF] text-[#0D7A5F]')"
                                            x-text="item.stock > 0 ? 'Stok: ' + item.stock : 'Habis'"></span>
                                    </div>
                                </div>
                                <div class="flex items-baseline justify-between gap-2 w-full">
                                    <span class="text-xs font-bold text-[#0D2620] truncate" x-text="item.name"></span>
                                    <span class="text-xs tabular-nums font-bold text-[#0D7A5F] shrink-0" x-text="rupiah(item.price)"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- 3. Fixed-Height Active Cart & Print Trigger -->
                    <div class="bg-white border border-[rgba(13,38,32,0.14)] rounded-[10px] p-3.5">
                        <div class="flex justify-between items-center text-[11px] pb-2 mb-2 border-b border-[rgba(13,38,32,0.08)]">
                            <span class="font-bold text-[#0D2620]">Keranjang Transaksi (<span x-text="totalItems"></span> item)</span>
                            <button type="button" @click="resetDemo()" class="text-[#0D7A5F] font-semibold hover:underline">Reset Demo</button>
                        </div>

                        <div class="pos-cart-items-box">
                            <template x-for="row in cart" :key="row.sku">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#E6F4EF] text-[#0D2620] text-[11px] font-medium">
                                    <b class="text-[#0D7A5F]" x-text="row.qty + 'x'"></b>
                                    <span x-text="row.name"></span>
                                </span>
                            </template>
                            <span x-show="cart.length === 0" class="text-[11px] text-[#647C76]">
                                Klik produk atau tombol "Scan Barcode" di atas.
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2.5 sm:gap-3">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-[#647C76]">Total Pembayaran</span>
                                <strong class="text-base font-extrabold tabular-nums text-[#0D2620]" x-text="rupiah(cartTotal)"></strong>
                            </div>
                            <button type="button" @click="printReceipt()" :disabled="cart.length === 0"
                                class="px-3.5 sm:px-4 py-2 rounded-[8px] bg-[#0D2620] hover:bg-[#143B32] disabled:opacity-40 text-[#34D399] text-xs font-bold transition flex items-center gap-1.5">
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                <span>Bayar &amp; Cetak Struk</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4. In-Container Overlay Receipt Printer (ZERO height jump) -->
                    <div x-show="printedReceipt" x-transition.opacity.duration.200ms x-cloak class="receipt-overlay-stage">
                        <div class="printer-slot-bezel"></div>
                        <div class="thermal-receipt-wrap">
                            <div class="thermal-receipt-sheet">
                                <div class="text-center border-b border-dashed border-slate-300 pb-1.5 mb-2">
                                    <div class="font-bold text-xs">TANDHA POS · SURABAYA</div>
                                    <div class="text-[10px] text-slate-500" x-text="printedReceipt ? printedReceipt.invoice : ''"></div>
                                </div>
                                <div class="space-y-1 border-b border-dashed border-slate-300 pb-2 mb-2 max-h-[115px] overflow-y-auto">
                                    <template x-for="line in (printedReceipt ? printedReceipt.items : [])" :key="line.sku">
                                        <div class="flex justify-between gap-2">
                                            <span class="truncate" x-text="line.qty + 'x ' + line.name"></span>
                                            <span class="shrink-0" x-text="rupiah(line.qty * line.price)"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex justify-between font-bold">
                                    <span>TOTAL (QRIS · LUNAS)</span>
                                    <span x-text="printedReceipt ? rupiah(printedReceipt.total) : ''"></span>
                                </div>
                                <div class="mt-2.5 pt-2 border-t border-dashed border-slate-300 flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[10px] text-emerald-700 font-semibold">✓ Stok terpotong</span>
                                    <button type="button" @click="printedReceipt = null" class="px-2.5 py-1 rounded bg-[#0D7A5F] hover:bg-[#095C47] text-white text-[10px] font-bold transition">
                                        Transaksi Baru →
                                    </button>
                                </div>
                            </div>
                            <div class="thermal-receipt-teeth"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================================
     2. VISUALISASI FITUR TEMPLATE YANG DITAWARKAN
     ================================================================== -->
<section class="full-stage stage-capabilities" id="fitur-template">
    <div class="container">
        <div class="centered-header reveal-on-scroll">
            <span class="section-index">Visualisasi Paket Template Tandha</span>
            <h2 class="split-title">Ini yang langsung Anda dapatkan saat menyewa template POS Tandha.</h2>
            <p class="split-desc">
                Tiga modul utama yang langsung aktif di akun Anda begitu berlangganan—siap untuk kasir harian, kontrol stok cabang, dan rekap laba kotor.
            </p>
        </div>

        <div class="capability-grid-3">
            <!-- Visual Card 1 -->
            <article class="capability-card reveal-on-scroll">
                <div class="bg-[#0D2620] text-white rounded-[10px] p-4 mb-5 border border-[rgba(52,211,153,0.22)]">
                    <div class="flex justify-between items-center text-[10px] text-[#34D399] font-semibold mb-2.5">
                        <span>MODUL KASIR POS</span>
                        <span>STRUK 80MM</span>
                    </div>
                    <div class="space-y-1.5 text-xs tabular-nums">
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">#INV-A4F1</span>
                            <span class="text-[#34D399] font-bold">Rp 44.000</span>
                        </div>
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">Hold Meja #08</span>
                            <span class="text-white">Tersimpan</span>
                        </div>
                    </div>
                </div>
                <h3>1. Layar Kasir &amp; Hold Struk</h3>
                <p>
                    Scan barcode SKU cepat, simpan transaksi tertunda (Hold), diskon item/nota, serta cetak struk thermal 80mm.
                </p>
            </article>

            <!-- Visual Card 2 -->
            <article class="capability-card reveal-on-scroll delay-1">
                <div class="bg-[#0D2620] text-white rounded-[10px] p-4 mb-5 border border-[rgba(52,211,153,0.22)]">
                    <div class="flex justify-between items-center text-[10px] text-[#34D399] font-semibold mb-2.5">
                        <span>STOK MULTI-OUTLET</span>
                        <span>STOK TERKUNCI</span>
                    </div>
                    <div class="space-y-1.5 text-xs tabular-nums">
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">Outlet Pusat</span>
                            <span class="text-white">120 Pcs</span>
                        </div>
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">Mutasi Cabang</span>
                            <span class="text-[#34D399]">+10 Pack</span>
                        </div>
                    </div>
                </div>
                <h3>2. Kontrol Stok &amp; Transfer Cabang</h3>
                <p>
                    Setiap transaksi mengunci baris stok agar tidak minus, lengkap dengan riwayat keluar-masuk dan transfer antar-cabang.
                </p>
            </article>

            <!-- Visual Card 3 -->
            <article class="capability-card reveal-on-scroll delay-2">
                <div class="bg-[#0D2620] text-white rounded-[10px] p-4 mb-5 border border-[rgba(52,211,153,0.22)]">
                    <div class="flex justify-between items-center text-[10px] text-[#34D399] font-semibold mb-2.5">
                        <span>AUDIT SHIFT &amp; HPP</span>
                        <span>OTOMATIS</span>
                    </div>
                    <div class="space-y-1.5 text-xs tabular-nums">
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">Selisih Laci</span>
                            <span class="text-[#34D399] font-bold">Rp 0 (Cocok)</span>
                        </div>
                        <div class="flex justify-between bg-[#143B32] px-2.5 py-1.5 rounded">
                            <span class="text-[#B8D0C9]">Margin Laba</span>
                            <span class="text-white font-bold">48.4%</span>
                        </div>
                    </div>
                </div>
                <h3>3. Tutup Shift &amp; Laporan Laba</h3>
                <p>
                    Wajib isi uang fisik di akhir shift untuk mencegah selisih laci, ditambah laporan HPP dan margin laba kotor per SKU.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- ==================================================================
     3. SIMULATOR CARA KERJA (SPACIOUS 2-TIER RIGHT DIAGRAM)
     ================================================================== -->
<section class="full-stage stage-alt" x-data="posFlowSimulator()">
    <div class="container">
        <div class="centered-header reveal-on-scroll">
            <span class="section-index">Simulasi Cara Kerja Tandha</span>
            <h2 class="split-title">Klik skenario di toko Anda untuk melihat alur pemrosesan sistem.</h2>
            <p class="split-desc">
                Dari aksi kasir di depan hingga pembukuan pemilik di belakang, semuanya terhubung otomatis.
            </p>
        </div>

        <div class="pos-flow-grid reveal-on-scroll">
            <!-- Left Selector Buttons -->
            <div class="pos-scenario-list">
                <template x-for="sc in scenarios" :key="sc.key">
                    <button type="button" @click="activeKey = sc.key" :class="activeKey === sc.key ? 'pos-scenario-btn active' : 'pos-scenario-btn'">
                        <span class="pos-scenario-idx" x-text="sc.idx"></span>
                        <div>
                            <h3 x-text="sc.name"></h3>
                            <p x-text="sc.shortDesc"></p>
                        </div>
                    </button>
                </template>
            </div>

            <!-- Right Spacious 2-Tier Node Flow Panel -->
            <div class="pos-flow-panel">
                <div class="flex flex-wrap items-start justify-between gap-2.5 sm:gap-4 pb-4 border-b border-[rgba(52,211,153,0.18)]">
                    <div class="min-w-0">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#34D399] block mb-1" x-text="current.tag"></span>
                        <h3 class="text-lg sm:text-xl font-bold text-white leading-snug" x-text="current.headline"></h3>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#34D399] bg-[rgba(52,211,153,0.12)] border border-[rgba(52,211,153,0.3)] px-3 py-1 rounded-full shrink-0">
                        <i class="live-pulse-dot"></i> Live Sync
                    </span>
                </div>

                <!-- Tier 1: Input -> Engine -->
                <div>
                    <div class="flow-tier-top">
                        <div class="flow-node-box">
                            <span class="flow-node-kicker">01 · Input Lapangan</span>
                            <strong class="flow-node-title" x-text="current.inTitle"></strong>
                            <span class="flow-node-sub" x-text="current.inSub"></span>
                        </div>

                        <svg width="36" height="16" viewBox="0 0 36 16" fill="none" aria-hidden="true">
                            <line x1="0" y1="8" x2="29" y2="8" stroke="#34D399" stroke-width="2" class="arch-flow-line" />
                            <circle cx="31" cy="8" r="3" fill="#34D399" class="arch-pulse-ring" />
                        </svg>

                        <div class="flow-node-box core">
                            <span class="flow-node-kicker">02 · Tandha Engine</span>
                            <strong class="flow-node-title" x-text="current.midTitle"></strong>
                            <span class="flow-node-sub" x-text="current.midSub"></span>
                            <span class="flow-node-pill" x-text="current.midBadge"></span>
                        </div>
                    </div>

                    <div class="flow-tier-down" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <line x1="12" y1="0" x2="12" y2="18" stroke="#34D399" stroke-width="2" class="arch-flow-line" />
                            <circle cx="12" cy="20" r="3" fill="#34D399" class="arch-pulse-ring" />
                        </svg>
                    </div>

                    <!-- Tier 2: Dual Output Cards -->
                    <div class="flow-tier-outputs">
                        <div class="flow-node-box">
                            <span class="flow-node-kicker">Output 01</span>
                            <strong class="flow-node-title" x-text="current.out1Title"></strong>
                            <span class="flow-node-sub" x-text="current.out1Metric"></span>
                        </div>
                        <div class="flow-node-box">
                            <span class="flow-node-kicker">Output 02</span>
                            <strong class="flow-node-title" x-text="current.out2Title"></strong>
                            <span class="flow-node-sub" x-text="current.out2Metric"></span>
                        </div>
                    </div>
                </div>

                <div class="text-xs sm:text-sm text-[#B8D0C9] leading-relaxed p-3.5 rounded-[8px] bg-white/5 border border-[rgba(52,211,153,0.16)]" x-text="current.summary"></div>

                <div class="pt-3 border-t border-[rgba(52,211,153,0.16)] flex flex-wrap items-center justify-between gap-3 text-xs">
                    <span class="text-[#B8D0C9]">Data tiap entitas bisnis dipisahkan ketat</span>
                    <a :href="current.href" class="text-[#34D399] font-semibold hover:underline" x-text="current.linkText"></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================================
     4. LAYANAN SALING MELENGKAPI (LANGGANAN PAKET & KONSULTASI FITUR)
     ================================================================== -->
<section class="full-stage stage-capabilities">
    <div class="container">
        <div class="stage-top-bar reveal-on-scroll">
            <div>
                <span class="section-index">Fleksibilitas Bisnis Tandha</span>
                <h2 class="split-title" style="margin-bottom: 0;">Gunakan paket siap pakai &amp; ajukan update fitur sesuai SOP toko Anda.</h2>
            </div>
            <a href="/harga" class="link-arrow">Bandingkan semua paket →</a>
        </div>

        <div class="dual-path-grid reveal-on-scroll">
            <article class="dual-path-card light-card">
                <div>
                    <span class="section-index">Langganan Paket Tandha POS</span>
                    <h3 class="text-2xl font-bold text-[#0D2620] mb-3">Siap Pakai Mulai Rp 99rb/bln</h3>
                    <p class="text-sm text-[#3D544E] leading-relaxed mb-5">
                        Cocok untuk kafe, toko retail, dan outlet yang langsung butuh kasir cepat, kontrol tombol oleh Owner, pengaturan per cabang, tutup shift, serta laporan laba kotor.
                    </p>
                    <ul class="space-y-2 text-sm text-[#0D2620]">
                        <li>— Paket Starter, Business &amp; Pro Enterprise</li>
                        <li>— Jalan di browser kasir &amp; Android WebAPK</li>
                        <li>— Aktivasi &amp; nota lunas resmi via portal Akun Saya</li>
                    </ul>
                </div>
                <div>
                    <a href="/harga" class="btn btn-jade w-full">Lihat Harga Paket →</a>
                </div>
            </article>

            <article class="dual-path-card dark-card">
                <div>
                    <span class="section-index section-index-light">Konsultasi &amp; Request Penambahan Fitur</span>
                    <h3 class="text-2xl font-bold text-white mb-3">Tersedia Sebelum &amp; Setelah Langganan</h3>
                    <p class="text-sm text-[#B8D0C9] leading-relaxed mb-5">
                        Butuh potongan bahan baku resep, harga grosir bertingkat, atau penyesuaian alur cabang khusus? Ajukan konsultasi dan langsung terhubung ke WhatsApp tim SEKA GROUP.
                    </p>
                    <ul class="space-y-2 text-sm text-[#F4FAF8]">
                        <li>— Konsultasi bebas sebelum maupun sesudah berlangganan</li>
                        <li>— Terjadwal rapi minimal H-3 di jam layanan SEKA GROUP</li>
                        <li>— Didampingi langsung tim pengembang Surabaya</li>
                    </ul>
                </div>
                <div>
                    <a href="/contact" class="btn btn-mint w-full">Ajukan Konsultasi &amp; Request Fitur →</a>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ==================================================================
     5. CLOSING LAUNCH BANNER
     ================================================================== -->
<section class="full-stage stage-closing">
    <div class="hero-cursor-glow" style="--mx: 82%; --my: 50%;" aria-hidden="true"></div>
    <div class="container closing-banner reveal-on-scroll" style="position: relative; z-index: 2;">
        <div class="max-w-2xl">
            <span class="section-index section-index-light">Siap Memulai?</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight mb-3">
                Tertibkan meja kasir dan stok cabang Anda bersama Tandha.
            </h2>
            <p class="text-sm sm:text-base text-[#B8D0C9]">
                Daftarkan akun bisnis dalam 3 langkah singkat untuk mulai berlangganan maupun mengajukan konsultasi penambahan fitur kapan saja.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/login?tab=register&redirect={{ urlencode('/dashboard?tab=billing') }}" class="btn btn-mint btn-lg">Daftar Bisnis Baru →</a>
            <a href="/harga" class="btn btn-ghost-light btn-lg">Lihat Harga Paket</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
function heroTemplateDemo() {
    return {
        laserActive: false,
        lastScannedSku: 'KPS-01',
        lastScanText: '8991002 • Kopi Susu Aren (Stok -1)',
        scanIdx: 1,
        printedReceipt: null,
        catalog: [
            { sku: 'KPS-01', barcode: '8991002', name: 'Kopi Susu Aren', price: 22000, stock: 24, initialStock: 25 },
            { sku: 'MTC-02', barcode: '8991004', name: 'Matcha Latte', price: 25000, stock: 18, initialStock: 18 },
            { sku: 'CRS-03', barcode: '8991006', name: 'Butter Croissant', price: 24000, stock: 8, initialStock: 8 },
            { sku: 'CLY-04', barcode: '8997701', name: 'Matte Clay 100g', price: 85000, stock: 6, initialStock: 6 },
        ],
        cart: [
            { sku: 'KPS-01', name: 'Kopi Susu Aren', price: 22000, qty: 1 },
        ],
        scanNextSample() {
            const item = this.catalog[this.scanIdx % this.catalog.length];
            this.scanIdx += 1;
            if (item.stock > 0) {
                this.scanItem(item);
            } else {
                const avail = this.catalog.find((c) => c.stock > 0);
                if (avail) this.scanItem(avail);
            }
        },
        scanItem(item) {
            if (item.stock <= 0) return;
            this.printedReceipt = null;
            item.stock -= 1;
            this.lastScannedSku = item.sku;
            this.lastScanText = `${item.barcode} • ${item.name} (Stok -> ${item.stock})`;

            this.laserActive = false;
            setTimeout(() => { this.laserActive = true; }, 10);
            setTimeout(() => { this.laserActive = false; }, 480);

            const found = this.cart.find((c) => c.sku === item.sku);
            if (found) {
                found.qty += 1;
            } else {
                this.cart.push({ sku: item.sku, name: item.name, price: item.price, qty: 1 });
            }
        },
        printReceipt() {
            if (!this.cart.length) return;
            const code = Math.floor(1000 + Math.random() * 9000);
            this.printedReceipt = {
                invoice: `#INV-01-${code} · KASIR: FRONTDESK`,
                items: this.cart.map((c) => ({ ...c })),
                total: this.cartTotal,
            };
            this.cart = [];
        },
        resetDemo() {
            this.printedReceipt = null;
            this.catalog.forEach((c) => { c.stock = c.initialStock; });
            this.catalog[0].stock = this.catalog[0].initialStock - 1;
            this.cart = [{ sku: 'KPS-01', name: 'Kopi Susu Aren', price: 22000, qty: 1 }];
            this.lastScannedSku = 'KPS-01';
            this.lastScanText = '8991002 • Kopi Susu Aren (Siap Scan)';
        },
        get totalItems() {
            return this.cart.reduce((sum, r) => sum + r.qty, 0);
        },
        get cartTotal() {
            return this.cart.reduce((sum, r) => sum + (r.price * r.qty), 0);
        },
        rupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        }
    };
}

function posFlowSimulator() {
    return {
        activeKey: 'checkout',
        scenarios: [
            {
                key: 'checkout',
                idx: '01',
                name: 'Checkout Ramai & Anti Stok Minus',
                shortDesc: 'Dua kasir melayani pembeli bersamaan tanpa risiko tabrakan jumlah stok.',
                tag: 'Alur 01 · Checkout & Row Lock',
                headline: 'Setiap struk terbit mengunci baris stok agar tidak pernah minus.',
                inTitle: 'Kasir Frontdesk',
                inSub: 'Barcode SKU • Hold Struk',
                midTitle: 'Lock For Update',
                midSub: 'Validasi Qty & Potong Stok',
                midBadge: 'Atomic DB Transaction',
                out1Title: 'Kartu Mutasi Stok',
                out1Metric: 'Jejak keluar tercatat',
                out2Title: 'Struk Thermal 80mm',
                out2Metric: 'Cetak & simpan riwayat',
                summary: 'Saat transaksi diproses, database mengunci stok produk per outlet secara atomik sehingga tidak ada stok minus walau ramai.',
                href: '/fitur#kasir',
                linkText: 'Lihat Modul Kasir →',
            },
            {
                key: 'shift',
                idx: '02',
                name: 'Tutup Shift & Audit Laci Kas',
                shortDesc: 'Memastikan uang tunai di laci cocok 100% dengan catatan penjualan shift.',
                tag: 'Alur 02 · Rekonsiliasi Shift',
                headline: 'Wajib hitung uang fisik saat tutup shift untuk deteksi selisih instan.',
                inTitle: 'Tutup Shift Kasir',
                inSub: 'Input Uang Fisik Laci',
                midTitle: 'Hitung Expected Cash',
                midSub: 'Modal Awal + Tunai Shift',
                midBadge: 'Cek Selisih (Variance)',
                out1Title: 'Laporan Shift Valid',
                out1Metric: 'Tunai vs QRIS terpisah',
                out2Title: 'Log Audit Pemilik',
                out2Metric: 'Selisih langsung terlihat',
                summary: 'Kasir memasukkan uang fisik di akhir shift, sistem membandingkannya otomatis dengan kas seharusnya sebelum shift ditutup.',
                href: '/fitur#kasir',
                linkText: 'Pelajari Kontrol Shift →',
            },
            {
                key: 'branch',
                idx: '03',
                name: 'Multi-Outlet & Paket / Custom',
                shortDesc: 'Satu akun mengelola beberapa cabang atau konsultasi aturan unik ke SEKA GROUP.',
                tag: 'Alur 03 · Akun Terisolasi',
                headline: 'Mulai dengan paket template siap pakai atau custom workflow sesuai SOP.',
                inTitle: 'Portal Akun Klien',
                inSub: 'NIK Unik • Multi-Cabang',
                midTitle: 'Isolasi Tenant & HPP',
                midSub: 'Transfer Stok & Margin',
                midBadge: 'Template atau Custom POS',
                out1Title: 'Dashboard Laba Kotor',
                out1Metric: 'Pantauan real-time cabang',
                out2Title: 'Tiket Konsultasi',
                out2Metric: 'Jadwal analis tercatat',
                summary: 'Setiap bisnis terisolasi rapi dalam ruang data masing-masing—bisa langsung sewa bulanan atau lanjut ke sesi custom POS.',
                href: '/harga',
                linkText: 'Bandingkan Paket & Custom →',
            }
        ],
        get current() {
            return this.scenarios.find((s) => s.key === this.activeKey) || this.scenarios[0];
        }
    };
}
</script>
@endpush
