@extends('layouts.app')

@section('title', 'Akun Klien & Penyewaan Layanan — Tandha by SekaYasa')

@section('content')
<div class="min-h-screen bg-[#F7FAF9] text-[#0D2620] flex flex-col" x-data="clientPortalApp()" x-init="initClientPortal()">

    <!-- Top Navigation Bar (Deep Espresso #0D2620) -->
    <header class="bg-[#0D2620] text-[#FFFFFF] border-b border-[rgba(51,65,85,0.75)] px-6 py-4 sticky top-0 z-30">
        <div class="w-[92%] max-w-[1540px] mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2 shrink-0">
                    <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="h-9 w-auto">
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-semibold text-[#34D399] tracking-wider uppercase">00 / Halaman Akun Klien</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 bg-[#34D399] text-[#0D2620] rounded">Sedang Login</span>
                    </div>
                    <h1 class="text-base font-bold text-[#FFFFFF] leading-tight mt-0.5">
                        <span x-text="user.name || 'Akun Klien'"></span> — <span class="text-[#CBD5E1]" x-text="business.name || ''"></span>
                    </h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <a href="/" class="inline-flex items-center h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-[#CBD5E1] hover:text-[#FFFFFF] font-semibold transition">
                    Beranda
                </a>
                <a href="/fitur" class="inline-flex items-center h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-[#CBD5E1] hover:text-[#FFFFFF] font-semibold transition">
                    Fitur POS
                </a>
                <a href="/harga" class="inline-flex items-center h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-[#CBD5E1] hover:text-[#FFFFFF] font-semibold transition">
                    Harga Paket
                </a>

                <div x-show="user.role === 'owner'" class="inline-flex items-center gap-1.5">
                    <button type="button" @click="showInstallGuideModal('webapk')"
                        class="inline-flex items-center gap-1.5 h-8 px-2.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md font-bold transition"
                        title="Pasang aplikasi kasir WebAPK / PWA langsung lewat browser">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                        <span>Install WebAPK</span>
                    </button>
                    <button type="button" @click="showInstallGuideModal('apk')"
                        class="inline-flex items-center gap-1.5 h-8 px-2.5 bg-[#143B32] border border-[#34D399]/40 hover:border-[#34D399] text-[#34D399] rounded-md font-bold transition"
                        title="Unduh file installer Android (.apk)">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Download .APK</span>
                    </button>
                </div>

                <!-- Operational Shortcuts (Unlocked if template_saas rented or custom project live; Owner gets Read-Only Preview POS) -->
                <template x-if="canAccessPosApp">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openPosPreviewModal()"
                            class="inline-flex items-center h-8 px-3.5 bg-[#34D399] hover:bg-[#10B981] text-[#0D2620] rounded-md font-bold transition"
                            title="Mode Preview Kasir (Read-Only): Tinjau tampilan kasir &amp; struk langsung di halaman ini">
                            Preview Kasir →
                        </button>
                        <a href="/dashboard" class="inline-flex items-center h-8 px-3.5 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] text-[#FFFFFF] rounded-md font-bold transition">
                            Dashboard Toko
                        </a>
                    </div>
                </template>

                <button @click="logout()" class="inline-flex items-center h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-[#CBD5E1] hover:text-rose-400 font-semibold transition">
                    Keluar
                </button>
            </div>
        </div>
    </header>

    <!-- Loading Overlay -->
    <div x-show="loading" class="flex-1 flex items-center justify-center py-24">
        <div class="text-sm font-semibold text-[#0D7A5F]">Memuat data akun Anda...</div>
    </div>

    <!-- Main Content -->
    <div x-show="!loading" class="flex-1" x-cloak>

        <!-- SECTION 01: LANGGANAN TANDHA POS & KONSULTASI PENAMBAHAN FITUR -->
        <section class="py-16 border-b border-[rgba(15,23,42,0.12)]">
            <div class="w-[92%] max-w-[1540px] mx-auto grid grid-cols-1 lg:grid-cols-[0.72fr_1.08fr] gap-12 items-start">

                <!-- Left Sticky Column: Status Akun & Penjelasan Template -->
                <div class="lg:sticky lg:top-24 space-y-6 max-w-[440px]">
                    <div>
                        <div class="text-[13px] font-semibold text-[#0D7A5F] tracking-wide mb-2">01 / Langganan &amp; Request Fitur</div>
                        <h2 class="text-3xl font-bold text-[#0D2620] leading-tight">
                            Kelola Langganan POS &amp; Konsultasi Penambahan Fitur
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-[#475569]">
                            Anda dapat langsung menyewa paket Tandha POS atau mengajukan konsultasi penambahan fitur <b>kapan saja — baik sebelum berlangganan maupun setelah aktif berlangganan</b>.
                        </p>
                    </div>

                    <!-- Account Current Status Box -->
                    <div class="bg-[#0D2620] text-[#FFFFFF] p-5 rounded-lg border border-[rgba(51,65,85,0.75)] space-y-3 text-xs">
                        <div class="flex justify-between items-center border-b border-[rgba(51,65,85,0.75)] pb-2.5">
                            <span class="text-[#CBD5E1] uppercase font-semibold">Status Layanan Akun</span>
                            <span class="font-bold text-[#34D399]" x-text="serviceStatusLabel"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#CBD5E1]">Penanggung Jawab:</span>
                            <span class="font-semibold" x-text="(user.name || '-') + (user.job_title ? ' (' + user.job_title + ')' : '')"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#CBD5E1]">Email Terdaftar:</span>
                            <span class="font-mono" x-text="user.email"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#CBD5E1]">Entitas Usaha:</span>
                            <span class="font-semibold" x-text="(business.legal_type ? business.legal_type + ' ' : '') + (business.name || '-')"></span>
                        </div>
                        <div class="flex justify-between" x-show="business.business_category || business.city">
                            <span class="text-[#CBD5E1]">Bidang &amp; Kota:</span>
                            <span class="text-right" x-text="[business.business_category, business.city].filter(Boolean).join(' • ')"></span>
                        </div>
                        <template x-if="subscriptionSummary && (business.service_type === 'template_saas' || subscriptionSummary.id)">
                            <div class="pt-3 border-t border-[rgba(51,65,85,0.75)] space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-[#CBD5E1]">Paket Langganan:</span>
                                    <b class="text-[#34D399]" x-text="subscriptionSummary.plan_name || '-'"></b>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#CBD5E1]">Mulai Aktif:</span>
                                    <span class="font-mono" x-text="subscriptionSummary.starts_at_formatted || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#CBD5E1]">Berlaku Sampai:</span>
                                    <b class="font-mono text-white" x-text="subscriptionSummary.expires_at_formatted || '-'"></b>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[#CBD5E1]">Sisa Masa Aktif:</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                        :class="subscriptionSummary.lifecycle_status === 'expired' ? 'bg-rose-500/20 text-rose-300' : (subscriptionSummary.is_expiring_soon ? 'bg-amber-400/20 text-amber-300' : 'bg-[#34D399]/20 text-[#34D399]')"
                                        x-text="subscriptionSummary.countdown_label || '-'"></span>
                                </div>
                                <div x-show="subscriptionSummary.expires_at" class="w-full h-1.5 bg-[#143B32] rounded-full overflow-hidden">
                                    <div class="h-full transition-all"
                                        :class="subscriptionSummary.lifecycle_status === 'expired' ? 'bg-rose-400' : (subscriptionSummary.is_expiring_soon ? 'bg-amber-400' : 'bg-[#34D399]')"
                                        :style="'width: ' + Math.min(100, Math.max(5, subscriptionSummary.progress_percent || 5)) + '%'"></div>
                                </div>
                                <div class="grid grid-cols-3 gap-1.5 pt-1 text-[10px] text-center">
                                    <div class="bg-[#143B32] rounded p-1.5">
                                        <div class="text-[#CBD5E1]">Outlet</div>
                                        <div class="font-mono font-bold text-white" x-text="(usage.outlets_count || 0) + '/' + (subscriptionSummary.max_outlets || '-')"></div>
                                    </div>
                                    <div class="bg-[#143B32] rounded p-1.5">
                                        <div class="text-[#CBD5E1]">Staf</div>
                                        <div class="font-mono font-bold text-white" x-text="(usage.users_count || 0) + '/' + (subscriptionSummary.max_users || '-')"></div>
                                    </div>
                                    <div class="bg-[#143B32] rounded p-1.5">
                                        <div class="text-[#CBD5E1]">Produk</div>
                                        <div class="font-mono font-bold text-white" x-text="(usage.products_count || 0) + '/' + (subscriptionSummary.max_products || '-')"></div>
                                    </div>
                                </div>
                                <div class="pt-2 flex gap-2">
                                    <button type="button" @click="openPosPreviewModal()" title="Mode Preview Kasir (Read-Only)" class="flex-1 py-2 text-center bg-[#34D399] hover:bg-[#10B981] text-[#0D2620] rounded-md font-bold transition">Preview Kasir</button>
                                    <a href="/dashboard" class="flex-1 py-2 text-center bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] text-[#FFFFFF] rounded-md font-bold transition">Dashboard</a>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Template POS Standard Capabilities & APK Guide Summary -->
                    <div class="bg-[#FFFFFF] p-5 rounded-lg border border-[rgba(15,23,42,0.12)] space-y-3 text-xs">
                        <div class="font-bold text-[#0D2620] uppercase tracking-wider text-[11px]">Cakupan Template POS Standar:</div>
                        <div class="space-y-1.5 text-[#475569]">
                            <div>• Katalog produk, SKU, barcode scanner &amp; kategori kustom</div>
                            <div>• Kasir cepat, diskon, PPN per cabang &amp; struk thermal</div>
                            <div>• Buka/tutup shift kasir dengan kontrol fitur oleh Owner</div>
                            <div>• Stok multi-outlet, penyesuaian stok &amp; laporan laba kotor</div>
                        </div>
                        <div x-show="user.role === 'owner'" class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-[11px] text-[#475569]">Pasang App Kasir di HP/Tablet:</span>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" @click="showInstallGuideModal('webapk')"
                                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-[#0D7A5F] hover:bg-[#095C47] text-white text-[11px] font-semibold transition">
                                    Install WebAPK (PWA)
                                </button>
                                <button type="button" @click="showInstallGuideModal('apk')"
                                    class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 text-[11px] font-medium transition">
                                    Download .APK
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Subscription + Always-Available Feature Consultation -->
                <div class="space-y-8">

                    <!-- BAGIAN A: LANGGANAN PAKET TEMPLATE POS -->
                    <div id="billing-section" class="bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-[rgba(15,23,42,0.12)]">
                            <div>
                                <span class="text-[11px] font-bold text-[#0D7A5F] uppercase tracking-wider">Langganan Paket Tandha POS Siap Pakai</span>
                                <h3 class="text-lg font-bold text-[#0D2620]" x-text="business.service_type === 'template_saas' ? 'Paket &amp; Masa Aktif Langganan Anda' : 'Pilih Paket POS untuk Diaktifkan'"></h3>
                            </div>
                            <span class="px-2.5 py-1 bg-[#FDF3EE] text-[#0D7A5F] text-xs font-semibold rounded">Nota Lunas Otomatis Tersedia</span>
                        </div>

                        <div class="mt-4 divide-y divide-[rgba(15,23,42,0.12)] border-b border-[rgba(15,23,42,0.12)]">
                            <template x-for="plan in plans" :key="plan.id">
                                <label class="py-3.5 flex flex-wrap items-center justify-between gap-4 cursor-pointer hover:bg-[#F7FAF9]/50 px-2 transition">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="selected_plan" :value="plan.id" x-model="selectedPlanId" class="mt-1 accent-[#0D7A5F]">
                                        <div>
                                            <div class="text-sm font-bold text-[#0D2620]" x-text="'Paket ' + plan.name"></div>
                                            <div class="text-xs text-[#475569] mt-0.5">
                                                Maks. <b x-text="plan.max_outlets"></b> Outlet • <b x-text="plan.max_users"></b> Akun Staf • <b x-text="plan.max_products"></b> Produk
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right font-mono">
                                        <span class="text-sm font-bold text-[#0D7A5F]" x-text="formatRupiah(plan.price_monthly)"></span>
                                        <span class="text-[11px] text-[#475569] block">/ bulan</span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                            <span class="text-xs text-[#475569]">Payment gateway <b>Midtrans</b> sedang dalam tahap aktivasi — tim admin dapat langsung mengaktifkan uji coba / paket untuk akun Anda.</span>
                            <a href="/contact" class="px-6 py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] text-[#F7FAF9] rounded-md text-xs font-bold transition">
                                Minta Aktivasi Uji Coba ke Admin →
                            </a>
                        </div>

                        <!-- Riwayat Tagihan Langganan (Filter + Table-Only Scroll + Canonical Pagination) -->
                        <div x-show="paymentHistory.length > 0" class="mt-6 pt-5 border-t border-slate-200">
                            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-[#0D2620]">Riwayat Tagihan &amp; Pembayaran</span>
                                        <span class="text-[11px] text-slate-500 ml-2" x-text="payPaginationRangeLabel"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="relative" @click.outside="if (openClientDropdown === 'payStatusFilter') openClientDropdown = null">
                                            <button type="button" @click="toggleClientDropdown('payStatusFilter')"
                                                class="px-2.5 py-1 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5 min-w-[130px] justify-between">
                                                <span x-text="activePayStatusLabel"></span>
                                                <svg class="w-3 h-3 text-slate-400 transition-transform" :class="openClientDropdown === 'payStatusFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            <div x-show="openClientDropdown === 'payStatusFilter'" x-transition class="absolute right-0 mt-1 w-40 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 text-[11px]">
                                                <template x-for="opt in payStatusOptions" :key="opt.value">
                                                    <button type="button" @click="payStatusFilter = opt.value; payPage = 1; openClientDropdown = null"
                                                        class="w-full px-3 py-1.5 text-left flex justify-between hover:bg-slate-50"
                                                        :class="payStatusFilter === opt.value ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                        <span x-text="opt.label"></span>
                                                        <span x-show="payStatusFilter === opt.value">✓</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text" x-model="paySearch" @input="payPage = 1" placeholder="Cari invoice / paket..."
                                                class="pl-2.5 pr-6 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] w-44 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600">
                                            <button x-show="paySearch" type="button" @click="paySearch = ''; payPage = 1" class="absolute right-1.5 top-1 text-slate-400 hover:text-slate-700 text-[11px]">✕</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="max-h-60 overflow-auto custom-scrollbar">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                            <tr>
                                                <th class="py-2 px-3.5">Invoice &amp; Nota</th>
                                                <th class="py-2 px-3.5">Paket &amp; Tanggal</th>
                                                <th class="py-2 px-3.5 text-right">Nominal</th>
                                                <th class="py-2 px-3.5 text-center">Status</th>
                                                <th class="py-2 px-3.5 text-right w-20">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="ph in paginatedPaymentHistory" :key="ph.id">
                                                <tr class="hover:bg-slate-50/70">
                                                    <td class="py-2.5 px-3.5">
                                                        <div class="font-mono font-semibold text-slate-900" x-text="ph.invoice_number"></div>
                                                        <div x-show="ph.receipt_number" class="text-[10px] font-mono text-[#0D7A5F]" x-text="'Nota: ' + ph.receipt_number"></div>
                                                    </td>
                                                    <td class="py-2.5 px-3.5">
                                                        <div class="font-medium text-slate-800" x-text="'Paket ' + (ph.plan ? ph.plan.name : (ph.plan_name_snapshot || '-'))"></div>
                                                        <div class="text-[11px] text-slate-400" x-text="'Dibuat: ' + formatDate(ph.created_at) + (ph.verified_at ? ' · Lunas: ' + formatDate(ph.verified_at) : '')"></div>
                                                    </td>
                                                    <td class="py-2.5 px-3.5 text-right font-mono font-semibold text-slate-900" x-text="formatRupiah(ph.amount)"></td>
                                                    <td class="py-2.5 px-3.5 text-center">
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                                                            :class="ph.status === 'verified' ? 'bg-emerald-50 text-emerald-700' : (ph.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600')"
                                                            x-text="ph.status"></span>
                                                    </td>
                                                    <td class="py-2.5 px-3.5 text-right whitespace-nowrap">
                                                        <button type="button" @click="openPayActionPopup(ph, $event)"
                                                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                            <span>Aksi</span>
                                                            <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="filteredPaymentHistory.length === 0">
                                                <td colspan="5" class="py-8 text-center text-xs text-slate-400">Tidak ada tagihan yang cocok dengan filter pencarian.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="bg-slate-50/60 border-t border-slate-200 px-3.5 py-2 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span>Tampilkan</span>
                                        <div class="relative" @click.outside="if (openClientDropdown === 'payPageSize') openClientDropdown = null">
                                            <button type="button" @click="toggleClientDropdown('payPageSize')"
                                                class="px-2.5 py-1 bg-white border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                                <span x-text="payPerPage + ' / hal'"></span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            <div x-show="openClientDropdown === 'payPageSize'" x-transition class="absolute bottom-full left-0 mb-1 w-28 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                                <template x-for="sz in [5, 10, 20, 50]" :key="'ps-' + sz">
                                                    <button type="button" @click="payPerPage = sz; payPage = 1; openClientDropdown = null"
                                                        class="w-full px-3 py-1.5 text-left flex justify-between hover:bg-slate-50"
                                                        :class="payPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                        <span x-text="sz + ' baris'"></span>
                                                        <span x-show="payPerPage === sz">✓</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <span class="hidden sm:inline" x-text="payPaginationRangeLabel"></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="if (payPage > 1) payPage--" :disabled="payPage <= 1"
                                            class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">
                                            ‹ Prev
                                        </button>
                                        <template x-for="(pg, idx) in getPageNumbers(payPage, payTotalPages)" :key="'cpg-' + idx + '-' + pg">
                                            <button type="button" @click="if (typeof pg === 'number') payPage = pg" :disabled="typeof pg !== 'number'"
                                                class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                                :class="payPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                                x-text="pg"></button>
                                        </template>
                                        <button type="button" @click="if (payPage < payTotalPages) payPage++" :disabled="payPage >= payTotalPages"
                                            class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">
                                            Next ›
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUBSCRIPTION PAYMENT INVOICE BOX (Midtrans Pending) -->
                        <template x-if="pendingPayment">
                            <div class="mt-6 bg-[#F7FAF9] border border-[#0D7A5F] rounded-lg overflow-hidden">
                                <div class="bg-[#0D2620] text-[#FFFFFF] px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-bold text-[#34D399] uppercase tracking-wider">Tagihan Langganan Menunggu Pembayaran / Verifikasi</span>
                                        <div class="text-xs font-mono" x-text="pendingPayment.invoice_number"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-[#34D399]">Status: Pending</span>
                                </div>

                                <div class="p-5 space-y-4 text-xs">
                                    <div class="bg-[#FFFFFF] p-4 rounded border border-[rgba(15,23,42,0.12)] space-y-2">
                                        <div class="flex justify-between text-[#475569]">
                                            <span>Paket Dipilih:</span>
                                            <b x-text="pendingPayment.plan ? pendingPayment.plan.name : '-'"></b>
                                        </div>
                                        <div class="flex justify-between text-[#475569]">
                                            <span>Metode Pembayaran:</span>
                                            <span class="font-semibold text-[#0D2620]">Midtrans (Dalam Pengajuan) / Konfirmasi Admin</span>
                                        </div>
                                        <div class="pt-2 border-t border-[rgba(15,23,42,0.12)] flex justify-between items-baseline">
                                            <span class="font-bold text-[#0D2620]">Total Tagihan:</span>
                                            <span class="text-lg font-bold font-mono text-[#0D7A5F]" x-text="formatRupiah(pendingPayment.amount)"></span>
                                        </div>
                                    </div>

                                    <p class="text-[11px] text-[#475569] leading-relaxed">
                                        Integrasi payment gateway <b>Midtrans</b> sedang dalam tahap pengajuan. Setelah pembayaran dikonfirmasi/diverifikasi oleh Super Admin, layanan kasir POS (`/pos`) dan dashboard (`/dashboard`) akan otomatis aktif di akun Anda.
                                    </p>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <button type="button" @click="manualCheckPayment()" class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition">
                                            Cek Status Pembayaran
                                        </button>
                                        <span class="text-[11px] text-[#475569]" x-text="'Berlaku hingga: ' + formatDate(pendingPayment.expires_at)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- BAGIAN B: REQUEST PENAMBAHAN FITUR & KONSULTASI SEKAYASA (SEBELUM MAUPUN SETELAH LANGGANAN) -->
                    <div id="consultation-section" class="bg-[#FDF3EE] border border-[rgba(15,23,42,0.12)] rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-[rgba(15,23,42,0.12)]">
                            <div>
                                <span class="text-[11px] font-bold text-[#0D7A5F] uppercase tracking-wider">Tersedia Sebelum &amp; Setelah Berlangganan</span>
                                <h3 class="text-lg font-bold text-[#0D2620]">Konsultasi &amp; Request Penambahan Fitur Tandha</h3>
                            </div>
                            <span class="px-2.5 py-1 bg-[#0D2620] text-[#34D399] text-xs font-semibold rounded">Terhubung ke WhatsApp SekaYasa</span>
                        </div>

                        <p class="mt-3 text-xs text-[#475569] leading-relaxed">
                            Punya kebutuhan alur khusus atau ingin menambahkan fitur baru ke software Tandha (baik sebelum menyewa paket maupun setelah aktif)? Isi jadwal di bawah (<b>minimal H-3 dari hari pengajuan</b>) dan permintaan Anda akan langsung diarahkan ke <b>WhatsApp tim SekaYasa</b> sekaligus tercatat di pengingat Super Admin.
                        </p>

                        <!-- Anti-Spam Rate Limit Banner -->
                        <div x-show="consultationGuard && !consultationGuard.can_submit" x-cloak
                            class="mt-4 p-4 bg-amber-50 border border-amber-300 rounded-md text-xs text-amber-950 space-y-2.5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-bold">Batas Pengiriman Konsultasi (Anti-Spam Aktif)</div>
                                    <p class="mt-0.5 text-[11px]" x-text="consultationGuard ? consultationGuard.reason : ''"></p>
                                    <p class="mt-1 text-[10px] text-amber-800" x-text="'Kuota pengajuan 7 hari terakhir: ' + (consultationGuard.weekly_used || 0) + ' / ' + (consultationGuard.weekly_limit || 2) + ' kali'"></p>
                                </div>
                            </div>
                            <div x-show="consultationGuard && consultationGuard.active_whatsapp_url">
                                <a :href="consultationGuard.active_whatsapp_url" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md font-semibold text-xs transition">
                                    Lanjutkan Chat WhatsApp SekaYasa →
                                </a>
                            </div>
                        </div>

                        <form @submit.prevent="submitConsultation()" class="mt-5 space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="relative" @click.outside="if (openClientDropdown === 'bizType') openClientDropdown = null">
                                    <label class="block font-semibold text-[#475569] mb-1">Sektor Usaha</label>
                                    <button type="button" @click="toggleClientDropdown('bizType')"
                                        class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                        <span class="truncate" x-text="consultForm.business_type"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openClientDropdown === 'bizType' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openClientDropdown === 'bizType'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="opt in businessTypeOptions" :key="opt">
                                            <button type="button" @click="consultForm.business_type = opt; openClientDropdown = null"
                                                class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50"
                                                :class="consultForm.business_type === opt ? 'bg-emerald-50/70 font-semibold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                                <span x-text="opt"></span>
                                                <span x-show="consultForm.business_type === opt">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative" @click.outside="if (openClientDropdown === 'scale') openClientDropdown = null">
                                    <label class="block font-semibold text-[#475569] mb-1">Skala Outlet &amp; Perangkat</label>
                                    <button type="button" @click="toggleClientDropdown('scale')"
                                        class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                        <span class="truncate" x-text="selectedScaleLabel"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openClientDropdown === 'scale' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openClientDropdown === 'scale'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                        <template x-for="opt in scaleOptions" :key="opt.value">
                                            <button type="button" @click="consultForm.scale = opt.value; openClientDropdown = null"
                                                class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50"
                                                :class="consultForm.scale === opt.value ? 'bg-emerald-50/70 font-semibold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                                <span x-text="opt.label"></span>
                                                <span x-show="consultForm.scale === opt.value">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div x-show="consultForm.business_type === 'Lainnya'" x-cloak>
                                <label class="block font-semibold text-[#475569] mb-1">Tulis Bidang Usaha Anda *</label>
                                <input type="text" x-model="consultForm.business_type_other" :required="consultForm.business_type === 'Lainnya'"
                                    placeholder="Contoh: Laundry Kiloan, Toko Bangunan, Klinik Kecantikan, Petshop"
                                    class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded text-[#0D2620]">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-[#475569] mb-1">No. WhatsApp PIC *</label>
                                    <input type="text" x-model="consultForm.phone" required placeholder="0812xxxxxxxx" class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded text-[#0D2620]">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#475569] mb-1">Tanggal Konsultasi (Min. H-3) *</label>
                                    <input type="date" x-model="consultForm.preferred_date" :min="minConsultDate" :max="maxConsultDate" required class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded text-[#0D2620]">
                                </div>
                                <div class="relative" @click.outside="if (openClientDropdown === 'consultTime') openClientDropdown = null">
                                    <label class="block font-semibold text-[#475569] mb-1">Jam Konsultasi (WIB) *</label>
                                    <button type="button" @click="toggleClientDropdown('consultTime')"
                                        class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                        <span x-text="consultForm.preferred_time + ' WIB'"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openClientDropdown === 'consultTime' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openClientDropdown === 'consultTime'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 max-h-48 overflow-y-auto custom-scrollbar">
                                        <template x-for="slot in consultTimeSlots" :key="slot">
                                            <button type="button" @click="consultForm.preferred_time = slot; openClientDropdown = null"
                                                class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50"
                                                :class="consultForm.preferred_time === slot ? 'bg-emerald-50/70 font-semibold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                                <span x-text="slot + ' WIB'"></span>
                                                <span x-show="consultForm.preferred_time === slot">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-[#475569]">
                                Pengajuan minimal <b>3 hari sebelum jadwal (H-3, mulai <span x-text="minConsultDate"></span>)</b> agar tim SekaYasa sempat mengulas &amp; menjawab request Anda. Jam layanan <b>09:00 – 16:00 WIB</b> (maks. 1 pengajuan / 24 jam).
                            </p>

                            <div>
                                <label class="block font-semibold text-[#475569] mb-1">Detail Penambahan Fitur / Penyesuaian Alur Usaha *</label>
                                <textarea x-model="consultForm.template_gap_notes" required minlength="15" rows="3" placeholder="Jelaskan fitur tambahan yang Anda butuhkan (minimal 15 karakter), contoh: pemotongan stok bahan baku resep otomatis per gramatur, harga grosir bertingkat, atau laporan khusus cabang." class="w-full px-3 py-2 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded text-[#0D2620]"></textarea>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="text-[11px] text-slate-500" x-text="'Kuota mingguan: ' + ((consultationGuard && consultationGuard.weekly_used) || 0) + ' / ' + ((consultationGuard && consultationGuard.weekly_limit) || 2)"></span>
                                <button type="submit" :disabled="submittingConsult || (consultationGuard && !consultationGuard.can_submit)"
                                    class="px-6 py-2.5 bg-[#0D2620] hover:bg-[#143B32] text-[#34D399] rounded-md text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed">
                                    <span x-show="!submittingConsult">Simpan Jadwal &amp; Kirim ke WhatsApp SekaYasa →</span>
                                    <span x-show="submittingConsult">Menyimpan &amp; Membuka WhatsApp...</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION 02: PELACAKAN KONSULTASI & PENGINGAT WHATSAPP AKUN SAYA -->
        <section id="consultation-history" class="bg-[#FFFFFF] py-16">
            <div class="w-[92%] max-w-[1540px] mx-auto space-y-6">
                <div>
                    <div class="text-[13px] font-semibold text-[#0D7A5F] tracking-wide mb-1">02 / Client Portal — Riwayat Konsultasi &amp; Request Fitur</div>
                    <h2 class="text-2xl font-bold text-[#0D2620]">Riwayat Konsultasi Penambahan Fitur Akun Anda</h2>
                    <p class="text-xs text-[#475569] mt-1">Seluruh pengajuan konsultasi &amp; request fitur akun Anda tersimpan di sini beserta status tindak lanjut WhatsApp dari tim SekaYasa.</p>
                </div>

                <!-- Empty State -->
                <div x-show="projects.length === 0" class="bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded-lg p-8 text-center text-xs text-[#475569]">
                    Anda belum memiliki pengajuan konsultasi atau request penambahan fitur. Gunakan form <b>Konsultasi &amp; Request Penambahan Fitur</b> di atas kapan saja (sebelum maupun setelah berlangganan) apabila bisnis Anda memerlukan penyesuaian tambahan.
                </div>

                <!-- Client Projects List -->
                <div class="space-y-6">
                    <template x-for="proj in projects" :key="proj.id">
                        <div class="bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] rounded-lg overflow-hidden">
                            <!-- Project Header -->
                            <div class="bg-[#0D2620] text-[#FFFFFF] p-5 flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <span class="text-[10px] font-semibold text-[#34D399] uppercase tracking-wider" x-text="proj.business_type"></span>
                                    <h3 class="text-lg font-bold mt-0.5" x-text="proj.business_name + ' (#KONSUL-' + proj.id + ')'"></h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-[#CBD5E1] uppercase block">Status Pengajuan</span>
                                    <span class="inline-block mt-1 px-3 py-1 bg-[#143B32] border border-[rgba(51,65,85,0.75)] text-[#34D399] rounded text-xs font-bold uppercase" x-text="formatStatus(proj.status)"></span>
                                </div>
                            </div>

                            <!-- Project Body -->
                            <div class="p-6 space-y-6 text-xs">
                                <!-- Feature Request & Consultation Schedule -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 bg-[#FFFFFF] rounded border border-[rgba(15,23,42,0.12)]">
                                        <span class="font-bold text-[#0D7A5F] uppercase text-[10px] block mb-1">Detail Request Penambahan Fitur</span>
                                        <p class="text-[#0D2620] leading-relaxed" x-text="proj.template_gap_notes || proj.notes || '-'"></p>
                                    </div>
                                    <div class="p-4 bg-[#FFFFFF] rounded border border-[rgba(15,23,42,0.12)] space-y-1.5">
                                        <span class="font-bold text-[#0D7A5F] uppercase text-[10px] block">Jadwal, Status WhatsApp &amp; PIC SekaYasa</span>
                                        <template x-if="proj.consultations && proj.consultations.length > 0">
                                            <div>
                                                <div class="font-bold text-[#0D2620]" x-text="'Jadwal: ' + formatDate(proj.consultations[0].scheduled_at) + (proj.consultation_time_slot ? ' (' + proj.consultation_time_slot + ')' : '')"></div>
                                                <div class="text-[#475569]" x-text="'PIC Analis: ' + proj.consultations[0].pic_name + (proj.subscription_plan_snapshot ? ' · ' + proj.subscription_plan_snapshot : '')"></div>
                                            </div>
                                        </template>
                                        <div class="pt-1 flex flex-wrap items-center justify-between gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                :class="proj.whatsapp_status === 'sent_from_sekayasa' || proj.whatsapp_status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                                x-text="proj.whatsapp_status === 'sent_from_sekayasa' ? '✓ Sudah Terkirim di WhatsApp (SekaYasa)' : (proj.whatsapp_status === 'completed' ? '✓ Selesai / Terjawab' : 'Menunggu Balasan WhatsApp SekaYasa')"></span>
                                            <a x-show="proj.whatsapp_url" :href="proj.whatsapp_url" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-medium transition">
                                                Buka WhatsApp →
                                            </a>
                                        </div>
                                        <div x-show="proj.whatsapp_sent_at || proj.reminder_notes" class="text-[11px] text-[#475569] pt-1 border-t border-slate-100">
                                            <div x-show="proj.whatsapp_sent_at" x-text="'Dikonfirmasi oleh ' + (proj.whatsapp_sent_by || 'SekaYasa') + ' pada ' + formatDate(proj.whatsapp_sent_at)"></div>
                                            <div x-show="proj.reminder_notes" class="text-slate-600 mt-0.5" x-text="'Catatan SekaYasa: ' + proj.reminder_notes"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Proposal & Payment Status (If Proposal Exists) -->
                                <template x-if="proj.current_proposal">
                                    <div class="p-4 bg-[#FDF3EE] rounded border border-[rgba(15,23,42,0.12)] flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <span class="text-[10px] font-bold text-[#0D7A5F] uppercase">Proposal & Biaya Pengembangan</span>
                                            <div class="text-sm font-bold text-[#0D2620] mt-0.5" x-text="proj.current_proposal.proposal_number + ' • ' + proj.current_proposal.timeline_weeks + ' Minggu'"></div>
                                            <div class="text-[#475569] mt-0.5" x-text="proj.current_proposal.deliverables"></div>
                                        </div>
                                        <div class="text-right font-mono">
                                            <div class="text-base font-bold text-[#0D2620]" x-text="formatRupiah(proj.current_proposal.development_cost)"></div>
                                            <div class="text-[11px] text-[#0D7A5F]" x-text="'Managed: ' + formatRupiah(proj.current_proposal.monthly_maintenance_cost) + '/bln'"></div>
                                        </div>
                                    </div>
                                </template>

                                <!-- UAT Client Sign-off Panel (OPS-026 / OPS-028) -->
                                <template x-if="proj.uat_cases && proj.uat_cases.length > 0">
                                    <div class="border border-[rgba(15,23,42,0.12)] rounded overflow-hidden">
                                        <div class="bg-[#FFFFFF] px-4 py-2.5 border-b border-[rgba(15,23,42,0.12)] flex justify-between items-center">
                                            <span class="font-bold text-[#0D2620] uppercase text-[11px]">Pengujian UAT Klien (User Acceptance Testing)</span>
                                            <span class="text-[11px] text-[#0D7A5F] font-semibold">Silakan verifikasi kesesuaian fitur custom</span>
                                        </div>
                                        <div class="divide-y divide-[rgba(15,23,42,0.12)]">
                                            <template x-for="tc in proj.uat_cases" :key="tc.id">
                                                <div class="p-3.5 flex flex-wrap items-center justify-between gap-4">
                                                    <div>
                                                        <div class="font-bold text-[#0D2620]" x-text="tc.test_title"></div>
                                                        <div class="text-[11px] text-[#475569] mt-0.5" x-text="'Skenario: ' + tc.scenario"></div>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <button type="button" @click="submitClientUat(tc.id, 'pass')" :class="tc.status === 'pass' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700'" class="inline-flex items-center px-2.5 py-1 rounded-md border text-[11px] font-medium transition">Pass</button>
                                                        <button type="button" @click="submitClientUat(tc.id, 'fail')" :class="tc.status === 'fail' ? 'bg-rose-600 border-rose-600 text-white' : 'bg-white border-rose-200 hover:bg-rose-50 hover:border-rose-300 text-rose-600'" class="inline-flex items-center px-2.5 py-1 rounded-md border text-[11px] font-medium transition">Fail</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Environment URLs (If Deployed) -->
                                <template x-if="proj.deployment_targets && proj.deployment_targets.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                        <template x-for="tgt in proj.deployment_targets" :key="tgt.id">
                                            <div class="p-3 bg-[#0D2620] text-[#FFFFFF] rounded flex items-center justify-between">
                                                <div>
                                                    <span class="text-[10px] uppercase font-bold text-[#34D399]" x-text="tgt.environment"></span>
                                                    <div class="font-mono text-[11px] text-[#CBD5E1]" x-text="tgt.host_url"></div>
                                                </div>
                                                <span class="text-[10px] font-bold uppercase text-[#34D399]" x-text="tgt.tls_ssl_active ? 'HTTPS AKTIF' : 'HTTP'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

    </div>

    <!-- Floating Action Popover Menu (Sama persis dengan Super Admin) -->
    <div x-show="showPayActionPopup" @click="showPayActionPopup = false" class="fixed inset-0 z-[60]" x-cloak></div>
    <div x-show="showPayActionPopup" x-transition.opacity.duration.120ms :style="payActionMenuStyle"
        class="fixed z-[70] w-52 bg-white rounded-lg border border-slate-200 shadow-lg py-1.5 text-xs" x-cloak>
        <div class="px-3 py-1.5 border-b border-slate-100">
            <div class="font-semibold text-slate-900 truncate font-mono" x-text="actionPayment ? actionPayment.invoice_number : ''"></div>
            <div class="text-[10px] text-slate-400 truncate" x-text="actionPayment ? (formatRupiah(actionPayment.amount) + ' · ' + (actionPayment.status || '').toUpperCase()) : ''"></div>
        </div>
        <button type="button" x-show="actionPayment && actionPayment.status === 'verified'" @click="runPayAction('receipt')"
            class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
            <span>Lihat &amp; Cetak Nota Lunas</span>
        </button>
        <button type="button" x-show="actionPayment && actionPayment.status === 'pending'" @click="runPayAction('check')"
            class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
            <span>Cek Status Pembayaran</span>
        </button>
        <button type="button" x-show="actionPayment && actionPayment.status === 'rejected'" @click="showPayActionPopup = false"
            class="w-full px-3 py-2 text-left text-slate-400 cursor-default">
            <span>Tagihan Ditutup / Digantikan</span>
        </button>
    </div>

    <!-- Modal Nota / Kwitansi Resmi Pembayaran Langganan -->
    <div x-show="showSubReceiptModal" @click.self="showSubReceiptModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#F7FAF9] rounded-lg max-w-xl w-full border border-slate-200 overflow-hidden shadow-xl flex flex-col max-h-[90vh]">
            <div class="bg-[#0D2620] text-white px-5 py-3.5 flex items-center justify-between shrink-0">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-[#34D399]">Bukti Pembayaran Resmi</div>
                    <h3 class="text-sm font-bold" x-text="'Nota Lunas ' + (activeReceipt.receipt_number || '')"></h3>
                </div>
                <button type="button" @click="showSubReceiptModal = false" class="text-slate-300 hover:text-white text-sm">✕</button>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-5">
                <div id="printable-sub-receipt" class="bg-white border border-slate-200 rounded-lg p-6 space-y-4 text-xs text-slate-800">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <div class="text-base font-bold text-[#0D2620]" x-text="activeReceipt.issuer_name || 'Tandha by SekaYasa'"></div>
                            <div class="text-[11px] text-slate-500" x-text="activeReceipt.issuer_tagline || ''"></div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase tracking-wider" x-text="activeReceipt.status || 'LUNAS'"></span>
                            <div class="font-mono font-bold text-slate-900 mt-1" x-text="activeReceipt.receipt_number"></div>
                            <div class="text-[10px] text-slate-400 font-mono" x-text="'Ref Invoice: ' + (activeReceipt.invoice_number || '-')"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] bg-slate-50 p-3.5 rounded border border-slate-200">
                        <div class="space-y-1">
                            <div class="text-slate-400 uppercase font-semibold text-[10px]">Diterima Dari</div>
                            <div class="font-bold text-slate-900" x-text="activeReceipt.business_name"></div>
                            <div x-text="'PIC: ' + (activeReceipt.owner_name || '-')"></div>
                            <div class="font-mono text-slate-600" x-text="activeReceipt.owner_email"></div>
                        </div>
                        <div class="space-y-1 sm:text-right">
                            <div class="text-slate-400 uppercase font-semibold text-[10px]">Detail Transaksi</div>
                            <div x-text="'Tanggal Lunas: ' + (activeReceipt.paid_at_formatted || '-')"></div>
                            <div x-text="'Metode: ' + (activeReceipt.payment_method || '-')"></div>
                            <div x-text="'NPWP: ' + (activeReceipt.npwp || '-')"></div>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-[11px]">
                                <tr>
                                    <th class="py-2 px-3">Layanan Paket Langganan</th>
                                    <th class="py-2 px-3">Masa Aktif</th>
                                    <th class="py-2 px-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-slate-900" x-text="'Sewa Bulanan Paket ' + (activeReceipt.plan_name || '-')"></div>
                                        <div class="text-[11px] text-slate-500" x-show="activeReceipt.plan_limits"
                                            x-text="activeReceipt.plan_limits ? ('Maks. ' + activeReceipt.plan_limits.max_outlets + ' Outlet · ' + activeReceipt.plan_limits.max_users + ' Staf · ' + activeReceipt.plan_limits.max_products + ' Produk') : ''"></div>
                                    </td>
                                    <td class="py-3 px-3 text-[11px] font-mono text-slate-600"
                                        x-text="(activeReceipt.billing_starts_at_formatted || '-') + ' s.d. ' + (activeReceipt.billing_expires_at_formatted || '-')"></td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900" x-text="formatRupiah(activeReceipt.amount)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                        <span class="font-bold text-slate-700">TOTAL DIBAYAR</span>
                        <span class="text-lg font-bold font-mono text-[#0D7A5F]" x-text="formatRupiah(activeReceipt.amount)"></span>
                    </div>

                    <div class="text-[10px] text-slate-400 text-center pt-2 border-t border-dashed border-slate-200">
                        Nota pembayaran ini diterbitkan secara elektronik oleh sistem Tandha by SekaYasa dan sah tanpa tanda tangan basah.
                    </div>
                </div>
            </div>
            <div class="px-5 py-3.5 bg-white border-t border-slate-200 flex justify-end gap-2 shrink-0">
                <button type="button" @click="showSubReceiptModal = false" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-md text-xs font-medium">Tutup</button>
                <button type="button" @click="window.print()" class="px-4 py-2 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold">Cetak / Simpan PDF</button>
            </div>
        </div>
    </div>

    <!-- Modal Overlay In-Page: Preview Layar Kasir POS (Owner Read-Only — Tanpa Pindah Halaman & Tanpa Logout) -->
    <div x-show="showPosPreviewModal" @click.self="closePosPreviewModal()" @keydown.window.escape="if (showPosPreviewModal) closePosPreviewModal()"
        class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs z-[80] flex items-center justify-center p-2 sm:p-5" x-cloak>
        <div class="bg-white rounded-xl w-full max-w-[1440px] h-[92vh] overflow-hidden border border-slate-200 shadow-2xl flex flex-col">
            <div class="shrink-0 bg-[#0D2620] text-white px-4 sm:px-5 py-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(51,65,85,0.75)]">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-8 h-8 rounded-md bg-[#143B32] border border-[rgba(51,65,85,0.75)] flex items-center justify-center text-[#34D399] shrink-0">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-white leading-none">Preview Layar Kasir POS</span>
                            <span class="px-2 py-0.5 rounded bg-amber-300 text-slate-950 text-[10px] font-bold uppercase tracking-wider">Mode Preview Owner (Read-Only)</span>
                        </div>
                        <p class="text-[11px] text-[#CBD5E1] mt-1 truncate">Meninjau layar kasir langsung dari Portal Klien tanpa berpindah halaman.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div x-show="business.outlets && business.outlets.length > 0" class="relative" @click.outside="if (openClientDropdown === 'previewOutlet') openClientDropdown = null">
                        <button type="button" @click="openClientDropdown = openClientDropdown === 'previewOutlet' ? null : 'previewOutlet'"
                            class="inline-flex items-center gap-2 h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] rounded-md text-xs font-medium text-white transition">
                            <span class="text-[#CBD5E1] hidden sm:inline">Cabang:</span>
                            <span class="font-semibold text-[#34D399] truncate max-w-[150px]" x-text="activePreviewOutletLabel"></span>
                            <svg class="w-3.5 h-3.5 text-[#CBD5E1] shrink-0 transition-transform" :class="openClientDropdown === 'previewOutlet' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openClientDropdown === 'previewOutlet'" x-transition class="absolute right-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-lg shadow-xl z-50 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">Pilih Cabang Preview</div>
                            <template x-for="out in (business.outlets || [])" :key="'cprev-' + out.id">
                                <button type="button" @click="selectPosPreviewOutlet(out.id)"
                                    class="w-full px-3 py-2 text-left text-xs flex items-center justify-between gap-2 hover:bg-slate-50"
                                    :class="String(previewModalOutletId) === String(out.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span class="truncate" x-text="out.name"></span>
                                    <span x-show="String(previewModalOutletId) === String(out.id)">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <button type="button" @click="reloadPosPreview()"
                        class="inline-flex items-center gap-1.5 h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] text-[#CBD5E1] hover:text-white rounded-md text-xs font-medium transition">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Muat Ulang</span>
                    </button>
                    <button type="button" @click="closePosPreviewModal()"
                        class="inline-flex items-center gap-1.5 h-8 px-3.5 bg-[#34D399] hover:bg-[#10B981] text-[#0D2620] rounded-md text-xs font-bold transition">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>Tutup Preview</span>
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0 bg-[#F7FAF9]">
                <template x-if="showPosPreviewModal">
                    <iframe :src="posPreviewIframeUrl" title="Preview Layar Kasir POS" class="w-full h-full border-0 bg-[#F7FAF9]"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function clientPortalApp() {
    const pad = (n) => String(n).padStart(2, '0');
    const localDateOffset = (daysAhead) => {
        const d = new Date();
        d.setDate(d.getDate() + daysAhead);
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    };
    return {
        token: localStorage.getItem('pos_token') || '',
        loading: true,
        user: {},
        business: {},
        subscriptionSummary: null,
        usage: { outlets_count: 0, users_count: 0, products_count: 0 },
        plans: [],
        projects: [],
        pendingPayment: null,
        paymentHistory: [],
        consultationGuard: { can_submit: true, reason: null, weekly_used: 0, weekly_limit: 2, active_whatsapp_url: null },
        sekayasaWaNumber: '6281234567890',
        pollTimer: null,
        selectedPlanId: null,
        submittingRent: false,
        submittingConsult: false,
        openClientDropdown: null,
        showPayActionPopup: false,
        actionPayment: null,
        payActionMenuStyle: 'top: 120px; right: 32px;',
        showSubReceiptModal: false,
        activeReceipt: {},
        showPosPreviewModal: false,
        previewModalOutletId: '',
        previewModalVersion: 0,
        payPage: 1,
        payPerPage: 10,
        paySearch: '',
        payStatusFilter: 'all',
        payStatusOptions: [
            { value: 'all', label: 'Semua Status' },
            { value: 'verified', label: 'Verified (Lunas)' },
            { value: 'pending', label: 'Pending' },
            { value: 'rejected', label: 'Rejected' },
        ],
        businessTypeOptions: [
            'F&B / Restoran / Kafe',
            'Retail / Minimarket / Grosir',
            'Apotek / Klinik / Farmasi',
            'Jasa / Bengkel / Barbershop',
            'Distribusi / Multi-Gudang',
            'Lainnya',
        ],
        consultTimeSlots: ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        minConsultDate: localDateOffset(3),
        maxConsultDate: localDateOffset(90),
        scaleOptions: [
            { value: '1 Outlet', label: '1 Outlet' },
            { value: '2-5 Cabang', label: '2–5 Cabang' },
            { value: 'Lebih dari 5 Cabang', label: 'Lebih dari 5 Cabang' },
        ],
        toggleClientDropdown(name) {
            this.openClientDropdown = this.openClientDropdown === name ? null : name;
        },
        getPageNumbers(current, total) {
            const t = Math.max(1, Number(total || 1));
            const c = Math.min(Math.max(1, Number(current || 1)), t);
            if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1);
            const pages = [1];
            if (c > 3) pages.push('...');
            for (let i = Math.max(2, c - 1); i <= Math.min(t - 1, c + 1); i++) pages.push(i);
            if (c < t - 2) pages.push('...');
            pages.push(t);
            return pages;
        },
        get selectedScaleLabel() {
            const f = this.scaleOptions.find((o) => o.value === this.consultForm.scale);
            return f ? f.label : this.consultForm.scale;
        },
        get activePayStatusLabel() {
            const f = this.payStatusOptions.find((o) => o.value === this.payStatusFilter);
            return f ? f.label : 'Semua Status';
        },
        get filteredPaymentHistory() {
            const q = (this.paySearch || '').trim().toLowerCase();
            return this.paymentHistory.filter((ph) => {
                if (this.payStatusFilter !== 'all' && String(ph.status || '') !== this.payStatusFilter) return false;
                if (!q) return true;
                return (ph.invoice_number || '').toLowerCase().includes(q) ||
                    ((ph.plan && ph.plan.name) || '').toLowerCase().includes(q) ||
                    (ph.status || '').toLowerCase().includes(q);
            });
        },
        get payTotalPages() {
            return Math.max(1, Math.ceil(this.filteredPaymentHistory.length / this.payPerPage));
        },
        get paginatedPaymentHistory() {
            const safe = Math.min(this.payPage, this.payTotalPages);
            const start = (safe - 1) * this.payPerPage;
            return this.filteredPaymentHistory.slice(start, start + this.payPerPage);
        },
        get payPaginationRangeLabel() {
            const total = this.filteredPaymentHistory.length;
            if (total === 0) return '0 tagihan';
            const safe = Math.min(this.payPage, this.payTotalPages);
            const from = (safe - 1) * this.payPerPage + 1;
            const to = Math.min(total, safe * this.payPerPage);
            return `Menampilkan ${from}–${to} dari ${total} tagihan`;
        },

        consultForm: {
            business_type: 'F&B / Restoran / Kafe',
            business_type_other: '',
            scale: '1 Outlet',
            phone: '',
            preferred_date: localDateOffset(3),
            preferred_time: '10:00',
            template_gap_notes: '',
        },

        showInstallGuideModal(mode = 'webapk') {
            if (!this.user || this.user.role !== 'owner') return;
            if (typeof window.triggerTandhaInstallApk === 'function') {
                return window.triggerTandhaInstallApk(mode);
            }
        },

        openPayActionPopup(paymentObj, evt) {
            this.actionPayment = paymentObj;
            if (evt && evt.currentTarget) {
                const rect = evt.currentTarget.getBoundingClientRect();
                const menuHeight = 105;
                const openAbove = (window.innerHeight - rect.bottom) < menuHeight;
                const top = openAbove ? Math.max(12, rect.top - menuHeight - 6) : (rect.bottom + 6);
                const right = Math.max(12, window.innerWidth - rect.right);
                this.payActionMenuStyle = `top: ${Math.round(top)}px; right: ${Math.round(right)}px;`;
            }
            this.showPayActionPopup = true;
        },

        runPayAction(kind) {
            const p = this.actionPayment;
            this.showPayActionPopup = false;
            if (!p) return;
            if (kind === 'receipt') return this.openPaymentReceiptModal(p.id);
            if (kind === 'check') {
                this.pendingPayment = p;
                return this.manualCheckPayment();
            }
        },

        async openPaymentReceiptModal(paymentId) {
            const res = await fetch(`/api/client/payments/${paymentId}/receipt`, {
                headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' },
            });
            const result = await res.json();
            if (res.ok && result.data) {
                this.activeReceipt = result.data;
                this.showSubReceiptModal = true;
            } else {
                Swal.fire({ icon: 'error', title: 'Nota Tidak Tersedia', text: result.message || 'Nota hanya tersedia untuk tagihan lunas.', confirmButtonColor: '#0D7A5F' });
            }
        },

        get serviceStatusLabel() {
            if (this.business.service_type === 'template_saas') return 'Langganan Tandha POS Aktif';
            if (this.business.service_type === 'custom_pos') return 'Request Penambahan Fitur (Konsultasi)';
            return 'Belum Memilih Paket';
        },

        get canAccessPosApp() {
            if (this.business.service_type === 'template_saas') return true;
            return this.projects.some(p => ['live', 'monthly_service_active'].includes(p.status));
        },

        async initClientPortal() {
            if (!this.token) {
                window.location.href = '/login?redirect=/client';
                return;
            }
            const cachedUser = JSON.parse(localStorage.getItem('pos_user') || '{}');
            if (cachedUser.role === 'cashier') {
                window.location.href = '/pos';
                return;
            }
            if (cachedUser.role === 'admin') {
                window.location.href = '/dashboard';
                return;
            }
            await this.loadOverview();
            if (this.user && this.user.role === 'cashier') {
                window.location.href = '/pos';
                return;
            }
            if (this.user && this.user.role === 'admin') {
                window.location.href = '/dashboard';
                return;
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async loadOverview() {
            this.loading = true;
            try {
                const res = await fetch('/api/client/overview', {
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Accept': 'application/json',
                    },
                });
                if (res.status === 401) {
                    localStorage.removeItem('pos_token');
                    window.location.href = '/login?redirect=/client';
                    return;
                }
                const result = await res.json();
                if (result.status === 'success') {
                    this.user = result.data.user;
                    this.business = result.data.user.business || {};
                    window.__tandhaCanInstallApk = Boolean((this.business && this.business.can_install_apk) || result.data.can_install_apk);
                    this.subscriptionSummary = result.data.subscription_summary || null;
                    this.usage = result.data.usage || { outlets_count: 0, users_count: 0, products_count: 0 };
                    this.plans = result.data.plans || [];
                    this.projects = result.data.projects || [];
                    this.pendingPayment = result.data.pending_payment || null;
                    this.paymentHistory = result.data.payment_history || [];
                    this.consultationGuard = result.data.consultation_guard || { can_submit: true, reason: null, weekly_used: 0, weekly_limit: 2, active_whatsapp_url: null };
                    this.sekayasaWaNumber = result.data.sekayasa_whatsapp_number || '6281234567890';
                    this.consultForm.phone = this.user.phone || '';
                    if (this.plans.length > 0 && !this.selectedPlanId) {
                        const sub = this.business.current_subscription;
                        this.selectedPlanId = (sub && sub.plan_id) ? sub.plan_id : this.plans[0].id;
                    }
                    if (this.pendingPayment) {
                        this.startPaymentPolling();
                    } else {
                        this.stopPaymentPolling();
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        startPaymentPolling() {
            this.stopPaymentPolling();
            if (!this.pendingPayment) return;
            this.pollTimer = setInterval(() => this.checkPaymentStatus(false), 3000);
        },

        stopPaymentPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async manualCheckPayment() {
            await this.checkPaymentStatus(true);
        },

        async checkPaymentStatus(showPendingAlert = false) {
            if (!this.pendingPayment) return;
            try {
                const res = await fetch(`/api/client/payments/${this.pendingPayment.id}/status`, {
                    headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' },
                });
                const result = await res.json();
                if (res.ok && result.data) {
                    if (result.data.payment_status === 'verified') {
                        this.stopPaymentPolling();
                        const paidId = this.pendingPayment.id;
                        this.pendingPayment = null;
                        const dlg = await Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Lunas & Terverifikasi!',
                            text: 'Layanan POS Anda kini aktif dan Nota Pembayaran Resmi telah diterbitkan.',
                            showCancelButton: true,
                            confirmButtonText: 'Lihat Nota Lunas',
                            cancelButtonText: 'Tutup',
                            confirmButtonColor: '#0D7A5F',
                        });
                        await this.loadOverview();
                        if (dlg.isConfirmed) {
                            await this.openPaymentReceiptModal(paidId);
                        }
                    } else if (result.data.payment_status === 'expired') {
                        this.stopPaymentPolling();
                        this.pendingPayment = null;
                        await this.loadOverview();
                    } else if (showPendingAlert) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Menunggu Verifikasi',
                            text: 'Tagihan masih berstatus pending. Integrasi Midtrans dalam tahap pengajuan, silakan konfirmasi ke tim kami.',
                            confirmButtonColor: '#0D7A5F',
                        });
                    }
                }
            } catch (err) {
                console.error(err);
            }
        },

        async rentTemplateAction() {
            if (!this.selectedPlanId) return;
            this.submittingRent = true;
            try {
                const res = await fetch('/api/client/rent-template', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ plan_id: this.selectedPlanId }),
                });
                const result = await res.json();
                if (res.ok) {
                    this.pendingPayment = result.data.payment;
                    this.startPaymentPolling();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message, confirmButtonColor: '#0D7A5F' });
                }
            } finally {
                this.submittingRent = false;
            }
        },

        async submitConsultation() {
            if (this.consultationGuard && !this.consultationGuard.can_submit) {
                Swal.fire({ icon: 'warning', title: 'Batas Anti-Spam Aktif', text: this.consultationGuard.reason, confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.consultForm.business_type === 'Lainnya' && (this.consultForm.business_type_other || '').trim().length < 3) {
                Swal.fire({ icon: 'warning', title: 'Bidang Usaha Lainnya Wajib Diisi', text: 'Tulis nama sektor/bidang usaha Anda minimal 3 karakter.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.submittingConsult = true;
            try {
                const res = await fetch('/api/client/consultations', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.consultForm),
                });
                const result = await res.json();
                if (res.ok) {
                    const waUrl = result.whatsapp_url || (result.data && result.data.whatsapp_url);
                    if (waUrl) {
                        window.open(waUrl, '_blank', 'noopener');
                    }
                    this.consultForm.template_gap_notes = '';
                    this.consultForm.business_type_other = '';
                    await this.loadOverview();
                    const ask = await Swal.fire({
                        icon: 'success',
                        title: 'Konsultasi Dicatat & Diarahkan ke WhatsApp',
                        text: result.message,
                        showCancelButton: Boolean(waUrl),
                        confirmButtonText: waUrl ? 'Buka WhatsApp SekaYasa →' : 'OK',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#0D7A5F',
                    });
                    if (ask.isConfirmed && waUrl) {
                        window.open(waUrl, '_blank', 'noopener');
                    }
                } else {
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Tidak Dapat Mengirim', text: firstErr || result.message || 'Periksa kembali isian Anda', confirmButtonColor: '#0D7A5F' });
                    if (res.status === 429) await this.loadOverview();
                }
            } finally {
                this.submittingConsult = false;
            }
        },

        async submitClientUat(tcId, status) {
            const res = await fetch(`/api/client/uat/${tcId}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status }),
            });
            if (res.ok) {
                await this.loadOverview();
            }
        },

        get activePreviewOutletLabel() {
            const list = (this.business && this.business.outlets) ? this.business.outlets : [];
            const found = list.find((o) => String(o.id) === String(this.previewModalOutletId));
            return found ? found.name : (list[0] ? list[0].name : 'Outlet Utama');
        },
        get posPreviewIframeUrl() {
            const oid = this.previewModalOutletId ? `&outlet_id=${encodeURIComponent(this.previewModalOutletId)}` : '';
            return `/pos?preview=1&embed=1${oid}&v=${this.previewModalVersion}`;
        },
        openPosPreviewModal(outletId = null) {
            if (!this.user || this.user.role !== 'owner') return;
            const list = (this.business && this.business.outlets) ? this.business.outlets : [];
            const fallbackId = this.user.outlet_id || (list[0] ? list[0].id : '');
            this.previewModalOutletId = outletId ? String(outletId) : String(fallbackId || '');
            this.previewModalVersion += 1;
            this.openClientDropdown = null;
            this.showPosPreviewModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        selectPosPreviewOutlet(outletId) {
            this.previewModalOutletId = String(outletId || '');
            this.openClientDropdown = null;
            this.previewModalVersion += 1;
        },
        reloadPosPreview() {
            this.previewModalVersion += 1;
        },
        closePosPreviewModal() {
            this.showPosPreviewModal = false;
            if (this.openClientDropdown === 'previewOutlet') this.openClientDropdown = null;
        },

        logout() {
            localStorage.removeItem('pos_token');
            localStorage.removeItem('pos_user');
            window.location.href = '/login';
        },

        formatStatus(st) {
            const map = {
                new_lead: 'Lead Masuk',
                consultation_scheduled: 'Konsultasi Dijadwalkan',
                discovery: 'Discovery As-Is/To-Be',
                proposal_sent: 'Proposal Dikirim',
                payment_pending: 'Menunggu Pelunasan 100%',
                ready_for_dev: 'Siap Dikerjakan',
                in_dev: 'Sedang Dikembangkan',
                client_uat: 'Pengujian UAT Anda',
                ready_to_deploy: 'Siap Rilis Produksi',
                live: 'Live Production',
                monthly_service_active: 'Layanan Bulanan Aktif',
            };
            return map[st] || st;
        },

        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) {
            if (!str) return '-';
            return new Date(str).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>
@endpush
