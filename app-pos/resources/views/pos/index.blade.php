@extends('layouts.app')

@section('title', 'Terminal Kasir — Tandha by SEKA GROUP')

@section('content')
<div class="h-[100dvh] flex flex-col bg-[#F7FAF9] text-[#0D2620] overflow-hidden relative" x-data="posApp()" x-init="initPos()">

    <!-- Top Indeterminate Progress Bar -->
    <div x-show="isPosFetching > 0 || isProcessingCheckout" x-cloak class="fixed top-0 left-0 right-0 z-[90] h-0.5 bg-emerald-950/10 overflow-hidden pointer-events-none">
        <div class="h-full bg-[#0D7A5F] tandha-progress-bar"></div>
    </div>

    <!-- Initial Boot Loader -->
    <div x-show="isPosBooting" x-transition.opacity.duration.200ms class="fixed inset-0 z-[85] bg-[#F7FAF9]/95 backdrop-blur-xs flex flex-col items-center justify-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#0D2620] border border-emerald-500/30 flex items-center justify-center p-2.5 shadow-sm">
            <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-full h-full object-contain animate-pulse">
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-[#0D2620]">
            <svg class="w-4 h-4 animate-spin text-[#0D7A5F]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
            <span>Menyiapkan Terminal Kasir...</span>
        </div>
    </div>

    <!-- Top Bar (Deep Espresso #0D2620 — hidden cleanly when embedded inside Dashboard Preview Studio) -->
    <header x-show="!isEmbedded" class="bg-[#0D2620] text-[#FFFFFF] border-b border-[rgba(51,65,85,0.75)] px-3 sm:px-4 py-2 sm:py-2.5 flex flex-wrap items-center justify-between gap-2 z-20 shrink-0">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-lg flex items-center justify-center p-1.5 shrink-0">
                <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-bold text-[#FFFFFF] leading-tight truncate max-w-[160px] sm:max-w-none" x-text="business.name || 'Tandha by SEKA GROUP'"></h1>
                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5 text-[10px] sm:text-[11px] text-[#CBD5E1]">
                    <span class="truncate max-w-[110px] sm:max-w-none" x-text="currentOutlet.name || 'Outlet Utama'"></span>
                    <span class="text-[#0D7A5F] hidden sm:inline">•</span>
                    <span class="hidden sm:inline" x-text="user.name || 'Kasir'"></span>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded shrink-0"
                        :class="isPreviewMode ? 'bg-amber-300 text-slate-950' : 'bg-[#34D399] text-[#0D2620]'"
                        x-text="isPreviewMode ? 'Mode Tinjau' : 'Kasir Aktif'"></span>
                </div>
            </div>
        </div>

        <!-- Shift & Actions (Focused Strictly for Cashier Operational Flow, or Read-Only Branch Preview for Owner) -->
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <!-- Branch Switcher for Owner Read-Only Preview -->
            <div x-show="isPreviewMode && business.outlets && business.outlets.length > 0" class="relative" @click.outside="if (openPosDropdown === 'previewOutlet') openPosDropdown = null" x-cloak>
                <button type="button" @click="togglePosDropdown('previewOutlet')"
                    class="h-8 px-2.5 sm:px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] text-[#FFFFFF] rounded-md text-xs font-medium flex items-center gap-1.5 sm:gap-2 transition">
                    <span class="text-[#CBD5E1] hidden sm:inline">Cabang:</span>
                    <span class="font-semibold text-[#34D399] truncate max-w-[100px] sm:max-w-[140px]" x-text="currentOutlet.name || 'Pilih Outlet'"></span>
                    <svg class="w-3.5 h-3.5 text-[#CBD5E1] shrink-0 transition-transform" :class="openPosDropdown === 'previewOutlet' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="openPosDropdown === 'previewOutlet'" x-transition class="absolute right-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                    <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">Preview Cabang Outlet</div>
                    <template x-for="out in (business.outlets || [])" :key="'prev-out-' + out.id">
                        <button type="button" @click="switchPreviewOutlet(out)"
                            class="w-full px-3 py-2 text-left text-xs flex items-center justify-between gap-2 hover:bg-slate-50"
                            :class="String(currentOutlet.id) === String(out.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                            <span class="truncate" x-text="out.name"></span>
                            <span x-show="String(currentOutlet.id) === String(out.id)">✓</span>
                        </button>
                    </template>
                </div>
            </div>

            <div x-show="!isPreviewMode && activeShift" class="hidden md:flex items-center gap-2 h-8 bg-[#143B32] border border-[rgba(51,65,85,0.75)] text-[#34D399] px-3 rounded-md text-xs font-semibold">
                <span>Kas Awal Shift: <b class="tabular-nums" x-text="formatRupiah(activeShift ? activeShift.initial_cash : 0)"></b></span>
            </div>

            <button x-show="!isPreviewMode && !activeShift && hasPosFeature('pos_require_shift')" @click="showOpenShiftModal = true" class="h-8 px-2.5 sm:px-3 bg-[#34D399] hover:bg-[#10B981] text-[#0D2620] rounded-md text-xs font-bold transition">
                Buka Shift
            </button>
            <button x-show="!isPreviewMode && activeShift" @click="openCloseShiftModal()" class="h-8 px-2.5 sm:px-3 bg-[#143B32] hover:border-[#34D399] border border-[rgba(51,65,85,0.75)] text-[#FFFFFF] rounded-md text-xs font-semibold transition">
                Tutup Shift
            </button>

            <button x-show="hasPosFeature('pos_allow_history')" @click="openHistoryModal()" class="h-8 px-2.5 sm:px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-xs font-semibold text-[#CBD5E1] hover:text-[#34D399] transition flex items-center gap-1.5" title="Riwayat Transaksi">
                <i data-lucide="history" class="w-3.5 h-3.5 shrink-0"></i>
                <span class="hidden sm:inline">Riwayat</span>
            </button>

            <a href="/dashboard" x-show="!isKasirAppMode && user.role === 'owner'" class="h-8 px-2.5 sm:px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md text-xs font-semibold text-[#CBD5E1] hover:text-[#FFFFFF] transition inline-flex items-center">
                Dashboard
            </a>

            <button x-show="!isPreviewMode" @click="logout()" class="h-8 px-2 sm:px-2.5 text-xs font-semibold text-[#CBD5E1] hover:text-rose-400 transition flex items-center gap-1" title="Keluar Akun">
                <i data-lucide="log-out" class="w-3.5 h-3.5 shrink-0"></i>
                <span class="hidden sm:inline">Keluar</span>
            </button>
        </div>
    </header>

    <!-- Owner Preview Notice Banner -->
    <div x-show="user.id && isPreviewMode" x-cloak class="relative bg-white border-b border-amber-200/90 pl-5 pr-4 py-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-700 shrink-0 shadow-2xs">
        <div class="absolute inset-y-0 left-0 w-1 bg-amber-500"></div>
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-7 h-7 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
            </span>
            <div class="min-w-0">
                <span class="px-2 py-0.5 rounded bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold uppercase tracking-wider mr-1.5">Mode Tinjau Pemilik</span>
                <span>Meninjau menu, stok, tarif pajak &amp; tombol kasir <b class="text-[#0D2620]" x-text="currentOutlet.name || 'cabang'"></b>. Transaksi nyata diproses dari akun <b>Kasir</b>.</span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button x-show="isEmbedded && hasPosFeature('pos_allow_history')" type="button" @click="openHistoryModal()" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-md font-semibold transition inline-flex items-center gap-1.5">
                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                <span>Riwayat Outlet</span>
            </button>
            <button type="button" @click="previewSampleReceipt(false)" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-md font-semibold transition">
                Preview Struk Cabang
            </button>
            <a x-show="!isEmbedded" href="/dashboard" class="px-3 py-1.5 bg-[#0D7A5F] text-white rounded-md font-bold hover:bg-[#095C47] transition">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Shift Wajib Dibuka Notice Banner -->
    <div x-show="user.id && !isPreviewMode && !activeShift && hasPosFeature('pos_require_shift')" x-cloak class="bg-amber-50 border-b border-amber-300 px-4 py-2 flex flex-wrap items-center justify-between gap-2 text-xs text-amber-950 shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span><b>Shift Kasir Belum Dibuka:</b> Buka shift dengan mengisi kas awal laci sebelum transaksi agar data kas &amp; penjualan tercatat lengkap.</span>
        </div>
        <button @click="showOpenShiftModal = true" class="px-3 py-1 bg-[#0D2620] text-[#34D399] rounded font-bold hover:bg-[#143B32] transition">
            Buka Shift Sekarang
        </button>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 min-h-0 flex flex-col lg:flex-row overflow-hidden relative">

        <!-- Left Column: Catalog & Barcode (Full screen on Mobile Catalog Tab, 65% on Desktop) -->
        <div :class="mobilePosTab === 'catalog' ? 'flex' : 'hidden lg:flex'"
            class="flex-1 min-h-0 flex flex-col p-3 sm:p-4 overflow-hidden lg:border-r border-[rgba(15,23,42,0.12)] bg-[#F7FAF9] relative">

            <!-- Search, Custom Category/Stock Filters & Barcode Input Bar -->
            <div class="flex flex-wrap items-center gap-2 mb-2.5 shrink-0">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-[#0D7A5F] absolute left-3 top-2.5"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.200ms="filterProducts()" placeholder="Cari nama produk, SKU, atau kategori..." class="w-full pl-9 pr-8 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded-md text-xs text-[#0D2620] placeholder-[#475569]/60 focus:outline-none focus:border-[#0D7A5F]">
                    <button x-show="searchQuery" @click="searchQuery = ''; filterProducts()" class="absolute right-2.5 top-2 text-[#475569]">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Custom Category Filter Dropdown (Scalable for 1000+ SKUs) -->
                <div class="relative flex-1 sm:flex-none" @click.outside="if (openPosDropdown === 'cat') openPosDropdown = null">
                    <button type="button" @click="togglePosDropdown('cat')"
                        class="w-full sm:w-auto px-3 py-2 bg-white border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded-md text-xs font-medium text-[#0D2620] flex items-center gap-2 sm:min-w-[165px] justify-between transition">
                        <span class="truncate" x-text="activePosCatLabel"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openPosDropdown === 'cat' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="openPosDropdown === 'cat'" x-transition class="absolute left-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-64 overflow-y-auto custom-scrollbar">
                        <button type="button" @click="selectedCategory = ''; openPosDropdown = null; filterProducts()"
                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                            :class="!selectedCategory ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                            <span>Semua Kategori</span><span x-show="!selectedCategory">✓</span>
                        </button>
                        <button type="button" @click="selectedCategory = 'none'; openPosDropdown = null; filterProducts()"
                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                            :class="selectedCategory === 'none' ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                            <span>Tanpa Kategori</span><span x-show="selectedCategory === 'none'">✓</span>
                        </button>
                        <div class="my-1 border-t border-slate-100" x-show="categories.length > 0"></div>
                        <template x-for="cat in categories" :key="'poscat-' + cat.id">
                            <button type="button" @click="selectedCategory = String(cat.id); openPosDropdown = null; filterProducts()"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between items-center hover:bg-slate-50"
                                :class="String(selectedCategory) === String(cat.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                <span class="truncate" x-text="cat.name + (cat.products_count !== undefined ? ' (' + cat.products_count + ')' : '')"></span>
                                <span x-show="String(selectedCategory) === String(cat.id)">✓</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Custom Stock Filter Dropdown -->
                <div class="relative flex-1 sm:flex-none" @click.outside="if (openPosDropdown === 'stock') openPosDropdown = null">
                    <button type="button" @click="togglePosDropdown('stock')"
                        class="w-full sm:w-auto px-3 py-2 bg-white border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded-md text-xs font-medium text-[#0D2620] flex items-center gap-2 sm:min-w-[145px] justify-between transition">
                        <span class="truncate" x-text="activePosStockLabel"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openPosDropdown === 'stock' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="openPosDropdown === 'stock'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-48 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                        <template x-for="opt in posStockOptions" :key="opt.value">
                            <button type="button" @click="posStockFilter = opt.value; openPosDropdown = null; filterProducts()"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="posStockFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                <span x-text="opt.label"></span><span x-show="posStockFilter === opt.value">✓</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Barcode Fast Scanner Input (Configurable by Owner/Branch) -->
                <div x-show="hasPosFeature('pos_allow_barcode')" class="relative w-full sm:w-52">
                    <i data-lucide="scan-barcode" class="w-4 h-4 text-[#0D7A5F] absolute left-3 top-2.5"></i>
                    <input type="text" x-model="barcodeInput" @keydown.enter.prevent="scanBarcode()" placeholder="Scan Barcode (Enter)" class="w-full pl-9 pr-3 py-2 bg-[#FFFFFF] border border-[#0D7A5F]/50 rounded-md text-xs tabular-nums text-[#0D2620] focus:outline-none focus:border-[#0D7A5F]">
                </div>
            </div>

            <!-- Catalog Status Strip -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 mb-1 text-[11px] text-slate-500">
                <span x-text="'Menampilkan ' + filteredProducts.length + ' dari ' + products.length + ' menu'"></span>
                <span x-show="categories.length === 0" class="text-amber-700 font-medium">Belum ada kategori menu — atur di menu Produk &amp; Kategori.</span>
            </div>

            <!-- Products Grid with Visual Product Thumbnails -->
            <div class="flex-1 overflow-y-auto custom-scrollbar pr-1 pb-20 lg:pb-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" class="bg-[#FFFFFF] rounded-lg border border-slate-200 overflow-hidden hover:border-[#0D7A5F] hover:shadow-sm transition cursor-pointer flex flex-col justify-between select-none active:scale-[0.98]">
                            <div>
                                <!-- Visual Menu Image + Stock Badge -->
                                <div class="relative h-24 sm:h-28 w-full bg-slate-100 overflow-hidden border-b border-slate-100">
                                    <img :src="product.image_url || '/assets/products/default-product.svg'"
                                        :alt="product.name"
                                        loading="lazy"
                                        onerror="this.src='/assets/products/default-product.svg'"
                                        class="w-full h-full object-cover">
                                    <span :class="product.current_stock > 10 ? 'bg-white/95 text-slate-700 border-slate-200' : (product.current_stock > 0 ? 'bg-amber-50/95 text-amber-800 border-amber-300' : 'bg-rose-600 text-white border-rose-600')"
                                        class="absolute top-2 right-2 text-[10px] px-1.5 py-0.5 rounded border font-semibold shadow-xs">
                                        Stok: <span x-text="product.current_stock"></span>
                                    </span>
                                </div>
                                <div class="p-2.5 sm:p-3 pb-0">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-[#0D7A5F]" x-text="product.category ? product.category.name : 'Umum'"></span>
                                    <h3 class="text-xs font-bold text-[#0D2620] line-clamp-2 mt-0.5 leading-snug" x-text="product.name"></h3>
                                </div>
                            </div>

                            <div class="p-2.5 sm:p-3 pt-2 mt-1 flex items-center justify-between border-t border-slate-100">
                                <span class="text-xs font-bold text-[#0D7A5F] tabular-nums" x-text="formatRupiah(product.sell_price)"></span>
                                <span class="px-2 py-0.5 rounded-md border border-slate-200 bg-white text-[11px] font-medium text-slate-700">+ Pilih</span>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="filteredProducts.length === 0" class="h-64 flex flex-col items-center justify-center text-[#475569]" x-cloak>
                    <p class="text-sm">Tidak ada produk ditemukan</p>
                </div>
            </div>

            <!-- Sticky Mobile Cart Summary Bar (Only visible on < lg screens & Preview Mobile Mode) -->
            <div x-show="mobilePosTab === 'catalog'" x-cloak
                class="lg:hidden absolute inset-x-3 bottom-3 z-30">
                <button type="button" @click="mobilePosTab = 'cart'"
                    class="w-full px-4 py-3 rounded-xl bg-[#0D2620] hover:bg-[#143B32] text-white shadow-lg border border-[#34D399]/30 flex items-center justify-between gap-3 text-xs transition active:scale-[0.99]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full bg-[#34D399] text-[#0D2620] font-bold tabular-nums shrink-0" x-text="totalCartQty"></span>
                        <div class="text-left min-w-0">
                            <div class="font-bold text-white leading-tight truncate" x-text="totalCartQty > 0 ? (totalCartQty + ' item di keranjang') : 'Keranjang Belanja'"></div>
                            <div class="text-[11px] text-[#CBD5E1] tabular-nums" x-text="formatRupiah(grandTotal)"></div>
                        </div>
                    </div>
                    <span class="font-bold text-[#34D399] shrink-0 inline-flex items-center gap-1">
                        <span>Lihat &amp; Bayar</span>
                        <span aria-hidden="true">→</span>
                    </span>
                </button>
            </div>

        </div>

        <!-- Right Column: Interactive Cart & Checkout (Full screen on Mobile Cart Tab, 35% on Desktop) -->
        <div :class="mobilePosTab === 'cart' ? 'flex' : 'hidden lg:flex'"
            class="w-full lg:w-96 xl:w-[420px] flex-1 lg:flex-none min-h-0 bg-[#FFFFFF] lg:border-l border-[rgba(15,23,42,0.12)] flex-col justify-between z-10">

            <!-- Cart Header & Customer Picker -->
            <div class="p-3.5 sm:p-4 border-b border-[rgba(15,23,42,0.12)] bg-[#F7FAF9]">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <button type="button" @click="mobilePosTab = 'catalog'"
                            class="lg:hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-[#0D2620] text-[11px] font-semibold shrink-0 transition">
                            <span>← Menu</span>
                        </button>
                        <h2 class="text-sm font-bold text-[#0D2620] truncate" x-text="isPreviewMode ? 'Simulasi Keranjang (Preview)' : 'Keranjang Belanja'"></h2>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" @click="openHeldOrdersModal()" x-show="!isPreviewMode" x-cloak
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-[#0D2620] text-[11px] font-semibold transition">
                            <span>Tertahan</span>
                            <span x-show="heldOrders.length > 0" class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold tabular-nums" x-text="heldOrders.length"></span>
                        </button>
                        <button type="button" @click="holdCurrentOrder()" x-show="!isPreviewMode && cart.length > 0" :disabled="isProcessingHold" x-cloak
                            class="inline-flex items-center px-2.5 py-1 rounded-md border border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-semibold transition disabled:opacity-40">
                            <span x-text="isProcessingHold ? 'Menahan...' : 'Tahan'"></span>
                        </button>
                        <button type="button" @click="clearCart()" x-show="cart.length > 0"
                            class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-200 bg-white hover:bg-rose-50 hover:border-rose-300 text-rose-600 text-[11px] font-medium shrink-0 transition">
                            Kosongkan
                        </button>
                    </div>
                </div>

                <div x-show="hasPosFeature('pos_allow_customer')" class="flex items-center gap-2">
                    <div class="relative flex-1" @click.outside="openCustomerDropdown = false">
                        <button type="button" @click="openCustomerDropdown = !openCustomerDropdown"
                            class="w-full px-3 py-1.5 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] hover:border-[#0D7A5F] rounded text-xs font-medium text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                            <span class="truncate" x-text="selectedCustomerLabel"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openCustomerDropdown ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openCustomerDropdown" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 overflow-hidden">
                            <div class="p-1.5 border-b border-slate-100">
                                <input type="text" x-model="customerSearch" placeholder="Cari nama / no. HP..." class="w-full px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] focus:outline-none focus:border-[#0D7A5F]">
                            </div>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar py-1">
                                <button type="button" @click="selectedCustomerId = ''; openCustomerDropdown = false"
                                    class="w-full px-3 py-1.5 text-left text-xs flex items-center justify-between hover:bg-slate-50"
                                    :class="!selectedCustomerId ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-[#0D2620]'">
                                    <span>Pelanggan Umum (Walk-in)</span>
                                    <span x-show="!selectedCustomerId">✓</span>
                                </button>
                                <template x-for="cust in filteredCustomers" :key="cust.id">
                                    <button type="button" @click="selectedCustomerId = cust.id; openCustomerDropdown = false"
                                        class="w-full px-3 py-1.5 text-left text-xs flex items-center justify-between gap-2 hover:bg-slate-50"
                                        :class="String(selectedCustomerId) === String(cust.id) ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-[#0D2620]'">
                                        <span class="truncate" x-text="cust.name + (cust.phone ? ' (' + cust.phone + ')' : '')"></span>
                                        <span x-show="String(selectedCustomerId) === String(cust.id)">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button x-show="!isPreviewMode" type="button" @click="openCreateCustomerModal()" class="px-2.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium shrink-0 transition" title="Tambah Data Pelanggan">
                        + Pelanggan
                    </button>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-3 sm:p-4 space-y-2">
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div class="flex items-center justify-between gap-2.5 sm:gap-3 p-2.5 sm:p-3 bg-[#F7FAF9] rounded border border-[rgba(15,23,42,0.12)]">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-[#0D2620] truncate" x-text="item.name"></div>
                            <div class="text-[11px] text-[#475569] mt-0.5" x-text="formatRupiah(item.price)"></div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <button @click="updateQty(index, -1)" class="w-7 h-7 sm:w-6 sm:h-6 rounded bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] text-[#0D2620] flex items-center justify-center font-bold">-</button>
                            <span class="w-6 text-center text-xs font-bold tabular-nums text-[#0D2620]" x-text="item.quantity"></span>
                            <button @click="updateQty(index, 1)" class="w-7 h-7 sm:w-6 sm:h-6 rounded bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] text-[#0D2620] flex items-center justify-center font-bold">+</button>
                        </div>

                        <div class="text-right w-20 shrink-0">
                            <div class="text-xs font-bold text-[#0D7A5F]" x-text="formatRupiah(item.price * item.quantity)"></div>
                            <button type="button" @click="removeFromCart(index)"
                                class="inline-flex items-center px-2 py-0.5 mt-1 rounded border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 text-[10px] font-medium transition">
                                Hapus
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-[#475569] py-12" x-cloak>
                    <p class="text-xs font-semibold">Keranjang masih kosong</p>
                    <p class="text-[11px] mt-0.5">Pilih produk di katalog atau scan barcode</p>
                </div>
            </div>

            <!-- Cart Calculation & Bottom Checkout -->
            <div class="p-3.5 sm:p-4 border-t border-[rgba(15,23,42,0.12)] bg-[#F7FAF9] space-y-2 shrink-0">
                <div class="flex justify-between text-xs text-[#475569]">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#0D2620]" x-text="formatRupiah(subtotal)"></span>
                </div>

                <div x-show="hasPosFeature('pos_allow_discount')" class="flex items-center justify-between text-xs">
                    <button @click="openDiscountModal()" class="text-[#0D7A5F] hover:underline font-semibold">
                        <span x-text="discountAmount > 0 ? ('Diskon (' + (discountType === 'percent' ? discountAmount + '%' : formatRupiah(discountAmount)) + ')') : '+ Beri Diskon'"></span>
                    </button>
                    <span class="font-bold text-rose-700" x-text="discountTotal > 0 ? ('-' + formatRupiah(discountTotal)) : 'Rp 0'"></span>
                </div>

                <div class="flex justify-between text-xs text-[#475569]">
                    <span>Pajak (PPN <span x-text="taxPercentage"></span>%)</span>
                    <span class="font-bold text-[#0D2620]" x-text="formatRupiah(taxAmount)"></span>
                </div>

                <div x-show="hasPosFeature('pos_allow_notes')">
                    <input type="text" x-model="orderNotes" placeholder="Catatan pesanan / No. Meja / Nama pemesan (opsional)"
                        class="w-full px-3 py-1.5 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-xs text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                </div>

                <div class="pt-2 border-t border-[rgba(15,23,42,0.12)] flex justify-between items-baseline">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#475569]">Total Tagihan</span>
                    <span class="text-xl font-bold text-[#0D2620] tabular-nums" x-text="formatRupiah(grandTotal)"></span>
                </div>

                <div class="pt-2">
                    <button @click="openPaymentModal()" :disabled="!isPreviewMode && cart.length === 0" class="w-full py-3 bg-[#0D2620] hover:bg-[#143B32] text-[#34D399] rounded text-sm font-bold transition disabled:opacity-40">
                        <span x-text="isPreviewMode ? (cart.length > 0 ? 'Preview Simulasi Struk' : 'Preview Contoh Struk Cabang') : ((activeShift || !hasPosFeature('pos_require_shift')) ? 'Bayar Sekarang (F4)' : 'Buka Shift & Bayar (F4)')"></span>
                    </button>
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- 1. Payment Modal -->
    <div x-show="showPaymentModal" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-lg w-full overflow-hidden border" @click.outside="showPaymentModal = false">
            <div class="bg-[#0D2620] text-[#FFFFFF] p-5 flex items-center justify-between border-b border-[rgba(51,65,85,0.75)]">
                <div>
                    <span class="text-xs font-semibold text-[#CBD5E1]">Total Pembayaran</span>
                    <div class="text-2xl font-bold text-[#34D399] tabular-nums" x-text="formatRupiah(grandTotal)"></div>
                </div>
                <button @click="showPaymentModal = false" class="text-[#CBD5E1] hover:text-[#34D399]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase text-[#475569] mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="paymentMethod = 'cash'; amountPaid = grandTotal" :class="paymentMethod === 'cash' ? 'bg-[#0D2620] text-[#34D399] border-[#0D2620]' : 'bg-[#FFFFFF] text-[#0D2620] border-[rgba(15,23,42,0.12)]'" class="py-2.5 rounded border text-xs font-bold transition">Tunai</button>
                        <button type="button" @click="paymentMethod = 'qris'; amountPaid = grandTotal" :class="paymentMethod === 'qris' ? 'bg-[#0D2620] text-[#34D399] border-[#0D2620]' : 'bg-[#FFFFFF] text-[#0D2620] border-[rgba(15,23,42,0.12)]'" class="py-2.5 rounded border text-xs font-bold transition">QRIS</button>
                        <button type="button" @click="paymentMethod = 'transfer'; amountPaid = grandTotal" :class="paymentMethod === 'transfer' ? 'bg-[#0D2620] text-[#34D399] border-[#0D2620]' : 'bg-[#FFFFFF] text-[#0D2620] border-[rgba(15,23,42,0.12)]'" class="py-2.5 rounded border text-xs font-bold transition">Transfer</button>
                        <button type="button" @click="paymentMethod = 'ewallet'; amountPaid = grandTotal" :class="paymentMethod === 'ewallet' ? 'bg-[#0D2620] text-[#34D399] border-[#0D2620]' : 'bg-[#FFFFFF] text-[#0D2620] border-[rgba(15,23,42,0.12)]'" class="py-2.5 rounded border text-xs font-bold transition">E-Wallet</button>
                    </div>
                </div>

                <div x-show="paymentMethod === 'cash'" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#475569] mb-1">Uang Diterima (Rp)</label>
                        <input type="number" x-model.number="amountPaid" class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-lg font-bold tabular-nums focus:outline-none focus:border-[#0D7A5F]">
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="amountPaid = grandTotal" class="py-1.5 bg-[#FDF3EE] rounded text-xs font-bold text-[#0D7A5F]">Uang Pas</button>
                        <button type="button" @click="amountPaid = 50000" class="py-1.5 bg-[#FDF3EE] rounded text-xs font-bold text-[#0D7A5F]">50.000</button>
                        <button type="button" @click="amountPaid = 100000" class="py-1.5 bg-[#FDF3EE] rounded text-xs font-bold text-[#0D7A5F]">100.000</button>
                        <button type="button" @click="amountPaid = 200000" class="py-1.5 bg-[#FDF3EE] rounded text-xs font-bold text-[#0D7A5F]">200.000</button>
                    </div>
                    <div class="p-3 rounded bg-[#FFFFFF] flex items-center justify-between border border-[rgba(15,23,42,0.12)]">
                        <span class="text-xs font-bold uppercase text-[#475569]">Kembalian</span>
                        <span :class="changeAmount >= 0 ? 'text-[#0D7A5F]' : 'text-rose-700'" class="text-lg font-bold tabular-nums" x-text="changeAmount >= 0 ? formatRupiah(changeAmount) : 'Kurang ' + formatRupiah(Math.abs(changeAmount))"></span>
                    </div>
                </div>

                <div x-show="paymentMethod !== 'cash'" class="space-y-1">
                    <label class="block text-xs font-bold uppercase text-[#475569]">Nomor Referensi / Bukti Pembayaran</label>
                    <input type="text" x-model="referenceNumber" placeholder="Contoh: REF-QRIS-9821 (untuk audit kasir)" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-sm">
                </div>

                <div x-show="hasPosFeature('pos_allow_notes')" class="space-y-1">
                    <label class="block text-xs font-bold uppercase text-[#475569]">Catatan Pesanan / No. Meja (Opsional)</label>
                    <input type="text" x-model="orderNotes" placeholder="Contoh: Meja 03, kurang manis, take away" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-xs">
                </div>

                <button type="button" @click="submitCheckout()" :disabled="isProcessingCheckout || (paymentMethod === 'cash' && changeAmount < 0)" class="w-full py-3.5 bg-[#0D7A5F] hover:bg-[#095C47] text-[#F7FAF9] font-bold rounded transition disabled:opacity-40 text-sm inline-flex items-center justify-center gap-2">
                    <svg x-show="isProcessingCheckout" class="w-4 h-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                    <span x-show="!isProcessingCheckout">Selesaikan Transaksi &amp; Cetak Struk</span>
                    <span x-show="isProcessingCheckout">Memproses Pembayaran...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Thermal Receipt Modal -->
    <div x-show="showReceiptModal" class="tandha-print-overlay fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-sm w-full overflow-hidden border" @click.outside="showReceiptModal = false">
            <div class="p-4 bg-[#0D2620] text-[#FFFFFF] flex justify-between items-center">
                <span class="text-xs font-bold uppercase">Struk Transaksi</span>
                <button @click="showReceiptModal = false" class="text-[#CBD5E1]"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <div class="p-5 max-h-[70vh] overflow-y-auto tandha-print-scroll-reset">
                <div x-show="lastReceipt.is_preview" x-cloak class="mb-3 px-3 py-2 rounded-md bg-amber-50 border border-amber-200 text-[11px] font-semibold text-amber-900 text-center">
                    CONTOH STRUK PREVIEW (READ-ONLY) — Tidak tercatat di riwayat penjualan maupun memotong stok.
                </div>
                <div id="printable-receipt" class="bg-white p-4 border border-dashed border-[#0D7A5F]/40 font-mono text-xs text-[#0D2620] space-y-2">
                    <div class="text-center pb-2 border-b border-dashed border-[#D1C9B8]">
                        <div class="font-bold text-sm" x-text="lastReceipt.business_name"></div>
                        <div class="text-[10px] font-semibold" x-text="lastReceipt.outlet_name"></div>
                        <div x-show="lastReceipt.receipt_header" class="text-[10px] font-semibold text-[#0D7A5F] mt-0.5" x-text="lastReceipt.receipt_header"></div>
                        <div class="text-[10px]" x-text="lastReceipt.outlet_address"></div>
                        <div x-show="lastReceipt.outlet_phone" class="text-[10px]" x-text="'Telp: ' + lastReceipt.outlet_phone"></div>
                    </div>
                    <div class="text-[10px] space-y-0.5 pb-2 border-b border-dashed border-[#D1C9B8]">
                        <div class="flex justify-between"><span>No: <b x-text="lastReceipt.invoice_number"></b></span><span x-text="lastReceipt.date"></span></div>
                        <div class="flex justify-between"><span>Kasir: <span x-text="lastReceipt.cashier_name"></span></span><span>Cust: <span x-text="lastReceipt.customer_name + (lastReceipt.customer_phone ? ' (' + lastReceipt.customer_phone + ')' : '')"></span></span></div>
                        <div x-show="lastReceipt.notes" class="text-[10px] text-[#3D544E]" x-text="'Catatan: ' + lastReceipt.notes"></div>
                    </div>
                    <div class="space-y-1 pb-2 border-b border-dashed border-[#D1C9B8] text-[11px]">
                        <template x-for="it in lastReceipt.items" :key="it.name">
                            <div>
                                <div class="font-bold" x-text="it.name"></div>
                                <div class="flex justify-between text-[10px] text-[#3D544E]">
                                    <span x-text="it.qty + ' x ' + formatRupiah(it.price)"></span>
                                    <span class="font-bold text-[#0D2620]" x-text="formatRupiah(it.subtotal)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="space-y-0.5 text-[11px] pb-2 border-b border-dashed border-[#D1C9B8]">
                        <div class="flex justify-between"><span>Subtotal</span><span x-text="formatRupiah(lastReceipt.subtotal)"></span></div>
                        <div x-show="lastReceipt.discount_total > 0" class="flex justify-between"><span>Diskon</span><span x-text="'-' + formatRupiah(lastReceipt.discount_total)"></span></div>
                        <div class="flex justify-between"><span>PPN (<span x-text="lastReceipt.tax_percentage ?? taxPercentage"></span>%)</span><span x-text="formatRupiah(lastReceipt.tax_amount)"></span></div>
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-[#E5E0D5]"><span>TOTAL</span><span x-text="formatRupiah(lastReceipt.total_amount)"></span></div>
                        <div class="flex justify-between text-[10px]"><span x-text="'BAYAR (' + lastReceipt.payment_method + ')'"></span><span x-text="formatRupiah(lastReceipt.amount_paid)"></span></div>
                        <div x-show="lastReceipt.reference_number" class="flex justify-between text-[10px]"><span>NO. REFERENSI</span><span x-text="lastReceipt.reference_number"></span></div>
                        <div class="flex justify-between text-[10px] font-bold"><span>KEMBALIAN</span><span x-text="formatRupiah(lastReceipt.change_amount)"></span></div>
                    </div>
                    <div class="text-center pt-2 text-[10px] text-[#647C76]">
                        <p x-text="lastReceipt.footer || 'Terima kasih atas kunjungan Anda!'"></p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-[#FFFFFF] border-t border-[rgba(15,23,42,0.12)] flex gap-2">
                <button type="button" @click="printReceipt()" class="flex-1 py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] text-[#F7FAF9] font-bold rounded text-xs">Cetak Struk</button>
                <button type="button" @click="showReceiptModal = false" class="py-2.5 px-4 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] text-[#0D2620] font-bold rounded text-xs">Selesai</button>
            </div>
        </div>
    </div>

    <!-- 3. Open Shift Modal -->
    <div x-show="showOpenShiftModal" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-sm w-full p-6 border" @click.outside="showOpenShiftModal = false">
            <h3 class="text-base font-bold text-[#0D2620]">Buka Shift Kasir</h3>
            <p class="text-xs text-[#475569] mt-1 mb-4">Wajib diisi sebelum transaksi agar uang modal laci dan penjualan tercatat lengkap.</p>
            <form @submit.prevent="submitOpenShift()" class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#475569] mb-1">Kas Modal Awal Laci (Rp) *</label>
                    <input type="number" x-model.number="initialCashInput" required min="0" class="w-full px-3 py-2.5 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-base font-bold tabular-nums">
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#475569] mb-1">Catatan Shift (Opsional)</label>
                    <input type="text" x-model="shiftOpenNotes" placeholder="Contoh: Shift Pagi - Kasir Utama" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-xs">
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showOpenShiftModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded text-xs font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-[#F7FAF9] rounded text-xs font-bold">Mulai Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Close Shift Modal -->
    <div x-show="showCloseShiftModal" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full overflow-hidden border" @click.outside="showCloseShiftModal = false">
            <div class="bg-[#0D2620] text-[#34D399] p-4 font-bold text-sm">Tutup Shift &amp; Rekonsiliasi Kas</div>
            <div class="p-6 space-y-4">
                <div class="bg-[#FFFFFF] rounded p-3 border border-[rgba(15,23,42,0.12)] space-y-1.5 text-xs">
                    <div class="flex justify-between"><span>Waktu Buka Shift:</span><b x-text="activeShift && activeShift.opened_at ? formatDate(activeShift.opened_at) : '-'"></b></div>
                    <div class="flex justify-between"><span>Modal Awal Kas:</span><b x-text="formatRupiah(activeShift ? activeShift.initial_cash : 0)"></b></div>
                    <div class="flex justify-between"><span>Penjualan Tunai:</span><b x-text="formatRupiah(activeShift ? activeShift.cash_sales_total : 0)"></b></div>
                    <div class="flex justify-between"><span>Penjualan Non-Tunai:</span><b x-text="formatRupiah(activeShift ? activeShift.non_cash_sales_total : 0)"></b></div>
                    <div class="flex justify-between pt-1.5 border-t border-[rgba(15,23,42,0.12)] font-bold text-[#0D7A5F]">
                        <span>Kas Seharusnya (Expected):</span>
                        <span x-text="formatRupiah((activeShift ? Number(activeShift.initial_cash) : 0) + (activeShift ? Number(activeShift.cash_sales_total) : 0))"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-[#475569] mb-1">Kas Aktual Fisik di Laci *</label>
                    <input type="number" x-model.number="actualCashInput" required min="0" class="w-full px-3 py-2.5 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-base font-bold tabular-nums">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-[#475569] mb-1">Catatan Tutup Shift (Opsional)</label>
                    <input type="text" x-model="shiftCloseNotes" placeholder="Alasan selisih / serah terima shift" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-xs">
                </div>
                <div class="p-3 rounded bg-[#FDF3EE] border border-[rgba(15,23,42,0.12)] flex items-center justify-between text-xs font-bold">
                    <span>Selisih Kas (Variance):</span>
                    <span class="tabular-nums" x-text="shiftVariance === 0 ? 'PAS (Cocok)' : formatRupiah(shiftVariance)"></span>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="showCloseShiftModal = false" class="flex-1 py-2.5 bg-[#FDF3EE] text-[#475569] rounded text-xs font-bold">Batal</button>
                    <button type="button" @click="submitCloseShift()" class="flex-1 py-2.5 bg-[#0D2620] text-[#34D399] rounded text-xs font-bold">Konfirmasi Tutup Shift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Discount Modal -->
    <div x-show="showDiscountModal" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-xs w-full p-5 border" @click.outside="showDiscountModal = false">
            <h3 class="text-sm font-bold text-[#0D2620] mb-3">Atur Diskon</h3>
            <div class="flex gap-2 mb-3">
                <button type="button" @click="tempDiscountType = 'percent'" :class="tempDiscountType === 'percent' ? 'bg-[#0D2620] text-[#34D399]' : 'bg-[#FFFFFF] text-[#475569]'" class="flex-1 py-1.5 rounded text-xs font-bold">Persen (%)</button>
                <button type="button" @click="tempDiscountType = 'fixed'" :class="tempDiscountType === 'fixed' ? 'bg-[#0D2620] text-[#34D399]' : 'bg-[#FFFFFF] text-[#475569]'" class="flex-1 py-1.5 rounded text-xs font-bold">Nominal (Rp)</button>
            </div>
            <input type="number" x-model.number="tempDiscountAmount" min="0" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded text-sm font-bold mb-4">
            <div class="flex gap-2">
                <button type="button" @click="tempDiscountAmount = 0; applyDiscount()" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded text-xs font-bold">Hapus</button>
                <button type="button" @click="applyDiscount()" class="flex-1 py-2 bg-[#0D7A5F] text-[#F7FAF9] rounded text-xs font-bold">Terapkan</button>
            </div>
        </div>
    </div>

    <!-- 6. History Modal (Filter + Table-Only Scroll + Canonical Pagination) -->
    <div x-show="showHistoryModal" @click.self="showHistoryModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-white rounded-xl max-w-4xl w-full border overflow-hidden flex flex-col max-h-[88vh]">
            <div class="px-5 py-3.5 bg-[#0F1F1B] text-[#FFFFFF] flex justify-between items-center shrink-0">
                <div>
                    <h3 class="text-sm font-bold">Riwayat Transaksi Outlet</h3>
                    <p class="text-[11px] text-slate-300" x-text="histPaginationRangeLabel"></p>
                </div>
                <button @click="showHistoryModal = false" class="text-slate-300 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <!-- Filter Bar Modal Riwayat -->
            <div class="shrink-0 bg-white border-b border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative" @click.outside="if (openPosDropdown === 'histStatus') openPosDropdown = null">
                        <button type="button" @click="togglePosDropdown('histStatus')"
                            class="px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 min-w-[140px] justify-between">
                            <span class="truncate" x-text="activeHistStatusLabel"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openPosDropdown === 'histStatus' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openPosDropdown === 'histStatus'" x-transition class="absolute left-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                            <template x-for="opt in histStatusOptions" :key="opt.value">
                                <button type="button" @click="histStatusFilter = opt.value; histPage = 1; openPosDropdown = null"
                                    class="w-full px-3 py-1.5 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="histStatusFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span x-text="opt.label"></span><span x-show="histStatusFilter === opt.value">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="relative" @click.outside="if (openPosDropdown === 'histPay') openPosDropdown = null">
                        <button type="button" @click="togglePosDropdown('histPay')"
                            class="px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 min-w-[145px] justify-between">
                            <span class="truncate" x-text="activeHistPayLabel"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openPosDropdown === 'histPay' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openPosDropdown === 'histPay'" x-transition class="absolute left-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                            <template x-for="opt in histPayOptions" :key="opt.value">
                                <button type="button" @click="histPayFilter = opt.value; histPage = 1; openPosDropdown = null"
                                    class="w-full px-3 py-1.5 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="histPayFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span x-text="opt.label"></span><span x-show="histPayFilter === opt.value">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="relative w-full sm:w-auto">
                    <input type="text" x-model="histSearch" @input="histPage = 1" placeholder="Cari invoice, item, pelanggan..."
                        class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-56 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600">
                    <button x-show="histSearch" type="button" @click="histSearch = ''; histPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                </div>
            </div>
            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                <table class="w-full min-w-[540px] text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="py-2.5 px-4">Transaksi &amp; Item</th>
                            <th class="py-2.5 px-4">Kasir &amp; Pelanggan</th>
                            <th class="py-2.5 px-4 text-right">Total</th>
                            <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="txn in paginatedHistoryList" :key="txn.id">
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-3 px-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="tabular-nums font-bold text-[#0D7A5F]" x-text="txn.invoice_number"></span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold uppercase"
                                            :class="txn.payment_status === 'voided' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'"
                                            x-text="txn.payment_status"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5" x-text="(txn.items || []).map(i => i.quantity + 'x ' + i.product_name).join(', ')"></div>
                                    <div x-show="txn.notes" class="text-[10px] text-slate-400 mt-0.5" x-text="'Catatan: ' + txn.notes"></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800" x-text="txn.customer ? txn.customer.name : 'Pelanggan Umum'"></div>
                                    <div class="text-[11px] text-slate-400" x-text="'Kasir: ' + (txn.cashier ? txn.cashier.name : '-') + ' · ' + formatDate(txn.created_at)"></div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="tabular-nums font-bold text-slate-900" x-text="formatRupiah(txn.total_amount)"></div>
                                    <div class="text-[10px] uppercase text-slate-400" x-text="txn.payment ? txn.payment.payment_method : ''"></div>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button type="button" @click="openPosActionPopup(txn, $event)"
                                        class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                        <span>Aksi</span>
                                        <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredHistoryList.length === 0">
                            <td colspan="4" class="py-10 text-center text-xs text-slate-400">Tidak ada transaksi yang sesuai filter di outlet ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="shrink-0 bg-slate-50/60 border-t border-slate-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-slate-500">
                    <span>Tampilkan</span>
                    <div class="relative" @click.outside="if (openPosDropdown === 'histPageSize') openPosDropdown = null">
                        <button type="button" @click="togglePosDropdown('histPageSize')"
                            class="px-2.5 py-1 bg-white border border-slate-200 rounded text-xs font-medium text-slate-700 flex items-center gap-1.5">
                            <span x-text="histPerPage + ' / hal'"></span>
                            <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openPosDropdown === 'histPageSize'" x-transition class="absolute bottom-full left-0 mb-1 w-28 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                            <template x-for="sz in [5, 10, 20, 50]" :key="'hsz-' + sz">
                                <button type="button" @click="histPerPage = sz; histPage = 1; openPosDropdown = null"
                                    class="w-full px-3 py-1.5 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="histPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                    <span x-text="sz + ' baris'"></span>
                                    <span x-show="histPerPage === sz">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <span class="hidden sm:inline" x-text="histPaginationRangeLabel"></span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" @click="if (histPage > 1) histPage--" :disabled="histPage <= 1"
                        class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                    <template x-for="(pg, idx) in getPageNumbers(histPage, histTotalPages)" :key="'hpg-' + idx + '-' + pg">
                        <button type="button" @click="if (typeof pg === 'number') histPage = pg" :disabled="typeof pg !== 'number'"
                            class="w-7 h-7 rounded text-[11px] font-semibold transition"
                            :class="histPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                            x-text="pg"></button>
                    </template>
                    <button type="button" @click="if (histPage < histTotalPages) histPage++" :disabled="histPage >= histTotalPages"
                        class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. Quick Add Customer Modal -->
    <div x-show="showCreateCustomerModal" @click.self="showCreateCustomerModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-sm w-full p-6 border">
            <h3 class="text-sm font-bold text-[#0D2620] mb-1">Tambah Data Pelanggan</h3>
            <p class="text-[11px] text-[#475569] mb-3">Simpan nama dan kontak pelanggan agar tercantum di struk &amp; riwayat transaksi.</p>
            <form @submit.prevent="submitCreateCustomer()" class="space-y-2.5 text-xs">
                <input type="text" x-model="newCustomer.name" required placeholder="Nama Lengkap Pelanggan *" class="w-full px-3 py-2 bg-white border border-slate-300 rounded">
                <input type="text" x-model="newCustomer.phone" placeholder="No. HP / WhatsApp (contoh: 0812...)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded">
                <input type="email" x-model="newCustomer.email" placeholder="Email (Opsional)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded">
                <input type="text" x-model="newCustomer.notes" placeholder="Catatan Pelanggan (Opsional)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCreateCustomerModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-white rounded font-bold">Simpan Pelanggan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. Held Orders Modal -->
    <div x-show="showHeldModal" @click.self="showHeldModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-white rounded-xl max-w-xl w-full border overflow-hidden flex flex-col max-h-[82vh]">
            <div class="px-5 py-3.5 bg-[#0D2620] text-[#FFFFFF] flex justify-between items-center shrink-0">
                <div>
                    <h3 class="text-sm font-bold">Daftar Pesanan Tertahan (Hold)</h3>
                    <p class="text-[11px] text-[#CBD5E1]">Pilih pesanan untuk dimuat kembali ke keranjang kasir.</p>
                </div>
                <button type="button" @click="showHeldModal = false" class="text-slate-300 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar p-4 space-y-2.5 bg-[#F7FAF9]">
                <div x-show="loadingHeldOrders" class="py-10 text-center text-xs text-slate-500">Memuat pesanan tertahan...</div>
                <div x-show="!loadingHeldOrders && heldOrders.length === 0" class="py-10 text-center text-xs text-slate-500">
                    Belum ada pesanan yang ditahan pada outlet ini.
                </div>
                <template x-for="held in heldOrders" :key="held.id">
                    <div class="bg-white border border-slate-200 rounded-lg p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-[#0D7A5F] tabular-nums" x-text="held.invoice_number"></span>
                                <span class="text-[11px] font-semibold text-[#0D2620]" x-text="held.customer ? held.customer.name : 'Pelanggan Umum'"></span>
                                <span class="text-[10px] text-slate-400" x-text="formatDate(held.created_at)"></span>
                            </div>
                            <div class="text-[11px] text-slate-600 truncate"
                                x-text="(held.items || []).map(i => i.quantity + 'x ' + i.product_name).join(', ') || ((held.cart_payload && held.cart_payload.length ? held.cart_payload.length + ' item' : 'Keranjang tersimpan'))"></div>
                            <div x-show="held.notes" class="text-[10px] text-amber-700 font-medium" x-text="'Catatan: ' + held.notes"></div>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                            <div class="text-right">
                                <div class="text-xs font-bold text-[#0D2620] tabular-nums" x-text="formatRupiah(held.total_amount)"></div>
                            </div>
                            <button type="button" @click="resumeHeldOrder(held.id)" :disabled="resumingHeldId === held.id"
                                class="px-3 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition disabled:opacity-40">
                                <span x-text="resumingHeldId === held.id ? 'Memuat...' : 'Lanjutkan'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Floating Action Popover Menu (Sama persis dengan Super Admin) -->
    <div x-show="showPosActionPopup" @click="showPosActionPopup = false" class="fixed inset-0 z-[60]" x-cloak></div>
    <div x-show="showPosActionPopup" x-transition.opacity.duration.120ms :style="posActionMenuStyle"
        class="tandha-modal-card fixed z-[70] w-52 bg-white rounded-lg border py-1.5 text-xs" x-cloak>
        <div class="px-3 py-1.5 border-b border-slate-100">
            <div class="font-semibold text-slate-900 truncate tabular-nums" x-text="actionTxn ? actionTxn.invoice_number : ''"></div>
            <div class="text-[10px] text-slate-400 truncate" x-text="actionTxn ? (formatRupiah(actionTxn.total_amount) + ' · ' + (actionTxn.customer ? actionTxn.customer.name : 'Pelanggan Umum')) : ''"></div>
        </div>
        <button type="button" @click="runPosAction('reprint')"
            class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
            <span>Lihat &amp; Cetak Struk</span>
        </button>
        <template x-if="!isPreviewMode && actionTxn && actionTxn.payment_status === 'paid' && hasPosFeature('pos_allow_void')">
            <div>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runPosAction('void')"
                    class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 font-medium flex items-center justify-between transition">
                    <span>Batalkan Transaksi (Void)</span>
                </button>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
<script>
function posApp() {
    const posUrlParams = new URLSearchParams(window.location.search);
    const posIsEmbedded = posUrlParams.get('embed') === '1';
    const posIsStandaloneApp = !posIsEmbedded && (
        posUrlParams.get('app') === 'kasir' ||
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true
    );
    return {
        token: localStorage.getItem('pos_token') || '',
        user: (() => { try { return JSON.parse(localStorage.getItem('pos_user') || '{}') || {}; } catch (_) { localStorage.removeItem('pos_user'); return {}; } })(),
        business: {},
        currentOutlet: {},
        categories: [],
        products: [],
        filteredProducts: [],
        customers: [],
        cart: [],
        searchQuery: '',
        barcodeInput: '',
        selectedCategory: '',
        posStockFilter: 'all',
        posStockOptions: [
            { value: 'all', label: 'Semua Kondisi Stok' },
            { value: 'in_stock', label: 'Stok Tersedia (> 0)' },
            { value: 'low', label: 'Stok Menipis (1–5)' },
            { value: 'empty', label: 'Stok Habis (0)' },
        ],
        selectedCustomerId: '',
        openCustomerDropdown: false,
        openPosDropdown: null,
        showPosActionPopup: false,
        actionTxn: null,
        posActionMenuStyle: 'top: 120px; right: 32px;',
        customerSearch: '',
        histPage: 1,
        histPerPage: 10,
        histSearch: '',
        histStatusFilter: 'all',
        histStatusOptions: [
            { value: 'all', label: 'Semua Status' },
            { value: 'paid', label: 'Selesai (Paid)' },
            { value: 'voided', label: 'Dibatalkan (Void)' },
        ],
        histPayFilter: 'all',
        histPayOptions: [
            { value: 'all', label: 'Semua Metode' },
            { value: 'cash', label: 'Tunai (Cash)' },
            { value: 'qris', label: 'QRIS' },
            { value: 'transfer', label: 'Transfer Bank' },
            { value: 'ewallet', label: 'E-Wallet' },
        ],
        discountType: null,
        discountAmount: 0,
        tempDiscountType: 'percent',
        tempDiscountAmount: 0,
        taxPercentage: 11,
        showPaymentModal: false,
        paymentMethod: 'cash',
        amountPaid: 0,
        referenceNumber: '',
        orderNotes: '',
        isProcessingCheckout: false,
        showReceiptModal: false,
        lastReceipt: {},
        activeShift: null,
        showOpenShiftModal: false,
        initialCashInput: 200000,
        shiftOpenNotes: '',
        showCloseShiftModal: false,
        actualCashInput: 0,
        shiftCloseNotes: '',
        showDiscountModal: false,
        showHistoryModal: false,
        historyList: [],
        heldOrders: [],
        showHeldModal: false,
        loadingHeldOrders: false,
        isProcessingHold: false,
        resumingHeldId: null,
        isOpeningShift: false,
        isClosingShift: false,
        isSavingCustomer: false,
        isProcessingVoid: false,
        showCreateCustomerModal: false,
        newCustomer: { name: '', phone: '', email: '', notes: '' },
        deferredInstallPrompt: null,
        isEmbedded: posIsEmbedded,
        isKasirAppMode: posIsStandaloneApp,
        isPosBooting: true,
        isPosFetching: 0,
        mobilePosTab: 'catalog',

        get totalCartQty() {
            return this.cart.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
        },
        get isPreviewMode() {
            return Boolean(!this.isKasirAppMode && this.user && this.user.role === 'owner');
        },
        get subtotal() { return this.cart.reduce((sum, it) => sum + (it.price * it.quantity), 0); },
        get discountTotal() {
            if (!this.discountAmount || this.discountAmount <= 0) return 0;
            return this.discountType === 'percent' ? (this.subtotal * this.discountAmount) / 100 : Math.min(this.discountAmount, this.subtotal);
        },
        get taxAmount() { return Math.round((Math.max(0, this.subtotal - this.discountTotal) * this.taxPercentage) / 100); },
        get grandTotal() { return Math.max(0, this.subtotal - this.discountTotal + this.taxAmount); },
        get changeAmount() { return Number(this.amountPaid) - Number(this.grandTotal); },
        get shiftVariance() {
            if (!this.activeShift) return 0;
            return Number(this.actualCashInput) - (Number(this.activeShift.initial_cash) + Number(this.activeShift.cash_sales_total));
        },
        togglePosDropdown(name) {
            this.openPosDropdown = this.openPosDropdown === name ? null : name;
        },
        hasPosFeature(key) {
            const bizSettings = (this.business && this.business.feature_settings) ? this.business.feature_settings : {};
            const outletOverrides = (this.currentOutlet && this.currentOutlet.feature_overrides) ? this.currentOutlet.feature_overrides : {};
            if (outletOverrides[key] !== undefined && outletOverrides[key] !== null) {
                return Boolean(outletOverrides[key]);
            }
            return bizSettings[key] === undefined ? true : Boolean(bizSettings[key]);
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
        get activePosCatLabel() {
            if (!this.selectedCategory) return 'Semua Kategori';
            if (this.selectedCategory === 'none') return 'Tanpa Kategori';
            const f = this.categories.find((c) => String(c.id) === String(this.selectedCategory));
            return f ? f.name : 'Semua Kategori';
        },
        get activePosStockLabel() {
            const f = this.posStockOptions.find((o) => o.value === this.posStockFilter);
            return f ? f.label : 'Semua Kondisi Stok';
        },
        get activeHistStatusLabel() {
            const f = this.histStatusOptions.find((o) => o.value === this.histStatusFilter);
            return f ? f.label : 'Semua Status';
        },
        get activeHistPayLabel() {
            const f = this.histPayOptions.find((o) => o.value === this.histPayFilter);
            return f ? f.label : 'Semua Metode';
        },
        get selectedCustomerLabel() {
            if (!this.selectedCustomerId) return 'Pelanggan Umum (Walk-in)';
            const found = this.customers.find((c) => String(c.id) === String(this.selectedCustomerId));
            return found ? (found.name + (found.phone ? ` (${found.phone})` : '')) : 'Pelanggan Umum (Walk-in)';
        },
        get filteredCustomers() {
            const q = (this.customerSearch || '').trim().toLowerCase();
            if (!q) return this.customers;
            return this.customers.filter((c) => (c.name || '').toLowerCase().includes(q) || (c.phone || '').toLowerCase().includes(q));
        },
        get filteredHistoryList() {
            const q = (this.histSearch || '').trim().toLowerCase();
            return this.historyList.filter((txn) => {
                if (this.histStatusFilter !== 'all' && txn.payment_status !== this.histStatusFilter) return false;
                if (this.histPayFilter !== 'all') {
                    const pm = (txn.payment && txn.payment.payment_method) ? String(txn.payment.payment_method).toLowerCase() : '';
                    if (pm !== this.histPayFilter) return false;
                }
                if (!q) return true;
                const itemNames = (txn.items || []).map((i) => (i.product_name || '').toLowerCase()).join(' ');
                return (txn.invoice_number || '').toLowerCase().includes(q) ||
                    ((txn.customer && txn.customer.name) || 'pelanggan umum').toLowerCase().includes(q) ||
                    ((txn.cashier && txn.cashier.name) || '').toLowerCase().includes(q) ||
                    itemNames.includes(q);
            });
        },
        get histTotalPages() {
            return Math.max(1, Math.ceil(this.filteredHistoryList.length / this.histPerPage));
        },
        get paginatedHistoryList() {
            const safe = Math.min(this.histPage, this.histTotalPages);
            const start = (safe - 1) * this.histPerPage;
            return this.filteredHistoryList.slice(start, start + this.histPerPage);
        },
        get histPaginationRangeLabel() {
            const total = this.filteredHistoryList.length;
            if (total === 0) return '0 transaksi';
            const safe = Math.min(this.histPage, this.histTotalPages);
            const from = (safe - 1) * this.histPerPage + 1;
            const to = Math.min(total, safe * this.histPerPage);
            return `Menampilkan ${from}–${to} dari ${total} transaksi`;
        },

        async initPos() {
            if (!this.token) {
                window.location.href = this.isKasirAppMode ? '/login?app=kasir' : '/login';
                return;
            }
            try {
                const ok = await this.loadInitialData();
                if (!ok) return;
                await Promise.all([
                    this.isPreviewMode ? Promise.resolve(this.activeShift = null) : this.loadShift(),
                    this.loadProducts(),
                    this.loadCategories(),
                    this.loadCustomers(),
                    (!this.isPreviewMode) ? this.loadHeldOrders() : Promise.resolve(),
                ]);
            } finally {
                this.isPosBooting = false;
            }

            window.addEventListener('keydown', (e) => {
                if (e.key === 'F4') {
                    const anyModalOpen = this.showPaymentModal || this.showReceiptModal || this.showHistoryModal ||
                        this.showHeldModal || this.showOpenShiftModal || this.showCloseShiftModal ||
                        this.showDiscountModal || this.showCreateCustomerModal;
                    if (anyModalOpen) return;
                    e.preventDefault();
                    if (this.cart.length > 0 || this.isPreviewMode) this.openPaymentModal();
                }
            });
        },

        async authFetch(url, options = {}) {
            options.headers = { ...options.headers, 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            this.isPosFetching += 1;
            try {
                const res = await fetch(url, options);
                if (res.status === 401) {
                    localStorage.removeItem('pos_token');
                    localStorage.removeItem('pos_user');
                    const loginUrl = this.isKasirAppMode ? '/login?app=kasir' : '/login';
                    if (this.isEmbedded && window.top) {
                        try { window.top.location.href = loginUrl; } catch (_) { window.location.href = loginUrl; }
                    } else {
                        window.location.href = loginUrl;
                    }
                    throw new Error('Expired');
                }
                return res;
            } finally {
                this.isPosFetching = Math.max(0, this.isPosFetching - 1);
            }
        },

        applyOutletConfig() {
            const branchTax = (this.currentOutlet && this.currentOutlet.tax_percentage !== null && this.currentOutlet.tax_percentage !== undefined)
                ? this.currentOutlet.tax_percentage
                : (this.business.tax_percentage ?? 11);
            this.taxPercentage = Number(branchTax);
        },

        async switchPreviewOutlet(outletObj) {
            if (!this.isPreviewMode || !outletObj) return;
            this.currentOutlet = outletObj;
            this.openPosDropdown = null;
            this.clearCart();
            this.applyOutletConfig();
            await this.loadProducts();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async loadInitialData() {
            const res = await this.authFetch('/api/me');
            const data = await this.parseJsonSafe(res);
            if (res.ok && data.status === 'success') {
                this.user = data.data;
                if (this.isKasirAppMode && this.user.role !== 'cashier') {
                    // Lock installed Kasir APK strictly to cashier role: clear inherited Owner/Admin browser session cleanly
                    const blockedRole = this.user.role || 'owner';
                    localStorage.removeItem('pos_token');
                    localStorage.removeItem('pos_user');
                    window.location.href = `/login?app=kasir&blocked_role=${encodeURIComponent(blockedRole)}`;
                    return false;
                }
                localStorage.setItem('pos_user', JSON.stringify(data.data));
                if (!['cashier', 'owner'].includes(this.user.role)) {
                    window.location.href = '/dashboard';
                    return false;
                }
                this.business = data.data.business || {};
                const hasActiveSub = Boolean(this.business && (this.business.has_active_subscription || this.business.can_install_apk));
                if (!hasActiveSub) {
                    if (this.user.role === 'owner') {
                        window.location.href = '/dashboard?tab=billing';
                        return false;
                    }
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Langganan Bisnis Belum Aktif',
                        text: 'Layanan kasir sedang terkunci hingga pemilik mengaktifkan atau memperpanjang paket langganan.',
                        confirmButtonText: 'Keluar',
                        confirmButtonColor: '#0D7A5F',
                    });
                    this.logout();
                    return false;
                }
                const params = new URLSearchParams(window.location.search);
                const requestedOutletId = params.get('outlet_id');
                if (this.user.role === 'owner' && requestedOutletId && Array.isArray(this.business.outlets)) {
                    const matched = this.business.outlets.find((o) => String(o.id) === String(requestedOutletId));
                    this.currentOutlet = matched || data.data.outlet || this.business.outlets[0] || {};
                } else {
                    this.currentOutlet = data.data.outlet ||
                        (this.business.outlets || []).find((o) => String(o.id) === String(this.user.outlet_id)) ||
                        (this.business.outlets ? this.business.outlets[0] : {});
                }
                this.applyOutletConfig();
                return true;
            }
            return false;
        },
        async parseJsonSafe(res) {
            const raw = await res.text();
            try {
                return raw ? JSON.parse(raw) : {};
            } catch (_) {
                return {};
            }
        },
        async loadShift() {
            if (this.isPreviewMode) {
                this.activeShift = null;
                return;
            }
            try {
                const res = await this.authFetch(`/api/shifts/current?outlet_id=${this.currentOutlet.id || 1}`);
                const data = await this.parseJsonSafe(res);
                if (res.ok) this.activeShift = data.data;
            } catch (_) {}
        },
        async loadProducts() {
            try {
                const res = await this.authFetch(`/api/products?outlet_id=${this.currentOutlet.id || 1}`);
                const data = await this.parseJsonSafe(res);
                if (res.ok && data.status === 'success') { this.products = data.data; this.filterProducts(); }
            } catch (_) {}
        },
        async loadCategories() {
            try {
                const res = await this.authFetch('/api/categories');
                const data = await this.parseJsonSafe(res);
                if (res.ok && data.status === 'success') this.categories = data.data;
            } catch (_) {}
        },
        async loadCustomers() {
            if (!this.hasPosFeature('pos_allow_customer')) {
                this.customers = [];
                return;
            }
            try {
                const res = await this.authFetch('/api/customers');
                const data = await this.parseJsonSafe(res);
                if (res.ok && data.status === 'success') this.customers = data.data;
            } catch (_) {}
        },
        async loadHeldOrders() {
            if (this.isPreviewMode) {
                this.heldOrders = [];
                return;
            }
            this.loadingHeldOrders = true;
            try {
                const res = await this.authFetch(`/api/pos/held?outlet_id=${this.currentOutlet.id || 1}`);
                const data = await this.parseJsonSafe(res);
                if (res.ok && data.status === 'success') {
                    this.heldOrders = Array.isArray(data.data) ? data.data : [];
                }
            } catch (_) {
            } finally {
                this.loadingHeldOrders = false;
            }
        },
        async openHeldOrdersModal() {
            if (this.isPreviewMode) return;
            this.showHeldModal = true;
            await this.loadHeldOrders();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        async holdCurrentOrder() {
            if (this.isPreviewMode || this.cart.length === 0 || this.isProcessingHold) return;
            this.isProcessingHold = true;
            try {
                const allowCust = this.hasPosFeature('pos_allow_customer');
                const allowNotes = this.hasPosFeature('pos_allow_notes');
                const res = await this.authFetch('/api/pos/hold', {
                    method: 'POST',
                    body: JSON.stringify({
                        outlet_id: this.currentOutlet.id || 1,
                        customer_id: allowCust ? (this.selectedCustomerId || null) : null,
                        notes: allowNotes ? (this.orderNotes || null) : null,
                        items: this.cart.map((c) => ({ product_id: c.product_id, quantity: c.quantity })),
                    }),
                });
                const result = await this.parseJsonSafe(res);
                if (res.ok) {
                    this.clearCart();
                    this.selectedCustomerId = '';
                    await this.loadHeldOrders();
                    Swal.fire({ icon: 'success', title: 'Pesanan Ditahan', text: 'Pesanan tersimpan sementara. Klik tombol "Tertahan" kapan saja untuk melanjutkan.', timer: 1600, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Menahan Pesanan', text: result.message || 'Periksa isi keranjang dan coba lagi.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Menahan Pesanan', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.isProcessingHold = false;
            }
        },
        async resumeHeldOrder(heldId) {
            if (this.isPreviewMode || this.resumingHeldId) return;
            if (this.cart.length > 0) {
                const { isConfirmed } = await Swal.fire({
                    icon: 'question',
                    title: 'Ganti Keranjang Saat Ini?',
                    text: 'Keranjang kasir saat ini masih berisi item. Melanjutkan pesanan tertahan akan mengganti keranjang saat ini.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Muat Pesanan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#0D7A5F',
                });
                if (!isConfirmed) return;
            }
            this.resumingHeldId = heldId;
            try {
                const res = await this.authFetch(`/api/pos/held/${heldId}/resume`, { method: 'DELETE' });
                const result = await this.parseJsonSafe(res);
                if (res.ok && result.data) {
                    const payload = Array.isArray(result.data.cart_payload) && result.data.cart_payload.length > 0
                        ? result.data.cart_payload
                        : (result.data.items || []).map((it) => ({ product_id: it.product_id, quantity: it.quantity }));
                    const nextCart = [];
                    for (const entry of payload) {
                        const prod = this.products.find((p) => Number(p.id) === Number(entry.product_id));
                        if (!prod || Number(prod.current_stock || 0) <= 0) continue;
                        const qty = Math.min(Math.max(1, Number(entry.quantity || 1)), Number(prod.current_stock));
                        nextCart.push({ product_id: prod.id, name: prod.name, price: Number(prod.sell_price), max_stock: Number(prod.current_stock), quantity: qty });
                    }
                    if (nextCart.length === 0) {
                        Swal.fire({ icon: 'warning', title: 'Stok Item Habis', text: 'Produk dalam pesanan tertahan saat ini kehabisan stok.', confirmButtonColor: '#0D7A5F' });
                    }
                    this.cart = nextCart;
                    this.selectedCustomerId = (this.hasPosFeature('pos_allow_customer') && result.data.customer_id) ? result.data.customer_id : '';
                    this.orderNotes = (this.hasPosFeature('pos_allow_notes') && result.data.notes) ? result.data.notes : '';
                    this.discountAmount = 0;
                    this.discountType = null;
                    this.showHeldModal = false;
                    await this.loadHeldOrders();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Memuat Pesanan', text: result.message || 'Pesanan tertahan tidak ditemukan.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Memuat Pesanan', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.resumingHeldId = null;
            }
        },

        filterProducts() {
            let list = this.products;
            if (this.selectedCategory === 'none') {
                list = list.filter((p) => !p.category_id);
            } else if (this.selectedCategory) {
                list = list.filter((p) => String(p.category_id || '') === String(this.selectedCategory));
            }
            if (this.posStockFilter === 'in_stock') {
                list = list.filter((p) => Number(p.current_stock || 0) > 0);
            } else if (this.posStockFilter === 'low') {
                list = list.filter((p) => Number(p.current_stock || 0) > 0 && Number(p.current_stock || 0) <= 5);
            } else if (this.posStockFilter === 'empty') {
                list = list.filter((p) => Number(p.current_stock || 0) <= 0);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter((p) =>
                    p.name.toLowerCase().includes(q) ||
                    (p.sku && p.sku.toLowerCase().includes(q)) ||
                    (p.barcode && p.barcode.toLowerCase().includes(q)) ||
                    (p.category && p.category.name && p.category.name.toLowerCase().includes(q))
                );
            }
            this.filteredProducts = list;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async scanBarcode() {
            if (!this.barcodeInput || !this.hasPosFeature('pos_allow_barcode')) return;
            try {
                const res = await this.authFetch(`/api/products/barcode/${encodeURIComponent(this.barcodeInput)}?outlet_id=${this.currentOutlet.id || 1}`);
                const result = await this.parseJsonSafe(res);
                if (res.ok && result.data) { this.addToCart(result.data); this.barcodeInput = ''; }
                else Swal.fire({ icon: 'warning', title: 'Tidak Ditemukan', text: result.message || 'Produk tidak ditemukan.', timer: 1500, showConfirmButton: false });
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Scan Barcode', text: e.message || 'Koneksi terputus.', confirmButtonColor: '#0D7A5F' });
            }
        },

        addToCart(product) {
            if (product.current_stock <= 0) { Swal.fire({ icon: 'warning', title: 'Stok Habis', timer: 1200, showConfirmButton: false }); return; }
            const existing = this.cart.find(it => it.product_id === product.id);
            if (existing) {
                if (existing.quantity >= product.current_stock) { Swal.fire({ icon: 'warning', title: 'Batas Stok', timer: 1200, showConfirmButton: false }); return; }
                existing.quantity++;
            } else {
                this.cart.push({ product_id: product.id, name: product.name, price: Number(product.sell_price), max_stock: product.current_stock, quantity: 1 });
            }
            this.$nextTick(() => lucide.createIcons());
        },

        updateQty(index, change) {
            const item = this.cart[index];
            const nextQty = item.quantity + change;
            if (nextQty <= 0) this.removeFromCart(index);
            else if (nextQty <= item.max_stock) item.quantity = nextQty;
        },
        removeFromCart(index) { this.cart.splice(index, 1); },
        clearCart() { this.cart = []; this.discountAmount = 0; this.discountType = null; this.orderNotes = ''; this.referenceNumber = ''; },
        openDiscountModal() {
            if (!this.hasPosFeature('pos_allow_discount')) return;
            this.tempDiscountType = this.discountType || 'percent';
            this.tempDiscountAmount = this.discountAmount || 0;
            this.showDiscountModal = true;
        },
        applyDiscount() { this.discountType = this.tempDiscountType; this.discountAmount = this.tempDiscountAmount; this.showDiscountModal = false; },
        previewSampleReceipt(useCart = true) {
            const out = this.currentOutlet || {};
            const hasCart = useCart && this.cart.length > 0;
            const sampleProduct = this.products && this.products.length > 0 ? this.products[0] : null;
            const items = hasCart
                ? this.cart.map((it) => ({
                    name: it.name,
                    qty: it.quantity,
                    price: it.price,
                    subtotal: it.price * it.quantity,
                }))
                : [{
                    name: sampleProduct ? sampleProduct.name : 'Contoh Menu Cabang',
                    qty: 1,
                    price: sampleProduct ? Number(sampleProduct.sell_price) : 25000,
                    subtotal: sampleProduct ? Number(sampleProduct.sell_price) : 25000,
                }];
            const sub = hasCart ? this.subtotal : items.reduce((s, it) => s + Number(it.subtotal), 0);
            const disc = hasCart ? this.discountTotal : 0;
            const tax = hasCart ? this.taxAmount : Math.round((Math.max(0, sub - disc) * this.taxPercentage) / 100);
            const grand = Math.max(0, sub - disc + tax);

            this.lastReceipt = {
                is_preview: true,
                invoice_number: 'PREVIEW-' + (out.outlet_code || 'CABANG'),
                date: new Date().toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                business_name: this.business.name || 'Tandha',
                outlet_name: out.name || 'Outlet Utama',
                outlet_address: out.address || '',
                outlet_phone: out.phone || '',
                receipt_header: out.receipt_header || null,
                tax_percentage: Number(this.taxPercentage || 0),
                cashier_name: 'Akun Kasir (Simulasi Preview)',
                customer_name: this.selectedCustomerLabel,
                customer_phone: null,
                notes: this.orderNotes || 'Contoh simulasi preview struk cabang',
                items,
                subtotal: sub,
                discount_total: disc,
                tax_amount: tax,
                total_amount: grand,
                payment_method: 'PREVIEW',
                reference_number: null,
                amount_paid: grand,
                change_amount: 0,
                footer: out.receipt_footer || this.business.receipt_footer || '',
            };
            this.showReceiptModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        openPaymentModal() {
            if (this.isPreviewMode) {
                this.previewSampleReceipt(this.cart.length > 0);
                return;
            }
            if (this.hasPosFeature('pos_require_shift') && !this.activeShift) {
                Swal.fire({
                    icon: 'info',
                    title: 'Buka Shift Kasir Terlebih Dahulu',
                    text: 'Isi kas modal awal di laci kasir terlebih dahulu sebelum memproses pembayaran agar seluruh data kas tercatat lengkap.',
                    confirmButtonText: 'Buka Shift',
                    confirmButtonColor: '#0D7A5F',
                }).then(() => {
                    this.showOpenShiftModal = true;
                });
                return;
            }
            this.paymentMethod = 'cash';
            this.amountPaid = this.grandTotal;
            this.showPaymentModal = true;
        },

        async submitCheckout() {
            if (this.isPreviewMode) {
                Swal.fire({ icon: 'info', title: 'Mode Tinjau Pemilik', text: 'Akun Owner hanya meninjau tampilan kasir. Masuk dengan akun Kasir untuk mencatat transaksi nyata.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.isProcessingCheckout) return;
            this.isProcessingCheckout = true;
            try {
                const allowDisc = this.hasPosFeature('pos_allow_discount');
                const allowCust = this.hasPosFeature('pos_allow_customer');
                const allowNotes = this.hasPosFeature('pos_allow_notes');
                const res = await this.authFetch('/api/pos/checkout', {
                    method: 'POST',
                    body: JSON.stringify({
                        outlet_id: this.currentOutlet.id || 1,
                        customer_id: allowCust ? (this.selectedCustomerId || null) : null,
                        items: this.cart.map(it => ({ product_id: it.product_id, quantity: it.quantity })),
                        discount_type: allowDisc ? this.discountType : null,
                        discount_amount: allowDisc ? this.discountAmount : 0,
                        tax_percentage: this.taxPercentage,
                        payment_method: this.paymentMethod,
                        amount_paid: this.amountPaid,
                        reference_number: this.referenceNumber,
                        notes: allowNotes ? (this.orderNotes || null) : null,
                    }),
                });
                const result = await this.parseJsonSafe(res);
                if (res.ok) {
                    this.lastReceipt = result.data.receipt;
                    this.showPaymentModal = false;
                    this.showReceiptModal = true;
                    this.clearCart();
                    await this.loadProducts();
                    await this.loadShift();
                } else {
                    if (result.code === 'SHIFT_NOT_OPEN') {
                        this.showPaymentModal = false;
                        this.showOpenShiftModal = true;
                    }
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Gagal memproses transaksi.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally { this.isProcessingCheckout = false; }
        },

        printReceipt() { window.print(); },
        async submitOpenShift() {
            if (this.isPreviewMode) {
                Swal.fire({ icon: 'info', title: 'Mode Tinjau Pemilik', text: 'Shift kasir hanya dibuka oleh akun Kasir.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.isOpeningShift) return;
            this.isOpeningShift = true;
            try {
                const res = await this.authFetch('/api/shifts/open', {
                    method: 'POST',
                    body: JSON.stringify({
                        outlet_id: this.currentOutlet.id || 1,
                        initial_cash: this.initialCashInput,
                        notes: this.shiftOpenNotes || null,
                    }),
                });
                const result = await this.parseJsonSafe(res);
                if (res.ok) {
                    this.activeShift = result.data;
                    this.shiftOpenNotes = '';
                    this.showOpenShiftModal = false;
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Membuka Shift', text: result.message || 'Periksa kas awal laci.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Membuka Shift', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.isOpeningShift = false;
            }
        },
        openCloseShiftModal() {
            this.actualCashInput = Number(this.activeShift.initial_cash) + Number(this.activeShift.cash_sales_total);
            this.shiftCloseNotes = '';
            this.showCloseShiftModal = true;
        },
        async submitCloseShift() {
            if (this.isPreviewMode || this.isClosingShift || !this.activeShift) return;
            this.isClosingShift = true;
            try {
                const res = await this.authFetch(`/api/shifts/${this.activeShift.id}/close`, {
                    method: 'POST',
                    body: JSON.stringify({
                        actual_cash: this.actualCashInput,
                        notes: this.shiftCloseNotes || null,
                    }),
                });
                const result = await this.parseJsonSafe(res);
                if (res.ok) {
                    const summary = result.data.summary;
                    this.activeShift = null;
                    this.showCloseShiftModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Shift Kasir Ditutup',
                        html: `<div style="text-align:left;font-size:13px;line-height:1.6">
                            Transaksi Selesai: <b>${summary.transactions_count ?? '-'}</b><br>
                            Kas Seharusnya: <b>${this.formatRupiah(summary.expected_cash)}</b><br>
                            Kas Aktual Laci: <b>${this.formatRupiah(summary.actual_cash)}</b><br>
                            Selisih: <b>${this.formatRupiah(summary.cash_variance)} (${summary.variance_status})</b>
                        </div>`,
                        confirmButtonColor: '#0D7A5F',
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Menutup Shift', text: result.message || 'Shift tidak dapat ditutup.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Menutup Shift', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.isClosingShift = false;
            }
        },
        openCreateCustomerModal() {
            if (this.isPreviewMode) {
                Swal.fire({ icon: 'info', title: 'Mode Tinjau Pemilik', text: 'Penambahan pelanggan dari layar kasir hanya aktif saat masuk sebagai Kasir.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.newCustomer = { name: '', phone: '', email: '', notes: '' };
            this.showCreateCustomerModal = true;
        },
        async submitCreateCustomer() {
            if (this.isPreviewMode || this.isSavingCustomer) return;
            this.isSavingCustomer = true;
            try {
                const res = await this.authFetch('/api/customers', {
                    method: 'POST',
                    body: JSON.stringify(this.newCustomer),
                });
                const result = await this.parseJsonSafe(res);
                if (res.ok && result.data) {
                    await this.loadCustomers();
                    this.selectedCustomerId = result.data.id;
                    this.showCreateCustomerModal = false;
                    Swal.fire({ icon: 'success', title: 'Pelanggan Ditambahkan', timer: 1300, showConfirmButton: false });
                } else {
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Gagal', text: firstErr || result.message || 'Gagal menambah pelanggan.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.isSavingCustomer = false;
            }
        },
        async openHistoryModal() {
            if (!this.hasPosFeature('pos_allow_history')) return;
            try {
                const res = await this.authFetch(`/api/pos/history?outlet_id=${this.currentOutlet.id || 1}`);
                const data = await this.parseJsonSafe(res);
                if (!res.ok) {
                    Swal.fire({ icon: 'error', title: 'Gagal Memuat Riwayat', text: data.message || 'Riwayat transaksi tidak dapat dibuka.', confirmButtonColor: '#0D7A5F' });
                    return;
                }
                this.historyList = data.data ? (Array.isArray(data.data) ? data.data : (data.data.data || [])) : [];
                this.histSearch = '';
                this.histStatusFilter = 'all';
                this.histPayFilter = 'all';
                this.histPage = 1;
                this.showHistoryModal = true;
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Gagal Memuat Riwayat', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
            }
        },
        reprintReceipt(txn) {
            const out = txn.outlet || this.currentOutlet || {};
            const branchTax = (out.tax_percentage !== null && out.tax_percentage !== undefined)
                ? out.tax_percentage
                : (this.business.tax_percentage ?? this.taxPercentage);
            this.lastReceipt = {
                invoice_number: txn.invoice_number,
                date: this.formatDate(txn.created_at),
                business_name: this.business.name || 'Tandha',
                outlet_name: out.name || '-',
                outlet_address: out.address || '',
                outlet_phone: out.phone || '',
                receipt_header: out.receipt_header || null,
                tax_percentage: Number(branchTax),
                cashier_name: txn.cashier ? txn.cashier.name : (this.user.name || '-'),
                customer_name: txn.customer ? txn.customer.name : 'Pelanggan Umum',
                customer_phone: txn.customer ? txn.customer.phone : null,
                notes: txn.notes || null,
                items: (txn.items || []).map(it => ({
                    name: it.product_name,
                    qty: it.quantity,
                    price: it.sell_price,
                    subtotal: it.subtotal,
                })),
                subtotal: txn.subtotal,
                discount_total: txn.discount_total,
                tax_amount: txn.tax_amount,
                total_amount: txn.total_amount,
                payment_method: txn.payment ? txn.payment.payment_method.toUpperCase() : '-',
                reference_number: txn.payment ? txn.payment.reference_number : null,
                amount_paid: txn.payment ? txn.payment.amount_paid : txn.total_amount,
                change_amount: txn.payment ? txn.payment.change_amount : 0,
                footer: out.receipt_footer || this.business.receipt_footer || '',
            };
            this.showHistoryModal = false;
            this.showReceiptModal = true;
        },
        openPosActionPopup(txn, evt) {
            this.actionTxn = txn;
            if (evt && evt.currentTarget) {
                const rect = evt.currentTarget.getBoundingClientRect();
                const menuHeight = (txn && txn.payment_status === 'paid' && this.hasPosFeature('pos_allow_void')) ? 135 : 95;
                const openAbove = (window.innerHeight - rect.bottom) < menuHeight;
                const top = openAbove ? Math.max(12, rect.top - menuHeight - 6) : (rect.bottom + 6);
                const right = Math.max(12, window.innerWidth - rect.right);
                this.posActionMenuStyle = `top: ${Math.round(top)}px; right: ${Math.round(right)}px;`;
            }
            this.showPosActionPopup = true;
        },
        runPosAction(kind) {
            const target = this.actionTxn;
            this.showPosActionPopup = false;
            if (!target) return;
            if (kind === 'reprint') return this.reprintReceipt(target);
            if (kind === 'void') return this.voidTxn(target.id);
        },
        async voidTxn(txnId) {
            if (this.isPreviewMode) {
                Swal.fire({ icon: 'info', title: 'Mode Tinjau Pemilik', text: 'Pembatalan transaksi hanya dapat dijalankan oleh akun Kasir.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.isProcessingVoid) return;
            const { value: reason } = await Swal.fire({
                title: 'Alasan Pembatalan (Void)',
                input: 'text',
                inputPlaceholder: 'Minimal 5 karakter (contoh: Salah input menu)',
                showCancelButton: true,
                confirmButtonText: 'Batalkan Transaksi',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#DC2626',
                inputValidator: (v) => (!v || v.trim().length < 5) ? 'Alasan minimal 5 karakter.' : null,
            });
            if (reason) {
                this.isProcessingVoid = true;
                try {
                    const res = await this.authFetch(`/api/pos/transactions/${txnId}/void`, { method: 'POST', body: JSON.stringify({ reason }) });
                    if (res.ok) {
                        await this.openHistoryModal();
                        await this.loadProducts();
                        await this.loadShift();
                    } else {
                        const err = await this.parseJsonSafe(res);
                        Swal.fire({ icon: 'error', title: 'Gagal', text: err.message || 'Gagal membatalkan transaksi.', confirmButtonColor: '#0D7A5F' });
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: e.message || 'Koneksi ke server terputus.', confirmButtonColor: '#0D7A5F' });
                } finally {
                    this.isProcessingVoid = false;
                }
            }
        },
        async logout() {
            const currentToken = this.token;
            if (currentToken) {
                try {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' },
                    });
                } catch (_) {}
            }
            localStorage.removeItem('pos_token');
            localStorage.removeItem('pos_user');
            window.location.href = this.isKasirAppMode ? '/login?app=kasir' : '/login';
        },
        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) { return str ? new Date(str).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'; }
    }
}
</script>
@endpush
