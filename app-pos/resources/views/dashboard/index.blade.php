@extends('layouts.app')

@section('title', 'Dashboard Pemilik & Admin — Seka Yasa POS')

@section('content')
<div class="min-h-screen flex bg-[#FAF7F2] text-[#1A1208]" x-data="dashboardApp()" x-init="initDashboard()">

    <!-- Sidebar Navigation (Deep Espresso #1A1208) -->
    <aside class="w-64 bg-[#1A1208] text-[#C4B5A2] flex flex-col justify-between shrink-0 border-r border-[rgba(247,176,95,0.16)] z-20">
        <div>
            <!-- Brand -->
            <div class="p-5 border-b border-[rgba(247,176,95,0.16)]">
                <a href="/" class="block">
                    <span class="text-[10px] font-semibold text-[#F7B05F] uppercase tracking-wider">Seka Yasa POS</span>
                    <h1 class="text-sm font-bold text-[#F7F3EC] mt-0.5" x-text="business.name || 'Manajemen Bisnis'"></h1>
                </a>
            </div>

            <!-- Outlet Switcher -->
            <div class="p-4 border-b border-[rgba(247,176,95,0.16)]">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#C4B5A2] mb-1">Outlet Aktif</label>
                <select x-model="selectedOutletId" @change="onOutletChange()" class="w-full px-2.5 py-1.5 bg-[#23190D] border border-[rgba(247,176,95,0.22)] rounded text-xs font-medium text-[#F7F3EC] focus:outline-none">
                    <option value="">Semua Outlet</option>
                    <template x-for="out in outlets" :key="out.id">
                        <option :value="out.id" x-text="out.name"></option>
                    </template>
                </select>
            </div>

            <!-- Menu Items -->
            <nav class="p-3 space-y-1 text-xs font-semibold">
                <button @click="setTab('overview')" :class="tab === 'overview' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'text-[#C4B5A2] hover:bg-[#23190D] hover:text-[#F7F3EC]'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Ringkasan Bisnis
                </button>
                <button @click="setTab('products')" :class="tab === 'products' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'text-[#C4B5A2] hover:bg-[#23190D] hover:text-[#F7F3EC]'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                    <i data-lucide="package" class="w-4 h-4"></i> Produk & Kategori
                </button>
                <button @click="setTab('inventory')" :class="tab === 'inventory' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'text-[#C4B5A2] hover:bg-[#23190D] hover:text-[#F7F3EC]'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                    <i data-lucide="boxes" class="w-4 h-4"></i> Inventori & Stok
                </button>
                <button @click="setTab('reports')" :class="tab === 'reports' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'text-[#C4B5A2] hover:bg-[#23190D] hover:text-[#F7F3EC]'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Laporan & Laba
                </button>
                <button @click="setTab('outlets')" :class="tab === 'outlets' ? 'bg-[#F7B05F] text-[#1A1208] font-bold' : 'text-[#C4B5A2] hover:bg-[#23190D] hover:text-[#F7F3EC]'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                    <i data-lucide="store" class="w-4 h-4"></i> Outlet & Profil
                </button>
                <a href="/portal" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition text-[#F7B05F] hover:bg-[#23190D] border border-[rgba(247,176,95,0.2)] mt-2">
                    <i data-lucide="rocket" class="w-4 h-4"></i> Portal Deploy PRD v2
                </a>
            </nav>
        </div>

        <!-- Sidebar Bottom -->
        <div class="p-4 border-t border-[rgba(247,176,95,0.16)] space-y-2.5">
            <a href="/pos" class="w-full py-2.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded text-xs font-bold transition flex items-center justify-center gap-2">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i> Buka Layar Kasir
            </a>

            <div class="flex items-center justify-between pt-2">
                <div class="leading-tight">
                    <div class="text-xs font-bold text-[#F7F3EC] truncate max-w-[140px]" x-text="user.name"></div>
                    <span class="text-[10px] text-[#C4B5A2] uppercase font-semibold" x-text="user.role"></span>
                </div>
                <button @click="logout()" class="p-1.5 text-[#C4B5A2] hover:text-rose-400 rounded transition" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <!-- Header Bar -->
        <header class="bg-[#F3EDE4] border-b border-[rgba(119,82,41,0.18)] px-8 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <div class="text-[11px] font-semibold text-[#775229] uppercase tracking-wider">Panel Manajemen</div>
                <h2 class="text-lg font-bold text-[#1A1208]" x-text="tabTitles[tab] || 'Dashboard'"></h2>
            </div>

            <div class="flex items-center gap-3">
                <button @click="refreshData()" class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.22)] hover:border-[#775229] text-[#1A1208] rounded text-xs font-semibold transition flex items-center gap-1.5">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> Muat Ulang
                </button>
            </div>
        </header>

        <div class="p-8 space-y-6">

            <!-- TAB 1: OVERVIEW -->
            <div x-show="tab === 'overview'" class="space-y-6" x-cloak>
                <!-- Structured Metric Strip (Flat, Hairline Bordered) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded-md divide-y sm:divide-y-0 sm:divide-x divide-[rgba(119,82,41,0.18)]">
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">01 / Penjualan Hari Ini</span>
                        <div class="text-2xl font-bold text-[#1A1208] mt-1 font-mono" x-text="formatRupiah(dashboardData.today_sales)"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">02 / Total Transaksi</span>
                        <div class="text-2xl font-bold text-[#1A1208] mt-1 font-mono" x-text="dashboardData.today_transactions || 0"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">03 / Rata-Rata Keranjang</span>
                        <div class="text-2xl font-bold text-[#1A1208] mt-1 font-mono" x-text="formatRupiah(dashboardData.today_avg_basket)"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">04 / Peringatan Stok Menipis</span>
                        <div class="text-2xl font-bold text-rose-700 mt-1 font-mono" x-text="dashboardData.low_stock_count || 0"></div>
                    </div>
                </div>

                <!-- Charts & Top Products -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-[#F3EDE4] p-6 rounded-md border border-[rgba(119,82,41,0.2)]">
                        <h3 class="text-sm font-bold text-[#1A1208] mb-4">Tren Penjualan 7 Hari Terakhir</h3>
                        <div class="h-64">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-[#F3EDE4] p-6 rounded-md border border-[rgba(119,82,41,0.2)] flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-[#1A1208] mb-4">Produk Terlaris</h3>
                            <div class="divide-y divide-[rgba(119,82,41,0.16)]">
                                <template x-for="(prod, idx) in dashboardData.top_products" :key="idx">
                                    <div class="py-2.5 flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-bold text-[#1A1208]" x-text="(idx + 1) + '. ' + prod.product_name"></div>
                                            <div class="text-[11px] text-[#524538]" x-text="prod.total_qty + ' terjual'"></div>
                                        </div>
                                        <span class="font-bold text-[#775229] font-mono" x-text="formatRupiah(prod.total_revenue)"></span>
                                    </div>
                                </template>
                                <div x-show="!dashboardData.top_products || dashboardData.top_products.length === 0" class="text-xs text-[#524538] py-6 text-center">
                                    Belum ada penjualan tercatat
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[rgba(119,82,41,0.18)] mt-4">
                            <span class="text-[11px] font-bold uppercase text-[#775229] block mb-2">Metode Pembayaran</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="pay in dashboardData.payment_breakdown" :key="pay.payment_method">
                                    <span class="px-2.5 py-1 bg-[#FAF7F2] border border-[rgba(119,82,41,0.2)] text-[#1A1208] rounded text-xs font-semibold">
                                        <span class="uppercase" x-text="pay.payment_method"></span>: <b x-text="pay.count"></b>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PRODUCTS -->
            <div x-show="tab === 'products'" class="space-y-4" x-cloak>
                <div class="flex items-center justify-between gap-4">
                    <input type="text" x-model="productSearch" @input.debounce.300ms="loadProductsTable()" placeholder="Cari nama, SKU, atau barcode..." class="px-3.5 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-xs w-72 focus:outline-none focus:border-[#775229]">
                    <button @click="openCreateProductModal()" class="px-4 py-2 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded text-xs font-bold transition">
                        + Tambah Produk Baru
                    </button>
                </div>

                <div class="bg-[#F3EDE4] rounded-md border border-[rgba(119,82,41,0.22)] overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#1A1208] text-[#F7F3EC] font-semibold uppercase">
                            <tr>
                                <th class="p-3.5">Produk</th>
                                <th class="p-3.5">SKU / Barcode</th>
                                <th class="p-3.5">Kategori</th>
                                <th class="p-3.5 text-right">Harga Beli</th>
                                <th class="p-3.5 text-right">Harga Jual</th>
                                <th class="p-3.5 text-center">Stok</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(119,82,41,0.16)] font-medium text-[#1A1208]">
                            <template x-for="p in productList" :key="p.id">
                                <tr class="hover:bg-[#FAF7F2]/60">
                                    <td class="p-3.5 font-bold" x-text="p.name"></td>
                                    <td class="p-3.5 font-mono text-[11px] text-[#524538]" x-text="(p.sku || '-') + ' / ' + (p.barcode || '-')"></td>
                                    <td class="p-3.5"><span class="px-2 py-0.5 rounded bg-[#F2EBE1] text-[#775229] font-semibold text-[10px]" x-text="p.category ? p.category.name : '-'"></span></td>
                                    <td class="p-3.5 text-right font-mono" x-text="formatRupiah(p.buy_price)"></td>
                                    <td class="p-3.5 text-right font-bold font-mono text-[#775229]" x-text="formatRupiah(p.sell_price)"></td>
                                    <td class="p-3.5 text-center font-bold" x-text="p.current_stock"></td>
                                    <td class="p-3.5 text-center">
                                        <button @click="archiveProduct(p.id)" class="text-rose-700 hover:underline text-[11px] font-bold">Arsip</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: INVENTORY -->
            <div x-show="tab === 'inventory'" class="space-y-4" x-cloak>
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[#1A1208]">Audit Mutasi Stok (Stock Movements)</h3>
                    <button @click="showAdjustmentModal = true" class="px-4 py-2 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded text-xs font-bold transition">
                        Penyesuaian Stok
                    </button>
                </div>

                <div class="bg-[#F3EDE4] rounded-md border border-[rgba(119,82,41,0.22)] overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#1A1208] text-[#F7F3EC] font-semibold uppercase">
                            <tr>
                                <th class="p-3.5">Waktu</th>
                                <th class="p-3.5">Produk</th>
                                <th class="p-3.5">Jenis</th>
                                <th class="p-3.5 text-center">Perubahan</th>
                                <th class="p-3.5 text-center">Stok Akhir</th>
                                <th class="p-3.5">Alasan</th>
                                <th class="p-3.5">Pengguna</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(119,82,41,0.16)] text-[#1A1208]">
                            <template x-for="m in stockMovements" :key="m.id">
                                <tr>
                                    <td class="p-3.5 text-[#524538] text-[11px]" x-text="formatDate(m.created_at)"></td>
                                    <td class="p-3.5 font-bold" x-text="m.product ? m.product.name : '-'"></td>
                                    <td class="p-3.5 font-mono text-[11px] uppercase text-[#775229]" x-text="m.type"></td>
                                    <td class="p-3.5 text-center font-bold font-mono" x-text="(m.quantity_change > 0 ? '+' : '') + m.quantity_change"></td>
                                    <td class="p-3.5 text-center font-mono font-bold" x-text="m.quantity_after"></td>
                                    <td class="p-3.5 text-[#524538] text-[11px]" x-text="m.reason"></td>
                                    <td class="p-3.5 text-[#524538]" x-text="m.user ? m.user.name : '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: REPORTS -->
            <div x-show="tab === 'reports'" class="space-y-6" x-cloak>
                <div class="bg-[#F3EDE4] p-4 rounded-md border border-[rgba(119,82,41,0.22)] flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#524538] mb-1">Dari Tanggal</label>
                        <input type="date" x-model="reportStartDate" class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#524538] mb-1">Sampai Tanggal</label>
                        <input type="date" x-model="reportEndDate" class="px-3 py-1.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-xs font-semibold">
                    </div>
                    <button @click="loadReports()" class="px-4 py-2 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded text-xs font-bold">
                        Tampilkan Laporan
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded-md divide-y sm:divide-y-0 sm:divide-x divide-[rgba(119,82,41,0.18)]">
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">Total Omset</span>
                        <div class="text-xl font-bold text-[#1A1208] mt-1 font-mono" x-text="formatRupiah(profitData.summary ? profitData.summary.total_revenue : 0)"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">Total HPP (Modal)</span>
                        <div class="text-xl font-bold text-[#524538] mt-1 font-mono" x-text="formatRupiah(profitData.summary ? profitData.summary.total_cogs : 0)"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">Estimasi Laba Kotor</span>
                        <div class="text-xl font-bold text-[#775229] mt-1 font-mono" x-text="formatRupiah(profitData.summary ? profitData.summary.total_gross_profit : 0)"></div>
                    </div>
                    <div class="p-5">
                        <span class="text-xs font-semibold text-[#775229]">Margin Laba</span>
                        <div class="text-xl font-bold text-[#1A1208] mt-1 font-mono" x-text="(profitData.summary ? profitData.summary.profit_margin_percentage : 0) + '%'"></div>
                    </div>
                </div>

                <div class="bg-[#F3EDE4] rounded-md border border-[rgba(119,82,41,0.22)] overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#1A1208] text-[#F7F3EC] font-semibold uppercase">
                            <tr>
                                <th class="p-3.5">Produk</th>
                                <th class="p-3.5 text-center">Terjual</th>
                                <th class="p-3.5 text-right">HPP</th>
                                <th class="p-3.5 text-right">Harga Jual</th>
                                <th class="p-3.5 text-right">Omset</th>
                                <th class="p-3.5 text-right">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(119,82,41,0.16)] text-[#1A1208]">
                            <template x-for="item in profitData.products" :key="item.product_name">
                                <tr>
                                    <td class="p-3.5 font-bold" x-text="item.product_name"></td>
                                    <td class="p-3.5 text-center font-bold" x-text="item.qty_sold"></td>
                                    <td class="p-3.5 text-right font-mono" x-text="formatRupiah(item.avg_buy_price)"></td>
                                    <td class="p-3.5 text-right font-mono" x-text="formatRupiah(item.avg_sell_price)"></td>
                                    <td class="p-3.5 text-right font-mono" x-text="formatRupiah(item.total_revenue)"></td>
                                    <td class="p-3.5 text-right font-bold font-mono text-[#775229]" x-text="formatRupiah(item.gross_profit)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 5: OUTLETS -->
            <div x-show="tab === 'outlets'" class="grid grid-cols-1 md:grid-cols-2 gap-6" x-cloak>
                <div class="bg-[#F3EDE4] p-6 rounded-md border border-[rgba(119,82,41,0.22)] space-y-4">
                    <h3 class="text-sm font-bold text-[#1A1208] border-b border-[rgba(119,82,41,0.18)] pb-2">Daftar Outlet</h3>
                    <div class="divide-y divide-[rgba(119,82,41,0.14)]">
                        <template x-for="out in outlets" :key="out.id">
                            <div class="py-3 flex justify-between items-center text-xs">
                                <div>
                                    <div class="font-bold text-[#1A1208]" x-text="out.name"></div>
                                    <div class="text-[#524538]" x-text="out.address || '-'"></div>
                                </div>
                                <span class="px-2 py-0.5 bg-[#F2EBE1] text-[#775229] rounded font-bold">Aktif</span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-[#F3EDE4] p-6 rounded-md border border-[rgba(119,82,41,0.22)] space-y-3 text-xs">
                    <h3 class="text-sm font-bold text-[#1A1208] border-b border-[rgba(119,82,41,0.18)] pb-2">Profil Bisnis</h3>
                    <div class="flex justify-between py-1"><span class="text-[#524538]">Nama Bisnis:</span><b x-text="business.name"></b></div>
                    <div class="flex justify-between py-1"><span class="text-[#524538]">Pemilik:</span><b x-text="business.owner_name"></b></div>
                    <div class="flex justify-between py-1"><span class="text-[#524538]">Email:</span><b x-text="business.email"></b></div>
                    <div class="flex justify-between py-1"><span class="text-[#524538]">Mata Uang & Pajak:</span><b x-text="business.currency + ' (PPN ' + business.tax_percentage + '%)'"></b></div>
                </div>
            </div>

        </div>

    </main>

    <!-- Stock Adjustment Modal -->
    <div x-show="showAdjustmentModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-sm w-full p-6 border border-[rgba(119,82,41,0.3)]" @click.outside="showAdjustmentModal = false">
            <h3 class="text-sm font-bold text-[#1A1208] mb-3">Penyesuaian Stok</h3>
            <form @submit.prevent="submitAdjustment()" class="space-y-3 text-xs">
                <select x-model="adjProduct" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                    <template x-for="p in productList" :key="p.id">
                        <option :value="p.id" x-text="p.name + ' (Stok: ' + p.current_stock + ')'"></option>
                    </template>
                </select>
                <select x-model="adjType" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                    <option value="adjustment_add">Tambah Stok (+)</option>
                    <option value="adjustment_sub">Kurangi Stok (-)</option>
                </select>
                <input type="number" x-model.number="adjQty" required min="1" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded font-bold">
                <input type="text" x-model="adjReason" required placeholder="Alasan audit (wajib)" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showAdjustmentModal = false" class="flex-1 py-2 bg-[#F2EBE1] text-[#524538] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#775229] text-[#FAF7F2] rounded font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Product Modal -->
    <div x-show="showCreateProductModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-md w-full p-6 border border-[rgba(119,82,41,0.3)]" @click.outside="showCreateProductModal = false">
            <h3 class="text-sm font-bold text-[#1A1208] mb-3">Tambah Produk Baru</h3>
            <form @submit.prevent="submitCreateProduct()" class="space-y-3 text-xs">
                <input type="text" x-model="newProduct.name" required placeholder="Nama Produk" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" x-model="newProduct.sku" placeholder="SKU" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                    <input type="text" x-model="newProduct.barcode" placeholder="Barcode" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" x-model.number="newProduct.buy_price" required min="0" placeholder="Harga Beli" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                    <input type="number" x-model.number="newProduct.sell_price" required min="0" placeholder="Harga Jual" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                </div>
                <input type="number" x-model.number="newProduct.initial_stock" min="0" placeholder="Stok Awal" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCreateProductModal = false" class="flex-1 py-2 bg-[#F2EBE1] text-[#524538] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#775229] text-[#FAF7F2] rounded font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    return {
        token: localStorage.getItem('pos_token') || '',
        user: JSON.parse(localStorage.getItem('pos_user') || '{}'),
        business: {},
        outlets: [],
        selectedOutletId: '',
        tab: 'overview',
        tabTitles: {
            overview: 'Ringkasan Bisnis & Analitik',
            products: 'Manajemen Produk & Kategori',
            inventory: 'Inventori & Audit Mutasi Stok',
            reports: 'Laporan Penjualan & Laba Kotor',
            outlets: 'Manajemen Outlet & Profil Bisnis',
        },
        dashboardData: {},
        salesChartInstance: null,
        productList: [],
        productSearch: '',
        showCreateProductModal: false,
        newProduct: { name: '', sku: '', barcode: '', buy_price: 0, sell_price: 0, initial_stock: 50 },
        stockMovements: [],
        showAdjustmentModal: false,
        adjProduct: '',
        adjType: 'adjustment_add',
        adjQty: 10,
        adjReason: '',
        reportStartDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        reportEndDate: new Date().toISOString().split('T')[0],
        profitData: {},

        async initDashboard() {
            if (!this.token) { window.location.href = '/login'; return; }
            await this.loadInitialProfile();
            await this.loadOverview();
        },
        async authFetch(url, options = {}) {
            options.headers = { ...options.headers, 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            const res = await fetch(url, options);
            if (res.status === 401) { localStorage.removeItem('pos_token'); window.location.href = '/login'; throw new Error('Expired'); }
            return res;
        },
        async loadInitialProfile() {
            const res = await this.authFetch('/api/me');
            const data = await res.json();
            if (data.status === 'success') { this.user = data.data; this.business = data.data.business || {}; this.outlets = this.business.outlets || []; }
        },
        setTab(t) {
            this.tab = t;
            if (t === 'overview') this.loadOverview();
            if (t === 'products') this.loadProductsTable();
            if (t === 'inventory') this.loadInventoryData();
            if (t === 'reports') this.loadReports();
            this.$nextTick(() => lucide.createIcons());
        },
        onOutletChange() { this.refreshData(); },
        refreshData() { this.setTab(this.tab); },

        async loadOverview() {
            const url = `/api/reports/dashboard${this.selectedOutletId ? '?outlet_id=' + this.selectedOutletId : ''}`;
            const res = await this.authFetch(url);
            const result = await res.json();
            if (result.status === 'success') { this.dashboardData = result.data; this.renderChart(result.data.sales_trend); }
        },
        renderChart(trend) {
            if (!trend) return;
            const ctx = document.getElementById('salesTrendChart');
            if (!ctx) return;
            if (this.salesChartInstance) this.salesChartInstance.destroy();
            this.salesChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trend.map(t => t.date),
                    datasets: [{
                        label: 'Omset (Rp)',
                        data: trend.map(t => t.total),
                        borderColor: '#775229',
                        backgroundColor: 'rgba(247, 176, 95, 0.18)',
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#1A1208',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (val) => 'Rp ' + (val / 1000) + 'k' } } }
                }
            });
        },
        async loadProductsTable() {
            let url = `/api/products?${this.selectedOutletId ? 'outlet_id=' + this.selectedOutletId : ''}`;
            if (this.productSearch) url += `&search=${encodeURIComponent(this.productSearch)}`;
            const res = await this.authFetch(url);
            const data = await res.json();
            if (data.status === 'success') {
                this.productList = data.data;
                if (this.productList.length > 0 && !this.adjProduct) this.adjProduct = this.productList[0].id;
            }
        },
        openCreateProductModal() { this.showCreateProductModal = true; },
        async submitCreateProduct() {
            const res = await this.authFetch('/api/products', { method: 'POST', body: JSON.stringify({ ...this.newProduct, outlet_id: this.selectedOutletId || 1 }) });
            if (res.ok) {
                this.showCreateProductModal = false;
                this.newProduct = { name: '', sku: '', barcode: '', buy_price: 0, sell_price: 0, initial_stock: 50 };
                await this.loadProductsTable();
            }
        },
        async archiveProduct(id) {
            const { isConfirmed } = await Swal.fire({ title: 'Arsipkan Produk?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#775229' });
            if (isConfirmed) { await this.authFetch(`/api/products/${id}`, { method: 'DELETE' }); await this.loadProductsTable(); }
        },
        async loadInventoryData() {
            await this.loadProductsTable();
            const url = `/api/inventory/movements${this.selectedOutletId ? '?outlet_id=' + this.selectedOutletId : ''}`;
            const res = await this.authFetch(url);
            const data = await res.json();
            if (data.status === 'success') this.stockMovements = data.data ? data.data.data : [];
        },
        async submitAdjustment() {
            const res = await this.authFetch('/api/inventory/adjust', {
                method: 'POST',
                body: JSON.stringify({ outlet_id: this.selectedOutletId || 1, product_id: this.adjProduct, type: this.adjType, quantity: this.adjQty, reason: this.adjReason }),
            });
            if (res.ok) { this.showAdjustmentModal = false; this.adjReason = ''; await this.loadInventoryData(); }
        },
        async loadReports() {
            let url = `/api/reports/profit?start_date=${this.reportStartDate}&end_date=${this.reportEndDate}`;
            if (this.selectedOutletId) url += `&outlet_id=${this.selectedOutletId}`;
            const res = await this.authFetch(url);
            const data = await res.json();
            if (data.status === 'success') this.profitData = data.data;
        },
        logout() { localStorage.removeItem('pos_token'); localStorage.removeItem('pos_user'); window.location.href = '/login'; },
        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) { return str ? new Date(str).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'; }
    }
}
</script>
@endpush
