@extends('layouts.app')

@section('title', 'Terminal Kasir POS — Seka Yasa')

@section('content')
<div class="h-screen flex flex-col bg-[#FAF7F2] text-[#1A1208] overflow-hidden" x-data="posApp()" x-init="initPos()">

    <!-- Top Bar (Deep Espresso #1A1208) -->
    <header class="bg-[#1A1208] text-[#F7F3EC] border-b border-[rgba(247,176,95,0.2)] px-4 py-2.5 flex items-center justify-between z-20">
        <div class="flex items-center gap-3">
            <a href="/" class="w-9 h-9 bg-[#23190D] border border-[rgba(247,176,95,0.3)] rounded flex items-center justify-center text-[#F7B05F] font-bold">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-sm font-bold text-[#F7F3EC] leading-none" x-text="business.name || 'Seka Yasa POS'"></h1>
                <div class="flex items-center gap-2 mt-1 text-[11px] text-[#C4B5A2]">
                    <span x-text="currentOutlet.name || 'Outlet Utama'"></span>
                    <span class="text-[#775229]">•</span>
                    <span x-text="user.name || 'Kasir'"></span>
                </div>
            </div>
        </div>

        <!-- Shift & Actions -->
        <div class="flex items-center gap-2">
            <div x-show="activeShift" class="hidden sm:flex items-center gap-2 bg-[#23190D] border border-[rgba(247,176,95,0.28)] text-[#F7B05F] px-3 py-1.5 rounded text-xs font-semibold">
                <span>Shift Aktif: <b class="font-mono" x-text="formatRupiah(activeShift ? activeShift.initial_cash : 0)"></b></span>
            </div>

            <button x-show="!activeShift" @click="showOpenShiftModal = true" class="px-3 py-1.5 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded text-xs font-bold transition">
                Buka Shift
            </button>
            <button x-show="activeShift" @click="openCloseShiftModal()" class="px-3 py-1.5 bg-[#23190D] hover:border-[#F7B05F] border border-[rgba(247,176,95,0.3)] text-[#F7F3EC] rounded text-xs font-semibold transition">
                Tutup Shift
            </button>

            <button @click="installPwa()" class="px-2.5 py-1.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded text-xs font-bold transition flex items-center gap-1">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> <span class="hidden md:inline">Install APK</span>
            </button>

            <button @click="openHeldModal()" class="relative p-2 bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded text-[#C4B5A2] hover:text-[#F7B05F] transition" title="Transaksi Ditahan">
                <i data-lucide="pause-circle" class="w-4 h-4"></i>
                <span x-show="heldCount > 0" class="absolute -top-1 -right-1 bg-[#F7B05F] text-[#1A1208] text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold" x-text="heldCount"></span>
            </button>

            <button @click="openHistoryModal()" class="p-2 bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded text-[#C4B5A2] hover:text-[#F7B05F] transition" title="Riwayat Transaksi">
                <i data-lucide="history" class="w-4 h-4"></i>
            </button>

            <a href="/dashboard" x-show="user.role !== 'cashier'" class="px-3 py-1.5 bg-[#23190D] border border-[rgba(247,176,95,0.2)] rounded text-xs font-semibold text-[#C4B5A2] hover:text-[#F7F3EC] transition">
                Dashboard
            </a>

            <button @click="logout()" class="p-2 text-[#C4B5A2] hover:text-rose-400 transition" title="Keluar">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="flex-1 flex overflow-hidden">

        <!-- Left Column: Catalog & Barcode (65%) -->
        <div class="flex-1 flex flex-col p-4 overflow-hidden border-r border-[rgba(119,82,41,0.18)] bg-[#FAF7F2]">

            <!-- Search & Barcode Input Bar -->
            <div class="flex gap-2 mb-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-[#775229] absolute left-3 top-2.5"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="filterProducts()" placeholder="Cari nama produk atau SKU..." class="w-full pl-9 pr-8 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm text-[#1A1208] placeholder-[#524538]/60 focus:outline-none focus:border-[#775229]">
                    <button x-show="searchQuery" @click="searchQuery = ''; filterProducts()" class="absolute right-2.5 top-2.5 text-[#524538]">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Barcode Fast Scanner Input -->
                <div class="relative w-48 sm:w-64">
                    <i data-lucide="scan-barcode" class="w-4 h-4 text-[#775229] absolute left-3 top-2.5"></i>
                    <input type="text" x-model="barcodeInput" @keydown.enter.prevent="scanBarcode()" placeholder="Scan Barcode (Enter)" class="w-full pl-9 pr-3 py-2 bg-[#F3EDE4] border border-[#775229]/50 rounded text-xs font-mono text-[#1A1208] focus:outline-none focus:border-[#775229]">
                </div>
            </div>

            <!-- Categories Filter -->
            <div class="flex gap-1.5 overflow-x-auto pb-2 mb-2">
                <button @click="selectedCategory = null; filterProducts()" :class="selectedCategory === null ? 'bg-[#1A1208] text-[#F7B05F] font-bold' : 'bg-[#F3EDE4] text-[#524538] border border-[rgba(119,82,41,0.18)] hover:text-[#1A1208]'" class="px-3.5 py-1.5 rounded text-xs whitespace-nowrap transition">
                    Semua Menu
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="selectedCategory = cat.id; filterProducts()" :class="selectedCategory === cat.id ? 'bg-[#1A1208] text-[#F7B05F] font-bold' : 'bg-[#F3EDE4] text-[#524538] border border-[rgba(119,82,41,0.18)] hover:text-[#1A1208]'" class="px-3.5 py-1.5 rounded text-xs whitespace-nowrap transition" x-text="cat.name"></button>
                </template>
            </div>

            <!-- Products Grid -->
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)" class="bg-[#F3EDE4] rounded-md border border-[rgba(119,82,41,0.22)] p-3.5 hover:border-[#775229] transition cursor-pointer flex flex-col justify-between select-none active:scale-[0.98]">
                            <div>
                                <div class="flex items-start justify-between gap-1 mb-1">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-[#775229]" x-text="product.category ? product.category.name : 'Umum'"></span>
                                    <span :class="product.current_stock > 10 ? 'bg-[#FAF7F2] text-[#1A1208] border-[rgba(119,82,41,0.25)]' : 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]'" class="text-[10px] px-1.5 py-0.5 rounded border font-semibold">
                                        Stok: <span x-text="product.current_stock"></span>
                                    </span>
                                </div>
                                <h3 class="text-xs font-bold text-[#1A1208] line-clamp-2" x-text="product.name"></h3>
                                <div class="text-[10px] text-[#524538] font-mono mt-0.5" x-text="product.barcode || product.sku || '-'"></div>
                            </div>

                            <div class="mt-3 flex items-center justify-between pt-2 border-t border-[rgba(119,82,41,0.16)]">
                                <span class="text-xs font-bold text-[#775229]" x-text="formatRupiah(product.sell_price)"></span>
                                <span class="text-[11px] font-bold text-[#1A1208]">+ Tambah</span>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="filteredProducts.length === 0" class="h-64 flex flex-col items-center justify-center text-[#524538]" x-cloak>
                    <p class="text-sm">Tidak ada produk ditemukan</p>
                </div>
            </div>

        </div>

        <!-- Right Column: Interactive Cart & Checkout (35%) -->
        <div class="w-96 lg:w-[420px] bg-[#F3EDE4] border-l border-[rgba(119,82,41,0.22)] flex flex-col justify-between z-10">

            <!-- Cart Header & Customer Picker -->
            <div class="p-4 border-b border-[rgba(119,82,41,0.18)] bg-[#FAF7F2]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-bold text-[#1A1208]">Keranjang Belanja</h2>
                    <button @click="clearCart()" x-show="cart.length > 0" class="text-xs text-[#775229] hover:underline font-semibold">
                        Kosongkan
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <select x-model="selectedCustomerId" class="flex-1 px-3 py-1.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-xs font-medium text-[#1A1208] focus:outline-none">
                        <option value="">Pelanggan Umum (Walk-in)</option>
                        <template x-for="cust in customers" :key="cust.id">
                            <option :value="cust.id" x-text="cust.name + (cust.phone ? ' (' + cust.phone + ')' : '')"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-for="(item, index) in cart" :key="item.product_id">
                    <div class="flex items-center justify-between gap-3 p-3 bg-[#FAF7F2] rounded border border-[rgba(119,82,41,0.18)]">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-[#1A1208] truncate" x-text="item.name"></div>
                            <div class="text-[11px] text-[#524538] mt-0.5" x-text="formatRupiah(item.price)"></div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button @click="updateQty(index, -1)" class="w-6 h-6 rounded bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] text-[#1A1208] flex items-center justify-center font-bold">-</button>
                            <span class="w-6 text-center text-xs font-bold font-mono text-[#1A1208]" x-text="item.quantity"></span>
                            <button @click="updateQty(index, 1)" class="w-6 h-6 rounded bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] text-[#1A1208] flex items-center justify-center font-bold">+</button>
                        </div>

                        <div class="text-right w-20">
                            <div class="text-xs font-bold text-[#775229]" x-text="formatRupiah(item.price * item.quantity)"></div>
                            <button @click="removeFromCart(index)" class="text-[10px] text-[#524538] hover:text-rose-700 mt-0.5">Hapus</button>
                        </div>
                    </div>
                </template>

                <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-[#524538] py-12" x-cloak>
                    <p class="text-xs font-semibold">Keranjang masih kosong</p>
                    <p class="text-[11px] mt-0.5">Pilih produk di katalog atau scan barcode</p>
                </div>
            </div>

            <!-- Cart Calculation & Bottom Checkout -->
            <div class="p-4 border-t border-[rgba(119,82,41,0.22)] bg-[#FAF7F2] space-y-2">
                <div class="flex justify-between text-xs text-[#524538]">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#1A1208]" x-text="formatRupiah(subtotal)"></span>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <button @click="openDiscountModal()" class="text-[#775229] hover:underline font-semibold">
                        <span x-text="discountAmount > 0 ? ('Diskon (' + (discountType === 'percent' ? discountAmount + '%' : formatRupiah(discountAmount)) + ')') : '+ Beri Diskon'"></span>
                    </button>
                    <span class="font-bold text-rose-700" x-text="discountTotal > 0 ? ('-' + formatRupiah(discountTotal)) : 'Rp 0'"></span>
                </div>

                <div class="flex justify-between text-xs text-[#524538]">
                    <span>Pajak (PPN 11%)</span>
                    <span class="font-bold text-[#1A1208]" x-text="formatRupiah(taxAmount)"></span>
                </div>

                <div class="pt-2 border-t border-[rgba(119,82,41,0.18)] flex justify-between items-baseline">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#524538]">Total Tagihan</span>
                    <span class="text-xl font-bold text-[#1A1208] font-mono" x-text="formatRupiah(grandTotal)"></span>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-2">
                    <button @click="holdCurrentOrder()" :disabled="cart.length === 0" class="col-span-1 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] text-[#775229] rounded text-xs font-bold transition disabled:opacity-40">
                        Tahan
                    </button>
                    <button @click="openPaymentModal()" :disabled="cart.length === 0" class="col-span-2 py-3 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] rounded text-sm font-bold transition disabled:opacity-40">
                        Bayar Sekarang (F4)
                    </button>
                </div>
            </div>

        </div>

    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- 1. Payment Modal -->
    <div x-show="showPaymentModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-lg w-full overflow-hidden border border-[rgba(119,82,41,0.3)]" @click.outside="showPaymentModal = false">
            <div class="bg-[#1A1208] text-[#F7F3EC] p-5 flex items-center justify-between border-b border-[rgba(247,176,95,0.2)]">
                <div>
                    <span class="text-xs font-semibold text-[#C4B5A2]">Total Pembayaran</span>
                    <div class="text-2xl font-bold text-[#F7B05F] font-mono" x-text="formatRupiah(grandTotal)"></div>
                </div>
                <button @click="showPaymentModal = false" class="text-[#C4B5A2] hover:text-[#F7B05F]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase text-[#524538] mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="paymentMethod = 'cash'; amountPaid = grandTotal" :class="paymentMethod === 'cash' ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.2)]'" class="py-2.5 rounded border text-xs font-bold transition">Tunai</button>
                        <button type="button" @click="paymentMethod = 'qris'; amountPaid = grandTotal" :class="paymentMethod === 'qris' ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.2)]'" class="py-2.5 rounded border text-xs font-bold transition">QRIS</button>
                        <button type="button" @click="paymentMethod = 'transfer'; amountPaid = grandTotal" :class="paymentMethod === 'transfer' ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.2)]'" class="py-2.5 rounded border text-xs font-bold transition">Transfer</button>
                        <button type="button" @click="paymentMethod = 'ewallet'; amountPaid = grandTotal" :class="paymentMethod === 'ewallet' ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.2)]'" class="py-2.5 rounded border text-xs font-bold transition">E-Wallet</button>
                    </div>
                </div>

                <div x-show="paymentMethod === 'cash'" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#524538] mb-1">Uang Diterima (Rp)</label>
                        <input type="number" x-model.number="amountPaid" class="w-full px-3.5 py-2.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] rounded text-lg font-bold font-mono focus:outline-none focus:border-[#775229]">
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="amountPaid = grandTotal" class="py-1.5 bg-[#F2EBE1] rounded text-xs font-bold text-[#775229]">Uang Pas</button>
                        <button type="button" @click="amountPaid = 50000" class="py-1.5 bg-[#F2EBE1] rounded text-xs font-bold text-[#775229]">50.000</button>
                        <button type="button" @click="amountPaid = 100000" class="py-1.5 bg-[#F2EBE1] rounded text-xs font-bold text-[#775229]">100.000</button>
                        <button type="button" @click="amountPaid = 200000" class="py-1.5 bg-[#F2EBE1] rounded text-xs font-bold text-[#775229]">200.000</button>
                    </div>
                    <div class="p-3 rounded bg-[#F3EDE4] flex items-center justify-between border border-[rgba(119,82,41,0.2)]">
                        <span class="text-xs font-bold uppercase text-[#524538]">Kembalian</span>
                        <span :class="changeAmount >= 0 ? 'text-[#775229]' : 'text-rose-700'" class="text-lg font-bold font-mono" x-text="changeAmount >= 0 ? formatRupiah(changeAmount) : 'Kurang ' + formatRupiah(Math.abs(changeAmount))"></span>
                    </div>
                </div>

                <div x-show="paymentMethod !== 'cash'" class="space-y-1">
                    <label class="block text-xs font-bold uppercase text-[#524538]">Nomor Referensi</label>
                    <input type="text" x-model="referenceNumber" placeholder="REF123456" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] rounded text-sm">
                </div>

                <button type="button" @click="submitCheckout()" :disabled="isProcessingCheckout || (paymentMethod === 'cash' && changeAmount < 0)" class="w-full py-3.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] font-bold rounded transition disabled:opacity-40 text-sm">
                    <span x-show="!isProcessingCheckout">Selesaikan Transaksi & Cetak Struk</span>
                    <span x-show="isProcessingCheckout">Memproses...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Thermal Receipt Modal -->
    <div x-show="showReceiptModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-sm w-full overflow-hidden border border-[rgba(119,82,41,0.3)]" @click.outside="showReceiptModal = false">
            <div class="p-4 bg-[#1A1208] text-[#F7F3EC] flex justify-between items-center">
                <span class="text-xs font-bold uppercase">Struk Transaksi</span>
                <button @click="showReceiptModal = false" class="text-[#C4B5A2]"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <div class="p-5 max-h-[70vh] overflow-y-auto">
                <div id="printable-receipt" class="bg-white p-4 border border-dashed border-[#775229]/40 font-mono text-xs text-[#1A1208] space-y-2">
                    <div class="text-center pb-2 border-b border-dashed border-slate-300">
                        <div class="font-bold text-sm" x-text="lastReceipt.business_name"></div>
                        <div class="text-[10px]" x-text="lastReceipt.outlet_name"></div>
                        <div class="text-[10px]" x-text="lastReceipt.outlet_address"></div>
                    </div>
                    <div class="text-[10px] space-y-0.5 pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>No: <b x-text="lastReceipt.invoice_number"></b></span><span x-text="lastReceipt.date"></span></div>
                        <div class="flex justify-between"><span>Kasir: <span x-text="lastReceipt.cashier_name"></span></span><span>Cust: <span x-text="lastReceipt.customer_name"></span></span></div>
                    </div>
                    <div class="space-y-1 pb-2 border-b border-dashed border-slate-300 text-[11px]">
                        <template x-for="it in lastReceipt.items" :key="it.name">
                            <div>
                                <div class="font-bold" x-text="it.name"></div>
                                <div class="flex justify-between text-[10px] text-slate-600">
                                    <span x-text="it.qty + ' x ' + formatRupiah(it.price)"></span>
                                    <span class="font-bold text-slate-800" x-text="formatRupiah(it.subtotal)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="space-y-0.5 text-[11px] pb-2 border-b border-dashed border-slate-300">
                        <div class="flex justify-between"><span>Subtotal</span><span x-text="formatRupiah(lastReceipt.subtotal)"></span></div>
                        <div x-show="lastReceipt.discount_total > 0" class="flex justify-between"><span>Diskon</span><span x-text="'-' + formatRupiah(lastReceipt.discount_total)"></span></div>
                        <div class="flex justify-between"><span>PPN (11%)</span><span x-text="formatRupiah(lastReceipt.tax_amount)"></span></div>
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-slate-200"><span>TOTAL</span><span x-text="formatRupiah(lastReceipt.total_amount)"></span></div>
                        <div class="flex justify-between text-[10px]"><span x-text="'BAYAR (' + lastReceipt.payment_method + ')'"></span><span x-text="formatRupiah(lastReceipt.amount_paid)"></span></div>
                        <div class="flex justify-between text-[10px] font-bold"><span>KEMBALIAN</span><span x-text="formatRupiah(lastReceipt.change_amount)"></span></div>
                    </div>
                    <div class="text-center pt-2 text-[10px] text-slate-500">
                        <p x-text="lastReceipt.footer || 'Terima kasih atas kunjungan Anda!'"></p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-[#F3EDE4] border-t border-[rgba(119,82,41,0.18)] flex gap-2">
                <button type="button" @click="printReceipt()" class="flex-1 py-2.5 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] font-bold rounded text-xs">Cetak Struk</button>
                <button type="button" @click="showReceiptModal = false" class="py-2.5 px-4 bg-[#FAF7F2] border border-[rgba(119,82,41,0.28)] text-[#1A1208] font-bold rounded text-xs">Selesai</button>
            </div>
        </div>
    </div>

    <!-- 3. Open Shift Modal -->
    <div x-show="showOpenShiftModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-sm w-full p-6 border border-[rgba(119,82,41,0.3)]" @click.outside="showOpenShiftModal = false">
            <h3 class="text-base font-bold text-[#1A1208]">Buka Shift Kasir</h3>
            <p class="text-xs text-[#524538] mt-1 mb-4">Masukkan kas modal awal di laci kasir</p>
            <form @submit.prevent="submitOpenShift()" class="space-y-4">
                <input type="number" x-model.number="initialCashInput" required min="0" class="w-full px-3 py-2.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-base font-bold font-mono">
                <div class="flex gap-2">
                    <button type="button" @click="showOpenShiftModal = false" class="flex-1 py-2 bg-[#F2EBE1] text-[#524538] rounded text-xs font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#775229] text-[#FAF7F2] rounded text-xs font-bold">Mulai Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Close Shift Modal -->
    <div x-show="showCloseShiftModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-md w-full overflow-hidden border border-[rgba(119,82,41,0.3)]" @click.outside="showCloseShiftModal = false">
            <div class="bg-[#1A1208] text-[#F7B05F] p-4 font-bold text-sm">Tutup Shift & Rekonsiliasi Kas</div>
            <div class="p-6 space-y-4">
                <div class="bg-[#F3EDE4] rounded p-3 border border-[rgba(119,82,41,0.2)] space-y-1.5 text-xs">
                    <div class="flex justify-between"><span>Modal Awal Kas:</span><b x-text="formatRupiah(activeShift ? activeShift.initial_cash : 0)"></b></div>
                    <div class="flex justify-between"><span>Penjualan Tunai:</span><b x-text="formatRupiah(activeShift ? activeShift.cash_sales_total : 0)"></b></div>
                    <div class="flex justify-between"><span>Penjualan Non-Tunai:</span><b x-text="formatRupiah(activeShift ? activeShift.non_cash_sales_total : 0)"></b></div>
                    <div class="flex justify-between pt-1.5 border-t border-[rgba(119,82,41,0.18)] font-bold text-[#775229]">
                        <span>Kas Seharusnya (Expected):</span>
                        <span x-text="formatRupiah((activeShift ? Number(activeShift.initial_cash) : 0) + (activeShift ? Number(activeShift.cash_sales_total) : 0))"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-[#524538] mb-1">Kas Aktual Fisik di Laci</label>
                    <input type="number" x-model.number="actualCashInput" required min="0" class="w-full px-3 py-2.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] rounded text-base font-bold font-mono">
                </div>
                <div class="p-3 rounded bg-[#F2EBE1] border border-[rgba(119,82,41,0.25)] flex items-center justify-between text-xs font-bold">
                    <span>Selisih Kas (Variance):</span>
                    <span class="font-mono" x-text="shiftVariance === 0 ? 'PAS (Cocok)' : formatRupiah(shiftVariance)"></span>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="showCloseShiftModal = false" class="flex-1 py-2.5 bg-[#F2EBE1] text-[#524538] rounded text-xs font-bold">Batal</button>
                    <button type="button" @click="submitCloseShift()" class="flex-1 py-2.5 bg-[#1A1208] text-[#F7B05F] rounded text-xs font-bold">Konfirmasi Tutup Shift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Discount Modal -->
    <div x-show="showDiscountModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-xs w-full p-5 border border-[rgba(119,82,41,0.3)]" @click.outside="showDiscountModal = false">
            <h3 class="text-sm font-bold text-[#1A1208] mb-3">Atur Diskon</h3>
            <div class="flex gap-2 mb-3">
                <button type="button" @click="tempDiscountType = 'percent'" :class="tempDiscountType === 'percent' ? 'bg-[#1A1208] text-[#F7B05F]' : 'bg-[#F3EDE4] text-[#524538]'" class="flex-1 py-1.5 rounded text-xs font-bold">Persen (%)</button>
                <button type="button" @click="tempDiscountType = 'fixed'" :class="tempDiscountType === 'fixed' ? 'bg-[#1A1208] text-[#F7B05F]' : 'bg-[#F3EDE4] text-[#524538]'" class="flex-1 py-1.5 rounded text-xs font-bold">Nominal (Rp)</button>
            </div>
            <input type="number" x-model.number="tempDiscountAmount" min="0" class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] rounded text-sm font-bold mb-4">
            <div class="flex gap-2">
                <button type="button" @click="tempDiscountAmount = 0; applyDiscount()" class="flex-1 py-2 bg-[#F2EBE1] text-[#524538] rounded text-xs font-bold">Hapus</button>
                <button type="button" @click="applyDiscount()" class="flex-1 py-2 bg-[#775229] text-[#FAF7F2] rounded text-xs font-bold">Terapkan</button>
            </div>
        </div>
    </div>

    <!-- 6. History Modal -->
    <div x-show="showHistoryModal" class="fixed inset-0 bg-[#1A1208]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-[#FAF7F2] rounded-lg max-w-2xl w-full border border-[rgba(119,82,41,0.3)] overflow-hidden flex flex-col max-h-[85vh]" @click.outside="showHistoryModal = false">
            <div class="p-4 bg-[#1A1208] text-[#F7F3EC] flex justify-between items-center">
                <h3 class="text-sm font-bold">Riwayat Transaksi Hari Ini</h3>
                <button @click="showHistoryModal = false" class="text-[#C4B5A2]"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-for="txn in historyList" :key="txn.id">
                    <div class="p-3 bg-[#F3EDE4] rounded border border-[rgba(119,82,41,0.18)] flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-[#775229]" x-text="txn.invoice_number"></span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#FAF7F2] font-bold uppercase" x-text="txn.payment_status"></span>
                            </div>
                            <div class="text-[11px] text-[#524538] mt-0.5" x-text="formatDate(txn.created_at) + ' • ' + (txn.payment ? txn.payment.payment_method.toUpperCase() : '')"></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-[#1A1208]" x-text="formatRupiah(txn.total_amount)"></span>
                            <button x-show="txn.payment_status === 'paid'" @click="voidTxn(txn.id)" class="px-2.5 py-1 bg-rose-100 text-rose-800 rounded text-xs font-bold">Void</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        token: localStorage.getItem('pos_token') || '',
        user: JSON.parse(localStorage.getItem('pos_user') || '{}'),
        business: {},
        currentOutlet: {},
        categories: [],
        products: [],
        filteredProducts: [],
        customers: [],
        cart: [],
        searchQuery: '',
        barcodeInput: '',
        selectedCategory: null,
        selectedCustomerId: '',
        discountType: null,
        discountAmount: 0,
        tempDiscountType: 'percent',
        tempDiscountAmount: 0,
        taxPercentage: 11,
        showPaymentModal: false,
        paymentMethod: 'cash',
        amountPaid: 0,
        referenceNumber: '',
        isProcessingCheckout: false,
        showReceiptModal: false,
        lastReceipt: {},
        activeShift: null,
        showOpenShiftModal: false,
        initialCashInput: 200000,
        showCloseShiftModal: false,
        actualCashInput: 0,
        showDiscountModal: false,
        showHistoryModal: false,
        historyList: [],
        heldCount: 0,
        deferredInstallPrompt: null,

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

        async initPos() {
            if (!this.token) { window.location.href = '/login'; return; }
            await this.loadInitialData();
            await this.loadShift();
            await this.loadProducts();
            await this.loadCategories();
            await this.loadCustomers();
            await this.checkHeldCount();

            window.addEventListener('keydown', (e) => {
                if (e.key === 'F4') { e.preventDefault(); if (this.cart.length > 0) this.openPaymentModal(); }
            });
            window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); this.deferredInstallPrompt = e; });
        },

        async installPwa() {
            if (this.deferredInstallPrompt) {
                this.deferredInstallPrompt.prompt();
                this.deferredInstallPrompt = null;
            } else {
                Swal.fire({ icon: 'info', title: 'Pasang di HP Android', text: 'Buka menu Chrome Android lalu pilih "Tambahkan ke Layar Utama / Install App".', confirmButtonColor: '#775229' });
            }
        },

        async authFetch(url, options = {}) {
            options.headers = { ...options.headers, 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            const res = await fetch(url, options);
            if (res.status === 401) { localStorage.removeItem('pos_token'); window.location.href = '/login'; throw new Error('Expired'); }
            return res;
        },

        async loadInitialData() {
            const res = await this.authFetch('/api/me');
            const data = await res.json();
            if (data.status === 'success') {
                this.user = data.data;
                this.business = data.data.business || {};
                this.currentOutlet = data.data.outlet || (this.business.outlets ? this.business.outlets[0] : {});
                this.taxPercentage = Number(this.business.tax_percentage || 11);
            }
        },
        async loadShift() {
            const res = await this.authFetch(`/api/shifts/current?outlet_id=${this.currentOutlet.id || 1}`);
            const data = await res.json();
            this.activeShift = data.data;
        },
        async loadProducts() {
            const res = await this.authFetch(`/api/products?outlet_id=${this.currentOutlet.id || 1}`);
            const data = await res.json();
            if (data.status === 'success') { this.products = data.data; this.filterProducts(); }
        },
        async loadCategories() {
            const res = await this.authFetch('/api/categories');
            const data = await res.json();
            if (data.status === 'success') this.categories = data.data;
        },
        async loadCustomers() {
            const res = await this.authFetch('/api/customers');
            const data = await res.json();
            if (data.status === 'success') this.customers = data.data;
        },

        filterProducts() {
            let list = this.products;
            if (this.selectedCategory) list = list.filter(p => p.category_id === this.selectedCategory);
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)) || (p.barcode && p.barcode.includes(q)));
            }
            this.filteredProducts = list;
            this.$nextTick(() => lucide.createIcons());
        },

        async scanBarcode() {
            if (!this.barcodeInput) return;
            const res = await this.authFetch(`/api/products/barcode/${encodeURIComponent(this.barcodeInput)}?outlet_id=${this.currentOutlet.id || 1}`);
            const result = await res.json();
            if (res.ok && result.data) { this.addToCart(result.data); this.barcodeInput = ''; }
            else Swal.fire({ icon: 'warning', title: 'Tidak Ditemukan', text: result.message, timer: 1500, showConfirmButton: false });
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
        clearCart() { this.cart = []; this.discountAmount = 0; this.discountType = null; },
        openDiscountModal() { this.tempDiscountType = this.discountType || 'percent'; this.tempDiscountAmount = this.discountAmount || 0; this.showDiscountModal = true; },
        applyDiscount() { this.discountType = this.tempDiscountType; this.discountAmount = this.tempDiscountAmount; this.showDiscountModal = false; },
        openPaymentModal() { this.paymentMethod = 'cash'; this.amountPaid = this.grandTotal; this.showPaymentModal = true; },

        async submitCheckout() {
            this.isProcessingCheckout = true;
            try {
                const res = await this.authFetch('/api/pos/checkout', {
                    method: 'POST',
                    body: JSON.stringify({
                        outlet_id: this.currentOutlet.id || 1,
                        customer_id: this.selectedCustomerId || null,
                        items: this.cart.map(it => ({ product_id: it.product_id, quantity: it.quantity })),
                        discount_type: this.discountType, discount_amount: this.discountAmount, tax_percentage: this.taxPercentage,
                        payment_method: this.paymentMethod, amount_paid: this.amountPaid, reference_number: this.referenceNumber,
                    }),
                });
                const result = await res.json();
                if (res.ok) {
                    this.lastReceipt = result.data.receipt;
                    this.showPaymentModal = false;
                    this.showReceiptModal = true;
                    this.clearCart();
                    await this.loadProducts();
                    await this.loadShift();
                } else { Swal.fire({ icon: 'error', title: 'Gagal', text: result.message, confirmButtonColor: '#775229' }); }
            } finally { this.isProcessingCheckout = false; }
        },

        printReceipt() { window.print(); },
        async submitOpenShift() {
            const res = await this.authFetch('/api/shifts/open', { method: 'POST', body: JSON.stringify({ outlet_id: this.currentOutlet.id || 1, initial_cash: this.initialCashInput }) });
            const result = await res.json();
            if (res.ok) { this.activeShift = result.data; this.showOpenShiftModal = false; }
        },
        openCloseShiftModal() { this.actualCashInput = Number(this.activeShift.initial_cash) + Number(this.activeShift.cash_sales_total); this.showCloseShiftModal = true; },
        async submitCloseShift() {
            const res = await this.authFetch(`/api/shifts/${this.activeShift.id}/close`, { method: 'POST', body: JSON.stringify({ actual_cash: this.actualCashInput }) });
            const result = await res.json();
            if (res.ok) {
                this.activeShift = null;
                this.showCloseShiftModal = false;
                Swal.fire({ icon: 'success', title: 'Shift Ditutup', text: `Selisih: ${this.formatRupiah(result.data.summary.cash_variance)}`, confirmButtonColor: '#775229' });
            }
        },
        async holdCurrentOrder() {
            const res = await this.authFetch('/api/pos/hold', { method: 'POST', body: JSON.stringify({ outlet_id: this.currentOutlet.id || 1, customer_id: this.selectedCustomerId || null, items: this.cart }) });
            if (res.ok) { this.clearCart(); await this.checkHeldCount(); }
        },
        async checkHeldCount() {
            const res = await this.authFetch(`/api/pos/held?outlet_id=${this.currentOutlet.id || 1}`);
            const data = await res.json();
            this.heldCount = data.data ? data.data.length : 0;
        },
        async openHeldModal() {
            const res = await this.authFetch(`/api/pos/held?outlet_id=${this.currentOutlet.id || 1}`);
            const data = await res.json();
            if (!data.data || data.data.length === 0) { Swal.fire({ icon: 'info', title: 'Kosong', confirmButtonColor: '#775229' }); return; }
            const options = {};
            data.data.forEach(h => { options[h.id] = h.invoice_number; });
            const { value: heldId } = await Swal.fire({ title: 'Lanjutkan Transaksi', input: 'select', inputOptions: options, showCancelButton: true, confirmButtonColor: '#775229' });
            if (heldId) {
                const resumeRes = await this.authFetch(`/api/pos/held/${heldId}/resume`, { method: 'DELETE' });
                const resumeData = await resumeRes.json();
                if (resumeRes.ok) { this.cart = resumeData.data.items || []; await this.checkHeldCount(); }
            }
        },
        async openHistoryModal() {
            const res = await this.authFetch(`/api/pos/history?outlet_id=${this.currentOutlet.id || 1}`);
            const data = await res.json();
            this.historyList = data.data ? data.data.data : [];
            this.showHistoryModal = true;
        },
        async voidTxn(txnId) {
            const { value: reason } = await Swal.fire({ title: 'Alasan Void', input: 'text', showCancelButton: true, confirmButtonColor: '#775229' });
            if (reason) {
                const res = await this.authFetch(`/api/pos/transactions/${txnId}/void`, { method: 'POST', body: JSON.stringify({ reason }) });
                if (res.ok) { await this.openHistoryModal(); await this.loadProducts(); }
            }
        },
        logout() { localStorage.removeItem('pos_token'); localStorage.removeItem('pos_user'); window.location.href = '/login'; },
        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) { return str ? new Date(str).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'; }
    }
}
</script>
@endpush
