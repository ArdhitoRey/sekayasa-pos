@extends('layouts.site')

@section('title', 'Tentang Kami — Seka Yasa POS Custom Studio')

@section('content')

<!-- Dark Inner Header -->
<section class="bg-[#1A1208] text-[#F7F3EC] py-20 border-b border-[rgba(247,176,95,0.16)]">
    <div class="sy-container">
        <div class="sy-index text-[#F7B05F] mb-3">01 / Filosofi & Standar Rekayasa</div>
        <h1 class="sy-h1 max-w-3xl">Tata, Titi, Tentrem dalam Membangun Infrastruktur Bisnis</h1>
        <p class="mt-4 text-[1.05rem] leading-[1.7] text-[#C4B5A2] max-w-[680px]">
            Seka Yasa memadukan ketelitian analisis proses kerja Jawa dengan rekayasa perangkat lunak modern yang terisolasi dan dapat diaudit.
        </p>
    </div>
</section>

<!-- Principles Split (#FAF7F2) -->
<section class="bg-[#FAF7F2] py-24">
    <div class="sy-container">
        <div class="sy-split">
            <div class="sy-split-left">
                <div class="sy-index text-[#775229] mb-3">02 / Prinsip Kerja Kami</div>
                <h2 class="sy-h2 text-[#1A1208]">Tiga Landasan yang Menjaga Stabilitas Operasional Klien</h2>
                <p class="mt-4 text-[0.98rem] leading-[1.65] text-[#524538]">
                    Kami percaya perangkat lunak bisnis yang baik adalah sistem yang tidak menyulitkan kasir dan tidak membuat pemilik cemas akan kehilangan data.
                </p>
            </div>

            <div class="sy-split-right divide-y divide-[rgba(119,82,41,0.18)] border-t border-b border-[rgba(119,82,41,0.18)]">
                <div class="py-8 grid grid-cols-[48px_1fr] gap-4">
                    <span class="text-sm font-bold text-[#775229] pt-1">01</span>
                    <div>
                        <h3 class="sy-h3 text-[#1A1208]">Tata — Tertib Scope & Arsitektur Terisolasi</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Setiap proyek diawali dokumen kebutuhan tertulis (SOW), menggunakan repository private serta database terpisah per klien agar data usaha tidak pernah bercampur.
                        </p>
                    </div>
                </div>

                <div class="py-8 grid grid-cols-[48px_1fr] gap-4">
                    <span class="text-sm font-bold text-[#775229] pt-1">02</span>
                    <div>
                        <h3 class="sy-h3 text-[#1A1208]">Titi — Ketelitian Pengujian & Safety Gate</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Tidak ada rilis produksi yang dijalankan sebelum verifikasi pembayaran 100%, persetujuan UAT klien, dan pengambilan snapshot backup database otomatis.
                        </p>
                    </div>
                </div>

                <div class="py-8 grid grid-cols-[48px_1fr] gap-4">
                    <span class="text-sm font-bold text-[#775229] pt-1">03</span>
                    <div>
                        <h3 class="sy-h3 text-[#1A1208]">Tentrem — Infrastruktur Produksi yang Andal</h3>
                        <p class="mt-2 sy-body-point text-[#524538]">
                            Hosting gratis hanya kami gunakan untuk preview atau staging dummy; operasional toko nyata wajib berjalan di VPS/hosting berbayar dengan backup eksternal dan SLA jelas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hosting & Ownership Policy (#F3EDE4) -->
<section class="bg-[#F3EDE4] py-20 border-t border-[rgba(119,82,41,0.18)]">
    <div class="sy-container">
        <div class="max-w-3xl">
            <div class="sy-index text-[#775229] mb-3">03 / Transparansi Hosting & Kepemilikan Data</div>
            <h2 class="sy-h2 text-[#1A1208]">Data Penjualan Anda Sepenuhnya Milik Perusahaan Anda</h2>
            <p class="mt-4 sy-body-point text-[#524538]">
                Penyedia hanya mengelola server, keamanan TLS/SSL, serta pemeliharaan sistem sesuai kontrak bulanan; seluruh data transaksi dapat diekspor utuh kapan saja sesuai ketentuan kontrak.
            </p>
            <div class="mt-8">
                <a href="/contact" class="px-6 py-3 rounded-md bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] text-sm font-bold transition inline-block">
                    Diskusikan Arsitektur Usaha Anda
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
