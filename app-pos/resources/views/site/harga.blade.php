@extends('layouts.site')

@section('title', 'Harga & Paket Sewa — Tandha by SEKA GROUP')

@section('content')

<!-- PAGE HEADER -->
<section class="inner-header">
    <div class="container inner-header-grid reveal-on-scroll is-visible">
        <div>
            <span class="section-index section-index-light">Harga &amp; Paket Tandha POS</span>
            <h1>Paket langganan bulanan yang jelas — lengkap dengan dukungan konsultasi penambahan fitur.</h1>
        </div>
        <p>
            Pilih paket sewa sesuai jumlah outlet, staf kasir, dan kapasitas produk Anda. Semua aktivasi, nota lunas, dan konsultasi fitur dilakukan melalui akun bisnis terdaftar.
        </p>
    </div>
</section>

<!-- PRICING TABLE -->
<section class="inner-content" style="border-bottom: 1px solid var(--line-light);">
    <div class="container">
        <div class="stage-top-bar reveal-on-scroll">
            <div>
                <span class="section-index">Paket Template Tandha Siap Pakai</span>
                <h2 class="split-title" style="margin-bottom: 0;">Tiga skala paket untuk UMKM hingga jaringan cabang.</h2>
            </div>
            <span class="text-xs text-[#3D544E] bg-white border border-[rgba(13,38,32,0.14)] px-3.5 py-2 rounded-[8px]">
                Payment gateway <b>Midtrans</b> sedang pengajuan (aktivasi via konfirmasi Admin)
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
            @foreach($plans as $plan)
                @php
                    $isPopular = $plan->slug === 'business';
                    $features = is_array($plan->features) ? $plan->features : [];
                    $featureLabels = [
                        'pos_mobile' => 'Terminal Kasir Web & Android WebAPK',
                        'basic_inventory' => 'Manajemen Stok Produk & Barcode SKU',
                        'daily_report' => 'Rekap Penjualan Harian & Shift Kasir',
                        'multi_outlet' => 'Manajemen Multi-Outlet Terpusat',
                        'shift_management' => 'Kontrol Selisih Kas Tutup Shift',
                        'profit_report' => 'Laporan HPP & Margin Laba Kotor',
                        'stock_transfer' => 'Transfer Stok Antar-Cabang',
                        'all_features' => 'Seluruh Fitur Template POS Lengkap',
                        'api_access' => 'Akses API Integrasi & Ekspor Penuh',
                        'priority_support' => 'Pendampingan & Dukungan Prioritas',
                    ];
                @endphp

                <div class="{{ $isPopular ? 'bg-[#0D2620] text-white border-[#34D399]' : 'bg-white text-[#0D2620] border-[rgba(13,38,32,0.14)]' }} border rounded-[14px] p-5 sm:p-7 flex flex-col justify-between reveal-on-scroll min-w-0">
                    <div>
                        <div class="flex items-start justify-between gap-2 pb-5 border-b {{ $isPopular ? 'border-[rgba(52,211,153,0.2)]' : 'border-[rgba(13,38,32,0.1)]' }}">
                            <div class="min-w-0">
                                <span class="text-xs font-bold uppercase tracking-wider {{ $isPopular ? 'text-[#34D399]' : 'text-[#0D7A5F]' }}">
                                    Paket {{ $plan->name }}
                                </span>
                                <div class="mt-2 flex flex-wrap items-baseline gap-1">
                                    <span class="text-2xl sm:text-3xl font-extrabold tabular-nums">Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                                    <span class="text-xs {{ $isPopular ? 'text-[#B8D0C9]' : 'text-[#647C76]' }}">/ bulan</span>
                                </div>
                                <div class="mt-1 text-[11px] {{ $isPopular ? 'text-[#B8D0C9]' : 'text-[#647C76]' }}">
                                    Opsi berlangganan: 1, 3, 6 bulan s.d. 1, 2, &amp; 3 tahun
                                </div>
                            </div>
                            @if($isPopular)
                                <span class="px-2.5 py-1 bg-[#34D399] text-[#0D2620] text-[10px] font-extrabold rounded-[6px] uppercase">Populer</span>
                            @endif
                        </div>

                        <div class="py-5 border-b {{ $isPopular ? 'border-[rgba(52,211,153,0.12)]' : 'border-[rgba(13,38,32,0.08)]' }} grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="{{ $isPopular ? 'bg-[#143B32]' : 'bg-[#F7FAF9]' }} p-2.5 rounded-[8px]">
                                <span class="block font-extrabold text-base">{{ $plan->max_outlets }}</span>
                                <span class="{{ $isPopular ? 'text-[#B8D0C9]' : 'text-[#647C76]' }}">Outlet</span>
                            </div>
                            <div class="{{ $isPopular ? 'bg-[#143B32]' : 'bg-[#F7FAF9]' }} p-2.5 rounded-[8px]">
                                <span class="block font-extrabold text-base">{{ $plan->max_users }}</span>
                                <span class="{{ $isPopular ? 'text-[#B8D0C9]' : 'text-[#647C76]' }}">Akun Staf</span>
                            </div>
                            <div class="{{ $isPopular ? 'bg-[#143B32]' : 'bg-[#F7FAF9]' }} p-2.5 rounded-[8px]">
                                <span class="block font-extrabold text-base">{{ number_format($plan->max_products, 0, ',', '.') }}</span>
                                <span class="{{ $isPopular ? 'text-[#B8D0C9]' : 'text-[#647C76]' }}">Produk</span>
                            </div>
                        </div>

                        <ul class="py-6 space-y-3 text-sm {{ $isPopular ? 'text-[#F4FAF8]' : 'text-[#3D544E]' }}">
                            @foreach($features as $feat)
                                <li class="flex items-start gap-2.5">
                                    <span class="{{ $isPopular ? 'text-[#34D399]' : 'text-[#0D7A5F]' }} font-bold">—</span>
                                    <span>{{ $featureLabels[$feat] ?? ucwords(str_replace('_', ' ', $feat)) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @php
                        $billingTarget = '/dashboard?tab=billing&plan=' . ($plan->slug ?? $plan->id);
                    @endphp
                    <div class="pt-4" x-data="{ target: '{{ $billingTarget }}' }">
                        <a :href="(() => { try { const t = localStorage.getItem('pos_token'); const u = JSON.parse(localStorage.getItem('pos_user') || '{}'); if (t && u && u.role === 'owner') return target; } catch (_) {} return '/login?redirect=' + encodeURIComponent(target); })()"
                            href="/login?redirect={{ urlencode($billingTarget) }}"
                            class="w-full {{ $isPopular ? 'btn btn-mint' : 'btn btn-jade' }}">
                            Sewa Paket {{ $plan->name }} →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CUSTOM POS SPLIT -->
<section class="full-stage stage-alt" id="custom" style="padding: 5rem 0;">
    <div class="container asymmetric-split">
        <div class="split-left reveal-on-scroll">
            <span class="section-index">Konsultasi &amp; Update Fitur Tandha by SEKA GROUP</span>
            <h2 class="split-title">Butuh fitur tambahan atau penyesuaian khusus di luar template?</h2>
            <p class="split-desc mb-6">
                Anda dapat mengajukan konsultasi penambahan fitur kapan saja — baik sebelum maupun setelah berlangganan — langsung terhubung ke WhatsApp tim SEKA GROUP.
            </p>
            <a href="/contact" class="btn btn-jade">Ajukan Konsultasi &amp; Request Fitur →</a>
        </div>

        <div class="space-y-4">
            <div class="step-card reveal-on-scroll delay-1">
                <span class="step-num">Custom Logic</span>
                <h3>Pemotongan stok bahan baku &amp; resep</h3>
                <p>Setiap 1 menu terjual otomatis memotong gramatur kopi, susu, kemasan, atau bahan produksi secara presisi.</p>
            </div>
            <div class="step-card reveal-on-scroll delay-2">
                <span class="step-num">Custom Pricing</span>
                <h3>Harga grosir bertingkat &amp; aturan diskon khusus</h3>
                <p>Mendukung harga berbeda per kuantitas beli, kategori pelanggan anggota, atau komisi staf kapster/mekanik.</p>
            </div>
            <div class="step-card reveal-on-scroll delay-3">
                <span class="step-num">Proposal SOW</span>
                <h3>Penawaran biaya tetap &amp; uji UAT sebelum Go-Live</h3>
                <p>Biaya pengembangan satu kali dan dukungan bulanan disepakati di muka setelah sesi konsultasi di akun Tandha Anda.</p>
            </div>
        </div>
    </div>
</section>

@endsection
