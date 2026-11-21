@extends('layouts.site')

@section('title', 'Karya Sistem — Seka Yasa POS Custom Studio')

@section('content')

<!-- Dark Inner Header -->
<section class="bg-[#1A1208] text-[#F7F3EC] py-20 border-b border-[rgba(247,176,95,0.16)]">
    <div class="sy-container">
        <div class="sy-index text-[#F7B05F] mb-3">01 / Bedah Arsitektur Karya</div>
        <h1 class="sy-h1 max-w-3xl">Sistem Nyata yang Dibangun Sesuai Konteks Operasional</h1>
        <p class="mt-4 text-[1.05rem] leading-[1.7] text-[#C4B5A2] max-w-[680px]">
            Setiap modul dirancang untuk menyelesaikan akar masalah akurasi stok, rekonsiliasi shift kasir, hingga keamanan deployment produksi klien.
        </p>
    </div>
</section>

<!-- Case Study 1 (#FAF7F2) -->
<section class="bg-[#FAF7F2] py-20 border-b border-[rgba(119,82,41,0.18)]">
    <div class="sy-container space-y-10">
        <div class="sy-showcase">
            <div class="sy-showcase-media bg-[#23190D] border border-[rgba(247,176,95,0.22)] rounded-lg p-6 text-[#F7F3EC]">
                <div class="flex justify-between text-xs pb-3 mb-4 border-b border-[rgba(247,176,95,0.16)] text-[#F7B05F] font-semibold">
                    <span>SISTEM 01 — TERMINAL KASIR & KONTROL SHIFT</span>
                    <span class="font-mono">LOCK FOR UPDATE</span>
                </div>
                <div class="space-y-2.5 font-mono text-xs">
                    <div class="p-3 bg-[#1A1208] border border-[rgba(247,176,95,0.15)] rounded flex justify-between">
                        <span class="text-[#C4B5A2]">Shift Dibuka (Modal Laci):</span>
                        <span class="font-bold text-[#F7F3EC]">Rp 200.000</span>
                    </div>
                    <div class="p-3 bg-[#1A1208] border border-[rgba(247,176,95,0.15)] rounded flex justify-between">
                        <span class="text-[#C4B5A2]">Total Penjualan Tunai Shift:</span>
                        <span class="font-bold text-[#F7F3EC]">Rp 1.450.000</span>
                    </div>
                    <div class="p-3 bg-[#1A1208] border border-[rgba(247,176,95,0.15)] rounded flex justify-between">
                        <span class="text-[#C4B5A2]">Kas Seharusnya (Expected):</span>
                        <span class="font-bold text-[#F7B05F]">Rp 1.650.000</span>
                    </div>
                    <div class="p-3 bg-[#1A1208] border border-[rgba(247,176,95,0.15)] rounded flex justify-between">
                        <span class="text-[#C4B5A2]">Selisih Kas Fisik (Variance):</span>
                        <span class="font-bold text-[#F7B05F]">Rp 0 (MATCH)</span>
                    </div>
                </div>
            </div>

            <div class="sy-showcase-copy">
                <div class="sy-index text-[#775229] mb-2">Karya 01</div>
                <h2 class="sy-h2 text-[#1A1208]">POS Kasir Multi-Pembayaran & Rekonsiliasi Shift</h2>
                <div class="mt-6 border-t border-[rgba(119,82,41,0.18)] divide-y divide-[rgba(119,82,41,0.18)] text-sm">
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Kondisi Awal</span>
                        <span class="text-[#524538]">Stok produk sering minus akibat dua kasir menjual barang terakhir bersamaan dan kas laci selisih tanpa pertanggungjawaban shift.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Solusi Sistem</span>
                        <span class="text-[#524538]">Pessimistic locking stok dalam transaksi DB atomik, scan barcode instan, hitung kembalian otomatis, dan kalkulator selisih kas saat tutup shift.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Hasil di Lapangan</span>
                        <span class="text-[#1A1208] font-medium">Nol transaksi stok minus saat jam sibuk dan seluruh setoran kasir tercatat presisi pada penutupan toko.</span>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="/pos" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229]">Uji Coba Terminal Kasir →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Case Study 2 (#F3EDE4) -->
