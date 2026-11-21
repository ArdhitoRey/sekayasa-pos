@extends('layouts.app')

@section('title', 'Akun Klien & Penyewaan Layanan — Seka Yasa POS')

@section('content')
<div class="min-h-screen bg-[#FAF7F2] text-[#1A1208] flex flex-col" x-data="clientPortalApp()" x-init="initClientPortal()">

    <!-- Top Navigation Bar (Deep Espresso #1A1208) -->
    <header class="bg-[#1A1208] text-[#F7F3EC] border-b border-[rgba(247,176,95,0.18)] px-6 py-4 sticky top-0 z-30">
        <div class="w-[92%] max-w-[1540px] mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/" class="w-9 h-9 rounded bg-[#23190D] border border-[rgba(247,176,95,0.3)] text-[#F7B05F] flex items-center justify-center font-bold">
                    SY
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-semibold text-[#F7B05F] tracking-wider uppercase">00 / Halaman Akun Klien</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 bg-[#F7B05F] text-[#1A1208] rounded">Sedang Login</span>
                    </div>
                    <h1 class="text-base font-bold text-[#F7F3EC] leading-tight mt-0.5">
                        <span x-text="user.name || 'Akun Klien'"></span> — <span class="text-[#C4B5A2]" x-text="business.name || ''"></span>
                    </h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <a href="/" class="px-3 py-1.5 bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded text-[#C4B5A2] hover:text-[#F7F3EC] font-semibold transition">
                    Beranda
                </a>

                <!-- Operational Shortcuts (Unlocked if template_saas rented or custom project live) -->
                <template x-if="canAccessPosApp">
                    <div class="flex items-center gap-2">
                        <a href="/pos" class="px-3.5 py-1.5 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded font-bold transition">
                            Buka Layar Kasir POS →
                        </a>
                        <a href="/dashboard" class="px-3.5 py-1.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded font-bold transition">
                            Dashboard Toko
                        </a>
                    </div>
                </template>

                <button @click="logout()" class="px-3 py-1.5 bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded text-[#C4B5A2] hover:text-rose-400 font-semibold transition">
                    Keluar
                </button>
            </div>
        </div>
    </header>

    <!-- Loading Overlay -->
    <div x-show="loading" class="flex-1 flex items-center justify-center py-24">
        <div class="text-sm font-semibold text-[#775229]">Memuat data akun Anda...</div>
    </div>

    <!-- Main Content -->
    <div x-show="!loading" class="flex-1" x-cloak>

        <!-- SECTION 01: PILIHAN LAYANAN (SEWA TEMPLATE vs KONSULTASI CUSTOM) -->
        <section class="py-16 border-b border-[rgba(119,82,41,0.18)]">
            <div class="w-[92%] max-w-[1540px] mx-auto grid grid-cols-1 lg:grid-cols-[0.72fr_1.08fr] gap-12 items-start">

                <!-- Left Sticky Column: Status Akun & Penjelasan Template -->
                <div class="lg:sticky lg:top-24 space-y-6 max-w-[440px]">
                    <div>
                        <div class="text-[13px] font-semibold text-[#775229] tracking-wide mb-2">01 / Evaluasi & Penyewaan Layanan</div>
                        <h2 class="text-3xl font-bold text-[#1A1208] leading-tight">
                            Pilih Jalur Layanan Sesuai Proses Bisnis Anda
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-[#524538]">
                            Jika alur toko Anda cocok dengan fitur template POS standar kami, Anda dapat langsung menyewa paket dan membuka kasir hari ini. Jika ada aturan khusus yang tidak cocok, ajukan konsultasi terlebih dahulu.
                        </p>
                    </div>

                    <!-- Account Current Status Box -->
                    <div class="bg-[#1A1208] text-[#F7F3EC] p-5 rounded-lg border border-[rgba(247,176,95,0.25)] space-y-3 text-xs">
                        <div class="flex justify-between items-center border-b border-[rgba(247,176,95,0.16)] pb-2.5">
                            <span class="text-[#C4B5A2] uppercase font-semibold">Status Layanan Akun</span>
                            <span class="font-bold text-[#F7B05F]" x-text="serviceStatusLabel"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#C4B5A2]">Email Terdaftar:</span>
                            <span class="font-mono" x-text="user.email"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#C4B5A2]">Entitas Usaha:</span>
                            <span class="font-semibold" x-text="business.name"></span>
                        </div>
                        <template x-if="business.service_type === 'template_saas' && business.current_subscription">
                            <div class="pt-2 border-t border-[rgba(247,176,95,0.16)] space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-[#C4B5A2]">Paket Template Aktif:</span>
                                    <b class="text-[#F7B05F]" x-text="business.current_subscription.plan ? business.current_subscription.plan.name : '-'"></b>
                                </div>
                                <div class="pt-2 flex gap-2">
                                    <a href="/pos" class="flex-1 py-2 text-center bg-[#F7B05F] text-[#1A1208] rounded font-bold">Layar Kasir</a>
                                    <a href="/dashboard" class="flex-1 py-2 text-center bg-[#23190D] border border-[rgba(247,176,95,0.3)] text-[#F7F3EC] rounded font-bold">Dashboard</a>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Template POS Standard Capabilities Summary -->
                    <div class="bg-[#F3EDE4] p-5 rounded-lg border border-[rgba(119,82,41,0.2)] space-y-2 text-xs">
                        <div class="font-bold text-[#1A1208] uppercase tracking-wider text-[11px] mb-2">Cakupan Template POS Standar:</div>
                        <div class="space-y-1.5 text-[#524538]">
                            <div>• Katalog produk, SKU, barcode scanner & kategori</div>
                            <div>• Kasir cepat, diskon nominal/persen, PPN & struk thermal</div>
                            <div>• Buka/tutup shift kasir dengan hitung selisih kas laci</div>
                            <div>• Stok multi-outlet, penyesuaian stok & laporan laba kotor</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Two Clear Interactive Paths -->
                <div class="space-y-8">

                    <!-- JALUR A: SEWA LAYANAN TEMPLATE LANGSUNG -->
                    <div class="bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-[rgba(119,82,41,0.18)]">
                            <div>
                                <span class="text-[11px] font-bold text-[#775229] uppercase tracking-wider">Opsi A — Proses Bisnis Sesuai Template</span>
                                <h3 class="text-lg font-bold text-[#1A1208]">Sewa Layanan POS Siap Pakai</h3>
                            </div>
                            <span class="px-2.5 py-1 bg-[#F2EBE1] text-[#775229] text-xs font-semibold rounded">Aktivasi Instan</span>
                        </div>

                        <div class="mt-4 divide-y divide-[rgba(119,82,41,0.16)] border-b border-[rgba(119,82,41,0.16)]">
                            <template x-for="plan in plans" :key="plan.id">
                                <label class="py-3.5 flex flex-wrap items-center justify-between gap-4 cursor-pointer hover:bg-[#FAF7F2]/50 px-2 transition">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="selected_plan" :value="plan.id" x-model="selectedPlanId" class="mt-1 accent-[#775229]">
                                        <div>
                                            <div class="text-sm font-bold text-[#1A1208]" x-text="'Paket ' + plan.name"></div>
                                            <div class="text-xs text-[#524538] mt-0.5">
                                                Maks. <b x-text="plan.max_outlets"></b> Outlet • <b x-text="plan.max_users"></b> Akun Staf • <b x-text="plan.max_products"></b> Produk
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right font-mono">
                                        <span class="text-sm font-bold text-[#775229]" x-text="formatRupiah(plan.price_monthly)"></span>
                                        <span class="text-[11px] text-[#524538] block">/ bulan</span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                            <span class="text-xs text-[#524538]">Pembayaran otomatis <b>Midtrans</b> sedang dalam pengajuan (saat ini verifikasi via Admin Portal).</span>
                            <button type="button" @click="rentTemplateAction()" :disabled="submittingRent || !selectedPlanId" class="px-6 py-2.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded-md text-xs font-bold transition disabled:opacity-50">
                                <span x-show="!submittingRent" x-text="pendingPayment ? 'Buat Ulang Tagihan Sewa' : 'Sewa Paket Sekarang →'"></span>
                                <span x-show="submittingRent">Memproses Tagihan...</span>
                            </button>
                        </div>

                        <!-- SUBSCRIPTION PAYMENT INVOICE BOX (Midtrans Pending) -->
                        <template x-if="pendingPayment">
                            <div class="mt-6 bg-[#FAF7F2] border border-[#775229] rounded-lg overflow-hidden">
                                <div class="bg-[#1A1208] text-[#F7F3EC] px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-bold text-[#F7B05F] uppercase tracking-wider">Tagihan Langganan Menunggu Pembayaran / Verifikasi</span>
                                        <div class="text-xs font-mono" x-text="pendingPayment.invoice_number"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-[#F7B05F]">Status: Pending</span>
                                </div>

                                <div class="p-5 space-y-4 text-xs">
                                    <div class="bg-[#F3EDE4] p-4 rounded border border-[rgba(119,82,41,0.2)] space-y-2">
                                        <div class="flex justify-between text-[#524538]">
                                            <span>Paket Dipilih:</span>
                                            <b x-text="pendingPayment.plan ? pendingPayment.plan.name : '-'"></b>
                                        </div>
                                        <div class="flex justify-between text-[#524538]">
                                            <span>Metode Pembayaran:</span>
                                            <span class="font-semibold text-[#1A1208]">Midtrans (Dalam Pengajuan) / Konfirmasi Admin</span>
                                        </div>
                                        <div class="pt-2 border-t border-[rgba(119,82,41,0.18)] flex justify-between items-baseline">
                                            <span class="font-bold text-[#1A1208]">Total Tagihan:</span>
                                            <span class="text-lg font-bold font-mono text-[#775229]" x-text="formatRupiah(pendingPayment.amount)"></span>
                                        </div>
                                    </div>

                                    <p class="text-[11px] text-[#524538] leading-relaxed">
                                        Integrasi payment gateway <b>Midtrans</b> sedang dalam tahap pengajuan. Setelah pembayaran dikonfirmasi/diverifikasi oleh Super Admin, layanan kasir POS (`/pos`) dan dashboard (`/dashboard`) akan otomatis aktif di akun Anda.
                                    </p>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <button type="button" @click="manualCheckPayment()" class="px-4 py-2 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] text-[#775229] rounded font-bold transition">
                                            Cek Status Pembayaran
                                        </button>
                                        <span class="text-[11px] text-[#524538]" x-text="'Berlaku hingga: ' + formatDate(pendingPayment.expires_at)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- JALUR B: KONSULTASI CUSTOM (JIKA TIDAK SESUAI TEMPLATE) -->
                    <div class="bg-[#F2EBE1] border border-[rgba(119,82,41,0.3)] rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-[rgba(119,82,41,0.18)]">
                            <div>
                                <span class="text-[11px] font-bold text-[#775229] uppercase tracking-wider">Opsi B — Proses Bisnis Berbeda dari Template</span>
                                <h3 class="text-lg font-bold text-[#1A1208]">Ajukan Konsultasi POS Custom Terlebih Dahulu</h3>
                            </div>
                            <span class="px-2.5 py-1 bg-[#1A1208] text-[#F7B05F] text-xs font-semibold rounded">Discovery As-Is / To-Be</span>
                        </div>

                        <p class="mt-3 text-xs text-[#524538] leading-relaxed">
                            Jangan memaksakan alur usaha Anda mengikuti template. Jelaskan proses kerja khusus yang Anda butuhkan agar Business Analyst kami melakukan sesi discovery sebelum penyusunan proposal dan development custom.
                        </p>

                        <form @submit.prevent="submitConsultation()" class="mt-5 space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">Sektor Usaha</label>
                                    <select x-model="consultForm.business_type" required class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                        <option value="F&B / Restoran / Kafe">F&B / Restoran / Kafe</option>
                                        <option value="Retail / Minimarket / Grosir">Retail / Minimarket / Grosir</option>
                                        <option value="Apotek / Klinik / Farmasi">Apotek / Klinik / Farmasi</option>
                                        <option value="Jasa / Bengkel / Barbershop">Jasa / Bengkel / Barbershop</option>
                                        <option value="Distribusi / Multi-Gudang">Distribusi / Multi-Gudang</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">Skala Outlet & Perangkat</label>
                                    <select x-model="consultForm.scale" required class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                        <option value="1 Outlet">1 Outlet</option>
                                        <option value="2-5 Cabang">2–5 Cabang</option>
                                        <option value="Lebih dari 5 Cabang">Lebih dari 5 Cabang</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">No. WhatsApp PIC</label>
                                    <input type="text" x-model="consultForm.phone" required placeholder="0812xxxxxxxx" class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">Jadwal Konsultasi yang Diinginkan</label>
                                    <input type="datetime-local" x-model="consultForm.preferred_consultation_at" required class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-[#524538] mb-1">Bagian Proses Bisnis yang Tidak Sesuai Template / Kebutuhan Custom</label>
                                <textarea x-model="consultForm.template_gap_notes" required rows="3" placeholder="Contoh: Kami butuh pemotongan stok bahan baku resep otomatis per gramatur, harga bertingkat grosir/eceran, serta integrasi timbangan digital." class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]"></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" :disabled="submittingConsult" class="px-6 py-2.5 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] rounded-md text-xs font-bold transition disabled:opacity-50">
                                    <span x-show="!submittingConsult">Ajukan Konsultasi Custom Terlebih Dahulu →</span>
                                    <span x-show="submittingConsult">Mengirim Pengajuan...</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION 02: PELACAKAN KONSULTASI & PROYEK CUSTOM AKUN SAYA (OPS-026 CLIENT ISOLATED PORTAL) -->
        <section class="bg-[#F3EDE4] py-16">
            <div class="w-[92%] max-w-[1540px] mx-auto space-y-6">
                <div>
                    <div class="text-[13px] font-semibold text-[#775229] tracking-wide mb-1">02 / Client Portal — Status Konsultasi & Proyek Saya</div>
                    <h2 class="text-2xl font-bold text-[#1A1208]">Riwayat Konsultasi, Proposal, & Pengujian UAT Akun Anda</h2>
                    <p class="text-xs text-[#524538] mt-1">Data proyek di bawah ini terisolasi khusus untuk akun Anda dan terhubung dengan Portal Internal penyedia.</p>
                </div>

                <!-- Empty State -->
                <div x-show="projects.length === 0" class="bg-[#FAF7F2] border border-[rgba(119,82,41,0.2)] rounded-lg p-8 text-center text-xs text-[#524538]">
                    Anda belum memiliki pengajuan konsultasi custom. Gunakan form <b>Opsi B</b> di atas apabila proses bisnis usaha Anda membutuhkan penyesuaian khusus di luar template standar.
                </div>

                <!-- Client Projects List -->
                <div class="space-y-6">
                    <template x-for="proj in projects" :key="proj.id">
                        <div class="bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded-lg overflow-hidden">
                            <!-- Project Header -->
                            <div class="bg-[#1A1208] text-[#F7F3EC] p-5 flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <span class="text-[10px] font-semibold text-[#F7B05F] uppercase tracking-wider" x-text="proj.business_type"></span>
                                    <h3 class="text-lg font-bold mt-0.5" x-text="proj.business_name + ' (Ref #' + proj.id + ')'"></h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-[#C4B5A2] uppercase block">Status Pipeline Proyek</span>
                                    <span class="inline-block mt-1 px-3 py-1 bg-[#23190D] border border-[rgba(247,176,95,0.35)] text-[#F7B05F] rounded text-xs font-bold uppercase" x-text="formatStatus(proj.status)"></span>
                                </div>
                            </div>

                            <!-- Project Body -->
                            <div class="p-6 space-y-6 text-xs">
                                <!-- Gap & Consultation Schedule -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 bg-[#F3EDE4] rounded border border-[rgba(119,82,41,0.18)]">
                                        <span class="font-bold text-[#775229] uppercase text-[10px] block mb-1">Kebutuhan Custom / Gap Template</span>
                                        <p class="text-[#1A1208] leading-relaxed" x-text="proj.template_gap_notes || proj.notes || '-'"></p>
                                    </div>
                                    <div class="p-4 bg-[#F3EDE4] rounded border border-[rgba(119,82,41,0.18)] space-y-1">
                                        <span class="font-bold text-[#775229] uppercase text-[10px] block mb-1">Jadwal Konsultasi & Discovery</span>
                                        <template x-if="proj.consultations && proj.consultations.length > 0">
                                            <div>
                                                <div class="font-bold text-[#1A1208]" x-text="'Jadwal: ' + formatDate(proj.consultations[0].scheduled_at)"></div>
                                                <div class="text-[#524538]" x-text="'PIC Analis: ' + proj.consultations[0].pic_name"></div>
                                                <div class="text-[#775229] font-semibold uppercase text-[10px] mt-1" x-text="'Status Sesi: ' + proj.consultations[0].status"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Proposal & Payment Status (If Proposal Exists) -->
                                <template x-if="proj.current_proposal">
                                    <div class="p-4 bg-[#F2EBE1] rounded border border-[rgba(119,82,41,0.25)] flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <span class="text-[10px] font-bold text-[#775229] uppercase">Proposal & Biaya Pengembangan</span>
                                            <div class="text-sm font-bold text-[#1A1208] mt-0.5" x-text="proj.current_proposal.proposal_number + ' • ' + proj.current_proposal.timeline_weeks + ' Minggu'"></div>
                                            <div class="text-[#524538] mt-0.5" x-text="proj.current_proposal.deliverables"></div>
                                        </div>
                                        <div class="text-right font-mono">
                                            <div class="text-base font-bold text-[#1A1208]" x-text="formatRupiah(proj.current_proposal.development_cost)"></div>
                                            <div class="text-[11px] text-[#775229]" x-text="'Managed: ' + formatRupiah(proj.current_proposal.monthly_maintenance_cost) + '/bln'"></div>
                                        </div>
                                    </div>
                                </template>

                                <!-- UAT Client Sign-off Panel (OPS-026 / OPS-028) -->
                                <template x-if="proj.uat_cases && proj.uat_cases.length > 0">
                                    <div class="border border-[rgba(119,82,41,0.22)] rounded overflow-hidden">
                                        <div class="bg-[#F3EDE4] px-4 py-2.5 border-b border-[rgba(119,82,41,0.18)] flex justify-between items-center">
                                            <span class="font-bold text-[#1A1208] uppercase text-[11px]">Pengujian UAT Klien (User Acceptance Testing)</span>
                                            <span class="text-[11px] text-[#775229] font-semibold">Silakan verifikasi kesesuaian fitur custom</span>
                                        </div>
                                        <div class="divide-y divide-[rgba(119,82,41,0.14)]">
                                            <template x-for="tc in proj.uat_cases" :key="tc.id">
                                                <div class="p-3.5 flex flex-wrap items-center justify-between gap-4">
                                                    <div>
                                                        <div class="font-bold text-[#1A1208]" x-text="tc.test_title"></div>
                                                        <div class="text-[11px] text-[#524538] mt-0.5" x-text="'Skenario: ' + tc.scenario"></div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button @click="submitClientUat(tc.id, 'pass')" :class="tc.status === 'pass' ? 'bg-[#775229] text-[#FAF7F2]' : 'bg-[#F3EDE4] text-[#524538] border border-[rgba(119,82,41,0.22)]'" class="px-3 py-1 rounded font-bold">Pass</button>
                                                        <button @click="submitClientUat(tc.id, 'fail')" :class="tc.status === 'fail' ? 'bg-rose-700 text-white' : 'bg-[#F3EDE4] text-[#524538] border border-[rgba(119,82,41,0.22)]'" class="px-3 py-1 rounded font-bold">Fail</button>
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
                                            <div class="p-3 bg-[#1A1208] text-[#F7F3EC] rounded flex items-center justify-between">
                                                <div>
                                                    <span class="text-[10px] uppercase font-bold text-[#F7B05F]" x-text="tgt.environment"></span>
                                                    <div class="font-mono text-[11px] text-[#C4B5A2]" x-text="tgt.host_url"></div>
                                                </div>
                                                <span class="text-[10px] font-bold uppercase text-[#F7B05F]" x-text="tgt.tls_ssl_active ? 'HTTPS AKTIF' : 'HTTP'"></span>
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
</div>
@endsection

@push('scripts')
<script>
function clientPortalApp() {
    return {
        token: localStorage.getItem('pos_token') || '',
        loading: true,
        user: {},
        business: {},
        plans: [],
        projects: [],
        pendingPayment: null,
        paymentHistory: [],
        pollTimer: null,
        selectedPlanId: null,
        submittingRent: false,
        submittingConsult: false,

        consultForm: {
            business_type: 'F&B / Restoran / Kafe',
            scale: '1 Outlet',
            phone: '',
            preferred_consultation_at: new Date(Date.now() + 86400000).toISOString().slice(0, 16),
            template_gap_notes: '',
        },

        get serviceStatusLabel() {
            if (this.business.service_type === 'template_saas') return 'Template POS Aktif (Disewa)';
            if (this.business.service_type === 'custom_pos') return 'Layanan POS Custom (Konsultasi/Proyek)';
            return 'Belum Memilih Layanan';
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
            await this.loadOverview();
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
                    this.plans = result.data.plans || [];
                    this.projects = result.data.projects || [];
                    this.pendingPayment = result.data.pending_payment || null;
                    this.paymentHistory = result.data.payment_history || [];
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
                        this.pendingPayment = null;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Terverifikasi!',
                            text: 'Layanan POS Anda kini telah aktif dan siap digunakan.',
                            confirmButtonColor: '#775229',
                        });
                        await this.loadOverview();
                    } else if (result.data.payment_status === 'expired') {
                        this.stopPaymentPolling();
                        this.pendingPayment = null;
                        await this.loadOverview();
                    } else if (showPendingAlert) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Menunggu Verifikasi',
                            text: 'Tagihan masih berstatus pending. Integrasi Midtrans dalam tahap pengajuan, silakan konfirmasi ke tim kami.',
                            confirmButtonColor: '#775229',
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
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message, confirmButtonColor: '#775229' });
                }
            } finally {
                this.submittingRent = false;
            }
        },

        async submitConsultation() {
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Konsultasi Custom Dijadwalkan',
                        text: result.message,
                        confirmButtonColor: '#775229',
                    });
                    this.consultForm.template_gap_notes = '';
                    await this.loadOverview();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Mengirim', text: result.message || 'Periksa kembali isian Anda', confirmButtonColor: '#775229' });
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
