@extends('layouts.site')

@section('title', 'Tentang Kami — Tandha by SEKA GROUP')

@section('content')

<!-- PAGE HEADER -->
<section class="inner-header">
    <div class="container inner-header-grid reveal-on-scroll is-visible">
        <div>
            <span class="section-index section-index-light">Tentang Tandha · by SEKA GROUP</span>
            <h1>Penanda transaksi kasir dan pembukuan stok cabang yang tertib bagi usaha Indonesia.</h1>
        </div>
        <p>
            Tandha dikembangkan di Surabaya sebagai produk Point of Sale mandiri untuk membantu toko retail, F&amp;B, dan usaha multi-cabang merapikan operasional hariannya.
        </p>
    </div>
</section>

<!-- STORY -->
<section class="inner-content" style="border-bottom: 1px solid var(--line-light);">
    <div class="container">
        <div class="about-story-grid reveal-on-scroll">
            <div class="about-logo-frame">
                <img src="/assets/Logo%20Tandha.svg" alt="Lambang Tandha POS" class="mark-lg">
                <img src="/assets/tandha-text.svg" alt="Tandha by SEKA GROUP" class="text-lg">
            </div>

            <div class="about-prose">
                <span class="section-index">Identitas Produk</span>
                <h2 class="split-title">Makna di balik nama Tandha</h2>
                <p>
                    Dalam kosakata Jawa, <strong>Tandha</strong> bermakna tanda bukti, cap pengesahan, atau penanda yang sah. Setiap nota penjualan, mutasi barang antar-cabang, dan penutupan laci kasir semestinya meninggalkan jejak bukti yang bersih dan mudah diaudit.
                </p>
                <p>
                    Dikembangkan sebagai produk POS tersendiri <strong>by SEKA GROUP</strong>, Tandha menggabungkan kemudahan paket kasir siap sewa bulanan dengan fleksibilitas layanan konsultasi penyesuaian alur usaha apabila toko Anda membutuhkan aturan operasional khusus.
                </p>
            </div>
        </div>

        <div class="capability-grid-3">
            <div class="capability-card reveal-on-scroll">
                <span class="capability-icon"><i data-lucide="database" class="w-5 h-5"></i></span>
                <h3>Isolasi Akun Per Bisnis</h3>
                <p>Setiap pemilik bisnis beroperasi dalam ruang data sendiri dengan validasi NIK unik, email, WhatsApp, serta proteksi anti-bot Cloudflare.</p>
            </div>
            <div class="capability-card reveal-on-scroll delay-1">
                <span class="capability-icon"><i data-lucide="receipt" class="w-5 h-5"></i></span>
                <h3>Transparan di Kasir &amp; Laba</h3>
                <p>Stok terkunci otomatis saat checkout, tutup shift mencatat uang fisik laci, dan pemilik langsung melihat HPP serta margin kotor per cabang.</p>
            </div>
            <div class="capability-card reveal-on-scroll delay-2">
                <span class="capability-icon"><i data-lucide="sliders" class="w-5 h-5"></i></span>
                <h3>Siap Pakai Maupun Custom</h3>
                <p>Mulai cepat dengan paket bulanan Starter, Business, dan Enterprise, atau ajukan penyesuaian alur POS bersama analis Surabaya kami.</p>
            </div>
        </div>
    </div>
</section>

<!-- CLOSING CTA -->
<section class="full-stage stage-closing">
    <div class="container closing-banner reveal-on-scroll">
        <div class="max-w-2xl">
            <span class="section-index section-index-light">Langkah Berikutnya</span>
            <h2 class="text-3xl font-bold text-white leading-tight mb-3">
                Siap merapikan kasir dan kontrol stok cabang bersama Tandha?
            </h2>
            <p class="text-sm sm:text-base text-[#B8D0C9]">
                Pilih paket langganan bulanan atau jadwalkan konsultasi kebutuhan toko Anda melalui portal akun Tandha.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/harga" class="btn btn-mint btn-lg">Lihat Harga Paket →</a>
            <a href="/contact" class="btn btn-ghost-light btn-lg">Jadwalkan Konsultasi →</a>
        </div>
    </div>
</section>

@endsection