<section class="bg-[#F3EDE4] py-20 border-b border-[rgba(119,82,41,0.18)]">
    <div class="sy-container">
        <div class="sy-showcase reverse">
            <div class="sy-showcase-media bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] rounded-lg p-6">
                <div class="flex justify-between text-xs pb-3 mb-4 border-b border-[rgba(119,82,41,0.18)] text-[#775229] font-semibold">
                    <span>SISTEM 02 — REKAM MUTASI STOK & HPP</span>
                    <span class="font-mono text-[#1A1208]">AUDIT TRAIL</span>
                </div>
                <table class="w-full text-left text-xs">
                    <thead class="text-[#524538] border-b border-[rgba(119,82,41,0.18)]">
                        <tr>
                            <th class="py-2">Item / Outlet</th>
                            <th class="py-2">Harga Pokok</th>
                            <th class="py-2">Harga Jual</th>
                            <th class="py-2 text-right">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(119,82,41,0.12)] text-[#1A1208]">
                        <tr>
                            <td class="py-2.5 font-semibold">Caramel Macchiato Ice</td>
                            <td class="py-2.5 font-mono">Rp 12.500</td>
                            <td class="py-2.5 font-mono">Rp 28.000</td>
                            <td class="py-2.5 text-right font-bold text-[#775229]">Rp 15.500</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">Japanese Matcha Latte</td>
                            <td class="py-2.5 font-mono">Rp 11.000</td>
                            <td class="py-2.5 font-mono">Rp 25.000</td>
                            <td class="py-2.5 text-right font-bold text-[#775229]">Rp 14.000</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 font-semibold">Pain Au Chocolat</td>
                            <td class="py-2.5 font-mono">Rp 15.000</td>
                            <td class="py-2.5 font-mono">Rp 27.000</td>
                            <td class="py-2.5 text-right font-bold text-[#775229]">Rp 12.000</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sy-showcase-copy">
                <div class="sy-index text-[#775229] mb-2">Karya 02</div>
                <h2 class="sy-h2 text-[#1A1208]">Dashboard Analitik Owner & Audit Stok Cabang</h2>
                <div class="mt-6 border-t border-[rgba(119,82,41,0.18)] divide-y divide-[rgba(119,82,41,0.18)] text-sm">
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Kondisi Awal</span>
                        <span class="text-[#524538]">Pemilik usaha baru mengetahui omset dan margin keuntungan pada akhir bulan setelah merekap manual spreadsheet dari tiap cabang.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Solusi Sistem</span>
                        <span class="text-[#524538]">Panel multi-outlet terpusat dengan grafik penjualan 7 hari, kalkulasi HPP otomatis, dan pencatatan mutasi stok masuk/keluar/void secara audit.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Hasil di Lapangan</span>
                        <span class="text-[#1A1208] font-medium">Pemilik memantau laba kotor, produk terlaris, dan peringatan stok menipis per cabang secara langsung dari web.</span>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="/dashboard" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229]">Lihat Dashboard Analitik →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Case Study 3 (#FAF7F2) -->
<section class="bg-[#FAF7F2] py-20">
    <div class="sy-container">
        <div class="sy-showcase">
            <div class="sy-showcase-media bg-[#1A1208] border border-[rgba(247,176,95,0.22)] rounded-lg p-6 text-[#F7F3EC]">
                <div class="flex justify-between text-xs pb-3 mb-4 border-b border-[rgba(247,176,95,0.16)] text-[#F7B05F] font-semibold">
                    <span>SISTEM 03 — AKUN KLIEN & KONSULTASI CUSTOM</span>
                    <span class="font-mono">CLIENT ISOLATION</span>
                </div>
                <div class="space-y-2 text-xs font-mono">
                    <div class="p-2.5 bg-[#23190D] rounded flex justify-between">
                        <span>Opsi A: Sewa Template POS Standar</span>
                        <span class="text-[#F7B05F]">ACTIVE SUBSCRIPTION</span>
                    </div>
                    <div class="p-2.5 bg-[#23190D] rounded flex justify-between">
                        <span>Opsi B: Konsultasi Proses Bisnis Custom</span>
                        <span class="text-[#F7B05F]">SCHEDULED</span>
                    </div>
                    <div class="p-3 bg-black/50 rounded text-[11px] text-[#C4B5A2]">
                        &gt; Proposal SOW & UAT Checklist terisolasi per akun klien<br>
                        &gt; Verifikasi pembayaran terintegrasi menuju Midtrans
                    </div>
                </div>
            </div>

            <div class="sy-showcase-copy">
                <div class="sy-index text-[#775229] mb-2">Karya 03</div>
                <h2 class="sy-h2 text-[#1A1208]">Portal Akun Klien untuk Sewa Layanan & Konsultasi Custom</h2>
                <div class="mt-6 border-t border-[rgba(119,82,41,0.18)] divide-y divide-[rgba(119,82,41,0.18)] text-sm">
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Kondisi Awal</span>
                        <span class="text-[#524538]">Klien kesulitan mengetahui apakah proses usahanya cocok dengan template standar dan tidak memiliki ruang terpisah untuk memantau konsultasi atau UAT.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Solusi Sistem</span>
                        <span class="text-[#524538]">Halaman akun terautentikasi (/client) dengan dua jalur jelas: sewa paket template POS langsung atau pengajuan konsultasi custom beserta pelacakan UAT.</span>
                    </div>
                    <div class="py-3.5 grid grid-cols-[130px_1fr] gap-4">
                        <span class="font-bold text-[#775229]">Hasil di Lapangan</span>
                        <span class="text-[#1A1208] font-medium">Seluruh riwayat sewa paket, jadwal konsultasi custom, dan persetujuan UAT tercatat rapi dan terisolasi per akun bisnis.</span>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="/client" class="inline-block pb-1 text-sm font-bold text-[#1A1208] border-b-[1.5px] border-[#775229] hover:text-[#775229]">Buka Halaman Akun Klien →</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
