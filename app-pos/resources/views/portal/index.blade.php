@extends('layouts.app')

@section('title', 'Portal Internal & One-Click Deploy — Seka Yasa POS')

@section('content')
<div class="min-h-screen bg-[#1A1208] text-[#F7F3EC] flex flex-col" x-data="portalApp()" x-init="initPortal()">

    <!-- Top Navigation Bar (Deep Espresso #1A1208) -->
    <header class="bg-[#1A1208] border-b border-[rgba(247,176,95,0.18)] px-6 py-4 sticky top-0 z-30">
        <div class="w-[94%] max-w-[1540px] mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/" class="w-9 h-9 rounded bg-[#23190D] border border-[rgba(247,176,95,0.3)] text-[#F7B05F] flex items-center justify-center">
                    <i data-lucide="rocket" class="w-4 h-4"></i>
                </a>
                <div>
                    <div class="text-[11px] font-semibold text-[#F7B05F] tracking-wider uppercase">00 / Portal Internal Operasional</div>
                    <h1 class="text-base font-bold text-[#F7F3EC] leading-tight">Layanan POS Custom & One-Click Deployment</h1>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="/" class="px-3 py-1.5 bg-[#23190D] hover:border-[#F7B05F] text-[#C4B5A2] hover:text-[#F7F3EC] border border-[rgba(247,176,95,0.2)] rounded-md text-xs font-semibold transition">
                    Beranda
                </a>
                <a href="/pos" class="px-3 py-1.5 bg-[#23190D] hover:border-[#F7B05F] text-[#C4B5A2] hover:text-[#F7F3EC] border border-[rgba(247,176,95,0.2)] rounded-md text-xs font-semibold transition">
                    Layar Kasir
                </a>
                <a href="/dashboard" class="px-3 py-1.5 bg-[#23190D] hover:border-[#F7B05F] text-[#C4B5A2] hover:text-[#F7F3EC] border border-[rgba(247,176,95,0.2)] rounded-md text-xs font-semibold transition">
                    Dashboard Toko
                </a>
                <button @click="openCreateLeadModal()" class="px-4 py-1.5 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded-md text-xs font-bold transition flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Lead Klien Baru
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container (Wide 94%, max-width 1540px) -->
    <div class="flex-1 w-[94%] max-w-[1540px] mx-auto py-8 space-y-8">

        <!-- KPI Summary Strip (Flat Espresso Surface, Hairline Borders — Anti-AI Slop) -->
        <div class="grid grid-cols-1 sm:grid-cols-4 border border-[rgba(247,176,95,0.2)] bg-[#23190D] rounded-lg divide-y sm:divide-y-0 sm:divide-x divide-[rgba(247,176,95,0.16)]">
            <div class="p-5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#C4B5A2]">Total Proyek Klien</span>
                <div class="text-2xl font-bold text-[#F7F3EC] mt-1" x-text="leads.length"></div>
            </div>
            <div class="p-5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#C4B5A2]">Nilai Kontrak 100% Lunas</span>
                <div class="text-2xl font-bold text-[#F7B05F] mt-1 font-mono" x-text="formatRupiah(totalVerifiedRevenue)"></div>
            </div>
            <div class="p-5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#C4B5A2]">Siap / Live Production</span>
                <div class="text-2xl font-bold text-[#F7F3EC] mt-1" x-text="readyToDeployCount"></div>
            </div>
            <div class="p-5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#C4B5A2]">Managed Service Bulanan</span>
                <div class="text-2xl font-bold text-[#F7B05F] mt-1 font-mono" x-text="formatRupiah(totalMonthlyRecurring) + '/bln'"></div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-[rgba(247,176,95,0.16)] pb-4">
            <div class="relative w-full sm:w-80">
                <i data-lucide="search" class="w-4 h-4 text-[#C4B5A2] absolute left-3 top-2.5"></i>
                <input type="text" x-model="search" @input.debounce.300ms="loadLeads()" placeholder="Cari nama bisnis, klien, atau email..." class="w-full pl-9 pr-4 py-2 bg-[#23190D] border border-[rgba(247,176,95,0.22)] rounded-md text-xs text-[#F7F3EC] placeholder-[#C4B5A2]/60 focus:outline-none focus:border-[#F7B05F]">
            </div>

            <div class="flex gap-2 overflow-x-auto w-full sm:w-auto pb-1">
                <button @click="statusFilter = ''; loadLeads()" :class="statusFilter === '' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.18)] hover:text-[#F7F3EC]'" class="px-3 py-1.5 rounded text-xs transition whitespace-nowrap">
                    Semua Proyek
                </button>
                <button @click="statusFilter = 'ready_to_deploy'; loadLeads()" :class="statusFilter === 'ready_to_deploy' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.18)] hover:text-[#F7F3EC]'" class="px-3 py-1.5 rounded text-xs transition whitespace-nowrap">
                    Siap Deploy
                </button>
                <button @click="statusFilter = 'in_dev'; loadLeads()" :class="statusFilter === 'in_dev' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.18)] hover:text-[#F7F3EC]'" class="px-3 py-1.5 rounded text-xs transition whitespace-nowrap">
                    In Development
                </button>
                <button @click="statusFilter = 'payment_pending'; loadLeads()" :class="statusFilter === 'payment_pending' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.18)] hover:text-[#F7F3EC]'" class="px-3 py-1.5 rounded text-xs transition whitespace-nowrap">
                    Payment Gate (Unpaid)
                </button>
            </div>
        </div>

        <!-- Editorial Project List Table (Clean Structured Matrix instead of generic rounded cards) -->
        <div class="bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded-lg overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[rgba(247,176,95,0.16)] flex justify-between items-center">
                <span class="text-xs font-semibold text-[#F7B05F]">01 / Pipeline Proyek Klien & Gate Keamanan</span>
                <span class="text-[11px] text-[#C4B5A2]">Klik Detail untuk membuka Konsol One-Click Deploy</span>
            </div>
            <div class="divide-y divide-[rgba(247,176,95,0.14)]">
                <template x-for="item in leads" :key="item.id">
                    <div class="p-5 hover:bg-[#1A1208]/40 transition grid grid-cols-1 lg:grid-cols-[1.5fr_1fr_1fr_auto] gap-4 items-center">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-xs font-mono text-[#F7B05F]" x-text="'#' + item.id"></span>
                                <h3 class="text-base font-bold text-[#F7F3EC]" x-text="item.business_name"></h3>
                            </div>
                            <div class="text-xs text-[#C4B5A2] mt-1">
                                PIC: <span class="text-[#F7F3EC]" x-text="item.client_name"></span> • <span x-text="item.business_type"></span>
                            </div>
                            <p class="text-[11px] text-[#C4B5A2]/80 mt-1 line-clamp-1 max-w-[540px]" x-text="item.notes || 'Tidak ada catatan'"></p>
                        </div>

                        <div class="text-xs space-y-1">
                            <div class="text-[#C4B5A2]">Biaya Dev: <b class="text-[#F7F3EC] font-mono" x-text="formatRupiah(item.total_cost)"></b></div>
                            <div class="text-[#C4B5A2]">Managed/bln: <b class="text-[#F7B05F] font-mono" x-text="formatRupiah(item.monthly_cost)"></b></div>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <div>
                                <span :class="getStatusBadgeClass(item.status)" class="inline-block px-2.5 py-0.5 rounded text-[11px] font-semibold" x-text="formatStatusLabel(item.status)"></span>
                            </div>
                            <div class="text-[11px]" :class="item.payment_verified ? 'text-[#F7B05F] font-semibold' : 'text-[#C4B5A2]'">
                                Payment Gate: <span x-text="item.payment_verified ? '100% LUNAS' : 'BELUM LUNAS'"></span>
                            </div>
                        </div>

                        <div class="text-right">
                            <button @click="openProjectDetail(item.id)" class="px-4 py-2 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded-md text-xs font-bold transition whitespace-nowrap">
                                Detail & Deploy Console →
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- QRIS Subscription Payment Monitor & Auto-Detection Webhook (Bab 4.11 & Merchant QRIS) -->
        <div class="bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded-lg overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[rgba(247,176,95,0.16)] flex flex-wrap justify-between items-center gap-3">
                <div>
                    <span class="text-xs font-semibold text-[#F7B05F]">02 / Pemantauan QRIS Dinamis Langganan & Deteksi Otomatis</span>
                    <div class="text-[11px] text-[#C4B5A2] mt-0.5">Merchant QRIS: <b class="text-[#F7F3EC]" x-text="qrisMeta.merchant_name || 'MUHHAMAD SATRIA RAMADHAN, DIGITAL & KREATIF'"></b> (NMID: <span class="font-mono" x-text="qrisMeta.merchant_nmid || 'ID1026599048299'"></span>)</div>
                </div>
                <button @click="loadQrisPayments()" class="px-3 py-1 bg-[#1A1208] border border-[rgba(247,176,95,0.25)] text-[#F7B05F] rounded text-xs font-semibold">Muat Ulang</button>
            </div>

            <!-- Webhook Setup Info -->
            <div class="p-4 bg-[#1A1208] border-b border-[rgba(247,176,95,0.14)] text-[11px] font-mono text-[#C4B5A2] space-y-1">
                <div class="text-[#F7B05F] font-bold">ENDPOINT WEBHOOK ANDROID NOTIFICATION FORWARDER (MacroDroid / Tasker / GoPay Merchant):</div>
                <div>POST <span class="text-[#F7F3EC]" x-text="qrisMeta.webhook_url || '/api/webhooks/qris-mutation'"></span></div>
                <div>Header: <span class="text-[#F7F3EC]">X-QRIS-WEBHOOK-SECRET: <span x-text="qrisMeta.webhook_secret || 'seka-yasa-qris-2026'"></span></span></div>
                <div>JSON Body: <span class="text-[#F7F3EC]">{"message": "{notifikasi_gopay_merchant}"}</span> atau <span class="text-[#F7F3EC]">{"amount": 99347}</span></div>
            </div>

            <div class="divide-y divide-[rgba(247,176,95,0.12)]">
                <template x-for="pay in qrisPayments" :key="pay.id">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div>
                            <div class="font-bold text-[#F7F3EC]">
                                <span class="font-mono text-[#F7B05F]" x-text="pay.invoice_number || ('PAY #' + pay.id)"></span>
                                — <span x-text="pay.business ? pay.business.name : '-'"></span>
                            </div>
                            <div class="text-[11px] text-[#C4B5A2] mt-0.5">
                                Paket: <b x-text="pay.plan ? pay.plan.name : '-'"></b> • Kode Unik: <span class="font-mono text-[#F7B05F]" x-text="'+' + pay.unique_code"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right font-mono">
                                <div class="text-sm font-bold text-[#F7F3EC]" x-text="formatRupiah(pay.amount)"></div>
                                <span class="text-[10px] uppercase font-bold" :class="pay.status === 'verified' ? 'text-[#F7B05F]' : 'text-[#C4B5A2]'" x-text="pay.status"></span>
                            </div>
                            <button x-show="pay.status === 'pending'" @click="simulateQrisWebhook(pay.amount)" class="px-3 py-1.5 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded font-bold text-[11px] transition">
                                Simulasi Uang Masuk
                            </button>
                        </div>
                    </div>
                </template>
                <div x-show="qrisPayments.length === 0" class="p-6 text-center text-xs text-[#C4B5A2]">
                    Belum ada tagihan QRIS langganan. Buat tagihan melalui halaman /client.
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== MODAL: PROJECT DETAIL & DEPLOYMENT CONSOLE ==================== -->
    <div x-show="showDetailModal" class="fixed inset-0 bg-[#1A1208]/90 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#23190D] border border-[rgba(247,176,95,0.3)] rounded-lg max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden" @click.outside="showDetailModal = false">

            <!-- Modal Header -->
            <div class="bg-[#1A1208] p-5 border-b border-[rgba(247,176,95,0.18)] flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-[11px] font-semibold text-[#F7B05F] uppercase tracking-wider" x-text="activeProject.lead ? activeProject.lead.business_type : ''"></div>
                    <h2 class="text-xl font-bold text-[#F7F3EC]" x-text="activeProject.lead ? activeProject.lead.business_name : ''"></h2>
                    <div class="text-xs text-[#C4B5A2] mt-0.5">PIC: <span class="text-[#F7F3EC]" x-text="activeProject.lead ? activeProject.lead.client_name : ''"></span> (<span x-text="activeProject.lead ? activeProject.lead.email : ''"></span>)</div>
                </div>

                <div class="flex items-center gap-3">
                    <select x-model="selectedStatus" @change="changeStatus()" class="px-3 py-1.5 bg-[#23190D] border border-[rgba(247,176,95,0.28)] rounded text-xs font-bold text-[#F7F3EC] focus:outline-none">
                        <option value="new_lead">New Lead</option>
                        <option value="discovery">Discovery & As-Is</option>
                        <option value="proposal_sent">Proposal Sent</option>
                        <option value="payment_pending">Payment Pending (Locked)</option>
                        <option value="ready_for_dev">Ready for Development (100% Lunas)</option>
                        <option value="in_dev">In Development</option>
                        <option value="client_uat">Client UAT</option>
                        <option value="ready_to_deploy">Ready to Deploy</option>
                        <option value="live">Live in Production</option>
                    </select>

                    <button @click="showDetailModal = false" class="text-[#C4B5A2] hover:text-[#F7B05F] p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Tabs -->
            <div class="flex border-b border-[rgba(247,176,95,0.16)] bg-[#1A1208] px-5 overflow-x-auto">
                <button @click="detailTab = 'scope'" :class="detailTab === 'scope' ? 'border-[#F7B05F] text-[#F7B05F] font-bold' : 'border-transparent text-[#C4B5A2] hover:text-[#F7F3EC]'" class="py-3 px-4 border-b-2 text-xs transition whitespace-nowrap">
                    01 / Scope & Kebutuhan
                </button>
                <button @click="detailTab = 'contract'" :class="detailTab === 'contract' ? 'border-[#F7B05F] text-[#F7B05F] font-bold' : 'border-transparent text-[#C4B5A2] hover:text-[#F7F3EC]'" class="py-3 px-4 border-b-2 text-xs transition whitespace-nowrap">
                    02 / Kontrak & Payment Gate
                </button>
                <button @click="detailTab = 'uat'" :class="detailTab === 'uat' ? 'border-[#F7B05F] text-[#F7B05F] font-bold' : 'border-transparent text-[#C4B5A2] hover:text-[#F7F3EC]'" class="py-3 px-4 border-b-2 text-xs transition whitespace-nowrap">
                    03 / UAT & BAST
                </button>
                <button @click="detailTab = 'deploy'; loadPreChecks()" :class="detailTab === 'deploy' ? 'border-[#F7B05F] text-[#F7B05F] font-bold' : 'border-transparent text-[#C4B5A2] hover:text-[#F7F3EC]'" class="py-3 px-4 border-b-2 text-xs transition whitespace-nowrap">
                    04 / One-Click Deploy Console
                </button>
            </div>

            <!-- Modal Tab Contents -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-[#23190D]">

                <!-- TAB 1: SCOPE & DISCOVERY -->
                <div x-show="detailTab === 'scope'" class="space-y-5" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.16)]">
                            <span class="text-[11px] font-bold text-[#F7B05F] uppercase tracking-wider block mb-1">Kondisi As-Is</span>
                            <p class="text-xs text-[#C4B5A2] leading-relaxed" x-text="activeProject.lead && activeProject.lead.discovery ? activeProject.lead.discovery.as_is_summary : 'Belum dianalisis'"></p>
                        </div>
                        <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.16)]">
                            <span class="text-[11px] font-bold text-[#F7B05F] uppercase tracking-wider block mb-1">Arsitektur To-Be</span>
                            <p class="text-xs text-[#C4B5A2] leading-relaxed" x-text="activeProject.lead && activeProject.lead.discovery ? activeProject.lead.discovery.to_be_summary : 'Belum dianalisis'"></p>
                        </div>
                    </div>

                    <div class="bg-[#1A1208] rounded border border-[rgba(247,176,95,0.16)] overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-[rgba(247,176,95,0.14)] text-xs font-bold text-[#F7F3EC]">Requirement Register (SOW)</div>
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-[rgba(247,176,95,0.12)] text-[#C4B5A2] text-[10px] uppercase">
                                <tr>
                                    <th class="p-3">Kode</th>
                                    <th class="p-3">Fitur Custom</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3">Prioritas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgba(247,176,95,0.1)] text-[#F7F3EC]">
                                <template x-for="req in (activeProject.lead ? activeProject.lead.requirements : [])" :key="req.id">
                                    <tr>
                                        <td class="p-3 font-mono text-[#F7B05F]" x-text="req.req_code"></td>
                                        <td class="p-3">
                                            <div class="font-bold" x-text="req.title"></div>
                                            <div class="text-[11px] text-[#C4B5A2]" x-text="req.description"></div>
                                        </td>
                                        <td class="p-3 uppercase text-[10px] text-[#C4B5A2]" x-text="req.category"></td>
                                        <td class="p-3 uppercase text-[10px] font-bold text-[#F7B05F]" x-text="req.priority"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: CONTRACT & PAYMENT GATE -->
                <div x-show="detailTab === 'contract'" class="space-y-4" x-cloak>
                    <div class="bg-[#1A1208] p-5 rounded border border-[rgba(247,176,95,0.28)] flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h4 class="text-sm font-bold text-[#F7B05F]" x-text="activeProject.is_payment_100_verified ? 'Payment Gate Lolos: Pembayaran 100% Terverifikasi' : 'Payment Gate Terkunci: Menunggu Pelunasan 100%'"></h4>
                            <p class="text-xs text-[#C4B5A2] mt-1" x-text="activeProject.is_payment_100_verified ? 'Development dan deployment produksi telah dibuka.' : 'Development tidak dapat dimulai sebelum pembayaran pengembangan 100% lunas.'"></p>
                        </div>
                        <button x-show="!activeProject.is_payment_100_verified && activeProject.dev_contract" @click="verifyPaymentAction(activeProject.dev_contract.id)" class="px-4 py-2 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded-md text-xs font-bold transition">
                            Verifikasi Pembayaran 100%
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.16)] space-y-1.5">
                            <span class="text-[10px] font-bold text-[#C4B5A2] uppercase">Kontrak Pengembangan (One-Time)</span>
                            <div class="text-xl font-bold text-[#F7F3EC] font-mono" x-text="formatRupiah(activeProject.dev_contract ? activeProject.dev_contract.total_amount : 0)"></div>
                            <div class="text-[#C4B5A2]">Nomor: <span class="font-mono text-[#F7F3EC]" x-text="activeProject.dev_contract ? activeProject.dev_contract.contract_number : '-'"></span></div>
                            <div class="text-[#C4B5A2]">Status: <b class="text-[#F7B05F]" x-text="activeProject.is_payment_100_verified ? 'LUNAS 100%' : 'BELUM LUNAS'"></b></div>
                        </div>
                        <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.16)] space-y-1.5">
                            <span class="text-[10px] font-bold text-[#C4B5A2] uppercase">Managed Service (Bulanan)</span>
                            <div class="text-xl font-bold text-[#F7B05F] font-mono" x-text="formatRupiah(activeProject.monthly_contract ? activeProject.monthly_contract.total_amount : 0) + ' / bln'"></div>
                            <div class="text-[#C4B5A2]">Cakupan: Hosting VPS, Uptime Monitoring, Backup Harian, SLA Support.</div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: UAT CHECKLIST -->
                <div x-show="detailTab === 'uat'" class="space-y-4" x-cloak>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[#C4B5A2]">Seluruh test case UAT wajib berstatus Pass sebelum produksi dirilis.</span>
                        <span class="font-bold text-[#F7B05F]" x-text="activeProject.uat_summary ? (activeProject.uat_summary.passed + '/' + activeProject.uat_summary.total + ' Lulus') : ''"></span>
                    </div>

                    <div class="divide-y divide-[rgba(247,176,95,0.14)] bg-[#1A1208] border border-[rgba(247,176,95,0.16)] rounded">
                        <template x-for="tc in (activeProject.lead ? activeProject.lead.uat_cases : [])" :key="tc.id">
                            <div class="p-4 flex items-center justify-between gap-4 text-xs">
                                <div>
                                    <div class="font-bold text-[#F7F3EC]" x-text="tc.test_title"></div>
                                    <div class="text-[11px] text-[#C4B5A2] mt-1">Skenario: <span x-text="tc.scenario"></span></div>
                                    <div class="text-[11px] text-[#C4B5A2]/75">Ekspektasi: <span x-text="tc.expected_result"></span></div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="setUatStatus(tc.id, 'pass')" :class="tc.status === 'pass' ? 'bg-[#F7B05F] text-[#1A1208]' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.2)]'" class="px-3 py-1 rounded text-xs font-bold">Pass</button>
                                    <button @click="setUatStatus(tc.id, 'fail')" :class="tc.status === 'fail' ? 'bg-rose-700 text-white' : 'bg-[#23190D] text-[#C4B5A2] border border-[rgba(247,176,95,0.2)]'" class="px-3 py-1 rounded text-xs font-bold">Fail</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- TAB 4: ONE-CLICK DEPLOYMENT CONSOLE -->
                <div x-show="detailTab === 'deploy'" class="space-y-5" x-cloak>
                    <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.2)] flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold uppercase text-[#C4B5A2]">Environment:</span>
                            <div class="flex bg-[#23190D] p-1 rounded border border-[rgba(247,176,95,0.2)]">
                                <button type="button" @click="deployEnv = 'staging'; loadPreChecks()" :class="deployEnv === 'staging' ? 'bg-[#F7B05F] text-[#1A1208]' : 'text-[#C4B5A2]'" class="px-3 py-1 rounded text-xs font-bold transition">Staging</button>
                                <button type="button" @click="deployEnv = 'production'; loadPreChecks()" :class="deployEnv === 'production' ? 'bg-[#F7B05F] text-[#1A1208]' : 'text-[#C4B5A2]'" class="px-3 py-1 rounded text-xs font-bold transition">Production</button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button x-show="deployEnv === 'staging'" @click="triggerDeployStaging()" :disabled="isDeploying" class="px-4 py-2 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] font-bold rounded-md text-xs transition disabled:opacity-50">
                                <span x-text="isDeploying ? 'Deploying...' : 'Deploy to Staging'"></span>
                            </button>

                            <button x-show="deployEnv === 'production'" @click="triggerDeployProduction()" :disabled="isDeploying || !preChecksData.can_deploy" :class="preChecksData.can_deploy ? 'bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208]' : 'bg-[#23190D] text-[#C4B5A2]/40 border border-[rgba(247,176,95,0.1)] cursor-not-allowed'" class="px-5 py-2.5 font-bold rounded-md text-xs transition">
                                <span x-text="isDeploying ? 'Deploying to Production...' : 'Deploy to Production'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Pre-Deployment Checklist -->
                    <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.18)]">
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-[rgba(247,176,95,0.14)] text-xs">
                            <span class="font-bold text-[#F7F3EC] uppercase">Pre-Deployment Safety Checklist (Bab 10.5)</span>
                            <span class="font-bold text-[#F7B05F]" x-text="preChecksData.can_deploy ? '7/7 SYARAT LOLOS' : 'TERKUNCI — SYARAT BELUM PENUH'"></span>
                        </div>
                        <div class="divide-y divide-[rgba(247,176,95,0.1)] text-xs">
                            <template x-for="chk in (preChecksData.checks || [])" :key="chk.name">
                                <div class="py-2 flex items-center justify-between gap-4">
                                    <span :class="chk.passed ? 'text-[#F7F3EC]' : 'text-[#C4B5A2]/60'" x-text="(chk.passed ? '[OK] ' : '[X] ') + chk.name"></span>
                                    <span class="font-mono text-[11px]" :class="chk.passed ? 'text-[#F7B05F]' : 'text-rose-400'" x-text="chk.detail"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Terminal Output -->
                    <div class="bg-black border border-[rgba(247,176,95,0.2)] rounded p-4 font-mono text-xs">
                        <div class="pb-2 mb-2 border-b border-[rgba(247,176,95,0.14)] text-[#C4B5A2] text-[10px] flex justify-between">
                            <span>TERMINAL OUTPUT — DEPLOYMENT RUNNER</span>
                            <span class="text-[#F7B05F]">READY</span>
                        </div>
                        <div class="h-40 overflow-y-auto space-y-1 text-[#C4B5A2]" id="terminal-log">
                            <template x-for="(line, idx) in terminalLines" :key="idx">
                                <div :class="line.includes('Successful') || line.includes('LIVE') ? 'text-[#F7B05F] font-bold' : 'text-[#C4B5A2]'" x-text="line"></div>
                            </template>
                            <div x-show="terminalLines.length === 0" class="text-[#C4B5A2]/50 italic">
                                Tekan tombol Deploy untuk menjalankan pipeline dan memantau log eksekusi.
                            </div>
                        </div>
                    </div>

                    <!-- Deployment History -->
                    <div class="bg-[#1A1208] p-4 rounded border border-[rgba(247,176,95,0.16)]">
                        <div class="text-xs font-bold text-[#F7F3EC] uppercase mb-2">Riwayat Rilis & Snapshot Backup</div>
                        <div class="divide-y divide-[rgba(247,176,95,0.1)] text-xs">
                            <template x-for="run in (activeProject.lead ? activeProject.lead.deployment_runs : [])" :key="run.id">
                                <div class="py-2.5 flex items-center justify-between gap-4">
                                    <div>
                                        <span class="font-mono font-bold text-[#F7B05F]" x-text="run.release_version"></span>
                                        <span class="ml-2 uppercase text-[10px] text-[#C4B5A2]" x-text="run.environment + ' • ' + run.commit_sha"></span>
                                        <div class="text-[10px] text-[#C4B5A2]/75">Backup Ref: <span class="font-mono" x-text="run.backup_reference || '-'"></span></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="uppercase text-[10px] font-bold text-[#F7B05F]" x-text="run.status"></span>
                                        <button @click="rollbackAction(run.id)" class="px-2.5 py-1 border border-[rgba(247,176,95,0.3)] hover:border-[#F7B05F] text-[#C4B5A2] hover:text-[#F7B05F] rounded text-[10px] font-bold">Rollback</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL: CREATE LEAD -->
    <div x-show="showCreateModal" class="fixed inset-0 bg-[#1A1208]/90 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] text-[#1A1208] rounded-lg max-w-md w-full p-6 border border-[rgba(119,82,41,0.3)]" @click.outside="showCreateModal = false">
            <h3 class="text-base font-bold mb-1">Tambah Calon Klien / Lead Baru</h3>
            <p class="text-xs text-[#524538] mb-4">Catat inquiry untuk sesi konsultasi & discovery proses bisnis</p>

            <form @submit.prevent="submitCreateLead()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">Nama PIC / Owner</label>
                    <input type="text" x-model="newLead.client_name" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">Nama Usaha / Brand</label>
                    <input type="text" x-model="newLead.business_name" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">Jenis Bisnis</label>
                    <input type="text" x-model="newLead.business_type" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">Email</label>
                    <input type="email" x-model="newLead.email" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">No. WhatsApp</label>
                    <input type="text" x-model="newLead.phone" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div>
                    <label class="block font-semibold text-[#524538] mb-1">Catatan Awal</label>
                    <textarea x-model="newLead.notes" rows="2" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded"></textarea>
                </div>

                <div class="flex gap-2 pt-3">
                    <button type="button" @click="showCreateModal = false" class="flex-1 py-2 bg-[#F2EBE1] text-[#524538] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded font-bold">Simpan Lead</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function portalApp() {
    return {
        leads: [],
        search: '',
        statusFilter: '',
        showDetailModal: false,
        showCreateModal: false,
        activeProject: {},
        detailTab: 'scope',
        deployEnv: 'staging',
        preChecksData: {},
        isDeploying: false,
        terminalLines: [],
        selectedStatus: '',
        qrisPayments: [],
        qrisMeta: {},
        newLead: { client_name: '', business_name: '', business_type: '', email: '', phone: '', notes: '' },

        get totalVerifiedRevenue() {
            return this.leads.filter(l => l.payment_verified).reduce((sum, l) => sum + (l.total_cost || 0), 0);
        },
        get totalMonthlyRecurring() {
            return this.leads.reduce((sum, l) => sum + (l.monthly_cost || 0), 0);
        },
        get readyToDeployCount() {
            return this.leads.filter(l => ['ready_to_deploy', 'deploying', 'live'].includes(l.status)).length;
        },

        async initPortal() {
            await this.loadLeads();
            await this.loadQrisPayments();
            this.$nextTick(() => lucide.createIcons());
        },

        async loadQrisPayments() {
            try {
                const res = await fetch('/api/portal/qris-payments');
                const data = await res.json();
                if (data.status === 'success') {
                    this.qrisPayments = data.data.payments || [];
                    this.qrisMeta = data.data;
                }
            } catch (err) { console.error(err); }
        },

        async simulateQrisWebhook(amount) {
            const secret = this.qrisMeta.webhook_secret || 'seka-yasa-qris-2026';
            const formatted = Number(amount).toLocaleString('id-ID');
            const res = await fetch('/api/webhooks/qris-mutation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-QRIS-WEBHOOK-SECRET': secret,
                },
                body: JSON.stringify({
                    message: `Pembayaran QRIS sebesar Rp ${formatted} berhasil diterima dari GoPay Merchant`,
                }),
            });
            const result = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Webhook Terdeteksi!', text: result.message, confirmButtonColor: '#775229' });
                await this.loadQrisPayments();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: result.message, confirmButtonColor: '#775229' });
            }
        },

        async loadLeads() {
            try {
                let url = '/api/portal/leads?';
                if (this.statusFilter) url += `status=${this.statusFilter}&`;
                if (this.search) url += `search=${encodeURIComponent(this.search)}`;
                const res = await fetch(url);
                const data = await res.json();
                if (data.status === 'success') this.leads = data.data;
            } catch (err) { console.error(err); }
            this.$nextTick(() => lucide.createIcons());
        },

        async openProjectDetail(id) {
            try {
                const res = await fetch(`/api/portal/leads/${id}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.activeProject = data.data;
                    this.selectedStatus = data.data.lead.status;
                    this.detailTab = 'scope';
                    this.showDetailModal = true;
                    this.$nextTick(() => lucide.createIcons());
                }
            } catch (err) { console.error(err); }
        },

        async loadPreChecks() {
            if (!this.activeProject.lead) return;
            try {
                const res = await fetch(`/api/deployment/${this.activeProject.lead.id}/prechecks?environment=${this.deployEnv}`);
                const data = await res.json();
                if (data.status === 'success') this.preChecksData = data.data;
            } catch (err) { console.error(err); }
            this.$nextTick(() => lucide.createIcons());
        },

        async changeStatus() {
            try {
                const res = await fetch(`/api/portal/leads/${this.activeProject.lead.id}/status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ status: this.selectedStatus }),
                });
                const result = await res.json();
                if (res.ok) {
                    Swal.fire({ icon: 'success', title: 'Status Diperbarui', timer: 1200, showConfirmButton: false });
                    await this.openProjectDetail(this.activeProject.lead.id);
                    await this.loadLeads();
                } else {
                    Swal.fire({ icon: 'error', title: 'Transisi Ditolak', text: result.message, confirmButtonColor: '#775229' });
                    this.selectedStatus = this.activeProject.lead.status;
                }
            } catch (err) { console.error(err); }
        },

        async verifyPaymentAction(contractId) {
            const { value: ref } = await Swal.fire({
                title: 'Verifikasi Pembayaran 100% Lunas',
                text: 'Masukkan nomor referensi transfer bank klien:',
                input: 'text',
                inputPlaceholder: 'Contoh: BCA-TRF-98237192837',
                showCancelButton: true,
                confirmButtonText: 'Verifikasi & Buka Development',
                confirmButtonColor: '#775229',
            });
            if (ref) {
                const res = await fetch(`/api/portal/contracts/${contractId}/verify-payment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ payment_reference: ref }),
                });
                const result = await res.json();
                if (res.ok) {
                    Swal.fire({ icon: 'success', title: 'Payment Gate Lolos', text: result.message, confirmButtonColor: '#775229' });
                    await this.openProjectDetail(this.activeProject.lead.id);
                    await this.loadLeads();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message, confirmButtonColor: '#775229' });
                }
            }
        },

        async setUatStatus(tcId, status) {
            const res = await fetch(`/api/portal/uat/${tcId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status }),
            });
            if (res.ok) {
                await this.openProjectDetail(this.activeProject.lead.id);
                await this.loadPreChecks();
            }
        },

        async triggerDeployStaging() {
            this.isDeploying = true;
            this.terminalLines = ["[00:00] Initializing Staging One-Click Deployment..."];
            try {
                const res = await fetch(`/api/deployment/${this.activeProject.lead.id}/deploy-staging`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                });
                const result = await res.json();
                if (res.ok) {
                    this.terminalLines = result.data.log_output.split("\n");
                    Swal.fire({ icon: 'success', title: 'Staging Berhasil Di-Deploy', confirmButtonColor: '#775229' });
                    await this.openProjectDetail(this.activeProject.lead.id);
                    await this.loadLeads();
                }
            } finally { this.isDeploying = false; }
        },

        async triggerDeployProduction() {
            const { isConfirmed } = await Swal.fire({
                title: 'Konfirmasi Deploy ke Production',
                text: 'Snapshot database otomatis dibuat sebelum migrasi. Lanjutkan rilis produksi?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#775229',
                confirmButtonText: 'Ya, Jalankan Deploy',
            });
            if (!isConfirmed) return;

            this.isDeploying = true;
            this.terminalLines = ["[00:00] Initializing Production Safety Gate..."];
            try {
                const res = await fetch(`/api/deployment/${this.activeProject.lead.id}/deploy-production`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                });
                const result = await res.json();
                if (res.ok) {
                    this.terminalLines = result.data.log_output.split("\n");
                    Swal.fire({ icon: 'success', title: 'Aplikasi POS Resmi LIVE', confirmButtonColor: '#775229' });
                    await this.openProjectDetail(this.activeProject.lead.id);
                    await this.loadLeads();
                } else {
                    Swal.fire({ icon: 'error', title: 'Production Gate Ditolak', text: result.message, confirmButtonColor: '#775229' });
                }
            } finally { this.isDeploying = false; }
        },

        async rollbackAction(runId) {
            const { isConfirmed } = await Swal.fire({
                title: 'Lakukan Rollback?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#775229',
                confirmButtonText: 'Ya, Rollback',
            });
            if (isConfirmed) {
                const res = await fetch(`/api/deployment/runs/${runId}/rollback`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                });
                if (res.ok) {
                    Swal.fire({ icon: 'success', title: 'Rollback Berhasil', confirmButtonColor: '#775229' });
                    await this.openProjectDetail(this.activeProject.lead.id);
                }
            }
        },

        openCreateLeadModal() { this.showCreateModal = true; },

        async submitCreateLead() {
            const res = await fetch('/api/portal/leads', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(this.newLead),
            });
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Lead Ditambahkan', timer: 1200, showConfirmButton: false });
                this.showCreateModal = false;
                this.newLead = { client_name: '', business_name: '', business_type: '', email: '', phone: '', notes: '' };
                await this.loadLeads();
            }
        },

        formatStatusLabel(st) {
            const map = {
                new_lead: 'New Lead',
                discovery: 'Discovery',
                proposal_sent: 'Proposal Sent',
                payment_pending: 'Payment Gate',
                ready_for_dev: 'Ready for Dev',
                in_dev: 'In Development',
                client_uat: 'Client UAT',
                ready_to_deploy: 'Ready to Deploy',
                live: 'Live Production',
                monthly_service_active: 'Monthly Service',
            };
            return map[st] || st;
        },

        getStatusBadgeClass(st) {
            if (st === 'live' || st === 'monthly_service_active' || st === 'ready_to_deploy') return 'bg-[#F7B05F]/20 text-[#F7B05F] border border-[#F7B05F]/40';
            if (st === 'payment_pending') return 'bg-rose-950/60 text-rose-300 border border-rose-500/30';
            return 'bg-[#1A1208] text-[#C4B5A2] border border-[rgba(247,176,95,0.2)]';
        },

        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) {
            if (!str) return '-';
            return new Date(str).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>
@endpush
