@extends('layouts.app')

@section('title', 'Dashboard Pemilik & Admin — Tandha by SEKA GROUP')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.28); border-radius: 9999px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.5); }
    aside .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.25); }
    aside .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.45); }
</style>
<div class="h-[100dvh] overflow-hidden flex bg-slate-50 text-slate-900 relative" x-data="dashboardApp()" x-init="initDashboard()">

    <!-- Global Slim Progress Bar -->
    <div x-show="isFetching > 0 || isSaving" x-cloak class="fixed top-0 left-0 right-0 z-[95] h-0.5 bg-emerald-950/10 overflow-hidden pointer-events-none">
        <div class="h-full bg-[#0D7A5F] tandha-progress-bar"></div>
    </div>

    <!-- Branded Initial Boot Loader -->
    <div x-show="isBooting" x-transition.opacity.duration.200ms class="fixed inset-0 z-[90] bg-slate-50/95 backdrop-blur-xs flex flex-col items-center justify-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#0D2620] border border-emerald-500/30 flex items-center justify-center p-2.5 shadow-sm">
            <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-full h-full object-contain animate-pulse">
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-[#0D2620]">
            <svg class="w-4 h-4 animate-spin text-[#0D7A5F]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
            <span>Memuat Panel Manajemen Tandha...</span>
        </div>
    </div>

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="mobileMenuOpen" x-transition.opacity.duration.150ms @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-[#0D2620]/60 backdrop-blur-[1px] z-40 lg:hidden" x-cloak></div>

    <!-- Sidebar Navigation (Slide-over Drawer on Mobile/Tablet + Persistent Sidebar on Desktop) -->
    <aside :class="mobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-64 max-w-[85vw] h-[100dvh] bg-[#0F1F1B] text-slate-300 flex flex-col shrink-0 border-r border-slate-800 transform transition-transform duration-200 ease-out lg:static lg:z-20">
        <!-- Brand (Sticky Top) -->
        <div class="p-4 sm:p-5 border-b border-slate-800/90 shrink-0 flex items-center justify-between gap-2">
            <a href="/" class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-8 h-8 rounded-lg bg-[#143B32] border border-emerald-500/25 flex items-center justify-center p-1.5 shrink-0">
                    <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0 leading-none">
                    <span class="text-[11px] font-extrabold text-white tracking-[0.08em] uppercase block">TANDHA</span>
                    <span class="text-[9px] font-bold text-emerald-400 tracking-[0.08em] block mt-0.5">by SEKA GROUP</span>
                    <h1 class="text-xs font-semibold text-slate-300 mt-1 truncate" x-text="user.role === 'superadmin' ? 'Platform Monitoring Pusat' : (business.name || 'Manajemen Bisnis')"></h1>
                </div>
            </a>
            <button type="button" @click="mobileMenuOpen = false" aria-label="Tutup menu"
                class="lg:hidden w-7 h-7 rounded-md bg-white/5 hover:bg-white/10 text-slate-300 flex items-center justify-center shrink-0 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <!-- Custom Designed Outlet Switcher Dropdown (Unlocked workspaces only) -->
        <div x-show="user.role !== 'superadmin' && hasUnlockedWorkspace" class="p-4 border-b border-slate-800/90 shrink-0 relative" @click.outside="if (openDropdown === 'outlet') openDropdown = null">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Outlet Aktif</label>
            <button type="button" @click="toggleDropdown('outlet')"
                class="w-full px-3 py-2 bg-[#162B25] border border-slate-700 hover:border-emerald-500/60 rounded-md text-xs font-medium text-white flex items-center justify-between gap-2 transition">
                <span class="truncate" x-text="selectedOutletLabel"></span>
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'outlet' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-show="openDropdown === 'outlet'" x-transition class="absolute left-4 right-4 mt-1.5 bg-[#162B25] border border-slate-700 rounded-md shadow-xl z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                <button type="button" @click="selectedOutletId = ''; openDropdown = null; onOutletChange()"
                    class="w-full px-3 py-2 text-left text-xs flex items-center justify-between hover:bg-white/5 transition"
                    :class="!selectedOutletId ? 'text-emerald-400 font-semibold bg-white/5' : 'text-slate-300'">
                    <span>Semua Outlet</span>
                    <span x-show="!selectedOutletId" class="text-emerald-400">✓</span>
                </button>
                <template x-for="out in outlets" :key="out.id">
                    <button type="button" @click="selectedOutletId = out.id; openDropdown = null; onOutletChange()"
                        class="w-full px-3 py-2 text-left text-xs flex items-center justify-between hover:bg-white/5 transition"
                        :class="String(selectedOutletId) === String(out.id) ? 'text-emerald-400 font-semibold bg-white/5' : 'text-slate-300'">
                        <span class="truncate" x-text="out.name"></span>
                        <span x-show="String(selectedOutletId) === String(out.id)" class="text-emerald-400">✓</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Scrollable Menu Items -->
        <nav class="flex-1 min-h-0 overflow-y-auto custom-scrollbar p-3 space-y-1 text-xs font-medium">
            <template x-if="user.role !== 'superadmin'">
                <div class="space-y-1">
                    <!-- Operasional & Laporan (Terbuka setelah berlangganan aktif) -->
                    <div x-show="hasUnlockedWorkspace" class="space-y-1">
                        <div class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Operasional &amp; Laporan</div>
                        <button x-show="user.role === 'owner' || hasFeature('admin_view_dashboard')" @click="setTab('overview')" :class="tab === 'overview' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> <span class="truncate">Ringkasan Bisnis</span>
                        </button>
                        <button x-show="user.role === 'owner' || hasFeature('admin_manage_products') || hasFeature('admin_manage_categories')" @click="setTab('products')" :class="tab === 'products' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                            <i data-lucide="package" class="w-4 h-4 shrink-0"></i> <span class="truncate">Produk &amp; Kategori</span>
                        </button>
                        <button x-show="user.role === 'owner' || hasFeature('admin_manage_inventory')" @click="setTab('inventory')" :class="tab === 'inventory' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                            <i data-lucide="boxes" class="w-4 h-4 shrink-0"></i> <span class="truncate">Inventori &amp; Stok</span>
                        </button>
                        <button x-show="user.role === 'owner' || hasFeature('admin_view_sales_report') || hasFeature('admin_view_shift_history')" @click="setTab('reports')" :class="tab === 'reports' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 shrink-0"></i> <span class="truncate" x-text="user.role === 'owner' ? 'Laporan & Laba' : 'Laporan Penjualan'"></span>
                        </button>
                    </div>

                    <!-- Pengaturan Bisnis (Terbuka setelah berlangganan aktif) -->
                    <template x-if="user.role === 'owner' && hasUnlockedWorkspace">
                        <div class="space-y-1 pt-2 mt-2 border-t border-slate-800/90">
                            <div class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pengaturan Bisnis</div>
                            <button @click="setTab('staff')" :class="tab === 'staff' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded transition">
                                <span class="flex items-center gap-2.5 truncate"><i data-lucide="users" class="w-4 h-4 shrink-0"></i> Tim &amp; Staf</span>
                                <span x-show="staffLimits.max_users" class="text-[10px] font-tabular px-1.5 py-0.5 rounded bg-white/10 text-emerald-300" x-text="staffLimits.used_users + '/' + staffLimits.max_users"></span>
                            </button>
                            <button @click="setTab('outlets')" :class="tab === 'outlets' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                                <i data-lucide="store" class="w-4 h-4 shrink-0"></i> <span class="truncate">Outlet &amp; Profil</span>
                            </button>
                            <button @click="setTab('settings')" :class="tab === 'settings' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                                <i data-lucide="sliders-horizontal" class="w-4 h-4 shrink-0"></i> <span class="truncate">Pengaturan Fitur</span>
                            </button>
                        </div>
                    </template>

                    <!-- Paket & Dukungan (Selalu tersedia untuk akun pemilik sebelum & sesudah berlangganan) -->
                    <template x-if="user.role === 'owner'">
                        <div class="space-y-1" :class="hasUnlockedWorkspace ? 'pt-2 mt-2 border-t border-slate-800/90' : 'pt-1'">
                            <div class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Paket &amp; Dukungan</div>
                            <button @click="setTab('billing')" :class="tab === 'billing' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded transition">
                                <span class="flex items-center gap-2.5 truncate"><i data-lucide="credit-card" class="w-4 h-4 shrink-0"></i> Langganan &amp; Tagihan</span>
                                <span x-show="business.subscription_summary && ['expired', 'none'].includes(business.subscription_summary.lifecycle_status)" class="w-2 h-2 rounded-full bg-rose-400 shrink-0"></span>
                            </button>
                            <button @click="setTab('consultation')" :class="tab === 'consultation' ? 'bg-[#1B332C] text-white font-semibold border-l-2 border-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded transition">
                                <i data-lucide="message-square-plus" class="w-4 h-4 shrink-0"></i> <span class="truncate">Konsultasi Fitur</span>
                            </button>
                            <div x-show="!hasUnlockedWorkspace" class="mx-1 mt-3 p-3 rounded-md bg-[#143B32]/80 border border-emerald-500/20 text-[11px] leading-relaxed text-slate-300">
                                Modul kasir, produk, stok, laporan, staf, dan outlet otomatis terbuka setelah paket langganan diaktifkan.
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="user.role === 'superadmin'">
                <div class="space-y-1">
                    <div class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Menu Platform</div>
                    <button @click="setAdminSubTab('clients')" :class="tab === 'admin_platform' && adminSubTab === 'clients' ? 'bg-[#1B332C] text-white font-semibold' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-md transition">
                        <span class="flex items-center gap-2.5 truncate"><i data-lucide="building-2" class="w-4 h-4 shrink-0 text-emerald-400"></i> Semua Klien</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/10 text-slate-200 font-tabular" x-text="adminBusinesses.length"></span>
                    </button>
                    <button @click="setAdminSubTab('billing')" :class="tab === 'admin_platform' && adminSubTab === 'billing' ? 'bg-[#1B332C] text-white font-semibold' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-md transition">
                        <span class="flex items-center gap-2.5 truncate"><i data-lucide="message-square-plus" class="w-4 h-4 shrink-0 text-emerald-400"></i> Konsultasi Fitur</span>
                        <span x-show="adminConsultations.length > 0" class="text-[10px] px-1.5 py-0.5 rounded bg-white/10 text-slate-200 font-tabular" x-text="adminConsultations.length"></span>
                    </button>
                    <button @click="setAdminSubTab('logs')" :class="tab === 'admin_platform' && adminSubTab === 'logs' ? 'bg-[#1B332C] text-white font-semibold' : 'text-slate-300 hover:bg-white/5 hover:text-white'" class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-md transition">
                        <span class="flex items-center gap-2.5 truncate"><i data-lucide="bug" class="w-4 h-4 shrink-0 text-emerald-400"></i> Error &amp; Aktivitas</span>
                        <span x-show="(adminMetrics.error_log_count || 0) > 0" class="text-[10px] px-1.5 py-0.5 rounded font-tabular bg-rose-500/20 text-rose-300" x-text="adminMetrics.error_log_count"></span>
                    </button>

                    <!-- Quick Jump Klien di Sidebar (Scrollable saat klien banyak) -->
                    <div x-show="adminBusinesses.length > 0" class="pt-4 mt-3 border-t border-slate-800 space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Akses Cepat Klien</div>
                        <template x-for="sbBiz in adminBusinesses" :key="'sb-' + sbBiz.id">
                            <button type="button" @click="openClientDetail(sbBiz.id)"
                                class="w-full px-3 py-1.5 rounded text-left hover:bg-white/5 transition flex items-center justify-between gap-2 group">
                                <span class="truncate text-[11px] text-slate-300 group-hover:text-white" x-text="sbBiz.name"></span>
                                <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                    :class="!sbBiz.is_active ? 'bg-rose-400' : (sbBiz.subscription_summary && sbBiz.subscription_summary.is_active ? 'bg-emerald-400' : 'bg-amber-400')"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </nav>

        <!-- Sidebar Bottom (Sticky Bottom) -->
        <div class="p-4 border-t border-[rgba(51,65,85,0.75)] space-y-2.5 shrink-0">
            <!-- Subscription Status Widget (Owner Exclusive) -->
            <template x-if="user.role === 'owner' && business.subscription_summary">
                <button type="button" @click="setTab('billing')" class="w-full text-left block p-3 rounded-md bg-[#143B32] border border-[rgba(52,211,153,0.28)] hover:border-[#34D399] transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#34D399]" x-text="business.subscription_summary.plan_name"></span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                            :class="business.subscription_summary.lifecycle_status === 'expired' ? 'bg-rose-500/20 text-rose-300' : (business.subscription_summary.lifecycle_status === 'none' ? 'bg-white/10 text-slate-300' : (business.subscription_summary.is_expiring_soon ? 'bg-amber-400/20 text-amber-300' : 'bg-[#0D7A5F]/40 text-[#34D399]'))"
                            x-text="business.subscription_summary.lifecycle_status === 'expired' ? 'Kedaluwarsa' : (business.subscription_summary.is_expiring_soon ? 'Segera Habis' : (business.subscription_summary.lifecycle_status === 'none' ? 'Belum Aktif' : 'Aktif'))"></span>
                    </div>
                    <div class="text-[11px] text-[#E2E8F0] mt-1 font-tabular" x-text="business.subscription_summary.expires_at ? ('s.d. ' + (business.subscription_summary.expires_at_formatted || '-')) : (business.subscription_summary.expires_at_formatted || 'Belum ada paket aktif')"></div>
                    <div class="text-[10px] text-[#CBD5E1] mt-0.5 flex items-center justify-between">
                        <span x-text="business.subscription_summary.countdown_label"></span>
                        <span class="text-[#34D399] font-semibold" x-text="business.subscription_summary.lifecycle_status === 'none' ? 'Pilih Paket →' : 'Kelola →'"></span>
                    </div>
                </button>
            </template>

            <div class="flex items-center justify-between pt-1">
                <div class="leading-tight">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-[#FFFFFF] truncate max-w-[110px]" x-text="user.name"></span>
                        <span class="text-[8px] font-bold uppercase px-1 py-0.5 bg-[#34D399] text-[#0D2620] rounded">Login</span>
                    </div>
                    <span class="text-[10px] text-[#CBD5E1] uppercase font-semibold" x-text="user.role"></span>
                </div>
                <button @click="logout()" class="p-1.5 text-[#CBD5E1] hover:text-rose-400 rounded transition" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Fixed Viewport Shell on Desktop, Responsive Drawer Shell on Mobile) -->
    <main class="flex-1 flex flex-col min-w-0 h-[100dvh] overflow-hidden">

        <!-- Header Bar -->
        <header class="shrink-0 bg-[#FFFFFF] border-b border-[rgba(15,23,42,0.12)] px-4 sm:px-6 lg:px-8 py-3 sm:py-4 flex items-center justify-between gap-2 z-10">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Buka menu navigasi"
                    class="lg:hidden w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-[#0D2620] flex items-center justify-center shrink-0 transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="min-w-0">
                    <div class="text-[10px] sm:text-[11px] font-semibold text-[#0D7A5F] uppercase tracking-wider truncate" x-text="user.role === 'superadmin' ? 'Platform Super Admin' : 'Panel Manajemen'"></div>
                    <h2 class="text-sm sm:text-lg font-bold text-[#0D2620] truncate" x-text="activeHeaderTitle"></h2>
                </div>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <button x-show="user.role === 'owner' && hasUnlockedWorkspace" type="button" @click="openPosPreviewModal()"
                    class="hidden md:inline-flex items-center gap-1.5 h-8 px-3 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition"
                    title="Tinjau tampilan menu, pajak, dan struk cabang langsung di halaman ini">
                    <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i> Preview Kasir
                </button>
                <div x-show="user.role === 'owner' && hasUnlockedWorkspace" class="inline-flex items-center gap-1.5">
                    <button type="button" @click="installKasirApk('webapk')"
                        class="inline-flex items-center gap-1.5 h-8 px-2 sm:px-2.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-semibold transition"
                        title="Lihat panduan pemasangan WebAPK / PWA di perangkat kasir">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 shrink-0 text-[#0D7A5F]"></i>
                        <span class="hidden sm:inline">Cara WebAPK</span>
                        <span class="sm:hidden">WebAPK</span>
                    </button>
                    <button type="button" @click="installKasirApk('apk')"
                        class="inline-flex items-center gap-1.5 h-8 px-2 sm:px-2.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-semibold transition"
                        title="Unduh file installer Android (.apk)">
                        <i data-lucide="download" class="w-3.5 h-3.5 shrink-0"></i>
                        <span class="hidden sm:inline">Download .APK</span>
                        <span class="sm:hidden">.APK</span>
                    </button>
                </div>
                <button type="button" x-show="user.role === 'owner' && business.subscription_summary" @click="setTab('billing')"
                    class="hidden md:inline-flex items-center gap-1.5 h-8 px-3 rounded-md text-xs font-semibold border transition"
                    :class="business.subscription_summary && business.subscription_summary.lifecycle_status === 'expired'
                        ? 'bg-rose-50 border-rose-300 text-rose-700'
                        : (business.subscription_summary && business.subscription_summary.lifecycle_status === 'none'
                            ? 'bg-slate-100 border-slate-300 text-slate-700 hover:border-slate-400'
                            : (business.subscription_summary && business.subscription_summary.is_expiring_soon
                                ? 'bg-amber-50 border-amber-300 text-amber-800'
                                : 'bg-[#E6F4EF] border-[#0D7A5F]/25 text-[#0D7A5F] hover:border-[#0D7A5F]'))"
                    title="Kelola paket &amp; masa aktif langganan">
                    <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>
                    <span x-text="business.subscription_summary ? (business.subscription_summary.plan_name + ' • ' + business.subscription_summary.countdown_label) : ''"></span>
                </button>
                <button @click="refreshData()" :disabled="isRefreshing" class="inline-flex items-center gap-1.5 h-8 px-2.5 sm:px-3 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition disabled:opacity-60" title="Muat Ulang Data">
                    <svg class="w-3.5 h-3.5 text-slate-500 transition-transform shrink-0" :class="isRefreshing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    <span class="hidden sm:inline" x-text="isRefreshing ? 'Memuat...' : 'Muat Ulang'"></span>
                </button>
            </div>
        </header>

        <div class="flex-1 min-h-0 flex flex-col p-3.5 sm:p-6 gap-3 sm:gap-4 overflow-hidden">

            <!-- Store Owner / Admin Area (Fixed viewport shell so tables scroll inside cards only) -->
            <div x-show="tab !== 'admin_platform'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-hidden" x-cloak>

            <!-- Client Subscription Expiry / Activation Notice (Refined Architectural Alert Card) -->
            <template x-if="user.role === 'owner' && business.subscription_summary && ['expiring_soon', 'expired', 'none'].includes(business.subscription_summary.lifecycle_status) && tab !== 'billing'">
                <div class="shrink-0 relative overflow-hidden bg-white rounded-xl border shadow-xs pl-5 pr-4 py-3.5 flex flex-wrap items-center justify-between gap-4 text-xs"
                    :class="business.subscription_summary.lifecycle_status === 'expiring_soon' ? 'border-amber-200/90' : (business.subscription_summary.lifecycle_status === 'none' ? 'border-[#0D7A5F]/25' : 'border-rose-200/90')">
                    <div class="absolute inset-y-0 left-0 w-1"
                        :class="business.subscription_summary.lifecycle_status === 'expiring_soon' ? 'bg-amber-500' : (business.subscription_summary.lifecycle_status === 'none' ? 'bg-[#0D7A5F]' : 'bg-rose-500')"></div>
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg border flex items-center justify-center shrink-0"
                            :class="business.subscription_summary.lifecycle_status === 'expiring_soon' ? 'bg-amber-50 border-amber-200 text-amber-700' : (business.subscription_summary.lifecycle_status === 'none' ? 'bg-[#E6F4EF] border-[#0D7A5F]/20 text-[#0D7A5F]' : 'bg-rose-50 border-rose-200 text-rose-700')">
                            <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0 space-y-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-[#0D2620]"
                                    x-text="business.subscription_summary.lifecycle_status === 'none'
                                        ? 'Paket Langganan Belum Aktif'
                                        : (business.subscription_summary.lifecycle_status === 'expired'
                                            ? 'Masa Aktif Langganan Telah Berakhir'
                                            : ('Paket ' + business.subscription_summary.plan_name + ' Segera Berakhir'))"></span>
                                <span x-show="business.subscription_summary.lifecycle_status !== 'none'"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold font-tabular"
                                    :class="business.subscription_summary.lifecycle_status === 'expiring_soon' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    x-text="business.subscription_summary.countdown_label"></span>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed"
                                x-text="business.subscription_summary.lifecycle_status === 'none'
                                    ? 'Aktifkan paket Tandha POS untuk membuka modul kasir cabang, stok, laporan laba, staf, dan pemasangan aplikasi kasir.'
                                    : (business.subscription_summary.lifecycle_status === 'expired'
                                        ? 'Perpanjang paket langganan Anda untuk membuka kembali seluruh modul operasional cabang.'
                                        : ('Berlaku s.d. ' + business.subscription_summary.expires_at_formatted + '. Perpanjang lebih awal agar operasional kasir tetap berjalan lancar.'))"></p>
                        </div>
                    </div>
                    <button type="button" @click="setTab('billing')"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition shrink-0 shadow-xs"
                        :class="business.subscription_summary.lifecycle_status === 'expiring_soon' ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-[#0D7A5F] hover:bg-[#095C47] text-white'"
                        x-text="business.subscription_summary.lifecycle_status === 'none' ? 'Pilih Paket Langganan →' : 'Perpanjang Sekarang →'"></button>
                </div>
            </template>

            <!-- Workspace Locked Notice for Admin whose Business is Unsubscribed/Expired -->
            <div x-show="tab === 'locked'" class="flex-1 min-h-0 flex items-center justify-center p-6" x-cloak>
                <div class="max-w-md w-full bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden relative p-6 pl-7 space-y-3">
                    <div class="absolute inset-y-0 left-0 w-1 bg-amber-500"></div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-sm font-bold text-[#0D2620]">Akses Modul Operasional Terkunci</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Paket langganan bisnis ini belum aktif atau telah berakhir. Hubungi Pemilik bisnis untuk mengaktifkan kembali langganan pada menu <b>Langganan &amp; Tagihan</b>.
                    </p>
                </div>
            </div>

            <!-- TAB 1: OVERVIEW -->
            <div x-show="tab === 'overview'" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden custom-scrollbar space-y-5 sm:space-y-6 sm:pr-1" x-cloak>
                <!-- Structured Metric Strip (Flat, Hairline Bordered) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded-md divide-y sm:divide-y-0 sm:divide-x divide-[rgba(15,23,42,0.12)]">
                    <div class="p-4 sm:p-5 min-w-0">
                        <span class="text-xs font-semibold text-[#0D7A5F]">Penjualan Hari Ini</span>
                        <div class="text-xl sm:text-2xl font-bold text-[#0D2620] mt-1 font-tabular truncate" x-text="formatRupiah(dashboardData.today_sales)"></div>
                    </div>
                    <div class="p-4 sm:p-5 min-w-0">
                        <span class="text-xs font-semibold text-[#0D7A5F]">Total Transaksi</span>
                        <div class="text-xl sm:text-2xl font-bold text-[#0D2620] mt-1 font-tabular" x-text="dashboardData.today_transactions || 0"></div>
                    </div>
                    <div class="p-4 sm:p-5 min-w-0">
                        <span class="text-xs font-semibold text-[#0D7A5F]">Rata-Rata Keranjang</span>
                        <div class="text-xl sm:text-2xl font-bold text-[#0D2620] mt-1 font-tabular truncate" x-text="formatRupiah(dashboardData.today_avg_basket)"></div>
                    </div>
                    <div class="p-4 sm:p-5 min-w-0">
                        <span class="text-xs font-semibold text-[#0D7A5F]">Peringatan Stok Menipis</span>
                        <div class="text-xl sm:text-2xl font-bold text-rose-700 mt-1 font-tabular" x-text="dashboardData.low_stock_count || 0"></div>
                    </div>
                </div>

                <!-- Charts & Top Products -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                    <div class="lg:col-span-2 bg-[#FFFFFF] p-4 sm:p-6 rounded-md border border-[rgba(15,23,42,0.12)] min-w-0">
                        <h3 class="text-sm font-bold text-[#0D2620] mb-4">Tren Penjualan 7 Hari Terakhir</h3>
                        <div class="h-64 w-full overflow-hidden">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-[#FFFFFF] p-4 sm:p-6 rounded-md border border-[rgba(15,23,42,0.12)] flex flex-col justify-between min-w-0">
                        <div>
                            <h3 class="text-sm font-bold text-[#0D2620] mb-4">Produk Terlaris</h3>
                            <div class="divide-y divide-[rgba(15,23,42,0.12)]">
                                <template x-for="(prod, idx) in dashboardData.top_products" :key="idx">
                                    <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-[#0D2620] truncate" x-text="(idx + 1) + '. ' + prod.product_name"></div>
                                            <div class="text-[11px] text-[#475569]" x-text="prod.total_qty + ' terjual'"></div>
                                        </div>
                                        <span class="font-bold text-[#0D7A5F] font-tabular shrink-0" x-text="formatRupiah(prod.total_revenue)"></span>
                                    </div>
                                </template>
                                <div x-show="!dashboardData.top_products || dashboardData.top_products.length === 0" class="text-xs text-[#475569] py-6 text-center">
                                    Belum ada penjualan tercatat
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[rgba(15,23,42,0.12)] mt-4">
                            <span class="text-[11px] font-bold uppercase text-[#0D7A5F] block mb-2">Metode Pembayaran</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="pay in dashboardData.payment_breakdown" :key="pay.payment_method">
                                    <span class="px-2.5 py-1 bg-[#F7FAF9] border border-[rgba(15,23,42,0.12)] text-[#0D2620] rounded text-xs font-semibold">
                                        <span class="uppercase" x-text="pay.payment_method"></span>: <b x-text="pay.count"></b>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PRODUCTS & CATEGORIES (FILTERS + CATEGORY VISUALIZATION TABLE + CANONICAL PAGINATION) -->
            <div x-show="tab === 'products'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden" x-cloak>
                <!-- Sub-Tab Switcher & Filter Toolbar -->
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 flex flex-col xl:flex-row xl:items-center justify-between gap-3 shrink-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-md text-xs font-semibold">
                            <button type="button" x-show="user.role === 'owner' || hasFeature('admin_manage_products')"
                                @click="productSubTab = 'products'"
                                class="px-3 py-1.5 rounded transition"
                                :class="productSubTab === 'products' ? 'bg-white text-[#0D2620] shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                                Daftar Produk (<span x-text="productList.length"></span>)
                            </button>
                            <button type="button" x-show="user.role === 'owner' || hasFeature('admin_manage_categories')"
                                @click="productSubTab = 'categories'; loadCategories()"
                                class="px-3 py-1.5 rounded transition"
                                :class="productSubTab === 'categories' ? 'bg-white text-[#0D2620] shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                                Kategori &amp; Produknya (<span x-text="categories.length"></span>)
                            </button>
                        </div>
                    </div>

                    <!-- Filters for Daftar Produk -->
                    <div x-show="productSubTab === 'products'" class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <!-- Custom Filter Kategori -->
                        <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'prodCatFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('prodCatFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[165px] justify-between transition">
                                <span class="truncate" x-text="activeProdCatFilterLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'prodCatFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'prodCatFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-52 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                                <button type="button" @click="prodCatFilter = ''; prodPage = 1; openDropdown = null"
                                    class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="!prodCatFilter ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span>Semua Kategori</span><span x-show="!prodCatFilter">✓</span>
                                </button>
                                <button type="button" @click="prodCatFilter = 'none'; prodPage = 1; openDropdown = null"
                                    class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="prodCatFilter === 'none' ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span>Belum Berkategori</span><span x-show="prodCatFilter === 'none'">✓</span>
                                </button>
                                <template x-for="cat in categories" :key="'pcf-' + cat.id">
                                    <button type="button" @click="prodCatFilter = String(cat.id); prodPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="String(prodCatFilter) === String(cat.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span class="truncate" x-text="cat.name + ' (' + (cat.products_count || 0) + ')'"></span>
                                        <span x-show="String(prodCatFilter) === String(cat.id)">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Custom Filter Status Stok -->
                        <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'prodStockFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('prodStockFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[140px] justify-between transition">
                                <span class="truncate" x-text="activeProdStockFilterLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'prodStockFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'prodStockFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                <template x-for="opt in prodStockFilterOptions" :key="opt.value">
                                    <button type="button" @click="prodStockFilter = opt.value; prodPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="prodStockFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span x-text="opt.label"></span><span x-show="prodStockFilter === opt.value">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative w-full sm:w-auto">
                            <input type="text" x-model="productSearch" @input.debounce.250ms="prodPage = 1" placeholder="Cari produk, SKU, barcode..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-52 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                            <button x-show="productSearch" type="button" @click="productSearch = ''; prodPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                        </div>

                        <span x-show="business.plan_limits" class="hidden xl:inline-flex items-center px-2.5 py-1 rounded-md bg-slate-50 border border-slate-200 text-[11px] font-medium text-slate-600"
                            x-text="productList.length + ' / ' + (business.plan_limits ? business.plan_limits.max_products : '-') + ' SKU'"></span>

                        <button x-show="user.role === 'owner'" type="button" @click="openPosPreviewModal(selectedOutletId || null)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition" title="Lihat produk &amp; stok terbaru di Preview Kasir">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i> Preview Kasir
                        </button>
                        <button x-show="user.role === 'owner' || hasFeature('admin_manage_categories')" @click="openCreateCategoryModal()" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition">
                            + Kategori
                        </button>
                        <button @click="openCreateProductModal()" class="px-3.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition">
                            + Tambah Produk
                        </button>
                    </div>

                    <!-- Filters for Kategori & Produknya -->
                    <div x-show="productSubTab === 'categories'" class="flex flex-wrap items-center gap-2 w-full xl:w-auto" x-cloak>
                        <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'catStatusFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('catStatusFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[160px] justify-between transition">
                                <span class="truncate" x-text="activeCatStatusFilterLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'catStatusFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'catStatusFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                <template x-for="opt in catStatusFilterOptions" :key="opt.value">
                                    <button type="button" @click="catStatusFilter = opt.value; catPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="catStatusFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span x-text="opt.label"></span><span x-show="catStatusFilter === opt.value">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative w-full sm:w-auto">
                            <input type="text" x-model="catSearch" @input="catPage = 1" placeholder="Cari kategori atau nama produk..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-56 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                            <button x-show="catSearch" type="button" @click="catSearch = ''; catPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                        </div>

                        <button x-show="user.role === 'owner'" type="button" @click="openPosPreviewModal(selectedOutletId || null)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition" title="Lihat filter kategori di Preview Kasir">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i> Preview Kasir
                        </button>
                        <button @click="openCreateCategoryModal()" class="px-3.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition">
                            + Tambah Kategori Baru
                        </button>
                    </div>
                </div>

                <!-- Sub-Tab 1 Table: Daftar Produk -->
                <div x-show="productSubTab === 'products'" class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                    <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                        <table class="w-full min-w-[680px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-4">Produk &amp; Visual</th>
                                    <th class="py-2.5 px-4">SKU / Barcode</th>
                                    <th class="py-2.5 px-4">Kategori</th>
                                    <th x-show="user.role === 'owner'" class="py-2.5 px-4 text-right">Harga Beli</th>
                                    <th class="py-2.5 px-4 text-right">Harga Jual</th>
                                    <th class="py-2.5 px-4 text-center">Stok</th>
                                    <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-[#0D2620]">
                                <template x-for="p in paginatedProducts" :key="p.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-3">
                                                <img :src="p.image_url || '/assets/products/default-product.svg'" :alt="p.name" class="w-10 h-10 rounded-md object-cover border border-slate-200 bg-slate-100 shrink-0">
                                                <span class="font-semibold text-slate-900" x-text="p.name"></span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-tabular text-[11px] text-slate-500" x-text="(p.sku || '-') + ' / ' + (p.barcode || '-')"></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded font-semibold text-[10px]"
                                                :class="p.category ? 'bg-[#E6F4EF] text-[#0D7A5F]' : 'bg-slate-100 text-slate-500'"
                                                x-text="p.category ? p.category.name : 'Belum dikategorikan'"></span>
                                        </td>
                                        <td x-show="user.role === 'owner'" class="py-3 px-4 text-right font-tabular text-slate-600" x-text="formatRupiah(p.buy_price)"></td>
                                        <td class="py-3 px-4 text-right font-bold font-tabular text-[#0D7A5F]" x-text="formatRupiah(p.sell_price)"></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="font-bold font-tabular" :class="p.current_stock <= 0 ? 'text-rose-600' : (p.is_low_stock ? 'text-amber-600' : 'text-slate-900')" x-text="p.current_stock"></span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <button type="button" @click="openActionPopup('product', p, $event)"
                                                class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                <span>Aksi</span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredProductsList.length === 0">
                                    <td colspan="7" class="py-10 text-center text-xs text-slate-400">Tidak ada produk yang cocok dengan filter atau pencarian.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Canonical Pagination Bar -->
                    <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="prodPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'prodPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('prodPageSize')"
                                    class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="prodPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'prodPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'pp-' + sz">
                                        <button type="button" @click="prodPerPage = sz; prodPage = 1; openDropdown = null"
                                            class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50"
                                            :class="prodPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="prodPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (prodPage > 1) prodPage--" :disabled="prodPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(prodPage, prodTotalPages)" :key="'ppg-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') prodPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="prodPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (prodPage < prodTotalPages) prodPage++" :disabled="prodPage >= prodTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                </div>

                <!-- Sub-Tab 2 Table: 1 Tabel Kategori & Produknya -->
                <div x-show="productSubTab === 'categories'" class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden" x-cloak>
                    <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                        <table class="w-full min-w-[600px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-4 w-56">Kategori</th>
                                    <th class="py-2.5 px-4 text-center w-28">Total Item</th>
                                    <th class="py-2.5 px-4">Daftar Produk di Kategori Ini</th>
                                    <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="cat in paginatedCategories" :key="cat.id">
                                    <tr class="hover:bg-slate-50/70 align-top">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-slate-900" x-text="cat.name"></div>
                                            <div x-show="cat.description" class="text-[11px] text-slate-400 mt-0.5" x-text="cat.description"></div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold font-tabular bg-slate-100 text-slate-700" x-text="(cat.products_count || 0) + ' item'"></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div x-show="cat.products && cat.products.length > 0" class="flex flex-wrap gap-1.5">
                                                <template x-for="cp in (cat.products || [])" :key="'cp-' + cat.id + '-' + cp.id">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-50 border border-slate-200 text-[11px] text-slate-700">
                                                        <img :src="cp.image_url || '/assets/products/default-product.svg'" :alt="cp.name" class="w-4 h-4 rounded object-cover shrink-0">
                                                        <span class="font-medium" x-text="cp.name"></span>
                                                        <span class="font-tabular text-[10px] text-[#0D7A5F]" x-text="formatRupiah(cp.sell_price)"></span>
                                                    </span>
                                                </template>
                                            </div>
                                            <span x-show="!cat.products || cat.products.length === 0" class="text-[11px] text-slate-400 italic">Belum ada produk pada kategori ini.</span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <button type="button" @click="openActionPopup('category', cat, $event)"
                                                class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                <span>Aksi</span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredCategoriesList.length === 0">
                                    <td colspan="4" class="py-12 px-6 text-center">
                                        <div class="text-xs font-semibold text-slate-700">Belum ada kategori tersimpan</div>
                                        <p class="text-[11px] text-slate-400 mt-1 max-w-md mx-auto">Kategori awal sengaja dikosongkan agar setiap bisnis bebas menyusun kategori menunya sendiri.</p>
                                        <button type="button" @click="openCreateCategoryModal()" class="mt-3 px-4 py-1.5 bg-[#0D7A5F] text-white rounded-md text-xs font-bold hover:bg-[#095C47] transition">+ Buat Kategori Pertama</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Canonical Pagination Bar -->
                    <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="catPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'catPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('catPageSize')"
                                    class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="catPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'catPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'cp-' + sz">
                                        <button type="button" @click="catPerPage = sz; catPage = 1; openDropdown = null"
                                            class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50"
                                            :class="catPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="catPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (catPage > 1) catPage--" :disabled="catPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(catPage, catTotalPages)" :key="'cpg-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') catPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="catPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (catPage < catTotalPages) catPage++" :disabled="catPage >= catTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: INVENTORY (FILTERS + TABLE-ONLY SCROLL + CANONICAL PAGINATION) -->
            <div x-show="tab === 'inventory'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden" x-cloak>
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Audit Mutasi Stok (Stock Movements)</h3>
                        <p class="text-[11px] text-slate-500" x-text="invPaginationRangeLabel"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                        <!-- Custom Filter Jenis Mutasi -->
                        <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'invTypeFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('invTypeFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[165px] justify-between transition">
                                <span class="truncate" x-text="activeInvTypeLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'invTypeFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'invTypeFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-48 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                <template x-for="opt in invTypeFilterOptions" :key="opt.value">
                                    <button type="button" @click="invTypeFilter = opt.value; invPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="invTypeFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span x-text="opt.label"></span><span x-show="invTypeFilter === opt.value">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Custom Filter Produk -->
                        <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'invProdFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('invProdFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[165px] justify-between transition">
                                <span class="truncate" x-text="activeInvProdLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'invProdFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'invProdFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                                <button type="button" @click="invProdFilter = ''; invPage = 1; openDropdown = null"
                                    class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="!invProdFilter ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                    <span>Semua Produk</span><span x-show="!invProdFilter">✓</span>
                                </button>
                                <template x-for="p in productList" :key="'ipf-' + p.id">
                                    <button type="button" @click="invProdFilter = String(p.id); invPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="String(invProdFilter) === String(p.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span class="truncate" x-text="p.name"></span><span x-show="String(invProdFilter) === String(p.id)">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="relative w-full sm:w-auto">
                            <input type="text" x-model="invSearch" @input="invPage = 1" placeholder="Cari produk, alasan, kasir..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-52 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                            <button x-show="invSearch" type="button" @click="invSearch = ''; invPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                        </div>

                        <button @click="showAdjustmentModal = true" class="w-full sm:w-auto px-3.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-[#F7FAF9] rounded-md text-xs font-bold transition">
                            Penyesuaian Stok
                        </button>
                    </div>
                </div>

                <div class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                    <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                        <table class="w-full min-w-[680px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-4">Waktu</th>
                                    <th class="py-2.5 px-4">Produk</th>
                                    <th class="py-2.5 px-4">Jenis</th>
                                    <th class="py-2.5 px-4 text-center">Perubahan</th>
                                    <th class="py-2.5 px-4 text-center">Stok Akhir</th>
                                    <th class="py-2.5 px-4">Alasan</th>
                                    <th class="py-2.5 px-4">Pengguna</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-[#0D2620]">
                                <template x-for="m in paginatedMovements" :key="m.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="py-3 px-4 text-slate-500 text-[11px] whitespace-nowrap" x-text="formatDate(m.created_at)"></td>
                                        <td class="py-3 px-4 font-semibold text-slate-900" x-text="m.product ? m.product.name : '-'"></td>
                                        <td class="py-3 px-4 font-tabular text-[11px] uppercase text-[#0D7A5F]" x-text="m.type"></td>
                                        <td class="py-3 px-4 text-center font-bold font-tabular" :class="m.quantity_change > 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="(m.quantity_change > 0 ? '+' : '') + m.quantity_change"></td>
                                        <td class="py-3 px-4 text-center font-tabular font-bold" x-text="m.quantity_after"></td>
                                        <td class="py-3 px-4 text-slate-600 text-[11px]" x-text="m.reason"></td>
                                        <td class="py-3 px-4 text-slate-600" x-text="m.user ? m.user.name : '-'"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredMovements.length === 0">
                                    <td colspan="7" class="py-10 text-center text-xs text-slate-400">Tidak ada mutasi stok yang cocok dengan filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Canonical Pagination Bar -->
                    <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="invPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'invPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('invPageSize')"
                                    class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="invPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'invPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'ip-' + sz">
                                        <button type="button" @click="invPerPage = sz; invPage = 1; openDropdown = null"
                                            class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50"
                                            :class="invPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="invPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (invPage > 1) invPage--" :disabled="invPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(invPage, invTotalPages)" :key="'ipg-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') invPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="invPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (invPage < invTotalPages) invPage++" :disabled="invPage >= invTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: REPORTS (TABLE-ONLY SCROLL + PAGINATION + OWNER SHIFT AUDIT) -->
            <div x-show="tab === 'reports'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden" x-cloak>
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 flex flex-col xl:flex-row xl:items-end justify-between gap-3 shrink-0">
                    <div class="flex flex-wrap items-end gap-3">
                        <template x-if="reportSubTab === 'summary'">
                            <div class="flex flex-wrap items-end gap-2 sm:gap-3 w-full sm:w-auto">
                                <div class="flex-1 sm:flex-none min-w-[130px]">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dari Tanggal</label>
                                    <input type="date" x-model="reportStartDate" class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-xs font-semibold">
                                </div>
                                <div class="flex-1 sm:flex-none min-w-[130px]">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Sampai Tanggal</label>
                                    <input type="date" x-model="reportEndDate" class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-xs font-semibold">
                                </div>
                                <button @click="repPage = 1; loadReports()" class="w-full sm:w-auto px-4 py-2 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded text-xs font-bold">
                                    Tampilkan
                                </button>
                            </div>
                        </template>
                        <div x-show="reportSubTab === 'shifts'" class="text-xs text-slate-600" x-cloak>
                            Riwayat buka/tutup laci kasir terpisah dari hitungan omset (termasuk Kas Awal &amp; Selisih Kas).
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <!-- Custom Payment Filter for Admin Sales -->
                        <div x-show="user.role === 'admin' && reportSubTab === 'summary'" class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'repPayFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('repPayFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[140px] justify-between transition">
                                <span class="truncate" x-text="activeRepPayLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'repPayFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'repPayFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                <template x-for="opt in repPayFilterOptions" :key="opt.value">
                                    <button type="button" @click="repPayFilter = opt.value; repPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="repPayFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span x-text="opt.label"></span><span x-show="repPayFilter === opt.value">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Custom Status Filter for Shift History -->
                        <div x-show="reportSubTab === 'shifts'" class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'shiftStatusFilter') openDropdown = null">
                            <button type="button" @click="toggleDropdown('shiftStatusFilter')"
                                class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[140px] justify-between transition">
                                <span class="truncate" x-text="activeShiftStatusLabel"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'shiftStatusFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openDropdown === 'shiftStatusFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                <template x-for="opt in shiftStatusFilterOptions" :key="opt.value">
                                    <button type="button" @click="shiftStatusFilter = opt.value; shiftPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="shiftStatusFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span x-text="opt.label"></span><span x-show="shiftStatusFilter === opt.value">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="reportSubTab === 'summary'" class="relative w-full sm:w-auto">
                            <input type="text" x-model="repSearch" @input="repPage = 1" :placeholder="user.role === 'owner' ? 'Cari nama produk...' : 'Cari invoice atau kasir...'" class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-48 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                            <button x-show="repSearch" type="button" @click="repSearch = ''; repPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                        </div>

                        <div x-show="reportSubTab === 'shifts'" class="relative w-full sm:w-auto" x-cloak>
                            <input type="text" x-model="shiftSearch" @input="shiftPage = 1" placeholder="Cari kasir atau catatan..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-48 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                            <button x-show="shiftSearch" type="button" @click="shiftSearch = ''; shiftPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                        </div>

                        <div x-show="user.role === 'owner' || hasFeature('admin_view_shift_history')" class="flex items-center gap-1 p-1 bg-slate-100 rounded-md text-xs font-semibold w-full sm:w-auto">
                            <button type="button" x-show="user.role === 'owner' || hasFeature('admin_view_sales_report')" @click="reportSubTab = 'summary'; loadReports()"
                                class="flex-1 sm:flex-none px-3 py-1.5 rounded transition"
                                :class="reportSubTab === 'summary' ? 'bg-white text-[#0D2620] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                x-text="user.role === 'owner' ? 'Penjualan & Laba' : 'Laporan Penjualan'">
                            </button>
                            <button type="button" @click="reportSubTab = 'shifts'; loadShiftHistory()"
                                class="flex-1 sm:flex-none px-3 py-1.5 rounded transition"
                                :class="reportSubTab === 'shifts' ? 'bg-white text-[#0D2620] shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                                Riwayat Shift Kasir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Owner Profit + HPP View -->
                <template x-if="user.role === 'owner' && reportSubTab === 'summary'">
                    <div class="flex-1 min-h-0 flex flex-col gap-4 lg:overflow-hidden">
                        <div class="shrink-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 bg-white border border-slate-200 rounded-lg divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Total Omset</span>
                                <div class="text-lg font-bold text-slate-900 mt-0.5 font-tabular" x-text="formatRupiah(profitData.summary ? profitData.summary.total_revenue : 0)"></div>
                            </div>
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Total HPP (Modal)</span>
                                <div class="text-lg font-bold text-slate-600 mt-0.5 font-tabular" x-text="formatRupiah(profitData.summary ? profitData.summary.total_cogs : 0)"></div>
                            </div>
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Estimasi Laba Kotor</span>
                                <div class="text-lg font-bold text-[#0D7A5F] mt-0.5 font-tabular" x-text="formatRupiah(profitData.summary ? profitData.summary.total_gross_profit : 0)"></div>
                            </div>
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Margin Laba</span>
                                <div class="text-lg font-bold text-slate-900 mt-0.5 font-tabular" x-text="(profitData.summary ? profitData.summary.profit_margin_percentage : 0) + '%'"></div>
                            </div>
                        </div>

                        <div class="flex-1 min-h-[300px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                                <table class="w-full min-w-[640px] text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="py-2.5 px-4">Produk</th>
                                            <th class="py-2.5 px-4 text-center">Terjual</th>
                                            <th class="py-2.5 px-4 text-right">HPP</th>
                                            <th class="py-2.5 px-4 text-right">Harga Jual</th>
                                            <th class="py-2.5 px-4 text-right">Omset</th>
                                            <th class="py-2.5 px-4 text-right">Laba Kotor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-[#0D2620]">
                                        <template x-for="item in paginatedReportRows" :key="item.product_name">
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="py-3 px-4 font-semibold text-slate-900" x-text="item.product_name"></td>
                                                <td class="py-3 px-4 text-center font-bold" x-text="item.qty_sold"></td>
                                                <td class="py-3 px-4 text-right font-tabular text-slate-600" x-text="formatRupiah(item.avg_buy_price)"></td>
                                                <td class="py-3 px-4 text-right font-tabular text-slate-600" x-text="formatRupiah(item.avg_sell_price)"></td>
                                                <td class="py-3 px-4 text-right font-tabular" x-text="formatRupiah(item.total_revenue)"></td>
                                                <td class="py-3 px-4 text-right font-bold font-tabular text-[#0D7A5F]" x-text="formatRupiah(item.gross_profit)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="filteredReportRows.length === 0">
                                            <td colspan="6" class="py-10 text-center text-xs text-slate-400">Belum ada penjualan yang cocok pada rentang tanggal ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Canonical Pagination Bar -->
                            <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 text-slate-500">
                                    <span x-text="repPaginationRangeLabel"></span>
                                    <div class="relative" @click.outside="if (openDropdown === 'repPageSize') openDropdown = null">
                                        <button type="button" @click="toggleDropdown('repPageSize')" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                            <span x-text="repPerPage + ' / hal'"></span>
                                            <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div x-show="openDropdown === 'repPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                            <template x-for="sz in [5, 10, 20, 50]" :key="'rp1-' + sz">
                                                <button type="button" @click="repPerPage = sz; repPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="repPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                    <span x-text="sz + ' baris'"></span><span x-show="repPerPage === sz">✓</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="if (repPage > 1) repPage--" :disabled="repPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                                    <template x-for="(pg, idx) in getPageNumbers(repPage, repTotalPages)" :key="'rp1g-' + idx + '-' + pg">
                                        <button type="button" @click="if (typeof pg === 'number') repPage = pg" :disabled="typeof pg !== 'number'"
                                            class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                            :class="repPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                            x-text="pg"></button>
                                    </template>
                                    <button type="button" @click="if (repPage < repTotalPages) repPage++" :disabled="repPage >= repTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Cashier Shift Audit View (Owner & Admin if allowed — Separate from Omset) -->
                <template x-if="reportSubTab === 'shifts' && (user.role === 'owner' || hasFeature('admin_view_shift_history'))">
                    <div class="flex-1 min-h-[300px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                        <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                            <table class="w-full min-w-[740px] text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-4">Kasir &amp; Waktu Shift</th>
                                        <th class="py-2.5 px-4">Outlet</th>
                                        <th class="py-2.5 px-4 text-right">Kas Awal</th>
                                        <th class="py-2.5 px-4 text-right">Penjualan Tunai</th>
                                        <th class="py-2.5 px-4 text-right">Non-Tunai</th>
                                        <th class="py-2.5 px-4 text-right">Kas Aktual</th>
                                        <th class="py-2.5 px-4 text-right">Selisih</th>
                                        <th class="py-2.5 px-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-[#0D2620]">
                                    <template x-for="sh in paginatedShiftHistory" :key="sh.id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-900" x-text="sh.cashier ? sh.cashier.name : '-'"></div>
                                                <div class="text-[11px] text-slate-400" x-text="formatDate(sh.opened_at) + (sh.closed_at ? ' – ' + formatDate(sh.closed_at) : ' (Masih Buka)')"></div>
                                                <div x-show="sh.notes" class="text-[10px] text-slate-500 mt-0.5" x-text="sh.notes"></div>
                                            </td>
                                            <td class="py-3 px-4 text-slate-600" x-text="sh.outlet ? sh.outlet.name : '-'"></td>
                                            <td class="py-3 px-4 text-right font-tabular text-slate-600" x-text="formatRupiah(sh.initial_cash)"></td>
                                            <td class="py-3 px-4 text-right font-tabular font-semibold text-slate-900" x-text="formatRupiah(sh.cash_sales_total)"></td>
                                            <td class="py-3 px-4 text-right font-tabular text-slate-600" x-text="formatRupiah(sh.non_cash_sales_total)"></td>
                                            <td class="py-3 px-4 text-right font-tabular" x-text="sh.status === 'closed' ? formatRupiah(sh.actual_cash) : '-'"></td>
                                            <td class="py-3 px-4 text-right font-tabular font-bold" :class="Number(sh.cash_variance || 0) === 0 ? 'text-emerald-700' : 'text-rose-600'"
                                                x-text="sh.status === 'closed' ? formatRupiah(sh.cash_variance) : '-'"></td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                                                    :class="sh.status === 'open' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                                    x-text="sh.status === 'open' ? 'Aktif' : 'Ditutup'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredShiftHistory.length === 0">
                                        <td colspan="8" class="py-10 text-center text-xs text-slate-400">Belum ada riwayat shift kasir yang cocok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Canonical Pagination Bar -->
                        <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 text-slate-500">
                                <span x-text="shiftPaginationRangeLabel"></span>
                                <div class="relative" @click.outside="if (openDropdown === 'shiftPageSize') openDropdown = null">
                                    <button type="button" @click="toggleDropdown('shiftPageSize')" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                        <span x-text="shiftPerPage + ' / hal'"></span>
                                        <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openDropdown === 'shiftPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                        <template x-for="sz in [5, 10, 20, 50]" :key="'shp-' + sz">
                                            <button type="button" @click="shiftPerPage = sz; shiftPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="shiftPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                <span x-text="sz + ' baris'"></span><span x-show="shiftPerPage === sz">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="if (shiftPage > 1) shiftPage--" :disabled="shiftPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                                <template x-for="(pg, idx) in getPageNumbers(shiftPage, shiftTotalPages)" :key="'shpg-' + idx + '-' + pg">
                                    <button type="button" @click="if (typeof pg === 'number') shiftPage = pg" :disabled="typeof pg !== 'number'"
                                        class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                        :class="shiftPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                        x-text="pg"></button>
                                </template>
                                <button type="button" @click="if (shiftPage < shiftTotalPages) shiftPage++" :disabled="shiftPage >= shiftTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Admin Operational Sales View (No HPP/Margin exposure) -->
                <template x-if="user.role === 'admin' && reportSubTab === 'summary' && hasFeature('admin_view_sales_report')">
                    <div class="flex-1 min-h-0 flex flex-col gap-4 lg:overflow-hidden">
                        <div class="shrink-0 grid grid-cols-1 sm:grid-cols-3 bg-white border border-slate-200 rounded-lg divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Total Omset Penjualan</span>
                                <div class="text-lg font-bold text-slate-900 mt-0.5 font-tabular" x-text="formatRupiah(salesReportData.summary ? salesReportData.summary.total_sales : 0)"></div>
                            </div>
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Jumlah Transaksi</span>
                                <div class="text-lg font-bold text-slate-900 mt-0.5 font-tabular" x-text="salesReportData.summary ? salesReportData.summary.total_transactions : 0"></div>
                            </div>
                            <div class="p-4">
                                <span class="text-[11px] font-semibold text-slate-500">Total Pajak Tercatat</span>
                                <div class="text-lg font-bold text-slate-600 mt-0.5 font-tabular" x-text="formatRupiah(salesReportData.summary ? salesReportData.summary.total_tax : 0)"></div>
                            </div>
                        </div>

                        <div class="flex-1 min-h-[300px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                                <table class="w-full min-w-[600px] text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="py-2.5 px-4">Invoice</th>
                                            <th class="py-2.5 px-4">Waktu</th>
                                            <th class="py-2.5 px-4">Kasir &amp; Outlet</th>
                                            <th class="py-2.5 px-4">Metode</th>
                                            <th class="py-2.5 px-4 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-[#0D2620]">
                                        <template x-for="tx in paginatedReportRows" :key="tx.id">
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="py-3 px-4 font-tabular font-semibold text-slate-900" x-text="tx.invoice_number"></td>
                                                <td class="py-3 px-4 text-slate-500" x-text="formatDate(tx.created_at)"></td>
                                                <td class="py-3 px-4" x-text="(tx.cashier ? tx.cashier.name : '-') + (tx.outlet ? ' · ' + tx.outlet.name : '')"></td>
                                                <td class="py-3 px-4 uppercase font-semibold text-[#0D7A5F]" x-text="tx.payment ? tx.payment.payment_method : '-'"></td>
                                                <td class="py-3 px-4 text-right font-tabular font-bold" x-text="formatRupiah(tx.total_amount)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="filteredReportRows.length === 0">
                                            <td colspan="5" class="py-10 text-center text-xs text-slate-400">Belum ada transaksi yang cocok pada rentang tanggal ini.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Canonical Pagination Bar -->
                            <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 text-slate-500">
                                    <span x-text="repPaginationRangeLabel"></span>
                                    <div class="relative" @click.outside="if (openDropdown === 'repPageSizeAdm') openDropdown = null">
                                        <button type="button" @click="toggleDropdown('repPageSizeAdm')" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                            <span x-text="repPerPage + ' / hal'"></span>
                                            <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div x-show="openDropdown === 'repPageSizeAdm'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                            <template x-for="sz in [5, 10, 20, 50]" :key="'rp2-' + sz">
                                                <button type="button" @click="repPerPage = sz; repPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="repPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                    <span x-text="sz + ' baris'"></span><span x-show="repPerPage === sz">✓</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="if (repPage > 1) repPage--" :disabled="repPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                                    <template x-for="(pg, idx) in getPageNumbers(repPage, repTotalPages)" :key="'rp2g-' + idx + '-' + pg">
                                        <button type="button" @click="if (typeof pg === 'number') repPage = pg" :disabled="typeof pg !== 'number'"
                                            class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                            :class="repPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                            x-text="pg"></button>
                                    </template>
                                    <button type="button" @click="if (repPage < repTotalPages) repPage++" :disabled="repPage >= repTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TAB 5A: TIM & STAF (OWNER EXCLUSIVE - FILTERS + CANONICAL PAGINATION) -->
            <div x-show="tab === 'staff' && user.role === 'owner'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden" x-cloak>
                <div class="shrink-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 bg-white border border-slate-200 rounded-lg divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500">Paket Aktif</span>
                        <div class="text-base font-bold text-[#0D2620] mt-0.5" x-text="staffLimits.plan_name || '-'"></div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500">Kuota Pengguna / Staf</span>
                        <div class="text-base font-bold font-tabular text-[#0D7A5F] mt-0.5" x-text="(staffLimits.used_users || 0) + ' / ' + (staffLimits.max_users || 0) + ' akun'"></div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500">Sisa Slot Akun</span>
                        <div class="text-base font-bold font-tabular mt-0.5" :class="(staffLimits.remaining_users || 0) === 0 ? 'text-rose-600' : 'text-slate-900'" x-text="(staffLimits.remaining_users || 0) + ' slot'"></div>
                    </div>
                    <div class="p-4 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-[11px] font-semibold text-slate-500">Status Admin Toko</span>
                            <div class="text-xs font-semibold text-slate-700 mt-0.5" x-text="staffLimits.has_admin ? 'Admin Aktif' : 'Belum Ada Admin (Opsional)'"></div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                    <div class="px-4 sm:px-5 py-3 bg-white border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">Daftar Akun Pemilik, Admin (Opsional) &amp; Kasir</h3>
                            <p class="text-[11px] text-slate-500" x-text="staffPaginationRangeLabel"></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                            <!-- Custom Filter Peran -->
                            <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'staffRoleFilter') openDropdown = null">
                                <button type="button" @click="toggleDropdown('staffRoleFilter')"
                                    class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[140px] justify-between transition">
                                    <span class="truncate" x-text="activeStaffRoleFilterLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'staffRoleFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'staffRoleFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                    <template x-for="opt in staffRoleFilterOptions" :key="opt.value">
                                        <button type="button" @click="staffRoleFilter = opt.value; staffPage = 1; openDropdown = null"
                                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                            :class="staffRoleFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                            <span x-text="opt.label"></span><span x-show="staffRoleFilter === opt.value">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Custom Filter Outlet -->
                            <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'staffOutletFilter') openDropdown = null">
                                <button type="button" @click="toggleDropdown('staffOutletFilter')"
                                    class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[150px] justify-between transition">
                                    <span class="truncate" x-text="activeStaffOutletFilterLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'staffOutletFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'staffOutletFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-52 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-56 overflow-y-auto custom-scrollbar">
                                    <button type="button" @click="staffOutletFilter = ''; staffPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="!staffOutletFilter ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span>Semua Outlet</span><span x-show="!staffOutletFilter">✓</span>
                                    </button>
                                    <template x-for="o in outlets" :key="'sof-' + o.id">
                                        <button type="button" @click="staffOutletFilter = String(o.id); staffPage = 1; openDropdown = null"
                                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                            :class="String(staffOutletFilter) === String(o.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                            <span class="truncate" x-text="o.name"></span><span x-show="String(staffOutletFilter) === String(o.id)">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="relative w-full sm:w-auto">
                                <input type="text" x-model="staffSearch" @input="staffPage = 1" placeholder="Cari nama, email, HP..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-48 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                                <button x-show="staffSearch" type="button" @click="staffSearch = ''; staffPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                            </div>

                            <button @click="openCreateStaffModal()" class="w-full sm:w-auto px-3.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold shrink-0 transition">
                                + Tambah Staf
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                        <table class="w-full min-w-[640px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-4">Nama &amp; Email</th>
                                    <th class="py-2.5 px-4">Peran (Role)</th>
                                    <th class="py-2.5 px-4">Outlet Penugasan</th>
                                    <th class="py-2.5 px-4 text-center">Status Login</th>
                                    <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <template x-for="member in paginatedStaffMembers" :key="member.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-slate-900" x-text="member.name"></div>
                                            <div class="text-[11px] text-slate-500" x-text="member.email + (member.phone ? ' · ' + member.phone : '')"></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded font-semibold text-[10px] uppercase"
                                                :class="member.role === 'owner' ? 'bg-emerald-50 text-emerald-700' : (member.role === 'admin' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700')"
                                                x-text="member.role === 'owner' ? 'Pemilik (Owner)' : (member.role === 'admin' ? 'Admin (Opsional)' : 'Kasir POS')"></span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-600" x-text="member.outlet ? member.outlet.name : 'Semua Outlet'"></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold" :class="member.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                                x-text="member.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <button type="button" x-show="member.role !== 'owner'" @click="openActionPopup('staff', member, $event)"
                                                class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                <span>Aksi</span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            <span x-show="member.role === 'owner'" class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-50 border border-slate-200 text-[11px] font-medium text-slate-400">Akun Utama</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredStaffMembers.length === 0">
                                    <td colspan="5" class="py-10 text-center text-xs text-slate-400">Tidak ada akun staf yang cocok dengan filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Canonical Pagination Bar -->
                    <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="staffPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'staffPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('staffPageSize')" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="staffPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'staffPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'stf-' + sz">
                                        <button type="button" @click="staffPerPage = sz; staffPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="staffPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="staffPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (staffPage > 1) staffPage--" :disabled="staffPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(staffPage, staffTotalPages)" :key="'stpg-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') staffPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="staffPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (staffPage < staffTotalPages) staffPage++" :disabled="staffPage >= staffTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5B: OUTLETS & PROFIL BISNIS (OWNER EXCLUSIVE — BALANCED EQUAL-HEIGHT CARDS + APK BAR) -->
            <div x-show="tab === 'outlets' && user.role === 'owner'" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden custom-scrollbar sm:pr-1 space-y-5 sm:space-y-6 pb-2" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6 items-stretch">
                    <!-- Left Card: Outlet List (Full Height Stretched) -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 flex flex-col justify-between h-full min-w-0">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3.5">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-[#0D2620]">Daftar Outlet &amp; Pengaturan Cabang</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5" x-text="'Terpakai: ' + outlets.length + ' / ' + (business.plan_limits ? business.plan_limits.max_outlets : 1) + ' outlet sesuai paket'"></p>
                                </div>
                                <button @click="showCreateOutletModal = true" class="inline-flex items-center h-8 px-3.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-semibold transition shrink-0">+ Tambah Outlet</button>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <template x-for="out in outlets" :key="out.id">
                                    <div class="py-3.5 first:pt-1 last:pb-1 flex flex-wrap justify-between items-start gap-3 text-xs">
                                        <div class="space-y-1 min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-slate-900" x-text="out.name"></span>
                                                <span x-show="out.outlet_code" class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-tabular text-[10px]" x-text="out.outlet_code"></span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                    :class="out.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                                    x-text="out.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                            </div>
                                            <div class="text-slate-500 break-words" x-text="(out.address || '-') + (out.city ? ', ' + out.city : '') + (out.phone ? ' · ' + out.phone : '')"></div>
                                            <div class="flex flex-wrap gap-1.5 pt-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border"
                                                    :class="out.tax_percentage !== null && out.tax_percentage !== undefined ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-slate-50 border-slate-200 text-slate-500'"
                                                    x-text="out.tax_percentage !== null && out.tax_percentage !== undefined ? ('Pajak Cabang: ' + out.tax_percentage + '%') : ('Pajak Ikut Pusat: ' + (business.tax_percentage ?? 0) + '%')"></span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium border"
                                                    :class="(out.receipt_header || out.receipt_footer) ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-500'"
                                                    x-text="(out.receipt_header || out.receipt_footer) ? 'Struk Kustom Cabang' : 'Struk Ikut Pusat'"></span>
                                            </div>
                                        </div>
                                        <button type="button" @click="openActionPopup('outlet', out, $event)"
                                            class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition whitespace-nowrap shrink-0">
                                            <span>Aksi</span>
                                            <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="pt-4 mt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                            <span>Pajak &amp; struk cabang memprioritaskan pengaturan khusus outlet jika diisi.</span>
                            <button type="button" @click="openPosPreviewModal()" class="text-[#0D7A5F] font-semibold hover:underline shrink-0">Preview Kasir →</button>
                        </div>
                    </div>

                    <!-- Right Card: Business Profile & Default Receipt (Equal Height) -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 flex flex-col justify-between h-full text-xs min-w-0">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3.5">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-[#0D2620]">Profil Bisnis &amp; Default Struk Pusat</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Berlaku otomatis untuk cabang yang tidak memakai pengaturan khusus.</p>
                                </div>
                                <button type="button" @click="editBusinessMode = !editBusinessMode"
                                    class="inline-flex items-center justify-center h-8 px-3 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 text-xs font-medium transition shrink-0"
                                    x-text="editBusinessMode ? 'Batal' : 'Edit Profil'"></button>
                            </div>
                            <div x-show="!editBusinessMode" class="divide-y divide-slate-100">
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5 first:pt-1"><span class="text-slate-500 shrink-0">Nama Bisnis</span><b class="sm:text-right text-slate-900 break-words" x-text="business.name"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5"><span class="text-slate-500 shrink-0">Pemilik</span><b class="sm:text-right text-slate-900 break-words" x-text="business.owner_name"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5"><span class="text-slate-500 shrink-0">Email</span><b class="sm:text-right text-slate-900 break-all" x-text="business.email"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5"><span class="text-slate-500 shrink-0">Telepon</span><b class="sm:text-right text-slate-900" x-text="business.phone || '-'"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5"><span class="text-slate-500 shrink-0">Alamat Pusat</span><b class="sm:text-right sm:max-w-[260px] text-slate-900 break-words" x-text="business.address || '-'"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5"><span class="text-slate-500 shrink-0">Mata Uang &amp; Pajak Default</span><b class="sm:text-right text-slate-900" x-text="(business.currency || 'IDR') + ' (Pajak ' + (business.tax_percentage || 0) + '%)'"></b></div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-4 py-2.5 last:pb-1"><span class="text-slate-500 shrink-0">Catatan Bawah Struk Default</span><b class="sm:text-right sm:max-w-[260px] text-slate-900 whitespace-pre-line break-words" x-text="business.receipt_footer || '-'"></b></div>
                            </div>
                            <form x-show="editBusinessMode" @submit.prevent="saveBusinessProfile()" class="space-y-3" x-cloak>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nama Bisnis</label>
                                    <input type="text" x-model="businessForm.name" required class="w-full px-3 py-2 border border-slate-200 rounded-md">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">No. Telepon</label>
                                        <input type="text" x-model="businessForm.phone" class="w-full px-3 py-2 border border-slate-200 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pajak Default (%)</label>
                                        <input type="number" step="0.5" min="0" max="100" x-model.number="businessForm.tax_percentage" required class="w-full px-3 py-2 border border-slate-200 rounded-md">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Alamat Pusat</label>
                                    <input type="text" x-model="businessForm.address" class="w-full px-3 py-2 border border-slate-200 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Catatan Bawah Struk Default</label>
                                    <input type="text" x-model="businessForm.receipt_footer" class="w-full px-3 py-2 border border-slate-200 rounded-md">
                                </div>
                                <button type="submit" :disabled="isSaving" class="w-full py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] disabled:opacity-60 text-white rounded-md font-bold transition inline-flex items-center justify-center gap-2">
                                    <svg x-show="isSaving" class="w-4 h-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Profil Bisnis'"></span>
                                </button>
                            </form>
                        </div>
                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                            <span>Perubahan profil pusat otomatis tampil di struk outlet standar.</span>
                            <button type="button" @click="setTab('billing')" class="text-[#0D7A5F] font-semibold hover:underline shrink-0">Kelola Paket →</button>
                        </div>
                    </div>
                </div>

                <!-- Full-Width Setup Device Bar (Balanced Across Grid, Subscription-Gated) -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-lg border flex items-center justify-center shrink-0"
                            :class="business.can_install_apk ? 'bg-[#E6F4EF] border-[#0D7A5F]/20 text-[#0D7A5F]' : 'bg-slate-100 border-slate-200 text-slate-500'">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-[#0D2620]">Pemasangan Aplikasi Kasir Perangkat Outlet</span>
                                <span x-show="!business.can_install_apk" class="px-2 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-semibold">Perlu Langganan Aktif</span>
                            </div>
                            <p x-show="business.can_install_apk" class="text-xs text-slate-500 mt-0.5">Unduh installer Android (<b>.APK</b>) untuk perangkat kasir atau baca panduan pasang <b>WebAPK / PWA</b> via browser. Setelah terpasang, kasir cukup login dengan akun Kasir masing-masing.</p>
                            <p x-show="!business.can_install_apk" class="text-xs text-slate-500 mt-0.5">Pemasangan aplikasi kasir pada perangkat outlet terbuka otomatis setelah Anda berlangganan paket aktif di menu Langganan &amp; Tagihan.</p>
                        </div>
                    </div>
                    <div x-show="business.can_install_apk" class="flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button" @click="installKasirApk('webapk')"
                            class="inline-flex items-center gap-2 h-9 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-bold transition">
                            <i data-lucide="smartphone" class="w-4 h-4 text-[#0D7A5F]"></i> Cara Pasang WebAPK
                        </button>
                        <button type="button" @click="installKasirApk('apk')"
                            class="inline-flex items-center gap-2 h-9 px-3.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition">
                            <i data-lucide="download" class="w-4 h-4"></i> Download File .APK
                        </button>
                    </div>
                    <button x-show="!business.can_install_apk" type="button" @click="setTab('billing')"
                        class="inline-flex items-center gap-2 h-9 px-4 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition shrink-0">
                        Aktifkan Paket Langganan →
                    </button>
                </div>
            </div>

            <!-- TAB 5C: PENGATURAN FITUR KASIR & ADMIN (OWNER EXCLUSIVE) -->
            <div x-show="tab === 'settings' && user.role === 'owner'" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden custom-scrollbar space-y-5 sm:pr-1" x-cloak>
                <div class="bg-white p-4 sm:p-5 rounded-lg border border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="max-w-2xl min-w-0">
                        <h3 class="text-sm font-bold text-[#0D2620]">Kebebasan Kustomisasi Tombol Kasir &amp; Akses Admin</h3>
                        <p class="text-xs text-slate-500 mt-1">Aktifkan atau nonaktifkan tombol serta hak akses fitur untuk akun <b>Kasir</b> dan <b>Admin Toko</b> sesuai kebutuhan usaha Anda. Perubahan langsung berlaku saat halaman kasir/admin dimuat ulang.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto lg:shrink-0">
                        <button type="button" @click="openPosPreviewModal(selectedOutletId || null)" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i> Preview Layar Kasir
                        </button>
                        <button type="button" @click="resetFeatureSettings()" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-medium transition">Reset Default</button>
                        <button type="button" @click="saveFeatureSettings()" :disabled="isSaving" class="w-full sm:w-auto justify-center px-4 py-2 bg-[#0D7A5F] hover:bg-[#095C47] disabled:opacity-60 text-white rounded-md text-xs font-bold transition inline-flex items-center gap-1.5">
                            <svg x-show="isSaving" class="w-3.5 h-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                            <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Pengaturan Fitur'"></span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
                    <!-- Pengaturan Tombol & Fitur Kasir -->
                    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden min-w-0">
                        <div class="px-4 sm:px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                            <div class="text-xs font-bold text-slate-900">Pengaturan Layar Kasir POS (Role: Kasir)</div>
                            <p class="text-[11px] text-slate-500">Menentukan tombol apa saja yang muncul dan boleh dipakai kasir.</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="item in cashierFeatureToggleMeta" :key="item.key">
                                <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3 sm:gap-4 hover:bg-slate-50/50">
                                    <div class="min-w-0 flex-1 break-words">
                                        <div class="text-xs font-semibold text-slate-900" x-text="item.title"></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5" x-text="item.desc"></div>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="featureSettingsForm[item.key]"
                                        @click="featureSettingsForm[item.key] = !featureSettingsForm[item.key]"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                                        :class="featureSettingsForm[item.key] ? 'bg-[#0D7A5F]' : 'bg-slate-300'">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                                            :class="featureSettingsForm[item.key] ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pengaturan Akses Fitur Admin Toko -->
                    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden min-w-0">
                        <div class="px-4 sm:px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                            <div class="text-xs font-bold text-slate-900">Pengaturan Menu &amp; Akses Admin Toko (Role: Admin Opsional)</div>
                            <p class="text-[11px] text-slate-500">Membatasi halaman/fitur apa saja yang bisa dibuka jika bisnis memiliki Admin.</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="item in adminFeatureToggleMeta" :key="item.key">
                                <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3 sm:gap-4 hover:bg-slate-50/50">
                                    <div class="min-w-0 flex-1 break-words">
                                        <div class="text-xs font-semibold text-slate-900" x-text="item.title"></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5" x-text="item.desc"></div>
                                    </div>
                                    <button type="button" role="switch" :aria-checked="featureSettingsForm[item.key]"
                                        @click="featureSettingsForm[item.key] = !featureSettingsForm[item.key]"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                                        :class="featureSettingsForm[item.key] ? 'bg-[#0D7A5F]' : 'bg-slate-300'">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                                            :class="featureSettingsForm[item.key] ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5D: LANGGANAN & TAGIHAN (OWNER EXCLUSIVE — UNIFIED PORTAL) -->
            <div x-show="tab === 'billing' && user.role === 'owner'" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden custom-scrollbar sm:pr-1 space-y-5 sm:space-y-6 pb-2" x-cloak>
                <!-- Status Langganan & Pemakaian Kuota -->
                <div class="grid grid-cols-1 md:grid-cols-4 bg-white border border-slate-200 rounded-xl divide-y md:divide-y-0 md:divide-x divide-slate-200">
                    <div class="p-4 min-w-0">
                        <span class="text-[11px] font-semibold text-slate-500">Paket &amp; Status</span>
                        <div class="text-base font-bold text-[#0D2620] mt-0.5" x-text="(billingSummary && billingSummary.plan_name) || (business.subscription_summary ? business.subscription_summary.plan_name : 'Belum Berlangganan')"></div>
                        <div class="text-[11px] text-slate-500 mt-0.5 font-tabular" x-text="(billingSummary && billingSummary.expires_at) ? ('Berlaku s.d. ' + (billingSummary.expires_at_formatted || '-')) : ((business.subscription_summary && business.subscription_summary.expires_at) ? ('Berlaku s.d. ' + business.subscription_summary.expires_at_formatted) : 'Pilih paket di bawah untuk memulai')"></div>
                    </div>
                    <div class="p-4 min-w-0">
                        <span class="text-[11px] font-semibold text-slate-500">Sisa Masa Aktif</span>
                        <div class="text-base font-bold font-tabular mt-0.5"
                            :class="((billingSummary || business.subscription_summary)?.lifecycle_status === 'expired') ? 'text-rose-600' : (((billingSummary || business.subscription_summary)?.lifecycle_status === 'none') ? 'text-slate-700' : 'text-[#0D7A5F]')"
                            x-text="(billingSummary && billingSummary.countdown_label) || (business.subscription_summary ? business.subscription_summary.countdown_label : 'Belum Aktif')"></div>
                        <div class="text-[11px] text-slate-500 mt-0.5" x-text="business.can_install_apk ? 'Aplikasi kasir siap dipasang' : 'Perlu paket aktif untuk pasang APK'"></div>
                    </div>
                    <div class="p-4 min-w-0">
                        <span class="text-[11px] font-semibold text-slate-500">Kuota Outlet &amp; Staf</span>
                        <div class="text-base font-bold font-tabular text-slate-900 mt-0.5" x-text="(billingSummary && billingSummary.max_outlets > 0) ? ((billingUsage.outlets_count || 0) + '/' + billingSummary.max_outlets + ' outlet · ' + (billingUsage.users_count || 0) + '/' + (billingSummary.max_users || 0) + ' staf') : 'Mengikuti Paket'"></div>
                        <div class="text-[11px] text-slate-500 mt-0.5 font-tabular" x-text="(billingSummary && billingSummary.max_products > 0) ? ((billingUsage.products_count || 0) + '/' + billingSummary.max_products + ' produk') : 'Belum ada kuota paket aktif'"></div>
                    </div>
                    <div class="p-4 flex flex-wrap items-center justify-between gap-2 min-w-0">
                        <div class="min-w-0">
                            <span class="text-[11px] font-semibold text-slate-500">Perangkat Kasir</span>
                            <div class="text-xs font-semibold text-slate-800 mt-0.5" x-text="business.can_install_apk ? 'Siap Dipasang di Cabang' : 'Menunggu Langganan Aktif'"></div>
                        </div>
                        <div x-show="business.can_install_apk" class="flex flex-wrap items-center gap-1.5 shrink-0">
                            <button type="button" @click="installKasirApk('webapk')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-md text-xs font-bold transition">Cara WebAPK</button>
                            <button type="button" @click="installKasirApk('apk')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold transition">Download .APK</button>
                        </div>
                    </div>
                </div>

                <!-- Pilihan Paket + Durasi Multi-Bulan/Tahun -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-[#0D2620]" x-text="business.service_type === 'template_saas' ? 'Perpanjang atau Upgrade Paket Langganan' : 'Pilih Paket &amp; Durasi Berlangganan Tandha POS'"></h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Pilih kapasitas cabang beserta fleksibilitas durasi berlangganan mulai 1 bulan hingga 3 tahun.</p>
                        </div>
                        <span class="px-2.5 py-1 bg-[#E6F4EF] text-[#0D7A5F] text-[11px] font-semibold rounded-md">Nota Lunas Otomatis Tersedia</span>
                    </div>

                    <!-- Loading Skeleton -->
                    <div x-show="billingLoading && billingPlans.length === 0" class="grid grid-cols-1 md:grid-cols-3 gap-3 animate-pulse">
                        <div class="h-24 bg-slate-100 rounded-lg"></div>
                        <div class="h-24 bg-slate-100 rounded-lg"></div>
                        <div class="h-24 bg-slate-100 rounded-lg"></div>
                    </div>

                    <!-- Packet Cards -->
                    <div x-show="billingPlans.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <template x-for="plan in billingPlans" :key="'bp-' + plan.id">
                            <button type="button" @click="selectedPlanId = plan.id"
                                class="p-4 rounded-xl border text-left flex flex-col justify-between gap-3 transition min-w-0"
                                :class="String(selectedPlanId) === String(plan.id) ? 'bg-[#E6F4EF]/60 border-[#0D7A5F] ring-1 ring-[#0D7A5F]/20' : 'bg-white border-slate-200 hover:border-slate-300'">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0D7A5F]" x-text="'Paket ' + plan.name"></span>
                                        <div class="text-base font-extrabold font-tabular text-[#0D2620] mt-1" x-text="formatRupiah(plan.price_monthly) + '/bln'"></div>
                                    </div>
                                    <span class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5"
                                        :class="String(selectedPlanId) === String(plan.id) ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'border-slate-300 bg-white'">
                                        <span x-show="String(selectedPlanId) === String(plan.id)" class="text-[10px] font-bold">✓</span>
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 pt-2 border-t border-slate-200/80 font-tabular"
                                    x-text="'Maks. ' + plan.max_outlets + ' Outlet · ' + plan.max_users + ' Akun Staf · ' + plan.max_products + ' Produk'"></div>
                            </button>
                        </template>
                    </div>

                    <!-- Duration Selector (1 Bulan s.d. 3 Tahun) -->
                    <div class="pt-1">
                        <label class="block text-xs font-bold text-[#0D2620] mb-2">Pilih Durasi Berlangganan</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            <template x-for="period in billingPeriods" :key="'dur-' + period.months">
                                <button type="button" @click="billingDurationMonths = period.months"
                                    class="px-3 py-2.5 rounded-lg border text-left transition min-w-0"
                                    :class="Number(billingDurationMonths) === Number(period.months) ? 'bg-[#0D2620] border-[#0D2620] text-white' : 'bg-slate-50 border-slate-200 hover:border-slate-300 text-slate-700'">
                                    <div class="text-xs font-bold" :class="Number(billingDurationMonths) === Number(period.months) ? 'text-[#34D399]' : 'text-[#0D2620]'" x-text="period.label"></div>
                                    <div class="text-[10px] mt-0.5 font-tabular" :class="Number(billingDurationMonths) === Number(period.months) ? 'text-slate-300' : 'text-slate-500'" x-text="period.days.toLocaleString('id-ID') + ' hari aktif'"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Billing Summary & Single Clear Action CTA -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-0.5 min-w-0 flex-1">
                            <div class="text-[11px] text-slate-500" x-text="'Ringkasan: ' + selectedPlanSummaryText + ' · Durasi ' + selectedBillingPeriodLabel"></div>
                            <div class="text-lg font-extrabold font-tabular text-[#0D2620]" x-text="'Total: ' + formatRupiah(computedBillingAmount)"></div>
                            <div class="text-[11px] text-slate-500">Payment gateway Midtrans sedang dalam pengajuan. Tagihan tercatat langsung untuk aktivasi oleh tim Admin.</div>
                        </div>
                        <button type="button" @click="rentTemplateAction()" :disabled="submittingRent || !selectedPlanId"
                            class="w-full sm:w-auto justify-center px-5 py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] disabled:opacity-50 text-white rounded-md text-xs font-bold transition inline-flex items-center gap-2 shrink-0">
                            <svg x-show="submittingRent" class="w-4 h-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                            <span x-text="submittingRent ? 'Membuat Tagihan...' : 'Buat Tagihan Langganan →'"></span>
                        </button>
                    </div>

                    <!-- Pending Payment Box -->
                    <template x-if="pendingPayment">
                        <div class="bg-[#F7FAF9] border border-[#0D7A5F] rounded-xl overflow-hidden">
                            <div class="bg-[#0D2620] text-white px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold text-[#34D399] uppercase tracking-wider">Tagihan Menunggu Verifikasi</span>
                                    <div class="text-xs font-tabular font-bold" x-text="pendingPayment.invoice_number"></div>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 text-[11px] font-semibold">Pending</span>
                            </div>
                            <div class="p-5 space-y-3 text-xs">
                                <div class="bg-white p-4 rounded-lg border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <div class="text-[11px] text-slate-500">Paket &amp; Durasi</div>
                                        <div class="font-bold text-slate-900 mt-0.5" x-text="(pendingPayment.plan ? pendingPayment.plan.name : '-') + ' · ' + ((pendingPayment.duration_months || 1) + ' Bulan')"></div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] text-slate-500">Total Tagihan</div>
                                        <div class="font-bold font-tabular text-[#0D7A5F] mt-0.5" x-text="formatRupiah(pendingPayment.amount)"></div>
                                    </div>
                                    <div class="sm:text-right">
                                        <div class="text-[11px] text-slate-500">Batas Waktu Tagihan</div>
                                        <div class="font-medium text-slate-800 mt-0.5 font-tabular" x-text="formatDate(pendingPayment.expires_at)"></div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <span class="text-[11px] text-slate-500">Status diperbarui otomatis setiap 3 detik setelah Admin mengonfirmasi tagihan ini.</span>
                                    <button type="button" @click="manualCheckPayment()" :disabled="checkingPayment"
                                        class="px-3.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-xs font-semibold transition inline-flex items-center gap-1.5">
                                        <svg x-show="checkingPayment" class="w-3.5 h-3.5 animate-spin text-[#0D7A5F]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                                        <span x-text="checkingPayment ? 'Memeriksa...' : 'Cek Status Pembayaran'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Riwayat Tagihan & Nota Lunas -->
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">Riwayat Tagihan &amp; Nota Lunas</h3>
                            <p class="text-[11px] text-slate-500" x-text="payPaginationRangeLabel"></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'payStatusFilter') openDropdown = null">
                                <button type="button" @click="toggleDropdown('payStatusFilter')"
                                    class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[140px] justify-between">
                                    <span x-text="activePayStatusLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="openDropdown === 'payStatusFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'payStatusFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 text-xs">
                                    <template x-for="opt in payStatusOptions" :key="'bstat-' + opt.value">
                                        <button type="button" @click="payStatusFilter = opt.value; payPage = 1; openDropdown = null"
                                            class="w-full px-3 py-1.5 text-left flex justify-between hover:bg-slate-50"
                                            :class="payStatusFilter === opt.value ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="opt.label"></span><span x-show="payStatusFilter === opt.value">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="relative w-full sm:w-auto">
                                <input type="text" x-model="paySearch" @input="payPage = 1" placeholder="Cari invoice atau paket..."
                                    class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-48 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0D7A5F]">
                                <button x-show="paySearch" type="button" @click="paySearch = ''; payPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                            </div>
                        </div>
                    </div>
                    <div class="max-h-72 overflow-auto custom-scrollbar">
                        <table class="w-full min-w-[600px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-4">Invoice &amp; Nota</th>
                                    <th class="py-2.5 px-4">Paket, Durasi &amp; Tanggal</th>
                                    <th class="py-2.5 px-4 text-right">Nominal</th>
                                    <th class="py-2.5 px-4 text-center">Status</th>
                                    <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <template x-for="ph in paginatedBillingHistory" :key="'ph-' + ph.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="py-3 px-4">
                                            <div class="font-tabular font-semibold text-slate-900" x-text="ph.invoice_number"></div>
                                            <div x-show="ph.receipt_number" class="text-[11px] font-tabular text-[#0D7A5F]" x-text="'Nota: ' + ph.receipt_number"></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-slate-900" x-text="'Paket ' + (ph.plan ? ph.plan.name : (ph.plan_name_snapshot || '-')) + ' · ' + ((ph.duration_months || 1) + ' Bulan')"></div>
                                            <div class="text-[11px] text-slate-400 font-tabular" x-text="'Dibuat: ' + formatDate(ph.created_at) + (ph.verified_at ? ' · Lunas: ' + formatDate(ph.verified_at) : '')"></div>
                                        </td>
                                        <td class="py-3 px-4 text-right font-tabular font-semibold text-slate-900" x-text="formatRupiah(ph.amount)"></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                                                :class="ph.status === 'verified' ? 'bg-emerald-50 text-emerald-700' : (ph.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600')"
                                                x-text="ph.status"></span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <button type="button" @click="openPayActionPopup(ph, $event)"
                                                class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                <span>Aksi</span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredBillingHistory.length === 0">
                                    <td colspan="5" class="py-10 text-center text-xs text-slate-400">Belum ada riwayat tagihan langganan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="payPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'payPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('payPageSize')" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="payPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'payPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'bps-' + sz">
                                        <button type="button" @click="payPerPage = sz; payPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="payPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="payPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (payPage > 1) payPage--" :disabled="payPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(payPage, payTotalPages)" :key="'bppg-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') payPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="payPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (payPage < payTotalPages) payPage++" :disabled="payPage >= payTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5E: KONSULTASI FITUR & RIWAYAT UAT (OWNER EXCLUSIVE — UNIFIED PORTAL) -->
            <div x-show="tab === 'consultation' && user.role === 'owner'" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden custom-scrollbar sm:pr-1 space-y-5 sm:space-y-6 pb-2" x-cloak>
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-6 space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-[#0D2620]">Konsultasi &amp; Penambahan Fitur Khusus Tandha</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Jadwalkan diskusi minimal 3 hari sebelumnya untuk penyesuaian alur usaha atau fitur tambahan, terhubung langsung dengan tim SEKA GROUP.</p>
                        </div>
                        <span class="px-2.5 py-1 bg-[#0D2620] text-[#34D399] text-[11px] font-semibold rounded-md">Terhubung ke WhatsApp SEKA GROUP</span>
                    </div>

                    <!-- Notice when active submission already exists (Refined Architectural Alert Card — Single Primary CTA) -->
                    <div x-show="consultationGuard && !consultationGuard.can_submit" x-cloak
                        class="relative overflow-hidden p-4 pl-5 bg-white border border-amber-200/90 rounded-xl shadow-xs text-xs flex flex-wrap items-center justify-between gap-4">
                        <div class="absolute inset-y-0 left-0 w-1 bg-amber-500"></div>
                        <div class="flex items-start gap-3 max-w-2xl">
                            <div class="w-9 h-9 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                                <i data-lucide="clock-3" class="w-4 h-4"></i>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold text-[#0D2620]">Pengajuan Konsultasi Sedang Diproses</span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-semibold font-tabular"
                                        x-text="'Kuota: ' + (consultationGuard.weekly_used || 0) + ' / ' + (consultationGuard.weekly_limit || 2) + ' minggu ini'"></span>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-relaxed" x-text="consultationGuard ? consultationGuard.reason : ''"></p>
                            </div>
                        </div>
                        <a x-show="consultationGuard && consultationGuard.active_whatsapp_url" :href="consultationGuard.active_whatsapp_url" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-lg font-bold text-xs transition shrink-0 shadow-xs">
                            Lanjutkan Chat WhatsApp SEKA GROUP →
                        </a>
                    </div>

                    <form @submit.prevent="submitConsultation()" class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="relative" @click.outside="if (openDropdown === 'consultBizType') openDropdown = null">
                                <label class="block font-semibold text-slate-600 mb-1">Sektor Usaha</label>
                                <button type="button" @click="toggleDropdown('consultBizType')"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 hover:border-[#0D7A5F] rounded-md text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                    <span class="truncate" x-text="consultForm.business_type"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'consultBizType' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'consultBizType'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 max-h-48 overflow-y-auto custom-scrollbar">
                                    <template x-for="opt in businessTypeOptions" :key="'cbiz-' + opt">
                                        <button type="button" @click="consultForm.business_type = opt; openDropdown = null"
                                            class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50"
                                            :class="consultForm.business_type === opt ? 'bg-emerald-50/70 font-semibold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                            <span x-text="opt"></span>
                                            <span x-show="consultForm.business_type === opt">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="relative" @click.outside="if (openDropdown === 'consultScale') openDropdown = null">
                                <label class="block font-semibold text-slate-600 mb-1">Skala Outlet &amp; Perangkat</label>
                                <button type="button" @click="toggleDropdown('consultScale')"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 hover:border-[#0D7A5F] rounded-md text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                    <span class="truncate" x-text="selectedConsultScaleLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'consultScale' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'consultScale'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="opt in scaleOptions" :key="'cscl-' + opt.value">
                                        <button type="button" @click="consultForm.scale = opt.value; openDropdown = null"
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
                            <label class="block font-semibold text-slate-600 mb-1">Bidang Usaha Lainnya *</label>
                            <input type="text" x-model="consultForm.business_type_other" :required="consultForm.business_type === 'Lainnya'"
                                placeholder="Contoh: Laundry Kiloan, Toko Bangunan, Petshop"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-md text-[#0D2620] focus:bg-white focus:border-[#0D7A5F] focus:outline-none">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">No. WhatsApp PIC *</label>
                                <input type="text" x-model="consultForm.phone" required placeholder="0812xxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-md text-[#0D2620] font-tabular focus:bg-white focus:border-[#0D7A5F] focus:outline-none">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Tanggal Konsultasi (Min. 3 Hari Sebelumnya) *</label>
                                <input type="date" x-model="consultForm.preferred_date" :min="minConsultDate" :max="maxConsultDate" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-md text-[#0D2620] font-tabular focus:bg-white focus:border-[#0D7A5F] focus:outline-none">
                            </div>
                            <div class="relative" @click.outside="if (openDropdown === 'consultTime') openDropdown = null">
                                <label class="block font-semibold text-slate-600 mb-1">Jam Konsultasi (WIB) *</label>
                                <button type="button" @click="toggleDropdown('consultTime')"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 hover:border-[#0D7A5F] rounded-md text-[#0D2620] flex items-center justify-between gap-2 text-left transition">
                                    <span class="font-tabular" x-text="consultForm.preferred_time + ' WIB'"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'consultTime' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'consultTime'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 max-h-48 overflow-y-auto custom-scrollbar">
                                    <template x-for="slot in consultTimeSlots" :key="'ctm-' + slot">
                                        <button type="button" @click="consultForm.preferred_time = slot; openDropdown = null"
                                            class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50 font-tabular"
                                            :class="consultForm.preferred_time === slot ? 'bg-emerald-50/70 font-semibold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                            <span x-text="slot + ' WIB'"></span>
                                            <span x-show="consultForm.preferred_time === slot">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Detail Kebutuhan &amp; Penyesuaian Alur Usaha *</label>
                            <textarea x-model="consultForm.template_gap_notes" required minlength="15" rows="3"
                                :disabled="consultationGuard && !consultationGuard.can_submit"
                                placeholder="Tuliskan fitur tambahan yang dibutuhkan (minimal 15 karakter), seperti pemotongan stok bahan baku resep otomatis, harga grosir bertingkat, atau laporan khusus."
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-md text-[#0D2620] focus:bg-white focus:border-[#0D7A5F] focus:outline-none disabled:opacity-60"></textarea>
                        </div>

                        <div x-show="!consultationGuard || consultationGuard.can_submit" class="flex flex-wrap items-center justify-between gap-3 pt-1">
                            <span class="text-[11px] text-slate-500 font-tabular" x-text="'Jadwal tersedia mulai ' + minConsultDate + ' (09:00 – 16:00 WIB) · Kuota minggu ini: ' + ((consultationGuard && consultationGuard.weekly_used) || 0) + ' / ' + ((consultationGuard && consultationGuard.weekly_limit) || 2)"></span>
                            <button type="submit" :disabled="submittingConsult"
                                class="px-5 py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] disabled:opacity-50 text-white rounded-md font-bold transition inline-flex items-center gap-2">
                                <svg x-show="submittingConsult" class="w-4 h-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                                <span x-text="submittingConsult ? 'Mengirim...' : 'Simpan Jadwal &amp; Hubungi SEKA GROUP →'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Riwayat Konsultasi, Proposal & Pengujian UAT -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-4">
                    <div class="border-b border-slate-200 pb-3.5">
                        <h3 class="text-sm font-bold text-[#0D2620]">Riwayat Konsultasi, Proposal &amp; Pengujian UAT</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Progres pengerjaan penyesuaian fitur serta hasil uji UAT akun Anda tercatat rapi di sini.</p>
                    </div>

                    <template x-if="billingProjects.length === 0">
                        <div class="p-8 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs text-slate-500">
                            Belum ada pengajuan konsultasi fitur khusus. Gunakan formulir di atas apabila membutuhkan penyesuaian sistem di luar paket standar.
                        </div>
                    </template>

                    <div class="space-y-4">
                        <template x-for="proj in billingProjects" :key="'cproj-' + proj.id">
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                                <div class="bg-[#0D2620] text-white px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <span class="text-[10px] font-semibold text-[#34D399] uppercase tracking-wider" x-text="proj.business_type"></span>
                                        <h4 class="text-sm font-bold mt-0.5" x-text="proj.business_name + ' (#KONSUL-' + proj.id + ')'"></h4>
                                    </div>
                                    <span class="px-3 py-1 bg-[#143B32] border border-emerald-500/30 text-[#34D399] rounded text-[11px] font-bold uppercase" x-text="formatConsultStatus(proj.status)"></span>
                                </div>

                                <div class="p-5 space-y-4 text-xs">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                                            <span class="font-bold text-[#0D7A5F] uppercase text-[10px] block mb-1">Detail Kebutuhan Fitur</span>
                                            <p class="text-slate-800 leading-relaxed" x-text="proj.template_gap_notes || proj.notes || '-'"></p>
                                        </div>
                                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                                            <span class="font-bold text-[#0D7A5F] uppercase text-[10px] block">Jadwal &amp; Status WhatsApp</span>
                                            <template x-if="proj.consultations && proj.consultations.length > 0">
                                                <div>
                                                    <div class="font-semibold text-slate-900 font-tabular" x-text="'Jadwal: ' + formatDate(proj.consultations[0].scheduled_at) + (proj.consultation_time_slot ? ' (' + proj.consultation_time_slot + ' WIB)' : '')"></div>
                                                    <div class="text-slate-500" x-text="'PIC Analis: ' + proj.consultations[0].pic_name"></div>
                                                </div>
                                            </template>
                                            <div class="pt-1 flex flex-wrap items-center justify-between gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                    :class="proj.whatsapp_status === 'sent_from_sekayasa' || proj.whatsapp_status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                                    x-text="proj.whatsapp_status === 'sent_from_sekayasa' ? 'Sudah Dikonfirmasi di WhatsApp' : (proj.whatsapp_status === 'completed' ? 'Selesai / Terjawab' : 'Menunggu Balasan WhatsApp')"></span>
                                                <a x-show="proj.whatsapp_url" :href="proj.whatsapp_url" target="_blank" rel="noopener noreferrer"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-medium transition">
                                                    Buka WhatsApp →
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <template x-if="proj.current_proposal">
                                        <div class="p-4 bg-[#FDF3EE] rounded-lg border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                                            <div>
                                                <span class="text-[10px] font-bold text-[#0D7A5F] uppercase">Proposal &amp; Estimasi</span>
                                                <div class="text-sm font-bold text-[#0D2620] mt-0.5" x-text="proj.current_proposal.proposal_number + ' • ' + proj.current_proposal.timeline_weeks + ' Minggu'"></div>
                                                <div class="text-slate-600 mt-0.5" x-text="proj.current_proposal.deliverables"></div>
                                            </div>
                                            <div class="text-right font-tabular">
                                                <div class="text-base font-bold text-[#0D2620]" x-text="formatRupiah(proj.current_proposal.development_cost)"></div>
                                                <div class="text-[11px] text-[#0D7A5F]" x-text="'Managed: ' + formatRupiah(proj.current_proposal.monthly_maintenance_cost) + '/bln'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="proj.uat_cases && proj.uat_cases.length > 0">
                                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                                            <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex justify-between items-center">
                                                <span class="font-bold text-[#0D2620] uppercase text-[11px]">Pengujian UAT Akun Anda</span>
                                                <span class="text-[11px] text-[#0D7A5F] font-semibold">Konfirmasi kesesuaian fitur</span>
                                            </div>
                                            <div class="divide-y divide-slate-100">
                                                <template x-for="tc in proj.uat_cases" :key="'uat-' + tc.id">
                                                    <div class="p-3.5 flex flex-wrap items-center justify-between gap-4">
                                                        <div>
                                                            <div class="font-bold text-[#0D2620]" x-text="tc.test_title"></div>
                                                            <div class="text-[11px] text-slate-500 mt-0.5" x-text="'Skenario: ' + tc.scenario"></div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <button type="button" @click="submitClientUat(tc.id, 'pass')" :class="tc.status === 'pass' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700'" class="px-2.5 py-1 rounded-md border text-[11px] font-medium transition">Pass</button>
                                                            <button type="button" @click="submitClientUat(tc.id, 'fail')" :class="tc.status === 'fail' ? 'bg-rose-600 border-rose-600 text-white' : 'bg-white border-rose-200 hover:bg-rose-50 text-rose-600'" class="px-2.5 py-1 rounded-md border text-[11px] font-medium transition">Fail</button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            </div><!-- End Store Owner Scrollable Area -->

            <!-- TAB 6: SUPER ADMIN PLATFORM (SIMPLE, CALM MONITORING DASHBOARD) -->
            <div x-show="tab === 'admin_platform'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden" x-cloak>

                <!-- SUBVIEW 1 (via Sidebar): MONITORING SEMUA KLIEN -->
                <div x-show="adminSubTab === 'clients'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden">
                    <!-- Calm Summary KPI Strip -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 shrink-0">
                        <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                            <span class="text-[11px] font-medium text-slate-500">Total Klien</span>
                            <div class="text-xl font-bold text-slate-900 mt-0.5" x-text="adminMetrics.total_businesses || 0"></div>
                            <span class="text-[11px] text-slate-500" x-text="(adminMetrics.active_businesses || 0) + ' aktif · ' + (adminMetrics.total_outlets || 0) + ' cabang'"></span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                            <span class="text-[11px] font-medium text-slate-500">Estimasi MRR</span>
                            <div class="text-xl font-bold text-emerald-700 mt-0.5 font-tabular" x-text="formatRupiah(adminMetrics.mrr_estimate || 0)"></div>
                            <span class="text-[11px] text-slate-500" x-text="(adminMetrics.active_subscriptions || 0) + ' aktif · ' + (adminMetrics.trial_subscriptions || 0) + ' trial'"></span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                            <span class="text-[11px] font-medium text-slate-500">Belum Aktif / Segera Habis</span>
                            <div class="text-xl font-bold text-slate-900 mt-0.5" x-text="(adminMetrics.expiring_soon_count || 0) + (adminMetrics.unactivated_subscriptions_count || 0)"></div>
                            <span class="text-[11px] text-slate-500" x-text="(adminMetrics.expiring_soon_count || 0) + ' segera habis · ' + (adminMetrics.unactivated_subscriptions_count || 0) + ' belum aktif'"></span>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                            <span class="text-[11px] font-medium text-slate-500">Transaksi Hari Ini</span>
                            <div class="text-xl font-bold text-slate-900 mt-0.5" x-text="adminMetrics.today_transactions || 0"></div>
                            <span class="text-[11px] font-tabular text-slate-500" x-text="formatRupiah(adminMetrics.today_revenue || 0)"></span>
                        </div>
                        <div class="col-span-2 lg:col-span-1 bg-white border border-slate-200 rounded-lg p-3.5">
                            <span class="text-[11px] font-medium text-slate-500">Error &amp; Anomali</span>
                            <div class="text-xl font-bold mt-0.5" :class="(adminMetrics.error_log_count || 0) > 0 ? 'text-rose-600' : 'text-slate-900'" x-text="adminMetrics.error_log_count || 0"></div>
                            <span class="text-[11px] text-slate-500" x-text="'Failed jobs: ' + (adminMetrics.failed_jobs_count || 0)"></span>
                        </div>
                    </div>

                    <!-- Table Card (Clean White Header + Simple 2-Line Rows + Table-Only Scroll + Pagination) -->
                    <div class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 bg-white border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900">Daftar Bisnis Klien</h3>
                                <p class="text-[11px] text-slate-500" x-text="adminPaginationRangeLabel"></p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                                <!-- Custom Status Filter Dropdown -->
                                <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'adminFilter') openDropdown = null">
                                    <button type="button" @click="toggleDropdown('adminFilter')"
                                        class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[195px] justify-between transition">
                                        <span class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full shrink-0" :class="activeFilterOption.dot"></span>
                                            <span class="truncate" x-text="activeFilterOption.label"></span>
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'adminFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openDropdown === 'adminFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 overflow-hidden">
                                        <template x-for="opt in adminFilterOptions" :key="opt.value">
                                            <button type="button" @click="adminSubFilter = opt.value; adminPage = 1; openDropdown = null"
                                                class="w-full px-3 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 transition"
                                                :class="adminSubFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                                <span class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full shrink-0" :class="opt.dot"></span>
                                                    <span x-text="opt.label"></span>
                                                </span>
                                                <span x-show="adminSubFilter === opt.value" class="text-emerald-600">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Search Box -->
                                <div class="relative w-full sm:w-auto">
                                    <input type="text" x-model="adminSearch" @input="adminPage = 1" placeholder="Cari klien, pemilik, kota..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-56 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600">
                                    <button x-show="adminSearch" type="button" @click="adminSearch = ''; adminPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Table Wrapper ONLY -->
                        <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                            <table class="w-full min-w-[680px] text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-4">Klien</th>
                                        <th class="py-2.5 px-4">Langganan POS</th>
                                        <th class="py-2.5 px-4">Penggunaan</th>
                                        <th class="py-2.5 px-4 text-right">Omzet &amp; Transaksi</th>
                                        <th class="py-2.5 px-4 text-center">Status Akun</th>
                                        <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="b in paginatedAdminBusinesses" :key="b.id">
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-900" x-text="b.name"></div>
                                                <div class="text-[11px] text-slate-500" x-text="b.owner_name + (b.city ? ' · ' + b.city : '')"></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                        :class="!b.subscription_summary || b.subscription_summary.lifecycle_status === 'none' ? 'bg-slate-300' : (b.subscription_summary.lifecycle_status === 'expired' ? 'bg-rose-500' : (b.subscription_summary.is_expiring_soon ? 'bg-amber-500' : (b.subscription_summary.status === 'trial' ? 'bg-sky-500' : 'bg-emerald-500')))"></span>
                                                    <span class="font-medium text-slate-800" x-text="b.subscription_summary ? b.subscription_summary.plan_name : b.service_type"></span>
                                                    <span x-show="b.subscription_summary && b.subscription_summary.status === 'trial'" class="text-[10px] font-medium text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded">Trial</span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5"
                                                    x-text="b.subscription_summary && b.subscription_summary.expires_at ? ('s.d. ' + b.subscription_summary.expires_at_formatted + ' (' + b.subscription_summary.countdown_label + ')') : (b.subscription_summary ? b.subscription_summary.countdown_label : '-')"></div>
                                            </td>
                                            <td class="py-3 px-4 text-[11px] text-slate-600 font-tabular">
                                                <span x-text="(b.outlets_count || 0) + ' outlet · ' + (b.users_count || 0) + ' staf · ' + (b.products_count || 0) + ' produk'"></span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="font-tabular font-semibold text-slate-900" x-text="formatRupiah(b.paid_revenue_sum || 0)"></div>
                                                <div class="text-[11px] text-slate-500 flex items-center justify-end gap-1.5">
                                                    <span x-text="(b.transactions_count || 0) + ' nota'"></span>
                                                    <span x-show="b.anomaly_count > 0" class="text-rose-600 font-medium" x-text="'· ' + b.anomaly_count + ' error'"></span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium"
                                                    :class="b.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                                    :title="b.is_active ? 'Owner & kasir diizinkan login' : 'Login akun diblokir admin'">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="b.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                                    <span x-text="b.is_active ? 'Aktif' : 'Dibekukan'"></span>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <button type="button" @click="openActionPopup('client', b, $event)"
                                                    class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                    <span>Aksi</span>
                                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredAdminBusinesses.length === 0">
                                        <td colspan="6" class="p-8 text-center text-xs text-slate-400">Tidak ada bisnis klien yang cocok dengan filter atau pencarian.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Bar -->
                        <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 text-slate-500">
                                <span x-text="adminPaginationRangeLabel"></span>

                                <!-- Custom Per-Page Dropdown -->
                                <div class="relative" @click.outside="if (openDropdown === 'pageSize') openDropdown = null">
                                    <button type="button" @click="toggleDropdown('pageSize')"
                                        class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                        <span x-text="adminPerPage + ' / hal'"></span>
                                        <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openDropdown === 'pageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                        <template x-for="sz in [5, 10, 20, 50]" :key="sz">
                                            <button type="button" @click="adminPerPage = sz; adminPage = 1; openDropdown = null"
                                                class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50"
                                                :class="adminPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                <span x-text="sz + ' baris'"></span>
                                                <span x-show="adminPerPage === sz">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <button type="button" @click="if (adminPage > 1) adminPage--" :disabled="adminPage <= 1"
                                    class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">
                                    ‹ Prev
                                </button>
                                <template x-for="(pg, idx) in getPageNumbers(adminPage, adminTotalPages)" :key="'pg-' + idx + '-' + pg">
                                    <button type="button" @click="if (typeof pg === 'number') adminPage = pg" :disabled="typeof pg !== 'number'"
                                        class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                        :class="adminPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                        x-text="pg"></button>
                                </template>
                                <button type="button" @click="if (adminPage < adminTotalPages) adminPage++" :disabled="adminPage >= adminTotalPages"
                                    class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">
                                    Next ›
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBVIEW 2 (via Sidebar): KONSULTASI & PENGINGAT WHATSAPP SEKA GROUP (CONSISTENT TABLE + FILTER + PAGINATION) -->
                <div x-show="adminSubTab === 'billing'" class="flex-1 min-h-0 flex flex-col gap-3 overflow-y-auto lg:overflow-hidden">
                    <!-- Panel Pengingat Request Fitur & Status WhatsApp SEKA GROUP -->
                    <div class="shrink-0 bg-white border border-slate-200 rounded-lg px-4 sm:px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-semibold"
                                :class="(adminMetrics.consultation_pending_wa_count || 0) > 0 ? 'bg-amber-50 border border-amber-200 text-amber-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
                                <span class="w-2 h-2 rounded-full" :class="(adminMetrics.consultation_pending_wa_count || 0) > 0 ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                <span x-text="(adminMetrics.consultation_pending_wa_count || 0) > 0 ? ((adminMetrics.consultation_pending_wa_count || 0) + ' Request Menunggu Respon WA SEKA GROUP') : 'Semua Request Sudah Ditindaklanjuti via WA'"></span>
                            </span>
                            <span class="text-[11px] text-slate-500">Aturan pengajuan klien: minimal <b>H-3</b> sebelum jadwal konsultasi &amp; maksimal 1 pengajuan per 24 jam.</span>
                        </div>
                        <span class="text-[11px] text-slate-400" x-text="'Total tersimpan: ' + adminConsultations.length + ' pengajuan'"></span>
                    </div>

                    <div class="flex-1 min-h-[320px] lg:min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                        <div class="px-4 sm:px-5 py-3 bg-white border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900">Pengingat Konsultasi &amp; Request Penambahan Fitur Software Tandha</h3>
                                <p class="text-[11px] text-slate-500" x-text="consultPaginationRangeLabel"></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                                <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'consultFilter') openDropdown = null">
                                    <button type="button" @click="toggleDropdown('consultFilter')"
                                        class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center gap-2 sm:min-w-[190px] justify-between transition">
                                        <span class="truncate" x-text="activeConsultStatusLabel"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="openDropdown === 'consultFilter' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openDropdown === 'consultFilter'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                        <template x-for="opt in consultStatusOptions" :key="opt.value">
                                            <button type="button" @click="consultStatusFilter = opt.value; consultPage = 1; openDropdown = null"
                                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                                :class="consultStatusFilter === opt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                                <span x-text="opt.label"></span><span x-show="consultStatusFilter === opt.value">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative w-full sm:w-auto">
                                    <input type="text" x-model="consultSearch" @input="consultPage = 1" placeholder="Cari bisnis, kontak, catatan..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-56 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600">
                                    <button x-show="consultSearch" type="button" @click="consultSearch = ''; consultPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-h-0 overflow-auto custom-scrollbar">
                            <table class="w-full min-w-[680px] text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-500 text-[11px] font-semibold border-b border-slate-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-4">Klien &amp; Paket</th>
                                        <th class="py-2.5 px-4">Sektor &amp; Kontak WA</th>
                                        <th class="py-2.5 px-4">Jadwal (Min. H-3) &amp; Request Fitur</th>
                                        <th class="py-2.5 px-4">Status Pengingat WhatsApp</th>
                                        <th class="py-2.5 px-4 text-right w-20">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="c in paginatedConsultations" :key="c.id">
                                        <tr class="hover:bg-slate-50/70 align-top">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-900" x-text="c.business_name + ' (#KONSUL-' + c.id + ')'"></div>
                                                <div class="text-[11px] text-slate-500" x-text="c.client_name"></div>
                                                <div class="text-[10px] mt-1 inline-flex px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium" x-text="c.subscription_plan_snapshot || (c.source || '-')"></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-slate-800 font-medium" x-text="c.business_type"></div>
                                                <div class="text-[11px] text-slate-500" x-show="c.scale" x-text="c.scale"></div>
                                                <div class="text-[11px] font-tabular text-[#0D7A5F] mt-0.5" x-text="c.phone || c.email || '-'"></div>
                                            </td>
                                            <td class="py-3 px-4 max-w-md">
                                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                    <span class="font-semibold text-slate-900" x-text="c.preferred_consultation_at ? formatDate(c.preferred_consultation_at) : '-'"></span>
                                                    <span x-show="c.consultation_time_slot" class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-tabular text-[10px]" x-text="c.consultation_time_slot"></span>
                                                    <span x-show="c.days_until_consultation !== null && c.days_until_consultation !== undefined"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                                        :class="c.days_until_consultation <= 1 ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700'"
                                                        x-text="c.days_until_consultation >= 0 ? ('H-' + c.days_until_consultation) : 'Lewat Jadwal'"></span>
                                                </div>
                                                <div class="text-slate-700 leading-relaxed" x-text="c.template_gap_notes || c.notes || '-'"></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold text-[10px]"
                                                    :class="c.whatsapp_status === 'sent_from_sekayasa' ? 'bg-emerald-50 text-emerald-700' : (c.whatsapp_status === 'completed' ? 'bg-slate-900 text-white' : 'bg-amber-50 text-amber-800')"
                                                    x-text="c.whatsapp_status === 'sent_from_sekayasa' ? '✓ Sudah Terkirim di WA (SEKA GROUP)' : (c.whatsapp_status === 'completed' ? '✓ Selesai / Terjawab' : 'Menunggu WA SEKA GROUP')"></span>
                                                <div x-show="c.whatsapp_sent_at" class="text-[10px] text-slate-500 mt-1" x-text="(c.whatsapp_sent_by || 'SEKA GROUP') + ' · ' + formatDate(c.whatsapp_sent_at)"></div>
                                                <div x-show="c.reminder_notes" class="text-[11px] text-slate-600 mt-1 bg-slate-50 border border-slate-200 rounded px-2 py-1" x-text="c.reminder_notes"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <button type="button" @click="openActionPopup('consultation', c, $event)"
                                                    class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium text-[11px] transition">
                                                    <span>Aksi</span>
                                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredConsultations.length === 0">
                                        <td colspan="5" class="py-10 text-center text-xs text-slate-400">Belum ada pengajuan konsultasi fitur yang cocok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Canonical Pagination Bar -->
                        <div class="shrink-0 bg-white border-t border-slate-200 px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span x-text="consultPaginationRangeLabel"></span>
                            <div class="relative" @click.outside="if (openDropdown === 'consultPageSize') openDropdown = null">
                                <button type="button" @click="toggleDropdown('consultPageSize')"
                                    class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1.5">
                                    <span x-text="consultPerPage + ' / hal'"></span>
                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'consultPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                    <template x-for="sz in [5, 10, 20, 50]" :key="'cs-' + sz">
                                        <button type="button" @click="consultPerPage = sz; consultPage = 1; openDropdown = null"
                                            class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50"
                                            :class="consultPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                            <span x-text="sz + ' baris'"></span><span x-show="consultPerPage === sz">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="if (consultPage > 1) consultPage--" :disabled="consultPage <= 1" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">‹ Prev</button>
                            <template x-for="(pg, idx) in getPageNumbers(consultPage, consultTotalPages)" :key="'cpg2-' + idx + '-' + pg">
                                <button type="button" @click="if (typeof pg === 'number') consultPage = pg" :disabled="typeof pg !== 'number'"
                                    class="w-7 h-7 rounded text-[11px] font-semibold transition"
                                    :class="consultPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                    x-text="pg"></button>
                            </template>
                            <button type="button" @click="if (consultPage < consultTotalPages) consultPage++" :disabled="consultPage >= consultTotalPages" class="px-2.5 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition">Next ›</button>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- SUBVIEW 3 (via Sidebar): PELACAKAN ERROR SISTEM & LOG AKTIVITAS LINTAS KLIEN -->
                <div x-show="adminSubTab === 'logs'" class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto lg:overflow-hidden">
                    <!-- Filter Bar Log (Custom Designed Dropdowns) -->
                    <div class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs shrink-0">
                        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                            <!-- Custom Business Filter Dropdown -->
                            <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'logBusiness') openDropdown = null">
                                <button type="button" @click="toggleDropdown('logBusiness')"
                                    class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center justify-between gap-2 sm:min-w-[200px]">
                                    <span class="truncate" x-text="activeLogBusinessLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'logBusiness'" x-transition class="absolute left-0 mt-1.5 w-64 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                                    <button type="button" @click="adminLogBusinessFilter = ''; adminLogPage = 1; openDropdown = null"
                                        class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                        :class="!adminLogBusinessFilter ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                        <span>Semua Bisnis Klien</span>
                                        <span x-show="!adminLogBusinessFilter">✓</span>
                                    </button>
                                    <template x-for="b in adminBusinesses" :key="'lf-' + b.id">
                                        <button type="button" @click="adminLogBusinessFilter = String(b.id); adminLogPage = 1; openDropdown = null"
                                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                            :class="String(adminLogBusinessFilter) === String(b.id) ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                            <span class="truncate" x-text="b.name"></span>
                                            <span x-show="String(adminLogBusinessFilter) === String(b.id)">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Custom Log Category Filter Dropdown -->
                            <div class="relative flex-1 sm:flex-none" @click.outside="if (openDropdown === 'logCategory') openDropdown = null">
                                <button type="button" @click="toggleDropdown('logCategory')"
                                    class="w-full sm:w-auto px-3 py-1.5 bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-md text-xs font-medium text-slate-700 flex items-center justify-between gap-2 sm:min-w-[205px]">
                                    <span class="truncate" x-text="activeLogCategoryLabel"></span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="openDropdown === 'logCategory'" x-transition class="absolute left-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-lg shadow-lg z-40 py-1">
                                    <template x-for="catOpt in adminLogCategoryOptions" :key="catOpt.value">
                                        <button type="button" @click="adminLogCategoryFilter = catOpt.value; adminLogPage = 1; openDropdown = null"
                                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                            :class="adminLogCategoryFilter === catOpt.value ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                            <span x-text="catOpt.label"></span>
                                            <span x-show="adminLogCategoryFilter === catOpt.value">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="relative w-full sm:w-auto">
                                <input type="text" x-model="adminLogSearch" @input="adminLogPage = 1" placeholder="Cari event, user, email, payload..." class="pl-3 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs w-full sm:w-56 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600">
                                <button x-show="adminLogSearch" type="button" @click="adminLogSearch = ''; adminLogPage = 1" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-700 text-xs">✕</button>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-500" x-text="filteredAdminActivityLogs.length + ' log aktivitas · ' + adminErrorLogs.length + ' error server'"></span>
                    </div>

                    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-2 gap-4 lg:overflow-hidden">
                        <!-- Panel Kiri: Error Log Aplikasi Laravel -->
                        <div class="flex flex-col min-h-[280px] lg:min-h-0 bg-white rounded-lg border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 bg-white border-b border-slate-200 flex justify-between items-center shrink-0">
                                <span class="text-xs font-bold text-slate-900">Log Error Server (laravel.log)</span>
                                <span class="text-[11px] font-tabular text-slate-400" x-text="adminErrorLogs.length + ' entri'"></span>
                            </div>
                            <div class="flex-1 min-h-0 divide-y divide-slate-100 overflow-y-auto custom-scrollbar">
                                <template x-for="(err, idx) in adminErrorLogs" :key="idx">
                                    <div class="p-3.5 text-xs space-y-1">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="px-2 py-0.5 rounded font-semibold text-[10px] uppercase"
                                                :class="err.level === 'WARNING' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'"
                                                x-text="err.level"></span>
                                            <span class="font-tabular text-[10px] text-slate-400" x-text="err.timestamp"></span>
                                        </div>
                                        <div class="font-tabular text-[11px] text-slate-800 break-words" x-text="err.message"></div>
                                        <div x-show="err.context_line" class="font-tabular text-[10px] text-slate-400 break-all" x-text="err.context_line"></div>
                                    </div>
                                </template>
                                <div x-show="adminErrorLogs.length === 0" class="p-6 text-center text-xs text-slate-400">
                                    Tidak ada error tercatat di storage/logs/laravel.log.
                                </div>
                            </div>
                        </div>

                        <!-- Panel Kanan: Audit Trail & Transaksi Lintas Klien -->
                        <div class="flex flex-col min-h-[300px] lg:min-h-0 gap-4 lg:overflow-hidden">
                            <div class="flex-1 min-h-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                                <div class="px-4 py-3 bg-white border-b border-slate-200 flex justify-between items-center shrink-0">
                                    <span class="text-xs font-bold text-slate-900">Audit Trail Lintas Klien</span>
                                    <span class="text-[11px] text-slate-500" x-text="adminLogPaginationRangeLabel"></span>
                                </div>
                                <div class="flex-1 min-h-0 divide-y divide-slate-100 overflow-y-auto custom-scrollbar">
                                    <template x-for="log in paginatedAdminActivityLogs" :key="log.id">
                                        <div class="p-3 text-xs space-y-1">
                                            <div class="flex justify-between items-start gap-2">
                                                <div>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase"
                                                        :class="['login_failed','pos_checkout_failed','pos_void_transaction','system_exception'].includes(log.action) ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700'"
                                                        x-text="log.action"></span>
                                                    <span class="text-slate-700 font-medium" x-text="log.business ? ' · ' + log.business.name : ' · Platform'"></span>
                                                </div>
                                                <span class="font-tabular text-[10px] text-slate-400 shrink-0" x-text="formatDate(log.created_at)"></span>
                                            </div>
                                            <div class="text-[11px] text-slate-500" x-text="(log.user ? log.user.name + ' (' + log.user.email + ')' : 'Guest/System') + ' · IP: ' + (log.ip_address || '-')"></div>
                                            <div x-show="log.payload && Object.keys(log.payload).length > 0" class="font-tabular text-[10px] bg-slate-50 border border-slate-100 rounded px-2 py-1 text-slate-600 break-all" x-text="JSON.stringify(log.payload)"></div>
                                        </div>
                                    </template>
                                    <div x-show="filteredAdminActivityLogs.length === 0" class="p-5 text-center text-xs text-slate-400">Belum ada catatan aktivitas yang cocok.</div>
                                </div>
                                <!-- Canonical Pagination Bar -->
                                <div class="shrink-0 bg-white border-t border-slate-200 px-4 py-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="text-[11px]" x-text="adminLogPaginationRangeLabel"></span>
                                        <div class="relative" @click.outside="if (openDropdown === 'adminLogPageSize') openDropdown = null">
                                            <button type="button" @click="toggleDropdown('adminLogPageSize')" class="px-2 py-1 bg-slate-50 border border-slate-200 rounded text-[11px] font-medium text-slate-700 flex items-center gap-1">
                                                <span x-text="adminLogPerPage + ' / hal'"></span>
                                                <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            <div x-show="openDropdown === 'adminLogPageSize'" x-transition class="absolute bottom-full left-0 mb-1.5 w-24 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1">
                                                <template x-for="sz in [5, 10, 20, 50]" :key="'alg-' + sz">
                                                    <button type="button" @click="adminLogPerPage = sz; adminLogPage = 1; openDropdown = null" class="w-full px-3 py-1.5 text-left text-[11px] flex justify-between hover:bg-slate-50" :class="adminLogPerPage === sz ? 'font-semibold text-emerald-700 bg-slate-50' : 'text-slate-700'">
                                                        <span x-text="sz + ' baris'"></span><span x-show="adminLogPerPage === sz">✓</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="if (adminLogPage > 1) adminLogPage--" :disabled="adminLogPage <= 1" class="px-2 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition text-[11px]">‹ Prev</button>
                                        <template x-for="(pg, idx) in getPageNumbers(adminLogPage, adminLogTotalPages)" :key="'alpg-' + idx + '-' + pg">
                                            <button type="button" @click="if (typeof pg === 'number') adminLogPage = pg" :disabled="typeof pg !== 'number'"
                                                class="w-6 h-6 rounded text-[11px] font-semibold transition"
                                                :class="adminLogPage === pg ? 'bg-slate-900 text-white' : (typeof pg === 'number' ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default')"
                                                x-text="pg"></button>
                                        </template>
                                        <button type="button" @click="if (adminLogPage < adminLogTotalPages) adminLogPage++" :disabled="adminLogPage >= adminLogTotalPages" class="px-2 py-1 rounded border border-slate-200 bg-white text-slate-600 font-medium disabled:opacity-40 hover:bg-slate-50 transition text-[11px]">Next ›</button>
                                    </div>
                                </div>
                            </div>

                            <div class="h-44 shrink-0 flex flex-col bg-white rounded-lg border border-slate-200 overflow-hidden">
                                <div class="px-4 py-2.5 bg-white border-b border-slate-200 flex justify-between items-center shrink-0">
                                    <span class="text-xs font-bold text-slate-900">Transaksi Terakhir Lintas Klien</span>
                                </div>
                                <div class="flex-1 min-h-0 divide-y divide-slate-100 overflow-y-auto custom-scrollbar">
                                    <template x-for="tx in adminRecentTransactions" :key="tx.id">
                                        <div class="p-2.5 px-4 text-xs flex justify-between items-center gap-2">
                                            <div>
                                                <div class="font-medium text-slate-900" x-text="(tx.business ? tx.business.name : '-') + ' — ' + tx.invoice_number"></div>
                                                <div class="text-[11px] text-slate-500" x-text="(tx.outlet ? tx.outlet.name : '') + ' · Kasir: ' + (tx.cashier ? tx.cashier.name : '-')"></div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-tabular font-semibold text-slate-900" x-text="formatRupiah(tx.total_amount)"></div>
                                                <span class="text-[10px] uppercase font-medium" :class="tx.payment_status === 'voided' ? 'text-rose-600' : 'text-emerald-600'" x-text="tx.payment_status"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="adminRecentTransactions.length === 0" class="p-5 text-center text-xs text-slate-400">Belum ada transaksi klien.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Stock Adjustment Modal (Custom Dropdowns) -->
    <div x-show="showAdjustmentModal" @click.self="showAdjustmentModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-sm w-full p-6 border">
            <h3 class="text-sm font-bold text-[#0D2620] mb-3">Penyesuaian Stok</h3>
            <form @submit.prevent="submitAdjustment()" class="space-y-3 text-xs">
                <div class="relative" @click.outside="if (openDropdown === 'adjProd') openDropdown = null">
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Produk</label>
                    <button type="button" @click="toggleDropdown('adjProd')"
                        class="w-full px-3 py-2 bg-white border border-[rgba(15,23,42,0.14)] rounded flex items-center justify-between text-left font-semibold text-[#0D2620]">
                        <span class="truncate" x-text="selectedAdjProductLabel"></span>
                        <svg class="w-3.5 h-3.5 text-[#3D544E] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="openDropdown === 'adjProd'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-[rgba(13,38,32,0.16)] rounded-md shadow-xl z-50 max-h-48 overflow-y-auto custom-scrollbar py-1">
                        <template x-for="p in productList" :key="p.id">
                            <button type="button" @click="adjProduct = p.id; openDropdown = null"
                                class="w-full px-3 py-2 text-left flex justify-between hover:bg-[#F7FAF9]"
                                :class="String(adjProduct) === String(p.id) ? 'bg-[#E6F4EF]/70 font-bold text-[#0D7A5F]' : 'text-[#0D2620]'">
                                <span class="truncate" x-text="p.name + ' (Stok: ' + p.current_stock + ')'"></span>
                                <span x-show="String(adjProduct) === String(p.id)">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Jenis Mutasi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="adjType = 'adjustment_add'" class="py-2 rounded border font-bold transition"
                            :class="adjType === 'adjustment_add' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-[rgba(13,38,32,0.14)] text-[#0D2620]'">+ Tambah Stok</button>
                        <button type="button" @click="adjType = 'adjustment_sub'" class="py-2 rounded border font-bold transition"
                            :class="adjType === 'adjustment_sub' ? 'bg-rose-600 border-rose-600 text-white' : 'bg-white border-[rgba(13,38,32,0.14)] text-[#0D2620]'">- Kurangi Stok</button>
                    </div>
                </div>
                <input type="number" x-model.number="adjQty" required min="1" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded font-bold">
                <input type="text" x-model="adjReason" required placeholder="Alasan audit (wajib)" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showAdjustmentModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-[#F7FAF9] rounded font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Product Modal (Image Required) -->
    <div x-show="showCreateProductModal" @click.self="showCreateProductModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h3 class="text-sm font-bold text-[#0D2620] mb-1">Tambah Produk Baru</h3>
            <p class="text-[11px] text-[#475569] mb-3">Unggah gambar produk wajib diisi agar kasir lebih mudah memilih menu di layar POS.</p>
            <form @submit.prevent="submitCreateProduct()" class="space-y-3 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Foto / Gambar Produk (Wajib) *</label>
                    <div class="flex items-center gap-3 p-2.5 bg-white border border-dashed border-[#0D7A5F]/40 rounded-md">
                        <img :src="newProductPreview || '/assets/products/default-product.svg'" alt="Preview" class="w-14 h-14 rounded object-cover border border-slate-200 bg-slate-50 shrink-0">
                        <div class="flex-1 min-w-0">
                            <input id="create-product-image" type="file" accept="image/png,image/jpeg,image/webp" required @change="onCreateProductFileChange($event)"
                                class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-[#E6F4EF] file:text-[#0D7A5F] hover:file:bg-[#0D7A5F] hover:file:text-white cursor-pointer">
                            <span class="block text-[10px] text-slate-400 mt-1">Format JPG, PNG, WEBP maksimal 2MB</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Nama Produk *</label>
                    <input type="text" x-model="newProduct.name" required placeholder="Contoh: Es Kopi Susu Aren" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="relative" @click.outside="if (openDropdown === 'createProdCat') openDropdown = null">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[10px] font-bold uppercase text-[#3D544E]">Kategori</label>
                            <button type="button" @click="openCreateCategoryModal()" class="text-[10px] font-bold text-[#0D7A5F] hover:underline">+ Buat Kategori</button>
                        </div>
                        <button type="button" @click="toggleDropdown('createProdCat')"
                            class="w-full px-3 py-2 bg-white border border-slate-200 hover:border-[#0D7A5F] rounded text-left text-xs text-slate-800 flex items-center justify-between gap-2 transition">
                            <span class="truncate" x-text="selectedCreateProdCatLabel"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openDropdown === 'createProdCat'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-xl z-50 max-h-44 overflow-y-auto custom-scrollbar py-1">
                            <button type="button" @click="newProduct.category_id = ''; openDropdown = null"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="!newProduct.category_id ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                <span x-text="categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori'"></span><span x-show="!newProduct.category_id">✓</span>
                            </button>
                            <template x-for="cat in categories" :key="'nc-' + cat.id">
                                <button type="button" @click="newProduct.category_id = cat.id; openDropdown = null"
                                    class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="String(newProduct.category_id) === String(cat.id) ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                    <span class="truncate" x-text="cat.name"></span>
                                    <span x-show="String(newProduct.category_id) === String(cat.id)">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Stok Awal</label>
                        <input type="number" x-model.number="newProduct.initial_stock" min="0" placeholder="50" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" x-model="newProduct.sku" placeholder="SKU (Opsional)" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    <input type="text" x-model="newProduct.barcode" placeholder="Barcode (Opsional)" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Harga Beli / Modal *</label>
                        <input type="number" x-model.number="newProduct.buy_price" required min="0" placeholder="Harga Beli" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Harga Jual *</label>
                        <input type="number" x-model.number="newProduct.sell_price" required min="0" placeholder="Harga Jual" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCreateProductModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-[#F7FAF9] rounded font-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div x-show="showEditProductModal" @click.self="showEditProductModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h3 class="text-sm font-bold text-[#0D2620] mb-3">Edit Produk &amp; Gambar</h3>
            <form @submit.prevent="submitEditProduct()" class="space-y-3 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Ganti Gambar Produk (Opsional)</label>
                    <div class="flex items-center gap-3 p-2.5 bg-white border border-slate-200 rounded-md">
                        <img :src="editProductPreview || '/assets/products/default-product.svg'" alt="Preview" class="w-14 h-14 rounded object-cover border border-slate-200 bg-slate-50 shrink-0">
                        <div class="flex-1 min-w-0">
                            <input id="edit-product-image" type="file" accept="image/png,image/jpeg,image/webp" @change="onEditProductFileChange($event)"
                                class="block w-full text-[11px] text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-[#E6F4EF] file:text-[#0D7A5F] cursor-pointer">
                            <span class="block text-[10px] text-slate-400 mt-1">Biarkan kosong jika tidak ingin mengganti foto.</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Nama Produk *</label>
                    <input type="text" x-model="editProduct.name" required class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                </div>
                <div class="relative" @click.outside="if (openDropdown === 'editProdCat') openDropdown = null">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E]">Kategori</label>
                        <button type="button" @click="openCreateCategoryModal()" class="text-[10px] font-bold text-[#0D7A5F] hover:underline">+ Buat Kategori</button>
                    </div>
                    <button type="button" @click="toggleDropdown('editProdCat')"
                        class="w-full px-3 py-2 bg-white border border-slate-200 hover:border-[#0D7A5F] rounded text-left text-xs text-slate-800 flex items-center justify-between gap-2 transition">
                        <span class="truncate" x-text="selectedEditProdCatLabel"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="openDropdown === 'editProdCat'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-xl z-50 max-h-44 overflow-y-auto custom-scrollbar py-1">
                        <button type="button" @click="editProduct.category_id = ''; openDropdown = null"
                            class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                            :class="!editProduct.category_id ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                            <span x-text="categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori'"></span><span x-show="!editProduct.category_id">✓</span>
                        </button>
                        <template x-for="cat in categories" :key="'ec-' + cat.id">
                            <button type="button" @click="editProduct.category_id = cat.id; openDropdown = null"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="String(editProduct.category_id) === String(cat.id) ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                <span class="truncate" x-text="cat.name"></span>
                                <span x-show="String(editProduct.category_id) === String(cat.id)">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" x-model="editProduct.sku" placeholder="SKU" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    <input type="text" x-model="editProduct.barcode" placeholder="Barcode" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Harga Beli</label>
                        <input type="number" x-model.number="editProduct.buy_price" required min="0" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Harga Jual</label>
                        <input type="number" x-model.number="editProduct.sell_price" required min="0" class="w-full px-3 py-2 bg-[#FFFFFF] border border-[rgba(15,23,42,0.12)] rounded">
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showEditProductModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-[#F7FAF9] rounded font-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Staff Modal (Owner Only) -->
    <div x-show="showCreateStaffModal" @click.self="showCreateStaffModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h3 class="text-sm font-bold text-[#0D2620] mb-1">Tambah Akun Staf Baru</h3>
            <p class="text-[11px] text-[#475569] mb-3" x-text="'Kuota saat ini: ' + (staffLimits.used_users || 0) + ' / ' + (staffLimits.max_users || 0) + ' pengguna'"></p>
            <form @submit.prevent="submitCreateStaff()" class="space-y-3 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Peran Staf *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="newStaff.role = 'cashier'"
                            class="p-2.5 rounded border text-left transition"
                            :class="newStaff.role === 'cashier' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-slate-200 text-[#0D2620]'">
                            <div class="font-bold">Kasir POS</div>
                            <div class="text-[10px] opacity-85">Hanya layar kasir &amp; shift</div>
                        </button>
                        <button type="button" @click="newStaff.role = 'admin'"
                            class="p-2.5 rounded border text-left transition"
                            :class="newStaff.role === 'admin' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-slate-200 text-[#0D2620]'">
                            <div class="font-bold">Admin (Opsional)</div>
                            <div class="text-[10px] opacity-85">Produk, stok &amp; laporan omzet</div>
                        </button>
                    </div>
                </div>
                <div class="relative" @click.outside="if (openDropdown === 'createStaffOutlet') openDropdown = null">
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Outlet Penugasan *</label>
                    <button type="button" @click="toggleDropdown('createStaffOutlet')"
                        class="w-full px-3 py-2 bg-white border border-slate-200 hover:border-[#0D7A5F] rounded text-left text-xs text-slate-800 flex items-center justify-between gap-2 transition">
                        <span class="truncate" x-text="selectedCreateStaffOutletLabel"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="openDropdown === 'createStaffOutlet'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-xl z-50 max-h-44 overflow-y-auto custom-scrollbar py-1">
                        <template x-for="o in outlets" :key="'so-' + o.id">
                            <button type="button" @click="newStaff.outlet_id = o.id; openDropdown = null"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="String(newStaff.outlet_id) === String(o.id) ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                <span class="truncate" x-text="o.name"></span>
                                <span x-show="String(newStaff.outlet_id) === String(o.id)">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
                <input type="text" x-model="newStaff.name" required placeholder="Nama Lengkap Staf" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <input type="email" x-model="newStaff.email" required placeholder="Email Login Staf (contoh: kasir@toko.id)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <input type="text" x-model="newStaff.phone" placeholder="No. WhatsApp (Opsional)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <input type="password" x-model="newStaff.password" required minlength="6" placeholder="Kata Sandi Login (Min. 6 karakter)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCreateStaffModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-white rounded font-bold">Buat Akun Staf</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal (Owner Only) -->
    <div x-show="showEditStaffModal" @click.self="showEditStaffModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border">
            <h3 class="text-sm font-bold text-[#0D2620] mb-3">Edit Akses Staf</h3>
            <form @submit.prevent="submitEditStaff()" class="space-y-3 text-xs">
                <input type="text" x-model="editStaff.name" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <div class="grid grid-cols-2 gap-2">
                    <div class="relative" @click.outside="if (openDropdown === 'editStaffRole') openDropdown = null">
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Peran</label>
                        <button type="button" @click="toggleDropdown('editStaffRole')"
                            class="w-full px-3 py-2 bg-white border border-slate-200 hover:border-[#0D7A5F] rounded text-left text-xs text-slate-800 flex items-center justify-between gap-2 transition">
                            <span class="truncate" x-text="editStaff.role === 'admin' ? 'Admin (Opsional)' : 'Kasir POS'"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openDropdown === 'editStaffRole'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-xl z-50 py-1">
                            <button type="button" @click="editStaff.role = 'cashier'; openDropdown = null"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="editStaff.role === 'cashier' ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                <span>Kasir POS</span><span x-show="editStaff.role === 'cashier'">✓</span>
                            </button>
                            <button type="button" @click="editStaff.role = 'admin'; openDropdown = null"
                                class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                :class="editStaff.role === 'admin' ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                <span>Admin (Opsional)</span><span x-show="editStaff.role === 'admin'">✓</span>
                            </button>
                        </div>
                    </div>
                    <div class="relative" @click.outside="if (openDropdown === 'editStaffOutlet') openDropdown = null">
                        <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Outlet</label>
                        <button type="button" @click="toggleDropdown('editStaffOutlet')"
                            class="w-full px-3 py-2 bg-white border border-slate-200 hover:border-[#0D7A5F] rounded text-left text-xs text-slate-800 flex items-center justify-between gap-2 transition">
                            <span class="truncate" x-text="selectedEditStaffOutletLabel"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openDropdown === 'editStaffOutlet'" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-xl z-50 max-h-44 overflow-y-auto custom-scrollbar py-1">
                            <template x-for="o in outlets" :key="'eo-' + o.id">
                                <button type="button" @click="editStaff.outlet_id = o.id; openDropdown = null"
                                    class="w-full px-3 py-2 text-left text-xs flex justify-between hover:bg-slate-50"
                                    :class="String(editStaff.outlet_id) === String(o.id) ? 'font-semibold text-[#0D7A5F] bg-emerald-50/50' : 'text-slate-700'">
                                    <span class="truncate" x-text="o.name"></span>
                                    <span x-show="String(editStaff.outlet_id) === String(o.id)">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <label class="flex items-center gap-2 p-2.5 bg-white border border-slate-200 rounded cursor-pointer">
                    <input type="checkbox" x-model="editStaff.is_active" class="accent-[#0D7A5F]">
                    <span class="font-semibold text-[#0D2620]">Akun Staf Aktif (Diizinkan Login)</span>
                </label>
                <input type="password" x-model="editStaff.password" minlength="6" placeholder="Kata Sandi Baru (Kosongkan jika tidak ganti)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showEditStaffModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-white rounded font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create / Edit Category Modal -->
    <div x-show="showCategoryModal" @click.self="showCategoryModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border">
            <h3 class="text-sm font-bold text-[#0D2620] mb-1" x-text="categoryForm.id ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
            <p class="text-[11px] text-slate-500 mb-3">Kategori akan langsung tersedia di daftar produk, filter laporan, dan tab kasir POS.</p>
            <form @submit.prevent="submitCategoryForm()" class="space-y-3 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Nama Kategori *</label>
                    <input type="text" x-model="categoryForm.name" required placeholder="Contoh: Kopi Panas / Perawatan Rambut / Obat Bebas" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-[#3D544E] mb-1">Deskripsi (Opsional)</label>
                    <textarea x-model="categoryForm.description" rows="2" placeholder="Catatan singkat pengelompokan kategori" class="w-full px-3 py-2 bg-white border border-slate-200 rounded"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCategoryModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-white rounded font-bold">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Outlet Modal (Owner Only) -->
    <div x-show="showCreateOutletModal" @click.self="showCreateOutletModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full p-6 border">
            <h3 class="text-sm font-bold text-[#0D2620] mb-3">Tambah Outlet Cabang Baru</h3>
            <form @submit.prevent="submitCreateOutlet()" class="space-y-3 text-xs">
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <input type="text" x-model="newOutlet.name" required placeholder="Nama Outlet / Cabang *" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                    </div>
                    <input type="text" x-model="newOutlet.outlet_code" placeholder="Kode (OUT-02)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded font-tabular uppercase">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" x-model="newOutlet.city" placeholder="Kota Cabang" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                    <input type="text" x-model="newOutlet.phone" placeholder="No. Telepon Cabang" class="w-full px-3 py-2 bg-white border border-slate-200 rounded">
                </div>
                <textarea x-model="newOutlet.address" rows="2" placeholder="Alamat Lengkap Outlet" class="w-full px-3 py-2 bg-white border border-slate-200 rounded"></textarea>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCreateOutletModal = false" class="flex-1 py-2 bg-[#FDF3EE] text-[#475569] rounded font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] text-white rounded font-bold">Simpan Outlet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Outlet & Per-Branch Receipt/Tax/POS Overrides Modal (Owner Only) -->
    <div x-show="showEditOutletModal" @click.self="showEditOutletModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-2xl w-full border overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-[#0F1F1B] text-white px-5 py-4 flex items-center justify-between shrink-0">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400">Pengaturan Khusus Cabang</div>
                    <h3 class="text-sm font-bold" x-text="editOutletForm.name || 'Edit Outlet'"></h3>
                </div>
                <button type="button" @click="showEditOutletModal = false" class="text-xs text-slate-300 hover:text-white">✕</button>
            </div>

            <form @submit.prevent="submitEditOutlet()" class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-4 text-xs">
                <div class="bg-white p-4 rounded-lg border border-slate-200 space-y-3">
                    <div class="font-bold text-slate-900">1. Identitas &amp; Alamat Cabang</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Nama Cabang *</label>
                            <input type="text" x-model="editOutletForm.name" required class="w-full px-3 py-2 border border-slate-200 rounded">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Kode Cabang</label>
                            <input type="text" x-model="editOutletForm.outlet_code" placeholder="OUT-01" class="w-full px-3 py-2 border border-slate-200 rounded font-tabular uppercase">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Kota</label>
                            <input type="text" x-model="editOutletForm.city" class="w-full px-3 py-2 border border-slate-200 rounded">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">No. Telepon Cabang</label>
                            <input type="text" x-model="editOutletForm.phone" class="w-full px-3 py-2 border border-slate-200 rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Alamat Lengkap Cabang</label>
                        <textarea x-model="editOutletForm.address" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded"></textarea>
                    </div>
                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                        <input type="checkbox" x-model="editOutletForm.is_active" class="accent-[#0D7A5F]">
                        <span class="font-semibold text-slate-800">Cabang Aktif Beroperasi</span>
                    </label>
                </div>

                <div class="bg-white p-4 rounded-lg border border-slate-200 space-y-3">
                    <div>
                        <div class="font-bold text-slate-900">2. Kustomisasi Struk &amp; Pajak Khusus Cabang Ini</div>
                        <p class="text-[11px] text-slate-500">Kosongkan kolom bila cabang ini mengikuti pengaturan bisnis pusat.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Teks Header / Promo Atas Struk Cabang (Opsional)</label>
                            <input type="text" x-model="editOutletForm.receipt_header" placeholder="Contoh: Cabang Dago · Promo Happy Hour 14-17" class="w-full px-3 py-2 border border-slate-200 rounded">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pajak Khusus (%)</label>
                            <input type="number" step="0.5" min="0" max="100" x-model="editOutletForm.tax_percentage" :placeholder="'Pusat (' + (business.tax_percentage ?? 0) + '%)'" class="w-full px-3 py-2 border border-slate-200 rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Catatan Bawah Struk Cabang (Opsional)</label>
                        <input type="text" x-model="editOutletForm.receipt_footer" :placeholder="business.receipt_footer || 'Ikut catatan pusat'" class="w-full px-3 py-2 border border-slate-200 rounded">
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border border-slate-200 space-y-2.5">
                    <div>
                        <div class="font-bold text-slate-900">3. Penyesuaian Tombol Kasir Khusus Cabang Ini</div>
                        <p class="text-[11px] text-slate-500">Atur berbeda dari pusat bila outlet ini punya aturan kasir khusus.</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="item in cashierFeatureToggleMeta" :key="'ov-' + item.key">
                            <div class="py-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium text-slate-800" x-text="item.title"></span>
                                <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5 text-[11px] font-semibold">
                                    <button type="button" @click="editOutletForm.feature_overrides[item.key] = null"
                                        class="px-2 py-1 rounded transition"
                                        :class="editOutletForm.feature_overrides[item.key] === null || editOutletForm.feature_overrides[item.key] === undefined ? 'bg-slate-900 text-white' : 'text-slate-600'">Ikut Pusat</button>
                                    <button type="button" @click="editOutletForm.feature_overrides[item.key] = true"
                                        class="px-2 py-1 rounded transition"
                                        :class="editOutletForm.feature_overrides[item.key] === true ? 'bg-[#0D7A5F] text-white' : 'text-slate-600'">Aktif</button>
                                    <button type="button" @click="editOutletForm.feature_overrides[item.key] = false"
                                        class="px-2 py-1 rounded transition"
                                        :class="editOutletForm.feature_overrides[item.key] === false ? 'bg-rose-600 text-white' : 'text-slate-600'">Nonaktif</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" @click="showEditOutletModal = false" class="flex-1 py-2.5 bg-slate-200 text-slate-700 rounded-md font-bold">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md font-bold transition">Simpan Pengaturan Cabang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unified Floating Action Popover Menu (Sama persis untuk semua tabel & role) -->
    <div x-show="showActionPopup" @click="showActionPopup = false" class="fixed inset-0 z-[60]" x-cloak></div>
    <div x-show="showActionPopup" x-transition.opacity.duration.120ms :style="actionMenuStyle"
        class="tandha-modal-card fixed z-[70] w-52 bg-white rounded-lg border py-1.5 text-xs" x-cloak>
        <div class="px-3 py-1.5 border-b border-slate-100">
            <div class="font-semibold text-slate-900 truncate" x-text="actionTargetTitle"></div>
            <div class="text-[10px] text-slate-400 truncate" x-text="actionTargetSubtitle"></div>
        </div>

        <!-- Super Admin: Klien -->
        <template x-if="actionContext === 'client'">
            <div>
                <button type="button" @click="runActionFromPopup('activate')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span x-text="actionTarget && actionTarget.subscription_summary && actionTarget.subscription_summary.is_active ? 'Perpanjang / Paket' : 'Aktifkan Langganan'"></span>
                </button>
                <button type="button" @click="runActionFromPopup('detail')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Lihat Detail Klien</span>
                </button>
                <button type="button" @click="runActionFromPopup('toggle')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span x-text="actionTarget && actionTarget.is_active ? 'Bekukan Akses' : 'Buka Blokir Akses'"></span>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runActionFromPopup('delete')"
                    class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 font-medium flex items-center justify-between transition">
                    <span>Hapus Klien</span>
                </button>
            </div>
        </template>

        <!-- Produk -->
        <template x-if="actionContext === 'product'">
            <div>
                <button type="button" @click="runActionFromPopup('edit_product')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Edit Produk</span>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runActionFromPopup('archive_product')"
                    class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 font-medium flex items-center justify-between transition">
                    <span>Arsipkan Produk</span>
                </button>
            </div>
        </template>

        <!-- Kategori -->
        <template x-if="actionContext === 'category'">
            <div>
                <button type="button" @click="runActionFromPopup('view_category_products')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Lihat Daftar Produk</span>
                </button>
                <button type="button" @click="runActionFromPopup('edit_category')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Edit Kategori</span>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runActionFromPopup('delete_category')"
                    class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 font-medium flex items-center justify-between transition">
                    <span>Hapus Kategori</span>
                </button>
            </div>
        </template>

        <!-- Staf / Tim -->
        <template x-if="actionContext === 'staff'">
            <div>
                <button type="button" @click="runActionFromPopup('edit_staff')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Edit Staf &amp; Role</span>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runActionFromPopup('delete_staff')"
                    class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 font-medium flex items-center justify-between transition">
                    <span>Hapus Akun Staf</span>
                </button>
            </div>
        </template>

        <!-- Outlet / Cabang -->
        <template x-if="actionContext === 'outlet'">
            <div>
                <button type="button" @click="runActionFromPopup('preview_outlet')"
                    class="w-full px-3 py-2 text-left text-[#0D7A5F] font-semibold hover:bg-emerald-50/70 flex items-center justify-between transition">
                    <span>Preview Kasir Cabang</span>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" @click="runActionFromPopup('edit_outlet')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Atur Cabang, Struk &amp; Kasir</span>
                </button>
            </div>
        </template>

        <!-- Super Admin: Konsultasi Fitur & WhatsApp Reminder -->
        <template x-if="actionContext === 'consultation'">
            <div>
                <button type="button" @click="runActionFromPopup('wa_client')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Balas via WhatsApp Klien</span>
                </button>
                <button type="button" @click="runActionFromPopup('mark_wa_sent')"
                    class="w-full px-3 py-2 text-left text-[#0D7A5F] font-semibold hover:bg-emerald-50/70 flex items-center justify-between transition">
                    <span>Tandai: Terkirim WA (SEKA GROUP)</span>
                </button>
                <button type="button" @click="runActionFromPopup('mark_wa_completed')"
                    class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                    <span>Tandai: Konsultasi Selesai</span>
                </button>
                <template x-if="actionTarget && actionTarget.business_id">
                    <div>
                        <div class="border-t border-slate-100 my-1"></div>
                        <button type="button" @click="runActionFromPopup('consultation_detail')"
                            class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center justify-between transition">
                            <span>Lihat Detail Bisnis Klien</span>
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Client Detail & Subscription Inspection Modal (Super Admin) -->
    <div x-show="showClientDetailModal" @click.self="showClientDetailModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-4xl w-full max-h-[90vh] flex flex-col border overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-[#0D2620] text-white px-6 py-4 flex items-center justify-between gap-4 shrink-0">
                <div>
                    <div class="text-[10px] font-bold text-[#34D399] uppercase tracking-wider">Inspeksi Lengkap Klien Multi-Tenant</div>
                    <h3 class="text-base font-bold" x-text="selectedClientDetail?.business?.name || 'Memuat...'"></h3>
                    <div class="text-[11px] text-[#CBD5E1]" x-show="selectedClientDetail?.business?.id"
                        x-text="(selectedClientDetail?.business?.owner_name || '-') + ' • ' + (selectedClientDetail?.business?.email || '-') + (selectedClientDetail?.business?.phone ? ' • ' + selectedClientDetail.business.phone : '')"></div>
                </div>
                <button type="button" @click="showClientDetailModal = false" class="px-3 py-1.5 bg-[#143B32] text-[#CBD5E1] hover:text-white rounded text-xs font-semibold">Tutup ✕</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto custom-scrollbar space-y-5 text-xs" x-show="selectedClientDetail?.business?.id">
                <!-- Subscription & Quick Extend Card (Visual Pill Controls, Zero Native Select) -->
                <div class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md p-4 grid grid-cols-1 lg:grid-cols-2 gap-4 items-center">
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold uppercase text-[#0D7A5F]">Status Langganan &amp; Masa Aktif</div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-bold text-[#0D2620]" x-text="selectedClientDetail?.subscription_summary?.plan_name || '-'"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                :class="selectedClientDetail?.subscription_summary?.lifecycle_status === 'expired' ? 'bg-rose-100 text-rose-700' : (selectedClientDetail?.subscription_summary?.is_expiring_soon ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                                x-text="selectedClientDetail?.subscription_summary?.countdown_label || '-'"></span>
                        </div>
                        <div class="text-[#475569]">
                            Mulai: <b class="font-tabular" x-text="selectedClientDetail?.subscription_summary?.starts_at_formatted || '-'"></b>
                            <span class="mx-1.5">•</span>
                            Berlaku s.d.: <b class="font-tabular text-[#0D2620]" x-text="selectedClientDetail?.subscription_summary?.expires_at_formatted || '-'"></b>
                        </div>
                        <div class="text-[11px] text-[#647C76]"
                            x-text="'Outlet: ' + (selectedClientDetail?.business?.outlets_count || 0) + ' • Staf: ' + (selectedClientDetail?.business?.users_count || 0) + ' • Produk: ' + (selectedClientDetail?.business?.products_count || 0) + ' • NPWP: ' + (selectedClientDetail?.business?.npwp || '-')"></div>
                    </div>

                    <form @submit.prevent="submitExtendSubscription()" class="bg-[#F7FAF9] border border-[rgba(13,38,32,0.1)] rounded p-3 space-y-2.5">
                        <div class="text-[11px] font-bold text-[#0D2620]">Atur / Perpanjang Langganan Klien Ini</div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="pl in adminPlans" :key="'ext-' + pl.id">
                                <button type="button" @click="extendForm.plan_id = pl.id"
                                    class="px-2.5 py-1 rounded border text-[11px] font-semibold transition"
                                    :class="String(extendForm.plan_id) === String(pl.id) ? 'bg-[#0D2620] border-[#0D2620] text-[#34D399]' : 'bg-white border-[rgba(13,38,32,0.15)] text-[#0D2620]'"
                                    x-text="pl.name"></button>
                            </template>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="opt in adminDurationOptions" :key="'ed-' + opt.days">
                                    <button type="button" @click="extendForm.days = opt.days"
                                        class="px-2 py-1 rounded border text-[11px] font-semibold transition"
                                        :class="extendForm.days === opt.days ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-[rgba(13,38,32,0.15)] text-[#0D2620]'"
                                        x-text="'+' + opt.label"></button>
                                </template>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" @click="extendForm.status = 'trial'" class="px-2 py-1 rounded border text-[11px] font-semibold"
                                    :class="extendForm.status === 'trial' ? 'bg-sky-600 border-sky-600 text-white' : 'bg-white border-[rgba(13,38,32,0.15)] text-[#0D2620]'">Trial</button>
                                <button type="button" @click="extendForm.status = 'active'" class="px-2 py-1 rounded border text-[11px] font-semibold"
                                    :class="extendForm.status === 'active' ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-[rgba(13,38,32,0.15)] text-[#0D2620]'">Aktif</button>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-1.5 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded font-bold transition">
                            Terapkan Perpanjangan Masa Aktif
                        </button>
                    </form>
                </div>

                <!-- Modal Inner Subtabs -->
                <div class="inline-flex bg-white border border-[rgba(13,38,32,0.12)] rounded-md p-1 gap-1 font-semibold">
                    <button type="button" @click="clientDetailTab = 'logs'" :class="clientDetailTab === 'logs' ? 'bg-[#0D2620] text-[#34D399]' : 'text-[#3D544E]'" class="px-3 py-1 rounded">
                        Log Aktivitas &amp; Error (<span x-text="(selectedClientDetail.activity_logs || []).length"></span>)
                    </button>
                    <button type="button" @click="clientDetailTab = 'staff'" :class="clientDetailTab === 'staff' ? 'bg-[#0D2620] text-[#34D399]' : 'text-[#3D544E]'" class="px-3 py-1 rounded">
                        Outlet &amp; Staf (<span x-text="(selectedClientDetail.users || []).length"></span>)
                    </button>
                    <button type="button" @click="clientDetailTab = 'payments'" :class="clientDetailTab === 'payments' ? 'bg-[#0D2620] text-[#34D399]' : 'text-[#3D544E]'" class="px-3 py-1 rounded">
                        Tagihan (<span x-text="(selectedClientDetail.payments || []).length"></span>)
                    </button>
                    <button type="button" @click="clientDetailTab = 'tx'" :class="clientDetailTab === 'tx' ? 'bg-[#0D2620] text-[#34D399]' : 'text-[#3D544E]'" class="px-3 py-1 rounded">
                        Transaksi (<span x-text="(selectedClientDetail.transactions || []).length"></span>)
                    </button>
                </div>

                <!-- Tab: Logs -->
                <div x-show="clientDetailTab === 'logs'" class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md divide-y divide-[rgba(13,38,32,0.08)] max-h-60 overflow-y-auto custom-scrollbar">
                    <template x-for="lg in (selectedClientDetail.activity_logs || [])" :key="lg.id">
                        <div class="p-3 space-y-1">
                            <div class="flex justify-between">
                                <span class="font-bold text-[#0D2620]" x-text="lg.action"></span>
                                <span class="font-tabular text-[10px] text-[#647C76]" x-text="formatDate(lg.created_at)"></span>
                            </div>
                            <div class="text-[11px] text-[#647C76]" x-text="(lg.user ? lg.user.name + ' (' + lg.user.email + ')' : 'System') + ' • IP: ' + (lg.ip_address || '-')"></div>
                            <div x-show="lg.payload && Object.keys(lg.payload).length > 0" class="font-tabular text-[10px] bg-[#F7FAF9] p-1.5 rounded break-all" x-text="JSON.stringify(lg.payload)"></div>
                        </div>
                    </template>
                    <div x-show="!selectedClientDetail.activity_logs || selectedClientDetail.activity_logs.length === 0" class="p-5 text-center text-[#647C76]">Belum ada log aktivitas untuk klien ini.</div>
                </div>

                <!-- Tab: Outlets & Staff -->
                <div x-show="clientDetailTab === 'staff'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md p-3 space-y-2">
                        <div class="font-bold text-[#0D2620]">Daftar Outlet</div>
                        <template x-for="o in (selectedClientDetail?.business?.outlets || [])" :key="o.id">
                            <div class="p-2 bg-[#F7FAF9] rounded text-[11px]">
                                <div class="font-bold" x-text="o.name"></div>
                                <div class="text-[#647C76]" x-text="(o.address || '-') + (o.phone ? ' • ' + o.phone : '')"></div>
                            </div>
                        </template>
                    </div>
                    <div class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md p-3 space-y-2">
                        <div class="font-bold text-[#0D2620]">Daftar Akun Pengguna</div>
                        <template x-for="u in (selectedClientDetail.users || [])" :key="u.id">
                            <div class="p-2 bg-[#F7FAF9] rounded text-[11px] flex justify-between items-center">
                                <div>
                                    <div class="font-bold" x-text="u.name + ' (' + u.role + ')'"></div>
                                    <div class="text-[#647C76]" x-text="u.email + (u.outlet ? ' • ' + u.outlet.name : '')"></div>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold" :class="u.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" x-text="u.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tab: Payments -->
                <div x-show="clientDetailTab === 'payments'" class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md divide-y divide-[rgba(13,38,32,0.08)]">
                    <template x-for="p in (selectedClientDetail.payments || [])" :key="p.id">
                        <div class="p-3 flex justify-between items-center">
                            <div>
                                <div class="font-tabular font-bold text-[#0D2620]" x-text="p.invoice_number"></div>
                                <div class="text-[11px] text-[#647C76]" x-text="'Paket ' + (p.plan ? p.plan.name : '-') + ' • ' + formatDate(p.created_at)"></div>
                            </div>
                            <div class="text-right">
                                <div class="font-tabular font-bold" x-text="formatRupiah(p.amount)"></div>
                                <span class="text-[10px] font-bold uppercase" x-text="p.status"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="!selectedClientDetail.payments || selectedClientDetail.payments.length === 0" class="p-5 text-center text-[#647C76]">Belum ada riwayat tagihan.</div>
                </div>

                <!-- Tab: Transactions -->
                <div x-show="clientDetailTab === 'tx'" class="bg-white border border-[rgba(13,38,32,0.12)] rounded-md divide-y divide-[rgba(13,38,32,0.08)] max-h-60 overflow-y-auto custom-scrollbar">
                    <template x-for="t in (selectedClientDetail.transactions || [])" :key="t.id">
                        <div class="p-3 flex justify-between items-center">
                            <div>
                                <div class="font-tabular font-bold text-[#0D2620]" x-text="t.invoice_number"></div>
                                <div class="text-[11px] text-[#647C76]" x-text="(t.outlet ? t.outlet.name : '-') + ' • Kasir: ' + (t.cashier ? t.cashier.name : '-') + ' • ' + formatDate(t.created_at)"></div>
                            </div>
                            <div class="text-right">
                                <div class="font-tabular font-bold" x-text="formatRupiah(t.total_amount)"></div>
                                <span class="text-[10px] font-bold uppercase" :class="t.payment_status === 'voided' ? 'text-rose-600' : 'text-[#0D7A5F]'" x-text="t.payment_status"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="!selectedClientDetail.transactions || selectedClientDetail.transactions.length === 0" class="p-5 text-center text-[#647C76]">Belum ada transaksi.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aktivasi & Uji Coba Langganan Klien (Visual Pill Cards, Zero Native Select) -->
    <div x-show="showActivateModal" @click.self="showActivateModal = false" class="fixed inset-0 bg-[#0D2620]/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-md w-full border overflow-hidden">
            <div class="bg-[#0D2620] text-white px-5 py-4 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-[#34D399] uppercase tracking-wider">Pengaturan Paket &amp; Uji Coba Klien</div>
                    <h3 class="text-sm font-bold" x-text="activateTarget ? activateTarget.name : ''"></h3>
                </div>
                <button type="button" @click="showActivateModal = false" class="text-xs text-[#CBD5E1] hover:text-white">✕</button>
            </div>
            <form @submit.prevent="submitActivateModal()" class="p-5 space-y-4 text-xs">
                <p class="text-[#475569] leading-relaxed">
                    Karena payment gateway Midtrans belum aktif, Anda dapat langsung mengaktifkan paket atau mode <b>Uji Coba (Trial)</b> untuk klien ini. Sisa masa aktif sebelumnya otomatis diakumulasikan.
                </p>

                <div>
                    <label class="block font-bold text-[#0D2620] mb-1.5">1. Pilih Paket POS</label>
                    <div class="space-y-1.5">
                        <template x-for="pl in adminPlans" :key="'act-' + pl.id">
                            <button type="button" @click="activateForm.plan_id = pl.id"
                                class="w-full p-2.5 rounded-md border text-left flex items-center justify-between transition"
                                :class="String(activateForm.plan_id) === String(pl.id) ? 'bg-[#E6F4EF] border-[#0D7A5F] text-[#0D2620]' : 'bg-white border-[rgba(13,38,32,0.14)] text-[#475569] hover:border-[#0D7A5F]/50'">
                                <div>
                                    <div class="font-bold text-[#0D2620]" x-text="'Paket ' + pl.name"></div>
                                    <div class="text-[10px]" x-text="'Maks ' + pl.max_outlets + ' Outlet • ' + pl.max_users + ' Staf • ' + pl.max_products + ' Produk'"></div>
                                </div>
                                <span class="text-xs font-bold font-tabular text-[#0D7A5F]" x-text="String(activateForm.plan_id) === String(pl.id) ? '✓ Dipilih' : formatRupiah(pl.price_monthly)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-[#0D2620] mb-1.5">2. Tipe Aktivasi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="activateForm.status = 'trial'"
                            class="py-2 rounded-md border font-bold transition"
                            :class="activateForm.status === 'trial' ? 'bg-sky-600 border-sky-600 text-white' : 'bg-white border-[rgba(13,38,32,0.16)] text-[#0D2620]'">
                            Uji Coba (Trial)
                        </button>
                        <button type="button" @click="activateForm.status = 'active'"
                            class="py-2 rounded-md border font-bold transition"
                            :class="activateForm.status === 'active' ? 'bg-[#0D7A5F] border-[#0D7A5F] text-white' : 'bg-white border-[rgba(13,38,32,0.16)] text-[#0D2620]'">
                            Langganan Aktif
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-[#0D2620] mb-1.5">3. Durasi Masa Aktif</label>
                    <div class="grid grid-cols-4 gap-1.5">
                        <template x-for="opt in adminDurationOptions" :key="'ad-' + opt.days">
                            <button type="button" @click="activateForm.days = opt.days"
                                class="py-2 rounded-md border font-bold transition text-[11px]"
                                :class="activateForm.days === opt.days ? 'bg-[#0D2620] border-[#0D2620] text-[#34D399]' : 'bg-white border-[rgba(13,38,32,0.16)] text-[#0D2620]'"
                                x-text="opt.label"></button>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showActivateModal = false" class="flex-1 py-2 bg-[#E2E8F0] text-[#0D2620] rounded font-semibold">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded font-bold transition">Aktifkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Action Popover Menu for Tagihan Langganan -->
    <div x-show="showPayActionPopup" @click="showPayActionPopup = false" class="fixed inset-0 z-[60]" x-cloak></div>
    <div x-show="showPayActionPopup" x-transition.opacity.duration.120ms :style="payActionMenuStyle"
        class="tandha-modal-card fixed z-[70] w-56 bg-white rounded-lg border py-1.5 text-xs" x-cloak>
        <div class="px-3 py-1.5 border-b border-slate-100">
            <div class="font-semibold text-slate-900 truncate font-tabular" x-text="actionPayment ? actionPayment.invoice_number : ''"></div>
            <div class="text-[10px] text-slate-400 truncate font-tabular" x-text="actionPayment ? (formatRupiah(actionPayment.amount) + ' · ' + (actionPayment.status || '').toUpperCase()) : ''"></div>
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
        <div class="tandha-modal-card bg-[#F7FAF9] rounded-xl max-w-xl w-full border overflow-hidden flex flex-col max-h-[90vh]">
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
                            <div class="text-base font-bold text-[#0D2620]" x-text="activeReceipt.issuer_name || 'Tandha by SEKA GROUP'"></div>
                            <div class="text-[11px] text-slate-500" x-text="activeReceipt.issuer_tagline || ''"></div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase tracking-wider" x-text="activeReceipt.status || 'LUNAS'"></span>
                            <div class="font-tabular font-bold text-slate-900 mt-1" x-text="activeReceipt.receipt_number"></div>
                            <div class="text-[10px] text-slate-400 font-tabular" x-text="'Ref Invoice: ' + (activeReceipt.invoice_number || '-')"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] bg-slate-50 p-3.5 rounded border border-slate-200">
                        <div class="space-y-1">
                            <div class="text-slate-400 uppercase font-semibold text-[10px]">Diterima Dari</div>
                            <div class="font-bold text-slate-900" x-text="activeReceipt.business_name"></div>
                            <div x-text="'PIC: ' + (activeReceipt.owner_name || '-')"></div>
                            <div class="text-slate-600" x-text="activeReceipt.owner_email"></div>
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
                                    <th class="py-2 px-3">Layanan Paket &amp; Durasi</th>
                                    <th class="py-2 px-3">Masa Aktif</th>
                                    <th class="py-2 px-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-slate-900" x-text="'Sewa Paket ' + (activeReceipt.plan_name || '-') + ' (' + (activeReceipt.duration_label || ((activeReceipt.duration_months || 1) + ' Bulan')) + ')'"></div>
                                        <div class="text-[11px] text-slate-500 font-tabular" x-show="activeReceipt.plan_limits"
                                            x-text="activeReceipt.plan_limits ? ('Maks. ' + activeReceipt.plan_limits.max_outlets + ' Outlet · ' + activeReceipt.plan_limits.max_users + ' Staf · ' + activeReceipt.plan_limits.max_products + ' Produk') : ''"></div>
                                    </td>
                                    <td class="py-3 px-3 text-[11px] font-tabular text-slate-600"
                                        x-text="(activeReceipt.billing_starts_at_formatted || '-') + ' s.d. ' + (activeReceipt.billing_expires_at_formatted || '-')"></td>
                                    <td class="py-3 px-3 text-right font-tabular font-bold text-slate-900" x-text="formatRupiah(activeReceipt.amount)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                        <span class="font-bold text-slate-700">TOTAL DIBAYAR</span>
                        <span class="text-lg font-bold font-tabular text-[#0D7A5F]" x-text="formatRupiah(activeReceipt.amount)"></span>
                    </div>

                    <div class="text-[10px] text-slate-400 text-center pt-2 border-t border-dashed border-slate-200">
                        Nota pembayaran ini diterbitkan secara elektronik oleh sistem Tandha by SEKA GROUP dan sah tanpa tanda tangan basah.
                    </div>
                </div>
            </div>
            <div class="px-5 py-3.5 bg-white border-t border-slate-200 flex justify-end gap-2 shrink-0">
                <button type="button" @click="showSubReceiptModal = false" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-md text-xs font-medium">Tutup</button>
                <button type="button" @click="window.print()" class="px-4 py-2 bg-[#0D7A5F] hover:bg-[#095C47] text-white rounded-md text-xs font-bold">Cetak / Simpan PDF</button>
            </div>
        </div>
    </div>

    <!-- Modal Overlay Studio: Preview Layar Kasir POS (Desktop & Mobile Portrait + Branch Live Telemetry) -->
    <div x-show="showPosPreviewModal" @click.self="closePosPreviewModal()" @keydown.window.escape="if (showPosPreviewModal) closePosPreviewModal()"
        class="fixed inset-0 bg-[#0D2620]/85 backdrop-blur-xs z-[80] flex items-center justify-center p-2 sm:p-5" x-cloak>
        <div class="tandha-modal-card bg-white rounded-xl w-full max-w-[1440px] h-[93vh] overflow-hidden border flex flex-col">
            <div class="shrink-0 bg-[#0D2620] text-white px-4 sm:px-5 py-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(51,65,85,0.75)]">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-8 h-8 rounded-md bg-[#143B32] border border-[rgba(51,65,85,0.75)] flex items-center justify-center text-[#34D399] shrink-0">
                        <i data-lucide="monitor-smartphone" class="w-4 h-4"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-white leading-none">Studio Preview Layar Kasir</span>
                            <span class="px-2 py-0.5 rounded bg-amber-300 text-slate-950 text-[10px] font-bold uppercase tracking-wider">Mode Tinjau Pemilik</span>
                        </div>
                        <p class="text-[11px] text-[#CBD5E1] mt-1 truncate">Simulasikan tampilan kasir cabang pada layar meja maupun perangkat mobile sebelum dipakai kasir.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto lg:shrink-0">
                    <!-- Viewport Switcher: Desktop vs HP/Tablet -->
                    <div class="inline-flex bg-[#143B32] border border-[rgba(51,65,85,0.75)] rounded-md p-0.5 text-[11px]">
                        <button type="button" @click="previewDevice = 'desktop'"
                            class="px-2.5 py-1 rounded font-medium inline-flex items-center gap-1 transition"
                            :class="previewDevice === 'desktop' ? 'bg-[#34D399] text-[#0D2620] font-bold' : 'text-[#CBD5E1] hover:text-white'">
                            <i data-lucide="monitor" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Desktop</span>
                        </button>
                        <button type="button" @click="previewDevice = 'mobile'"
                            class="px-2.5 py-1 rounded font-medium inline-flex items-center gap-1 transition"
                            :class="previewDevice === 'mobile' ? 'bg-[#34D399] text-[#0D2620] font-bold' : 'text-[#CBD5E1] hover:text-white'">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Mobile APK</span>
                        </button>
                    </div>

                    <div x-show="outlets.length > 0" class="relative" @click.outside="if (openDropdown === 'previewOutlet') openDropdown = null">
                        <button type="button" @click="toggleDropdown('previewOutlet')"
                            class="inline-flex items-center gap-2 h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] rounded-md text-xs font-medium text-white transition">
                            <span class="text-[#CBD5E1] hidden sm:inline">Cabang:</span>
                            <span class="font-semibold text-[#34D399] truncate max-w-[150px]" x-text="activePreviewOutletLabel"></span>
                            <svg class="w-3.5 h-3.5 text-[#CBD5E1] shrink-0 transition-transform" :class="openDropdown === 'previewOutlet' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="openDropdown === 'previewOutlet'" x-transition class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-60 bg-white border border-slate-200 rounded-lg shadow-xl z-50 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">Pilih Cabang Preview</div>
                            <template x-for="out in outlets" :key="'mprev-' + out.id">
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
                        class="inline-flex items-center gap-1.5 h-8 px-3 bg-[#143B32] border border-[rgba(51,65,85,0.75)] hover:border-[#34D399] text-[#CBD5E1] hover:text-white rounded-md text-xs font-medium transition"
                        title="Muat ulang preview cabang">
                        <svg class="w-3.5 h-3.5" :class="previewIframeLoading ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        <span class="hidden sm:inline">Muat Ulang</span>
                    </button>
                    <button type="button" @click="closePosPreviewModal()"
                        class="inline-flex items-center gap-1.5 h-8 px-3.5 bg-[#34D399] hover:bg-[#10B981] text-[#0D2620] rounded-md text-xs font-bold transition">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>Tutup</span>
                    </button>
                </div>
            </div>
            <!-- Live Config Summary Strip -->
            <div class="shrink-0 bg-slate-50 border-b border-slate-200 px-4 sm:px-5 py-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-600">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold text-slate-800 font-tabular" x-text="'Pajak Aktif: ' + activePreviewBranchTax + '%'"></span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-medium" x-text="(activePreviewOutletObj && (activePreviewOutletObj.receipt_header || activePreviewOutletObj.receipt_footer)) ? 'Struk Kustom Cabang' : 'Struk Standar Pusat'"></span>
                    <span class="px-2 py-0.5 rounded font-medium" :class="featureSettingsForm.pos_allow_discount ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'" x-text="featureSettingsForm.pos_allow_discount ? 'Diskon Kasir: Aktif' : 'Diskon Kasir: Nonaktif'"></span>
                    <span class="px-2 py-0.5 rounded font-medium" :class="featureSettingsForm.pos_require_shift ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'" x-text="featureSettingsForm.pos_require_shift ? 'Wajib Buka Shift' : 'Tanpa Shift'"></span>
                </div>
                <span class="text-slate-500 hidden md:inline">Perubahan profil cabang atau tombol fitur langsung terlihat di layar tinjauan ini.</span>
            </div>
            <!-- Stage Frame + Loading Shimmer -->
            <div class="flex-1 min-h-0 bg-slate-100 relative flex items-center justify-center p-2 sm:p-4 overflow-hidden">
                <div x-show="previewIframeLoading" x-transition.opacity class="absolute inset-0 z-10 bg-slate-100/90 flex flex-col items-center justify-center gap-2.5">
                    <svg class="w-6 h-6 animate-spin text-[#0D7A5F]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                    <span class="text-xs font-semibold text-slate-700">Memuat layar kasir cabang...</span>
                </div>
                <div :class="previewDevice === 'mobile' ? 'w-full max-w-[440px] h-full rounded-[26px] border-[8px] border-[#0D2620] shadow-2xl bg-white overflow-hidden transition-all duration-200' : 'w-full h-full rounded-lg border border-slate-200 bg-white overflow-hidden transition-all duration-200'">
                    <template x-if="showPosPreviewModal">
                        <iframe :src="posPreviewIframeUrl" @load="previewIframeLoading = false" title="Preview Layar Kasir POS" class="w-full h-full border-0 bg-[#F7FAF9]"></iframe>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function localDateOffsetDash(days) {
    const d = new Date();
    d.setDate(d.getDate() + Number(days || 0));
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
function dashboardApp() {
    return {
        isBooting: true,
        isFetching: 0,
        isRefreshing: false,
        isSaving: false,
        mobileMenuOpen: false,
        initialServerTab: @json($initialTab ?? ''),
        token: localStorage.getItem('pos_token') || '',
        user: JSON.parse(localStorage.getItem('pos_user') || '{}'),
        business: {},
        outlets: [],
        selectedOutletId: '',
        openDropdown: null,
        tab: 'overview',
        tabTitles: {
            overview: 'Ringkasan Bisnis & Analitik',
            products: 'Manajemen Produk, Gambar & Kategori',
            inventory: 'Inventori & Audit Mutasi Stok',
            reports: 'Laporan Penjualan & Laba',
            staff: 'Manajemen Tim, Staf & Kuota Paket',
            outlets: 'Manajemen Outlet & Profil Bisnis',
            settings: 'Pengaturan Tombol Kasir & Akses Admin',
            billing: 'Langganan Paket & Riwayat Tagihan',
            consultation: 'Konsultasi & Request Penambahan Fitur',
            locked: 'Akses Modul Terkunci',
            admin_platform: 'Monitoring Platform Pusat',
        },
        adminDurationOptions: [
            { days: 7, label: '7 Hari' },
            { days: 14, label: '14 Hari' },
            { days: 30, label: '1 Bln' },
            { days: 90, label: '3 Bln' },
            { days: 180, label: '6 Bln' },
            { days: 365, label: '1 Thn' },
            { days: 730, label: '2 Thn' },
            { days: 1095, label: '3 Thn' },
        ],
        billingLoading: false,
        billingLoaded: false,
        billingSummary: null,
        billingUsage: { outlets_count: 0, users_count: 0, products_count: 0 },
        billingPlans: [],
        billingPeriods: [
            { months: 1, label: '1 Bulan', days: 30 },
            { months: 3, label: '3 Bulan', days: 90 },
            { months: 6, label: '6 Bulan', days: 180 },
            { months: 12, label: '1 Tahun', days: 365 },
            { months: 24, label: '2 Tahun', days: 730 },
            { months: 36, label: '3 Tahun', days: 1095 },
        ],
        billingDurationMonths: 1,
        selectedPlanId: null,
        pendingPayment: null,
        paymentHistory: [],
        billingProjects: [],
        consultationGuard: { can_submit: true, reason: null, weekly_used: 0, weekly_limit: 2, active_whatsapp_url: null },
        submittingRent: false,
        submittingConsult: false,
        checkingPayment: false,
        pollTimer: null,
        showPayActionPopup: false,
        actionPayment: null,
        payActionMenuStyle: 'top: 120px; right: 32px;',
        showSubReceiptModal: false,
        activeReceipt: {},
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
        scaleOptions: [
            { value: '1 Outlet', label: '1 Outlet' },
            { value: '2-5 Cabang', label: '2–5 Cabang' },
            { value: 'Lebih dari 5 Cabang', label: 'Lebih dari 5 Cabang' },
        ],
        consultTimeSlots: ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        minConsultDate: localDateOffsetDash(3),
        maxConsultDate: localDateOffsetDash(90),
        consultForm: {
            business_type: 'F&B / Restoran / Kafe',
            business_type_other: '',
            scale: '1 Outlet',
            phone: '',
            preferred_date: localDateOffsetDash(3),
            preferred_time: '10:00',
            template_gap_notes: '',
        },
        adminSubTab: 'clients',
        adminSubFilter: 'all',
        adminSearch: '',
        adminPage: 1,
        adminPerPage: 10,
        consultSearch: '',
        consultStatusFilter: 'all',
        consultPage: 1,
        consultPerPage: 10,
        consultStatusOptions: [
            { value: 'all', label: 'Semua Status Pengingat' },
            { value: 'wa_pending', label: 'Menunggu WA SEKA GROUP' },
            { value: 'sent_from_sekayasa', label: 'Sudah Terkirim WA (SEKA GROUP)' },
            { value: 'completed', label: 'Selesai / Terjawab' },
        ],
        adminLogBusinessFilter: '',
        adminLogCategoryFilter: 'all',
        adminLogSearch: '',
        adminLogPage: 1,
        adminLogPerPage: 10,
        adminFilterOptions: [
            { value: 'all', label: 'Semua Status Langganan', dot: 'bg-slate-400' },
            { value: 'active', label: 'Langganan Aktif', dot: 'bg-emerald-500' },
            { value: 'trial', label: 'Sedang Uji Coba (Trial)', dot: 'bg-sky-500' },
            { value: 'expiring_soon', label: 'Segera Habis (<= 7 Hari)', dot: 'bg-amber-500' },
            { value: 'expired', label: 'Kedaluwarsa', dot: 'bg-rose-500' },
            { value: 'none', label: 'Belum Berlangganan', dot: 'bg-slate-300' },
            { value: 'suspended', label: 'Login Dibekukan', dot: 'bg-rose-700' },
        ],
        adminLogCategoryOptions: [
            { value: 'all', label: 'Semua Kategori Event' },
            { value: 'errors', label: 'Error, Exception & Void' },
            { value: 'auth', label: 'Login, Gagal Login & Register' },
            { value: 'pos', label: 'Kasir Checkout & Shift' },
            { value: 'admin_billing', label: 'Stok, Langganan & Admin' },
        ],
        adminMetrics: {},
        adminPlans: [],
        adminBusinesses: [],
        adminConsultations: [],
        adminErrorLogs: [],
        adminActivityLogs: [],
        adminRecentTransactions: [],
        showActionPopup: false,
        actionContext: 'client',
        actionTarget: null,
        actionMenuStyle: 'top: 120px; right: 32px;',
        showClientDetailModal: false,
        clientDetailTab: 'logs',
        selectedClientDetail: { business: { outlets: [] }, subscription_summary: {}, activity_logs: [], users: [], payments: [], transactions: [] },
        extendForm: { plan_id: '', days: 30, status: 'active' },
        showActivateModal: false,
        activateTarget: null,
        activateForm: { plan_id: '', days: 14, status: 'trial' },
        showPosPreviewModal: false,
        previewModalOutletId: '',
        previewModalVersion: 0,
        previewDevice: 'desktop',
        previewIframeLoading: true,
        dashboardData: {},
        salesChartInstance: null,
        productSubTab: 'products',
        productList: [],
        categories: [],
        productSearch: '',
        prodCatFilter: '',
        prodStockFilter: 'all',
        prodStockFilterOptions: [
            { value: 'all', label: 'Semua Kondisi Stok' },
            { value: 'in_stock', label: 'Stok Aman (> 5)' },
            { value: 'low', label: 'Stok Menipis (1–5)' },
            { value: 'empty', label: 'Stok Habis (0)' },
        ],
        catSearch: '',
        catStatusFilter: 'all',
        catStatusFilterOptions: [
            { value: 'all', label: 'Semua Kategori' },
            { value: 'has_products', label: 'Sudah Memiliki Produk' },
            { value: 'empty', label: 'Masih Kosong (0 Produk)' },
        ],
        catPage: 1,
        catPerPage: 10,
        showCategoryModal: false,
        categoryForm: { id: null, name: '', description: '' },
        showCreateProductModal: false,
        newProduct: { name: '', category_id: '', sku: '', barcode: '', buy_price: 0, sell_price: 0, initial_stock: 50 },
        newProductFile: null,
        newProductPreview: '',
        showEditProductModal: false,
        editProduct: { id: null, name: '', category_id: '', sku: '', barcode: '', buy_price: 0, sell_price: 0 },
        editProductFile: null,
        editProductPreview: '',
        staffMembers: [],
        staffLimits: {},
        staffSearch: '',
        staffRoleFilter: 'all',
        staffOutletFilter: '',
        staffRoleFilterOptions: [
            { value: 'all', label: 'Semua Role Staf' },
            { value: 'owner', label: 'Pemilik (Owner)' },
            { value: 'admin', label: 'Admin (Opsional)' },
            { value: 'cashier', label: 'Kasir POS' },
        ],
        showCreateStaffModal: false,
        newStaff: { name: '', email: '', phone: '', role: 'cashier', outlet_id: '', password: '' },
        showEditStaffModal: false,
        editStaff: { id: null, name: '', role: 'cashier', outlet_id: '', is_active: true, password: '' },
        showCreateOutletModal: false,
        newOutlet: { name: '', outlet_code: '', city: '', phone: '', address: '' },
        showEditOutletModal: false,
        editOutletForm: {
            id: null,
            name: '',
            outlet_code: '',
            city: '',
            phone: '',
            address: '',
            is_active: true,
            receipt_header: '',
            receipt_footer: '',
            tax_percentage: '',
            feature_overrides: {},
        },
        editBusinessMode: false,
        businessForm: { name: '', phone: '', address: '', tax_percentage: 11, receipt_footer: '' },
        featureSettingsForm: {
            pos_allow_discount: true,
            pos_allow_customer: true,
            pos_allow_notes: true,
            pos_allow_barcode: true,
            pos_allow_history: true,
            pos_allow_void: true,
            pos_require_shift: true,
            admin_view_dashboard: true,
            admin_manage_products: true,
            admin_manage_categories: true,
            admin_manage_inventory: true,
            admin_view_sales_report: true,
            admin_view_shift_history: true,
        },
        cashierFeatureToggleMeta: [
            { key: 'pos_require_shift', title: 'Wajib Buka Shift & Kas Awal', desc: 'Kasir harus membuka shift & mengisi modal kas awal laci sebelum transaksi.' },
            { key: 'pos_allow_discount', title: 'Tombol Diskon Transaksi', desc: 'Izinkan kasir memberikan potongan diskon persen atau nominal pada nota.' },
            { key: 'pos_allow_customer', title: 'Pemilih & Tambah Pelanggan', desc: 'Tampilkan pilihan nama pelanggan dan tombol tambah pelanggan di POS.' },
            { key: 'pos_allow_notes', title: 'Kolom Catatan Pesanan / Meja', desc: 'Tampilkan kolom catatan nomor meja atau instruksi pesanan khusus.' },
            { key: 'pos_allow_barcode', title: 'Kotak Scan Barcode Cepat', desc: 'Tampilkan kolom input scan barcode kilat di bagian atas katalog POS.' },
            { key: 'pos_allow_history', title: 'Menu Riwayat Transaksi Kasir', desc: 'Izinkan kasir membuka riwayat transaksi outlet & cetak ulang struk.' },
            { key: 'pos_allow_void', title: 'Tombol Void / Batalkan Transaksi', desc: 'Izinkan kasir membatalkan transaksi yang sudah dibayar dari riwayat kasir.' },
        ],
        adminFeatureToggleMeta: [
            { key: 'admin_view_dashboard', title: 'Akses Menu Ringkasan & Analitik', desc: 'Tampilkan menu dashboard omset harian dan grafik tren untuk Admin.' },
            { key: 'admin_manage_products', title: 'Kelola Data Produk & Gambar', desc: 'Izinkan Admin menambah, mengubah harga/stok awal, dan mengarsipkan produk.' },
            { key: 'admin_manage_categories', title: 'Kelola Kategori Kustom', desc: 'Izinkan Admin membuat, mengubah, dan menghapus kategori produk bisnis.' },
            { key: 'admin_manage_inventory', title: 'Kelola Inventori & Penyesuaian Stok', desc: 'Izinkan Admin memantau mutasi stok dan melakukan opname penyesuaian stok.' },
            { key: 'admin_view_sales_report', title: 'Laporan Transaksi Penjualan', desc: 'Izinkan Admin melihat daftar penjualan outlet (nominal laba Owner tetap tersembunyi).' },
            { key: 'admin_view_shift_history', title: 'Riwayat Buka/Tutup Shift Kasir', desc: 'Izinkan Admin memantau histori kas awal, kas aktual, dan selisih shift kasir.' },
        ],
        stockMovements: [],
        invSearch: '',
        invTypeFilter: 'all',
        invProdFilter: '',
        invTypeFilterOptions: [
            { value: 'all', label: 'Semua Jenis Mutasi' },
            { value: 'adjustment_add', label: 'Penambahan Stok (+)' },
            { value: 'adjustment_sub', label: 'Pengurangan Stok (-)' },
            { value: 'sale', label: 'Penjualan Kasir (-)' },
            { value: 'void', label: 'Void / Batal Nota (+)' },
            { value: 'initial', label: 'Stok Awal' },
        ],
        showAdjustmentModal: false,
        adjProduct: '',
        adjType: 'adjustment_add',
        adjQty: 10,
        adjReason: '',
        reportStartDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        reportEndDate: new Date().toISOString().split('T')[0],
        reportSubTab: 'summary',
        repSearch: '',
        repPayFilter: 'all',
        repPayFilterOptions: [
            { value: 'all', label: 'Semua Pembayaran' },
            { value: 'cash', label: 'Tunai (Cash)' },
            { value: 'qris', label: 'QRIS' },
            { value: 'transfer', label: 'Transfer Bank' },
            { value: 'ewallet', label: 'E-Wallet' },
        ],
        shiftSearch: '',
        shiftStatusFilter: 'all',
        shiftStatusFilterOptions: [
            { value: 'all', label: 'Semua Status Shift' },
            { value: 'open', label: 'Sedang Buka (Aktif)' },
            { value: 'closed', label: 'Sudah Ditutup' },
        ],
        profitData: {},
        salesReportData: {},
        shiftHistory: [],
        prodPage: 1,
        prodPerPage: 10,
        invPage: 1,
        invPerPage: 10,
        repPage: 1,
        repPerPage: 10,
        staffPage: 1,
        staffPerPage: 10,
        shiftPage: 1,
        shiftPerPage: 10,

        defaultFeatureSettings() {
            return {
                pos_allow_discount: true,
                pos_allow_customer: true,
                pos_allow_notes: true,
                pos_allow_barcode: true,
                pos_allow_history: true,
                pos_allow_void: true,
                pos_require_shift: true,
                admin_view_dashboard: true,
                admin_manage_products: true,
                admin_manage_categories: true,
                admin_manage_inventory: true,
                admin_view_sales_report: true,
                admin_view_shift_history: true,
            };
        },
        hasFeature(key) {
            if (!this.user || this.user.role === 'owner' || this.user.role === 'superadmin') return true;
            const settings = (this.business && this.business.feature_settings) ? this.business.feature_settings : {};
            return settings[key] === undefined ? true : Boolean(settings[key]);
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

        get selectedCreateProdCatLabel() {
            if (!this.newProduct.category_id) return this.categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori';
            const f = this.categories.find((c) => String(c.id) === String(this.newProduct.category_id));
            return f ? f.name : (this.categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori');
        },
        get selectedEditProdCatLabel() {
            if (!this.editProduct.category_id) return this.categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori';
            const f = this.categories.find((c) => String(c.id) === String(this.editProduct.category_id));
            return f ? f.name : (this.categories.length === 0 ? 'Belum ada kategori' : 'Tanpa Kategori');
        },
        get selectedCreateStaffOutletLabel() {
            const f = this.outlets.find((o) => String(o.id) === String(this.newStaff.outlet_id));
            return f ? f.name : (this.outlets[0] ? this.outlets[0].name : 'Pilih Outlet');
        },
        get selectedEditStaffOutletLabel() {
            const f = this.outlets.find((o) => String(o.id) === String(this.editStaff.outlet_id));
            return f ? f.name : (this.outlets[0] ? this.outlets[0].name : 'Pilih Outlet');
        },
        get activeProdCatFilterLabel() {
            if (!this.prodCatFilter) return 'Semua Kategori';
            if (this.prodCatFilter === 'none') return 'Belum Dikategorikan';
            const found = this.categories.find((c) => String(c.id) === String(this.prodCatFilter));
            return found ? found.name : 'Semua Kategori';
        },
        get activeProdStockFilterLabel() {
            const f = this.prodStockFilterOptions.find((o) => o.value === this.prodStockFilter);
            return f ? f.label : 'Semua Kondisi Stok';
        },
        get filteredProductsList() {
            const q = (this.productSearch || '').trim().toLowerCase();
            return this.productList.filter((p) => {
                if (this.prodCatFilter === 'none' && p.category_id) return false;
                if (this.prodCatFilter && this.prodCatFilter !== 'none' && String(p.category_id || '') !== String(this.prodCatFilter)) return false;
                const stock = Number(p.current_stock || 0);
                if (this.prodStockFilter === 'in_stock' && stock <= 5) return false;
                if (this.prodStockFilter === 'low' && (stock <= 0 || stock > 5)) return false;
                if (this.prodStockFilter === 'empty' && stock > 0) return false;
                if (!q) return true;
                return (p.name || '').toLowerCase().includes(q) ||
                    (p.sku || '').toLowerCase().includes(q) ||
                    (p.barcode || '').toLowerCase().includes(q) ||
                    ((p.category && p.category.name) || '').toLowerCase().includes(q);
            });
        },
        get prodTotalPages() { return Math.max(1, Math.ceil(this.filteredProductsList.length / this.prodPerPage)); },
        get paginatedProducts() {
            const s = (Math.min(this.prodPage, this.prodTotalPages) - 1) * this.prodPerPage;
            return this.filteredProductsList.slice(s, s + this.prodPerPage);
        },
        get prodPaginationRangeLabel() {
            const total = this.filteredProductsList.length;
            if (total === 0) return '0 produk';
            const safe = Math.min(this.prodPage, this.prodTotalPages);
            return `Menampilkan ${(safe - 1) * this.prodPerPage + 1}–${Math.min(total, safe * this.prodPerPage)} dari ${total} produk`;
        },
        get activeCatStatusFilterLabel() {
            const f = this.catStatusFilterOptions.find((o) => o.value === this.catStatusFilter);
            return f ? f.label : 'Semua Kategori';
        },
        get filteredCategoriesList() {
            const q = (this.catSearch || '').trim().toLowerCase();
            return this.categories.filter((c) => {
                const count = Number(c.products_count ?? (c.products ? c.products.length : 0));
                if (this.catStatusFilter === 'has_products' && count === 0) return false;
                if (this.catStatusFilter === 'empty' && count > 0) return false;
                if (!q) return true;
                const prodNames = (c.products || []).map((cp) => (cp.name || '').toLowerCase()).join(' ');
                return (c.name || '').toLowerCase().includes(q) ||
                    (c.description || '').toLowerCase().includes(q) ||
                    prodNames.includes(q);
            });
        },
        get catTotalPages() { return Math.max(1, Math.ceil(this.filteredCategoriesList.length / this.catPerPage)); },
        get paginatedCategories() {
            const s = (Math.min(this.catPage, this.catTotalPages) - 1) * this.catPerPage;
            return this.filteredCategoriesList.slice(s, s + this.catPerPage);
        },
        get catPaginationRangeLabel() {
            const total = this.filteredCategoriesList.length;
            if (total === 0) return '0 kategori';
            const safe = Math.min(this.catPage, this.catTotalPages);
            return `Menampilkan ${(safe - 1) * this.catPerPage + 1}–${Math.min(total, safe * this.catPerPage)} dari ${total} kategori`;
        },
        get activeInvTypeLabel() {
            const f = this.invTypeFilterOptions.find((o) => o.value === this.invTypeFilter);
            return f ? f.label : 'Semua Jenis Mutasi';
        },
        get activeInvProdLabel() {
            if (!this.invProdFilter) return 'Semua Produk';
            const f = this.productList.find((p) => String(p.id) === String(this.invProdFilter));
            return f ? f.name : 'Semua Produk';
        },
        get filteredMovements() {
            const q = (this.invSearch || '').trim().toLowerCase();
            return this.stockMovements.filter((m) => {
                if (this.invTypeFilter !== 'all' && m.type !== this.invTypeFilter) return false;
                if (this.invProdFilter && String(m.product_id || (m.product ? m.product.id : '')) !== String(this.invProdFilter)) return false;
                if (!q) return true;
                return ((m.product && m.product.name) || '').toLowerCase().includes(q) ||
                    (m.reason || '').toLowerCase().includes(q) ||
                    (m.type || '').toLowerCase().includes(q) ||
                    ((m.user && m.user.name) || '').toLowerCase().includes(q);
            });
        },
        get invTotalPages() { return Math.max(1, Math.ceil(this.filteredMovements.length / this.invPerPage)); },
        get paginatedMovements() {
            const s = (Math.min(this.invPage, this.invTotalPages) - 1) * this.invPerPage;
            return this.filteredMovements.slice(s, s + this.invPerPage);
        },
        get invPaginationRangeLabel() {
            const total = this.filteredMovements.length;
            if (total === 0) return '0 mutasi';
            const safe = Math.min(this.invPage, this.invTotalPages);
            return `Menampilkan ${(safe - 1) * this.invPerPage + 1}–${Math.min(total, safe * this.invPerPage)} dari ${total} mutasi`;
        },
        get activeReportRows() {
            if (this.user && this.user.role === 'owner') return this.profitData.products || [];
            if (Array.isArray(this.salesReportData.transactions)) return this.salesReportData.transactions;
            return (this.salesReportData.transactions && this.salesReportData.transactions.data) ? this.salesReportData.transactions.data : [];
        },
        get activeRepPayLabel() {
            const f = this.repPayFilterOptions.find((o) => o.value === this.repPayFilter);
            return f ? f.label : 'Semua Pembayaran';
        },
        get filteredReportRows() {
            const q = (this.repSearch || '').trim().toLowerCase();
            return this.activeReportRows.filter((row) => {
                if (this.user && this.user.role === 'admin' && this.repPayFilter !== 'all') {
                    const pm = (row.payment && row.payment.payment_method) ? String(row.payment.payment_method).toLowerCase() : '';
                    if (pm !== this.repPayFilter) return false;
                }
                if (!q) return true;
                if (this.user && this.user.role === 'owner') {
                    return (row.product_name || '').toLowerCase().includes(q);
                }
                return (row.invoice_number || '').toLowerCase().includes(q) ||
                    ((row.cashier && row.cashier.name) || '').toLowerCase().includes(q) ||
                    ((row.outlet && row.outlet.name) || '').toLowerCase().includes(q);
            });
        },
        get repTotalPages() { return Math.max(1, Math.ceil(this.filteredReportRows.length / this.repPerPage)); },
        get paginatedReportRows() {
            const s = (Math.min(this.repPage, this.repTotalPages) - 1) * this.repPerPage;
            return this.filteredReportRows.slice(s, s + this.repPerPage);
        },
        get repPaginationRangeLabel() {
            const total = this.filteredReportRows.length;
            if (total === 0) return '0 baris';
            const safe = Math.min(this.repPage, this.repTotalPages);
            return `Menampilkan ${(safe - 1) * this.repPerPage + 1}–${Math.min(total, safe * this.repPerPage)} dari ${total} data`;
        },
        get activeStaffRoleFilterLabel() {
            const f = this.staffRoleFilterOptions.find((o) => o.value === this.staffRoleFilter);
            return f ? f.label : 'Semua Role Staf';
        },
        get activeStaffOutletFilterLabel() {
            if (!this.staffOutletFilter) return 'Semua Outlet';
            const f = this.outlets.find((o) => String(o.id) === String(this.staffOutletFilter));
            return f ? f.name : 'Semua Outlet';
        },
        get filteredStaffMembers() {
            const q = (this.staffSearch || '').trim().toLowerCase();
            return this.staffMembers.filter((m) => {
                if (this.staffRoleFilter !== 'all' && m.role !== this.staffRoleFilter) return false;
                if (this.staffOutletFilter && String(m.outlet_id || '') !== String(this.staffOutletFilter)) return false;
                if (!q) return true;
                return (m.name || '').toLowerCase().includes(q) ||
                    (m.email || '').toLowerCase().includes(q) ||
                    (m.phone || '').toLowerCase().includes(q);
            });
        },
        get staffTotalPages() { return Math.max(1, Math.ceil(this.filteredStaffMembers.length / this.staffPerPage)); },
        get paginatedStaffMembers() {
            const s = (Math.min(this.staffPage, this.staffTotalPages) - 1) * this.staffPerPage;
            return this.filteredStaffMembers.slice(s, s + this.staffPerPage);
        },
        get staffPaginationRangeLabel() {
            const total = this.filteredStaffMembers.length;
            if (total === 0) return '0 pengguna';
            const safe = Math.min(this.staffPage, this.staffTotalPages);
            return `Menampilkan ${(safe - 1) * this.staffPerPage + 1}–${Math.min(total, safe * this.staffPerPage)} dari ${total} akun`;
        },
        get activeShiftStatusLabel() {
            const f = this.shiftStatusFilterOptions.find((o) => o.value === this.shiftStatusFilter);
            return f ? f.label : 'Semua Status Shift';
        },
        get filteredShiftHistory() {
            const q = (this.shiftSearch || '').trim().toLowerCase();
            return this.shiftHistory.filter((sh) => {
                if (this.shiftStatusFilter !== 'all' && sh.status !== this.shiftStatusFilter) return false;
                if (!q) return true;
                return ((sh.cashier && sh.cashier.name) || '').toLowerCase().includes(q) ||
                    ((sh.outlet && sh.outlet.name) || '').toLowerCase().includes(q) ||
                    (sh.notes || '').toLowerCase().includes(q);
            });
        },
        get shiftTotalPages() { return Math.max(1, Math.ceil(this.filteredShiftHistory.length / this.shiftPerPage)); },
        get paginatedShiftHistory() {
            const s = (Math.min(this.shiftPage, this.shiftTotalPages) - 1) * this.shiftPerPage;
            return this.filteredShiftHistory.slice(s, s + this.shiftPerPage);
        },
        get shiftPaginationRangeLabel() {
            const total = this.filteredShiftHistory.length;
            if (total === 0) return '0 shift';
            const safe = Math.min(this.shiftPage, this.shiftTotalPages);
            return `Menampilkan ${(safe - 1) * this.shiftPerPage + 1}–${Math.min(total, safe * this.shiftPerPage)} dari ${total} shift`;
        },
        get activeConsultStatusLabel() {
            const f = this.consultStatusOptions.find((o) => o.value === this.consultStatusFilter);
            return f ? f.label : 'Semua Status Pengingat';
        },
        get filteredConsultations() {
            const q = (this.consultSearch || '').trim().toLowerCase();
            return this.adminConsultations.filter((c) => {
                if (this.consultStatusFilter === 'wa_pending' && ['sent_from_sekayasa', 'completed'].includes(String(c.whatsapp_status || ''))) return false;
                if (this.consultStatusFilter === 'sent_from_sekayasa' && String(c.whatsapp_status || '') !== 'sent_from_sekayasa') return false;
                if (this.consultStatusFilter === 'completed' && String(c.whatsapp_status || '') !== 'completed') return false;
                if (!q) return true;
                return (c.business_name || '').toLowerCase().includes(q) ||
                    (c.client_name || '').toLowerCase().includes(q) ||
                    (c.business_type || '').toLowerCase().includes(q) ||
                    (c.phone || '').toLowerCase().includes(q) ||
                    (c.email || '').toLowerCase().includes(q) ||
                    (c.reminder_notes || '').toLowerCase().includes(q) ||
                    (c.template_gap_notes || c.notes || '').toLowerCase().includes(q);
            });
        },
        get consultTotalPages() { return Math.max(1, Math.ceil(this.filteredConsultations.length / this.consultPerPage)); },
        get paginatedConsultations() {
            const s = (Math.min(this.consultPage, this.consultTotalPages) - 1) * this.consultPerPage;
            return this.filteredConsultations.slice(s, s + this.consultPerPage);
        },
        get consultPaginationRangeLabel() {
            const total = this.filteredConsultations.length;
            if (total === 0) return '0 konsultasi';
            const safe = Math.min(this.consultPage, this.consultTotalPages);
            return `Menampilkan ${(safe - 1) * this.consultPerPage + 1}–${Math.min(total, safe * this.consultPerPage)} dari ${total} konsultasi`;
        },

        get activeHeaderTitle() {
            if (this.tab === 'admin_platform') {
                const map = {
                    clients: 'Monitoring Semua Klien & Langganan',
                    billing: 'Konsultasi & Request Update Fitur',
                    logs: 'Lacak Error Server & Audit Aktivitas',
                };
                return map[this.adminSubTab] || 'Monitoring Semua Klien';
            }
            return this.tabTitles[this.tab] || 'Dashboard';
        },

        get selectedOutletLabel() {
            if (!this.selectedOutletId) return 'Semua Outlet';
            const found = this.outlets.find((o) => String(o.id) === String(this.selectedOutletId));
            return found ? found.name : 'Semua Outlet';
        },

        get activeFilterOption() {
            return this.adminFilterOptions.find((o) => o.value === this.adminSubFilter) || this.adminFilterOptions[0];
        },

        get activeLogBusinessLabel() {
            if (!this.adminLogBusinessFilter) return 'Semua Bisnis Klien';
            const found = this.adminBusinesses.find((b) => String(b.id) === String(this.adminLogBusinessFilter));
            return found ? found.name : 'Semua Bisnis Klien';
        },

        get activeLogCategoryLabel() {
            const found = this.adminLogCategoryOptions.find((c) => c.value === this.adminLogCategoryFilter);
            return found ? found.label : 'Semua Kategori Event';
        },

        get hasUnlockedWorkspace() {
            if (!this.user) return false;
            if (this.user.role === 'superadmin') return true;
            const sum = this.business && this.business.subscription_summary;
            return Boolean(
                (this.business && (this.business.has_active_subscription || this.business.can_install_apk)) ||
                (sum && (sum.has_active_subscription || sum.can_install_apk))
            );
        },

        get activePreviewOutletLabel() {
            const out = this.activePreviewOutletObj;
            return out ? out.name : 'Outlet Utama';
        },
        get activePreviewOutletObj() {
            const list = this.outlets || [];
            return list.find((o) => String(o.id) === String(this.previewModalOutletId)) || list[0] || null;
        },
        get activePreviewBranchTax() {
            const out = this.activePreviewOutletObj;
            if (out && out.tax_percentage !== null && out.tax_percentage !== undefined) {
                return Number(out.tax_percentage);
            }
            return Number((this.business && this.business.tax_percentage) ?? 11);
        },
        get selectedConsultScaleLabel() {
            const f = this.scaleOptions.find((o) => o.value === this.consultForm.scale);
            return f ? f.label : this.consultForm.scale;
        },
        get activePayStatusLabel() {
            const f = this.payStatusOptions.find((o) => o.value === this.payStatusFilter);
            return f ? f.label : 'Semua Status';
        },
        get filteredBillingHistory() {
            const q = (this.paySearch || '').trim().toLowerCase();
            return this.paymentHistory.filter((ph) => {
                if (this.payStatusFilter !== 'all' && String(ph.status || '') !== this.payStatusFilter) return false;
                if (!q) return true;
                return (ph.invoice_number || '').toLowerCase().includes(q) ||
                    ((ph.plan && ph.plan.name) || ph.plan_name_snapshot || '').toLowerCase().includes(q) ||
                    (ph.status || '').toLowerCase().includes(q);
            });
        },
        get payTotalPages() {
            return Math.max(1, Math.ceil(this.filteredBillingHistory.length / this.payPerPage));
        },
        get paginatedBillingHistory() {
            const safe = Math.min(this.payPage, this.payTotalPages);
            const start = (safe - 1) * this.payPerPage;
            return this.filteredBillingHistory.slice(start, start + this.payPerPage);
        },
        get payPaginationRangeLabel() {
            const total = this.filteredBillingHistory.length;
            if (total === 0) return '0 tagihan';
            const safe = Math.min(this.payPage, this.payTotalPages);
            return `Menampilkan ${(safe - 1) * this.payPerPage + 1}–${Math.min(total, safe * this.payPerPage)} dari ${total} tagihan`;
        },
        get selectedBillingPlanObj() {
            return (this.billingPlans || []).find((p) => String(p.id) === String(this.selectedPlanId)) || null;
        },
        get selectedBillingPeriodObj() {
            return (this.billingPeriods || []).find((p) => Number(p.months) === Number(this.billingDurationMonths)) || { months: 1, label: '1 Bulan', days: 30 };
        },
        get selectedBillingPeriodLabel() {
            return this.selectedBillingPeriodObj ? this.selectedBillingPeriodObj.label : '1 Bulan';
        },
        get selectedPlanSummaryText() {
            const pl = this.selectedBillingPlanObj;
            return pl ? `Paket ${pl.name}` : 'Pilih Paket';
        },
        get computedBillingAmount() {
            const pl = this.selectedBillingPlanObj;
            const months = Number(this.billingDurationMonths || 1);
            return pl ? Number(pl.price_monthly || 0) * months : 0;
        },
        get selectedAdjProductLabel() {
            const found = this.productList.find((p) => String(p.id) === String(this.adjProduct));
            return found ? `${found.name} (Stok: ${found.current_stock})` : 'Pilih Produk';
        },

        get filteredAdminBusinesses() {
            const q = (this.adminSearch || '').trim().toLowerCase();
            return this.adminBusinesses.filter((b) => {
                const subStatus = b.subscription_summary ? b.subscription_summary.lifecycle_status : 'none';
                if (this.adminSubFilter === 'suspended' && b.is_active) return false;
                if (this.adminSubFilter !== 'all' && this.adminSubFilter !== 'suspended' && subStatus !== this.adminSubFilter) return false;
                if (!q) return true;
                return (b.name || '').toLowerCase().includes(q) ||
                    (b.owner_name || '').toLowerCase().includes(q) ||
                    (b.email || '').toLowerCase().includes(q) ||
                    (b.city || '').toLowerCase().includes(q) ||
                    (b.business_category || '').toLowerCase().includes(q) ||
                    ((b.subscription_summary && b.subscription_summary.plan_name) || '').toLowerCase().includes(q);
            });
        },

        get adminTotalPages() {
            return Math.max(1, Math.ceil(this.filteredAdminBusinesses.length / this.adminPerPage));
        },

        get paginatedAdminBusinesses() {
            const safePage = Math.min(this.adminPage, this.adminTotalPages);
            const start = (safePage - 1) * this.adminPerPage;
            return this.filteredAdminBusinesses.slice(start, start + this.adminPerPage);
        },

        get adminPaginationRangeLabel() {
            const total = this.filteredAdminBusinesses.length;
            if (total === 0) return '0 klien';
            const safePage = Math.min(this.adminPage, this.adminTotalPages);
            const from = (safePage - 1) * this.adminPerPage + 1;
            const to = Math.min(total, safePage * this.adminPerPage);
            return `Menampilkan ${from}–${to} dari ${total} klien`;
        },

        get filteredAdminActivityLogs() {
            const bizId = this.adminLogBusinessFilter ? String(this.adminLogBusinessFilter) : '';
            const cat = this.adminLogCategoryFilter || 'all';
            const q = (this.adminLogSearch || '').trim().toLowerCase();
            const categoryMap = {
                errors: ['login_failed', 'pos_checkout_failed', 'pos_void_transaction', 'system_exception'],
                auth: ['register_business', 'login_success', 'login_failed', 'logout'],
                pos: ['pos_checkout', 'pos_checkout_failed', 'pos_void_transaction', 'shift_opened', 'shift_closed'],
                admin_billing: ['inventory_adjusted', 'inventory_transferred', 'subscription_invoice_created', 'custom_consultation_submitted', 'admin_extend_subscription', 'admin_activate_business', 'admin_suspend_business', 'admin_delete_business'],
            };
            return this.adminActivityLogs.filter((log) => {
                if (bizId && String(log.business_id || '') !== bizId) return false;
                if (cat !== 'all' && !(categoryMap[cat] || []).includes(log.action)) return false;
                if (!q) return true;
                return (log.action || '').toLowerCase().includes(q) ||
                    ((log.business && log.business.name) || '').toLowerCase().includes(q) ||
                    ((log.user && log.user.name) || '').toLowerCase().includes(q) ||
                    ((log.user && log.user.email) || '').toLowerCase().includes(q) ||
                    (log.ip_address || '').toLowerCase().includes(q) ||
                    JSON.stringify(log.payload || {}).toLowerCase().includes(q);
            });
        },

        get adminLogTotalPages() {
            return Math.max(1, Math.ceil(this.filteredAdminActivityLogs.length / this.adminLogPerPage));
        },

        get paginatedAdminActivityLogs() {
            const safePage = Math.min(this.adminLogPage, this.adminLogTotalPages);
            const start = (safePage - 1) * this.adminLogPerPage;
            return this.filteredAdminActivityLogs.slice(start, start + this.adminLogPerPage);
        },

        get adminLogPaginationRangeLabel() {
            const total = this.filteredAdminActivityLogs.length;
            if (total === 0) return '0 log';
            const safePage = Math.min(this.adminLogPage, this.adminLogTotalPages);
            const from = (safePage - 1) * this.adminLogPerPage + 1;
            const to = Math.min(total, safePage * this.adminLogPerPage);
            return `Menampilkan ${from}–${to} dari ${total} log`;
        },

        toggleDropdown(name) {
            this.openDropdown = this.openDropdown === name ? null : name;
        },

        get actionTargetTitle() {
            if (!this.actionTarget) return '';
            if (this.actionContext === 'consultation') return this.actionTarget.business_name || this.actionTarget.client_name || '-';
            return this.actionTarget.name || '-';
        },

        get actionTargetSubtitle() {
            const t = this.actionTarget;
            if (!t) return '';
            if (this.actionContext === 'client') return t.subscription_summary ? t.subscription_summary.plan_name : '';
            if (this.actionContext === 'product') return `${this.formatRupiah(t.sell_price)} · Stok ${t.current_stock ?? 0}`;
            if (this.actionContext === 'category') return `${t.products_count || 0} item produk`;
            if (this.actionContext === 'staff') return `${t.role === 'admin' ? 'Admin (Opsional)' : 'Kasir POS'} · ${t.email || ''}`;
            if (this.actionContext === 'outlet') return `${t.outlet_code || 'Outlet'}${t.city ? ' · ' + t.city : ''}`;
            if (this.actionContext === 'consultation') return `${t.client_name || '-'} · ${t.consultation_time_slot || ''}`;
            return '';
        },

        openActionPopup(contextOrObj, targetOrEvt, maybeEvt) {
            let context = 'client';
            let targetObj = contextOrObj;
            let evt = targetOrEvt;
            if (typeof contextOrObj === 'string') {
                context = contextOrObj;
                targetObj = targetOrEvt;
                evt = maybeEvt;
            }
            this.actionContext = context;
            this.actionTarget = targetObj;
            if (evt && evt.currentTarget) {
                const rect = evt.currentTarget.getBoundingClientRect();
                const heightMap = { client: 205, consultation: 205, category: 170, product: 140, staff: 140, outlet: 140 };
                const menuHeight = heightMap[context] || 180;
                const openAbove = (window.innerHeight - rect.bottom) < menuHeight;
                const top = openAbove ? Math.max(12, rect.top - menuHeight - 6) : (rect.bottom + 6);
                const right = Math.max(12, window.innerWidth - rect.right);
                this.actionMenuStyle = `top: ${Math.round(top)}px; right: ${Math.round(right)}px;`;
            }
            this.showActionPopup = true;
        },

        runActionFromPopup(kind) {
            const target = this.actionTarget;
            this.showActionPopup = false;
            if (!target) return;
            if (kind === 'activate') return this.openActivateModal(target);
            if (kind === 'detail') return this.openClientDetail(target.id);
            if (kind === 'toggle') return this.confirmToggleBusinessStatus(target);
            if (kind === 'delete') return this.confirmDeleteBusiness(target);
            if (kind === 'edit_product') return this.openEditProductModal(target);
            if (kind === 'archive_product') return this.archiveProduct(target.id);
            if (kind === 'view_category_products') return this.filterProductsByCategory(target.id);
            if (kind === 'edit_category') return this.openEditCategoryModal(target);
            if (kind === 'delete_category') return this.deleteCategory(target);
            if (kind === 'edit_staff') return this.openEditStaffModal(target);
            if (kind === 'delete_staff') return this.deleteStaffMember(target);
            if (kind === 'preview_outlet') return this.openPosPreviewModal(target.id);
            if (kind === 'edit_outlet') return this.openEditOutletModal(target);
            if (kind === 'wa_client') return this.openConsultationWhatsappClient(target);
            if (kind === 'mark_wa_sent') return this.markConsultationWhatsappStatus(target, 'sent_from_sekayasa');
            if (kind === 'mark_wa_completed') return this.markConsultationWhatsappStatus(target, 'completed');
            if (kind === 'consultation_detail' && target.business_id) return this.openClientDetail(target.business_id);
        },

        openConsultationWhatsappClient(lead) {
            if (!lead || !lead.whatsapp_client_url) {
                Swal.fire({ icon: 'warning', title: 'Nomor WhatsApp Kosong', text: 'Klien ini tidak mencantumkan nomor WhatsApp.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            window.open(lead.whatsapp_client_url, '_blank', 'noopener');
        },

        async markConsultationWhatsappStatus(lead, status) {
            const isSent = status === 'sent_from_sekayasa';
            const { value: notes, isConfirmed } = await Swal.fire({
                icon: 'info',
                title: isSent ? 'Tandai Sudah Terkirim di WA (SEKA GROUP)' : 'Tandai Konsultasi Selesai / Terjawab',
                input: 'text',
                inputLabel: 'Catatan Pengingat / Ringkasan Balasan WhatsApp (Opsional)',
                inputValue: lead.reminder_notes || (isSent ? 'Sudah dikirim balasan konfirmasi jadwal & estimasi via WhatsApp SEKA GROUP' : 'Konsultasi telah terjawab & selesai dibahas'),
                showCancelButton: true,
                confirmButtonText: 'Simpan Status Pengingat',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#0D7A5F',
            });
            if (!isConfirmed) return;

            const res = await this.authFetch(`/api/admin/consultations/${lead.id}/whatsapp-status`, {
                method: 'POST',
                body: JSON.stringify({
                    whatsapp_status: status,
                    reminder_notes: (notes || '').trim() || null,
                }),
            });
            const result = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Pengingat Diperbarui', text: result.message, timer: 1600, showConfirmButton: false });
                await this.loadAdminPlatform();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Gagal memperbarui status WhatsApp.', confirmButtonColor: '#0D7A5F' });
            }
        },

        firstPermittedTab() {
            if (!this.user || this.user.role === 'superadmin') return 'admin_platform';
            if (this.user.role === 'owner') {
                const qp = new URLSearchParams(window.location.search).get('tab');
                const cand = qp || this.initialServerTab;
                if (!this.hasUnlockedWorkspace) {
                    if (cand === 'consultation' || (this.business && this.business.service_type === 'custom_pos' && cand !== 'billing')) {
                        return 'consultation';
                    }
                    return 'billing';
                }
                if (['overview', 'products', 'inventory', 'reports', 'staff', 'outlets', 'settings', 'billing', 'consultation'].includes(cand)) {
                    return cand;
                }
                if (this.business && this.business.service_type === 'custom_pos') return 'consultation';
                return 'overview';
            }
            if (!this.hasUnlockedWorkspace) return 'locked';
            if (this.hasFeature('admin_view_dashboard')) return 'overview';
            if (this.hasFeature('admin_manage_products') || this.hasFeature('admin_manage_categories')) return 'products';
            if (this.hasFeature('admin_manage_inventory')) return 'inventory';
            if (this.hasFeature('admin_view_sales_report') || this.hasFeature('admin_view_shift_history')) return 'reports';
            return 'overview';
        },

        async initDashboard() {
            if (!this.token) { window.location.href = '/login'; return; }
            try {
                await this.loadInitialProfile();
                if (this.user && this.user.role === 'cashier') {
                    window.location.href = '/pos';
                    return;
                }
                if (this.user && this.user.role === 'superadmin') {
                    this.setTab('admin_platform');
                } else {
                    const startTab = this.firstPermittedTab();
                    if (this.hasUnlockedWorkspace) {
                        await Promise.all([
                            this.loadCategories(),
                            this.user && this.user.role === 'owner' ? this.loadStaffData() : Promise.resolve(),
                        ]);
                    }
                    this.setTab(startTab);
                }
            } finally {
                this.isBooting = false;
            }
        },
        setAdminSubTab(sub) {
            this.mobileMenuOpen = false;
            this.adminSubTab = sub;
            if (this.tab !== 'admin_platform') {
                this.setTab('admin_platform');
            } else {
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            }
        },
        async openClientDetail(businessId) {
            this.mobileMenuOpen = false;
            this.selectedClientDetail = { business: { outlets: [] }, subscription_summary: {}, activity_logs: [], users: [], payments: [], transactions: [] };
            this.clientDetailTab = 'logs';
            this.showClientDetailModal = true;
            const res = await this.authFetch(`/api/admin/businesses/${businessId}`);
            const result = await res.json();
            if (res.ok && result.data) {
                this.selectedClientDetail = result.data;
                const currentPlanId = result.data.subscription_summary?.plan_id || (this.adminPlans[0]?.id || '');
                this.extendForm = { plan_id: currentPlanId, days: 30, status: 'active' };
            }
        },
        openActivateModal(businessObj) {
            this.activateTarget = businessObj;
            const currentPlanId = businessObj.subscription_summary?.plan_id || (this.adminPlans[0]?.id || '');
            const isAlreadyActive = businessObj.subscription_summary && businessObj.subscription_summary.is_active;
            this.activateForm = {
                plan_id: currentPlanId,
                days: isAlreadyActive ? 30 : 14,
                status: isAlreadyActive ? 'active' : 'trial',
            };
            this.showActivateModal = true;
        },
        async applyClientSubscription(bizId, payload) {
            const res = await this.authFetch(`/api/admin/businesses/${bizId}/extend-subscription`, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            const result = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Langganan Diaktifkan', text: result.message, confirmButtonColor: '#0D7A5F' });
                await this.loadAdminPlatform();
                return true;
            }
            Swal.fire({ icon: 'error', title: 'Gagal Mengaktifkan', text: result.message || 'Terjadi kesalahan', confirmButtonColor: '#0D7A5F' });
            return false;
        },
        async submitActivateModal() {
            if (!this.activateTarget) return;
            const ok = await this.applyClientSubscription(this.activateTarget.id, this.activateForm);
            if (ok) this.showActivateModal = false;
        },
        async submitExtendSubscription() {
            if (!this.selectedClientDetail.business) return;
            const bizId = this.selectedClientDetail.business.id;
            const ok = await this.applyClientSubscription(bizId, this.extendForm);
            if (ok) await this.openClientDetail(bizId);
        },
        async confirmToggleBusinessStatus(businessObj) {
            const willFreeze = Boolean(businessObj.is_active);
            const safeName = window.tandhaEscapeHtml(businessObj.name);
            const { isConfirmed } = await Swal.fire({
                icon: 'warning',
                title: willFreeze ? 'Peringatan: Bekukan Akses Login?' : 'Buka Blokir Akses Login?',
                html: willFreeze
                    ? `<div style="text-align:left;font-size:13px;line-height:1.5">Membekukan <b>${safeName}</b> akan memblokir <b>${Number(businessObj.users_count || 0)} akun pengguna</b> (owner &amp; kasir) sehingga mereka tidak bisa login ke kasir POS maupun dashboard sampai Anda membukanya lagi.</div>`
                    : `<div style="text-align:left;font-size:13px;line-height:1.5">Akses login untuk seluruh akun bisnis <b>${safeName}</b> akan diizinkan masuk kembali ke sistem.</div>`,
                showCancelButton: true,
                confirmButtonText: willFreeze ? 'Ya, Bekukan Akses' : 'Ya, Buka Blokir',
                cancelButtonText: 'Batal',
                confirmButtonColor: willFreeze ? '#D97706' : '#0D7A5F',
            });
            if (!isConfirmed) return;

            const res = await this.authFetch(`/api/admin/businesses/${businessObj.id}/toggle-status`, { method: 'POST' });
            const result = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Akses Login Diperbarui', text: result.message, confirmButtonColor: '#0D7A5F' });
                await this.loadAdminPlatform();
            }
        },
        async confirmDeleteBusiness(businessObj) {
            const safeName = window.tandhaEscapeHtml(businessObj.name);
            const { value: typedName, isConfirmed } = await Swal.fire({
                icon: 'warning',
                title: 'Peringatan: Hapus Permanen Klien!',
                html: `<div style="text-align:left;font-size:13px;line-height:1.5">
                    Anda akan menghapus permanen bisnis <b>${safeName}</b>.<br><br>
                    Semua data terkait (<b>${Number(businessObj.outlets_count || 0)} outlet</b>, <b>${Number(businessObj.users_count || 0)} akun staf/owner</b>, <b>${Number(businessObj.products_count || 0)} produk</b>, <b>${Number(businessObj.transactions_count || 0)} transaksi</b>, serta riwayat langganan &amp; konsultasi) akan terhapus dan <b>tidak dapat dikembalikan</b>.<br><br>
                    Ketik nama bisnis persis <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-weight:700">${safeName}</code> untuk konfirmasi:
                </div>`,
                input: 'text',
                inputPlaceholder: businessObj.name,
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus Permanen',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#DC2626',
                preConfirm: (val) => {
                    if ((val || '').trim() !== businessObj.name) {
                        Swal.showValidationMessage(`Ketik "${businessObj.name}" persis untuk melanjutkan penghapusan.`);
                        return false;
                    }
                    return val;
                },
            });
            if (!isConfirmed || !typedName) return;

            const res = await this.authFetch(`/api/admin/businesses/${businessObj.id}`, { method: 'DELETE' });
            const result = await res.json();
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Klien Dihapus', text: result.message, confirmButtonColor: '#0D7A5F' });
                await this.loadAdminPlatform();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: result.message || 'Gagal menghapus data klien.', confirmButtonColor: '#0D7A5F' });
            }
        },
        async toggleBusinessStatus(businessId) {
            const biz = this.adminBusinesses.find((b) => b.id === businessId) || { id: businessId, name: 'klien ini', is_active: true };
            await this.confirmToggleBusinessStatus(biz);
        },
        async authFetch(url, options = {}) {
            const { silent = false, ...fetchOptions } = options;
            const isFormData = fetchOptions.body instanceof FormData;
            const defaultHeaders = { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' };
            if (!isFormData) defaultHeaders['Content-Type'] = 'application/json';
            fetchOptions.headers = { ...defaultHeaders, ...fetchOptions.headers };
            if (!silent) this.isFetching += 1;
            try {
                const res = await fetch(url, fetchOptions);
                if (res.status === 401) { localStorage.removeItem('pos_token'); window.location.href = '/login'; throw new Error('Expired'); }
                return res;
            } finally {
                if (!silent) this.isFetching = Math.max(0, this.isFetching - 1);
            }
        },
        async loadInitialProfile() {
            const res = await this.authFetch('/api/me');
            const data = await res.json();
            if (data.status === 'success') {
                this.user = data.data;
                localStorage.setItem('pos_user', JSON.stringify(data.data));
                this.business = data.data.business || {};
                this.outlets = this.business.outlets || [];
                window.__tandhaCanInstallApk = Boolean(this.business && this.business.can_install_apk);
                this.businessForm = {
                    name: this.business.name || '',
                    phone: this.business.phone || '',
                    address: this.business.address || '',
                    tax_percentage: Number(this.business.tax_percentage ?? 11),
                    receipt_footer: this.business.receipt_footer || '',
                };
                this.featureSettingsForm = {
                    ...this.defaultFeatureSettings(),
                    ...(this.business.feature_settings || {}),
                };
            }
        },
        setTab(t) {
            this.mobileMenuOpen = false;
            if ((t === 'staff' || t === 'outlets' || t === 'settings' || t === 'billing' || t === 'consultation') && (!this.user || this.user.role !== 'owner')) {
                t = this.firstPermittedTab();
            }
            if (this.user && this.user.role === 'owner' && !this.hasUnlockedWorkspace && !['billing', 'consultation'].includes(t)) {
                t = 'billing';
            }
            if (this.user && this.user.role === 'admin') {
                if (!this.hasUnlockedWorkspace) {
                    this.tab = 'locked';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    return;
                }
                if (t === 'overview' && !this.hasFeature('admin_view_dashboard')) t = this.firstPermittedTab();
                if (t === 'products' && !this.hasFeature('admin_manage_products') && !this.hasFeature('admin_manage_categories')) t = this.firstPermittedTab();
                if (t === 'inventory' && !this.hasFeature('admin_manage_inventory')) t = this.firstPermittedTab();
                if (t === 'reports' && !this.hasFeature('admin_view_sales_report') && !this.hasFeature('admin_view_shift_history')) t = this.firstPermittedTab();
            }
            this.tab = t;
            const tasks = [];
            if (t === 'overview') tasks.push(this.loadOverview());
            if (t === 'products') {
                if (this.user && this.user.role === 'admin' && !this.hasFeature('admin_manage_products') && this.hasFeature('admin_manage_categories')) {
                    this.productSubTab = 'categories';
                }
                if (this.user.role === 'owner' || this.hasFeature('admin_manage_products')) tasks.push(this.loadProductsTable());
                tasks.push(this.loadCategories());
            }
            if (t === 'inventory') tasks.push(this.loadInventoryData());
            if (t === 'reports') {
                if (this.user && this.user.role === 'admin' && !this.hasFeature('admin_view_sales_report') && this.hasFeature('admin_view_shift_history')) {
                    this.reportSubTab = 'shifts';
                }
                tasks.push(this.loadReports());
            }
            if (t === 'staff') tasks.push(this.loadStaffData());
            if (t === 'settings') {
                this.featureSettingsForm = {
                    ...this.defaultFeatureSettings(),
                    ...(this.business.feature_settings || {}),
                };
            }
            if (t === 'billing' || t === 'consultation') {
                tasks.push(this.loadClientPortalData());
            }
            if (t === 'admin_platform') tasks.push(this.loadAdminPlatform());
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            return Promise.all(tasks);
        },
        async loadAdminPlatform() {
            const res = await this.authFetch('/api/admin/platform-overview');
            const data = await res.json();
            if (data.status === 'success') {
                this.adminMetrics = data.data.metrics || {};
                this.adminPlans = data.data.plans || [];
                this.adminBusinesses = data.data.businesses || [];
                this.adminConsultations = data.data.consultations || [];
                this.adminErrorLogs = data.data.error_logs || [];
                this.adminActivityLogs = data.data.activity_logs || [];
                this.adminRecentTransactions = data.data.recent_transactions || [];
            }
        },
        onOutletChange() { this.refreshData(); },
        async refreshData() {
            if (this.isRefreshing) return;
            this.isRefreshing = true;
            try {
                await Promise.all([
                    this.loadInitialProfile(),
                    this.setTab(this.tab),
                ]);
            } finally {
                this.isRefreshing = false;
            }
        },

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
                        borderColor: '#0D7A5F',
                        backgroundColor: 'rgba(13, 122, 95, 0.14)',
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0D2620',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (val) => 'Rp ' + (val / 1000) + 'k' } } }
                }
            });
        },
        async loadCategories() {
            const res = await this.authFetch('/api/categories');
            const data = await res.json();
            if (data.status === 'success') this.categories = data.data || [];
        },
        openCreateCategoryModal() {
            this.categoryForm = { id: null, name: '', description: '' };
            this.showCategoryModal = true;
        },
        openEditCategoryModal(cat) {
            this.categoryForm = { id: cat.id, name: cat.name || '', description: cat.description || '' };
            this.showCategoryModal = true;
        },
        async submitCategoryForm() {
            const isEdit = Boolean(this.categoryForm.id);
            const url = isEdit ? `/api/categories/${this.categoryForm.id}` : '/api/categories';
            const res = await this.authFetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                body: JSON.stringify({
                    name: this.categoryForm.name,
                    description: this.categoryForm.description || null,
                }),
            });
            const result = await res.json();
            if (res.ok) {
                this.showCategoryModal = false;
                await this.loadCategories();
                await this.loadProductsTable();
                this.showSaveSuccessWithPreview(isEdit ? 'Kategori Diperbarui' : 'Kategori Ditambahkan', result.message);
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Menyimpan Kategori', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        async deleteCategory(cat) {
            const count = Number(cat.products_count ?? (cat.products ? cat.products.length : 0));
            const { isConfirmed } = await Swal.fire({
                icon: 'warning',
                title: `Hapus Kategori "${cat.name}"?`,
                text: count > 0
                    ? `${count} produk di kategori ini tidak akan terhapus, tetapi status kategorinya menjadi "Belum dikategorikan".`
                    : 'Kategori kosong ini akan dihapus permanen.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#DC2626',
            });
            if (!isConfirmed) return;
            const res = await this.authFetch(`/api/categories/${cat.id}`, { method: 'DELETE' });
            const result = await res.json();
            if (res.ok) {
                if (String(this.prodCatFilter) === String(cat.id)) this.prodCatFilter = '';
                await this.loadCategories();
                await this.loadProductsTable();
                Swal.fire({ icon: 'success', title: 'Kategori Dihapus', text: result.message, timer: 1400, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: result.message || 'Gagal menghapus kategori.', confirmButtonColor: '#0D7A5F' });
            }
        },
        filterProductsByCategory(catId) {
            this.prodCatFilter = String(catId);
            this.prodPage = 1;
            this.productSubTab = 'products';
        },
        async loadProductsTable() {
            let url = `/api/products?${this.selectedOutletId ? 'outlet_id=' + this.selectedOutletId : ''}`;
            const res = await this.authFetch(url);
            const data = await res.json();
            if (data.status === 'success') {
                this.productList = data.data || [];
                if (this.productList.length > 0 && !this.adjProduct) this.adjProduct = this.productList[0].id;
            }
        },
        openCreateProductModal() {
            this.newProduct = { name: '', category_id: '', sku: '', barcode: '', buy_price: 0, sell_price: 0, initial_stock: 50 };
            this.newProductFile = null;
            this.newProductPreview = '';
            const input = document.getElementById('create-product-image');
            if (input) input.value = '';
            this.showCreateProductModal = true;
        },
        onCreateProductFileChange(evt) {
            const file = evt.target.files[0];
            this.newProductFile = file || null;
            this.newProductPreview = file ? URL.createObjectURL(file) : '';
        },
        async submitCreateProduct() {
            if (!this.newProductFile) {
                Swal.fire({ icon: 'warning', title: 'Gambar Wajib Diisi', text: 'Silakan unggah foto/gambar produk terlebih dahulu.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            const fd = new FormData();
            fd.append('name', this.newProduct.name);
            if (this.newProduct.category_id) fd.append('category_id', this.newProduct.category_id);
            if (this.newProduct.sku) fd.append('sku', this.newProduct.sku);
            if (this.newProduct.barcode) fd.append('barcode', this.newProduct.barcode);
            fd.append('buy_price', String(this.newProduct.buy_price ?? 0));
            fd.append('sell_price', String(this.newProduct.sell_price ?? 0));
            fd.append('initial_stock', String(this.newProduct.initial_stock ?? 0));
            fd.append('outlet_id', String(this.selectedOutletId || (this.outlets[0] ? this.outlets[0].id : 1)));
            fd.append('image', this.newProductFile);

            const res = await this.authFetch('/api/products', { method: 'POST', body: fd });
            const result = await res.json();
            if (res.ok) {
                this.showCreateProductModal = false;
                this.newProductFile = null;
                this.newProductPreview = '';
                await this.loadProductsTable();
                this.showSaveSuccessWithPreview('Produk Ditambahkan', result.message);
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Menambah Produk', text: firstErr || result.message || 'Periksa kembali data & gambar produk.', confirmButtonColor: '#0D7A5F' });
            }
        },
        openEditProductModal(p) {
            this.editProduct = {
                id: p.id,
                name: p.name || '',
                category_id: p.category_id || '',
                sku: p.sku || '',
                barcode: p.barcode || '',
                buy_price: Number(p.buy_price ?? 0),
                sell_price: Number(p.sell_price ?? 0),
            };
            this.editProductFile = null;
            this.editProductPreview = p.image_url || '';
            const input = document.getElementById('edit-product-image');
            if (input) input.value = '';
            this.showEditProductModal = true;
        },
        onEditProductFileChange(evt) {
            const file = evt.target.files[0];
            this.editProductFile = file || null;
            if (file) this.editProductPreview = URL.createObjectURL(file);
        },
        async submitEditProduct() {
            if (!this.editProduct.id) return;
            const fd = new FormData();
            fd.append('name', this.editProduct.name);
            if (this.editProduct.category_id) fd.append('category_id', this.editProduct.category_id);
            if (this.editProduct.sku) fd.append('sku', this.editProduct.sku);
            if (this.editProduct.barcode) fd.append('barcode', this.editProduct.barcode);
            fd.append('buy_price', String(this.editProduct.buy_price ?? 0));
            fd.append('sell_price', String(this.editProduct.sell_price ?? 0));
            if (this.editProductFile) fd.append('image', this.editProductFile);

            const res = await this.authFetch(`/api/products/${this.editProduct.id}`, { method: 'POST', body: fd });
            const result = await res.json();
            if (res.ok) {
                this.showEditProductModal = false;
                await this.loadProductsTable();
                this.showSaveSuccessWithPreview('Produk Diperbarui', result.message || 'Perubahan menu, harga, atau foto siap ditinjau di Kasir.');
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Memperbarui', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        async archiveProduct(id) {
            const { isConfirmed } = await Swal.fire({ title: 'Arsipkan Produk?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#0D7A5F' });
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
            if (res.ok) {
                this.showAdjustmentModal = false;
                this.adjReason = '';
                await this.loadInventoryData();
                this.showSaveSuccessWithPreview('Stok Disesuaikan', 'Saldo stok outlet terbaru langsung tampil di terminal kasir.');
            }
        },
        async loadReports() {
            const qs = `start_date=${this.reportStartDate}&end_date=${this.reportEndDate}${this.selectedOutletId ? '&outlet_id=' + this.selectedOutletId : ''}`;
            if (this.user && this.user.role === 'owner') {
                const res = await this.authFetch(`/api/reports/profit?${qs}`);
                const data = await res.json();
                if (data.status === 'success') this.profitData = data.data;
                if (this.reportSubTab === 'shifts') await this.loadShiftHistory();
            } else {
                if (this.hasFeature('admin_view_sales_report')) {
                    const res = await this.authFetch(`/api/reports/sales?${qs}`);
                    const data = await res.json();
                    if (data.status === 'success') this.salesReportData = data.data;
                }
                if (this.reportSubTab === 'shifts' && this.hasFeature('admin_view_shift_history')) {
                    await this.loadShiftHistory();
                }
            }
        },
        async loadShiftHistory() {
            if (!this.user || (this.user.role !== 'owner' && !this.hasFeature('admin_view_shift_history'))) return;
            const url = `/api/shifts/history${this.selectedOutletId ? '?outlet_id=' + this.selectedOutletId : ''}`;
            const res = await this.authFetch(url);
            const data = await res.json();
            if (data.status === 'success') {
                this.shiftHistory = data.data ? (data.data.data || []) : [];
                this.shiftPage = 1;
            }
        },
        async loadStaffData() {
            if (!this.user || this.user.role !== 'owner') return;
            const res = await this.authFetch('/api/staff');
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                this.staffMembers = data.data.members || [];
                this.staffLimits = data.data.limits || {};
            }
        },
        openCreateStaffModal() {
            if ((this.staffLimits.remaining_users || 0) <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kuota Staf Paket Penuh',
                    text: `Paket ${this.staffLimits.plan_name || ''} maksimal ${this.staffLimits.max_users || 0} pengguna. Upgrade paket Anda untuk menambah akun kasir/admin baru.`,
                    confirmButtonText: 'Kelola Paket',
                    showCancelButton: true,
                    confirmButtonColor: '#0D7A5F',
                }).then((r) => {
                    if (r.isConfirmed) this.setTab('billing');
                });
                return;
            }
            this.newStaff = {
                name: '',
                email: '',
                phone: '',
                role: 'cashier',
                outlet_id: this.outlets[0] ? this.outlets[0].id : '',
                password: '',
            };
            this.showCreateStaffModal = true;
        },
        async submitCreateStaff() {
            const res = await this.authFetch('/api/staff', {
                method: 'POST',
                body: JSON.stringify(this.newStaff),
            });
            const result = await res.json();
            if (res.ok) {
                this.showCreateStaffModal = false;
                await this.loadStaffData();
                Swal.fire({ icon: 'success', title: 'Akun Staf Dibuat', text: result.message, confirmButtonColor: '#0D7A5F' });
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Menambah Staf', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        openEditStaffModal(member) {
            this.editStaff = {
                id: member.id,
                name: member.name,
                role: member.role,
                outlet_id: member.outlet_id || (this.outlets[0] ? this.outlets[0].id : ''),
                is_active: Boolean(member.is_active),
                password: '',
            };
            this.showEditStaffModal = true;
        },
        async submitEditStaff() {
            const payload = {
                name: this.editStaff.name,
                role: this.editStaff.role,
                outlet_id: this.editStaff.outlet_id,
                is_active: this.editStaff.is_active,
            };
            if (this.editStaff.password) payload.password = this.editStaff.password;
            const res = await this.authFetch(`/api/staff/${this.editStaff.id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            const result = await res.json();
            if (res.ok) {
                this.showEditStaffModal = false;
                await this.loadStaffData();
                Swal.fire({ icon: 'success', title: 'Staf Diperbarui', text: result.message, timer: 1500, showConfirmButton: false });
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        async deleteStaffMember(member) {
            const { isConfirmed } = await Swal.fire({
                icon: 'warning',
                title: `Hapus akun ${member.name}?`,
                text: 'Akun staf ini tidak lagi dapat login ke sistem POS maupun dashboard.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus Staf',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#DC2626',
            });
            if (!isConfirmed) return;
            const res = await this.authFetch(`/api/staff/${member.id}`, { method: 'DELETE' });
            if (res.ok) {
                await this.loadStaffData();
                Swal.fire({ icon: 'success', title: 'Staf Dihapus', timer: 1400, showConfirmButton: false });
            }
        },
        async submitCreateOutlet() {
            const res = await this.authFetch('/api/outlets', {
                method: 'POST',
                body: JSON.stringify(this.newOutlet),
            });
            const result = await res.json();
            if (res.ok) {
                this.showCreateOutletModal = false;
                this.newOutlet = { name: '', outlet_code: '', city: '', phone: '', address: '' };
                await this.loadInitialProfile();
                Swal.fire({ icon: 'success', title: 'Outlet Ditambahkan', text: result.message, confirmButtonColor: '#0D7A5F' });
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Menambah Outlet', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        openEditOutletModal(out) {
            const overrides = {};
            const rawOverrides = (out && out.feature_overrides) ? out.feature_overrides : {};
            for (const item of this.cashierFeatureToggleMeta) {
                overrides[item.key] = (rawOverrides[item.key] === undefined) ? null : rawOverrides[item.key];
            }
            this.editOutletForm = {
                id: out.id,
                name: out.name || '',
                outlet_code: out.outlet_code || '',
                city: out.city || '',
                phone: out.phone || '',
                address: out.address || '',
                is_active: Boolean(out.is_active),
                receipt_header: out.receipt_header || '',
                receipt_footer: out.receipt_footer || '',
                tax_percentage: (out.tax_percentage !== null && out.tax_percentage !== undefined) ? String(out.tax_percentage) : '',
                feature_overrides: overrides,
            };
            this.showEditOutletModal = true;
        },
        async submitEditOutlet() {
            if (!this.editOutletForm.id) return;
            const cleanedOverrides = {};
            for (const item of this.cashierFeatureToggleMeta) {
                const val = this.editOutletForm.feature_overrides[item.key];
                if (val === true || val === false) cleanedOverrides[item.key] = val;
            }
            const payload = {
                name: this.editOutletForm.name,
                outlet_code: this.editOutletForm.outlet_code || null,
                city: this.editOutletForm.city || null,
                phone: this.editOutletForm.phone || null,
                address: this.editOutletForm.address || null,
                is_active: Boolean(this.editOutletForm.is_active),
                receipt_header: this.editOutletForm.receipt_header || null,
                receipt_footer: this.editOutletForm.receipt_footer || null,
                tax_percentage: (this.editOutletForm.tax_percentage !== '' && this.editOutletForm.tax_percentage !== null) ? Number(this.editOutletForm.tax_percentage) : null,
                feature_overrides: cleanedOverrides,
            };
            const res = await this.authFetch(`/api/outlets/${this.editOutletForm.id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            const result = await res.json();
            if (res.ok) {
                const savedOutletId = this.editOutletForm.id;
                this.showEditOutletModal = false;
                await this.loadInitialProfile();
                this.showSaveSuccessWithPreview('Pengaturan Cabang Disimpan', result.message, savedOutletId);
            } else {
                const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                Swal.fire({ icon: 'error', title: 'Gagal Memperbarui Cabang', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
            }
        },
        resetFeatureSettings() {
            this.featureSettingsForm = this.defaultFeatureSettings();
            Swal.fire({
                icon: 'info',
                title: 'Reset ke Default',
                text: 'Semua switch telah dikembalikan ke pengaturan default. Klik "Simpan Pengaturan Fitur" untuk menerapkan.',
                confirmButtonColor: '#0D7A5F',
            });
        },
        async saveFeatureSettings() {
            this.isSaving = true;
            try {
                const res = await this.authFetch('/api/business/settings', {
                    method: 'PUT',
                    body: JSON.stringify({ feature_settings: this.featureSettingsForm }),
                });
                const result = await res.json();
                if (res.ok) {
                    await this.loadInitialProfile();
                    this.showSaveSuccessWithPreview('Pengaturan Fitur Disimpan', result.message);
                } else {
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan Pengaturan', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
                }
            } finally {
                this.isSaving = false;
            }
        },
        async saveBusinessProfile() {
            this.isSaving = true;
            try {
                const res = await this.authFetch('/api/business/profile', {
                    method: 'PUT',
                    body: JSON.stringify(this.businessForm),
                });
                const result = await res.json();
                if (res.ok) {
                    this.editBusinessMode = false;
                    await this.loadInitialProfile();
                    this.showSaveSuccessWithPreview('Profil Disimpan', result.message);
                } else {
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: firstErr || result.message, confirmButtonColor: '#0D7A5F' });
                }
            } finally {
                this.isSaving = false;
            }
        },
        async loadClientPortalData() {
            if (!this.user || this.user.role !== 'owner') return;
            this.billingLoading = true;
            try {
                const res = await this.authFetch('/api/client/overview');
                const result = await res.json();
                if (res.ok && result.data) {
                    this.billingSummary = result.data.subscription_summary || null;
                    this.billingUsage = result.data.usage || { outlets_count: 0, users_count: 0, products_count: 0 };
                    this.billingPlans = result.data.plans || [];
                    if (Array.isArray(result.data.billing_periods) && result.data.billing_periods.length > 0) {
                        this.billingPeriods = result.data.billing_periods;
                    }
                    this.billingProjects = result.data.projects || [];
                    this.pendingPayment = result.data.pending_payment || null;
                    this.paymentHistory = result.data.payment_history || [];
                    this.consultationGuard = result.data.consultation_guard || { can_submit: true, reason: null, weekly_used: 0, weekly_limit: 2, active_whatsapp_url: null };
                    if (this.user && !this.consultForm.phone) this.consultForm.phone = this.user.phone || '';
                    if (this.billingPlans.length > 0 && !this.selectedPlanId) {
                        const sub = this.business && this.business.current_subscription;
                        this.selectedPlanId = (sub && sub.plan_id) ? sub.plan_id : this.billingPlans[0].id;
                    }
                    if (result.data.can_install_apk !== undefined) {
                        this.business.can_install_apk = Boolean(result.data.can_install_apk);
                        window.__tandhaCanInstallApk = Boolean(result.data.can_install_apk);
                    }
                    if (this.pendingPayment) {
                        this.startPaymentPolling();
                    } else {
                        this.stopPaymentPolling();
                    }
                    this.billingLoaded = true;
                }
            } finally {
                this.billingLoading = false;
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
            this.checkingPayment = true;
            try {
                await this.checkPaymentStatus(true);
            } finally {
                this.checkingPayment = false;
            }
        },
        async checkPaymentStatus(showPendingAlert = false) {
            if (!this.pendingPayment) return;
            const res = await this.authFetch(`/api/client/payments/${this.pendingPayment.id}/status`, { silent: !showPendingAlert });
            const result = await res.json();
            if (res.ok && result.data) {
                if (result.data.payment_status === 'verified') {
                    this.stopPaymentPolling();
                    const paidId = this.pendingPayment.id;
                    this.pendingPayment = null;
                    const dlg = await Swal.fire({
                        icon: 'success',
                        title: 'Pembayaran Terverifikasi',
                        text: 'Paket langganan Anda kini aktif dan Nota Pembayaran Resmi telah diterbitkan.',
                        showCancelButton: true,
                        confirmButtonText: 'Lihat Nota Lunas',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#0D7A5F',
                    });
                    await Promise.all([this.loadInitialProfile(), this.loadClientPortalData(), this.loadStaffData()]);
                    if (dlg.isConfirmed) {
                        await this.openPaymentReceiptModal(paidId);
                    }
                } else if (result.data.payment_status === 'expired') {
                    this.stopPaymentPolling();
                    this.pendingPayment = null;
                    await this.loadClientPortalData();
                } else if (showPendingAlert) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Menunggu Verifikasi',
                        text: 'Tagihan masih menunggu konfirmasi Admin atau penyelesaian pembayaran.',
                        confirmButtonColor: '#0D7A5F',
                    });
                }
            }
        },
        async rentTemplateAction() {
            if (!this.selectedPlanId) return;
            this.submittingRent = true;
            try {
                const res = await this.authFetch('/api/client/rent-template', {
                    method: 'POST',
                    body: JSON.stringify({
                        plan_id: this.selectedPlanId,
                        duration_months: Number(this.billingDurationMonths || 1),
                    }),
                });
                const result = await res.json();
                if (res.ok) {
                    this.pendingPayment = result.data.payment;
                    this.startPaymentPolling();
                    await this.loadClientPortalData();
                    Swal.fire({
                        icon: 'success',
                        title: 'Tagihan Langganan Dibuat',
                        text: `Invoice ${this.pendingPayment.invoice_number} (${this.selectedBillingPeriodLabel}) berhasil dibuat dan menunggu konfirmasi.`,
                        confirmButtonColor: '#0D7A5F',
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Membuat Tagihan', text: result.message || 'Periksa kembali pilihan paket Anda.', confirmButtonColor: '#0D7A5F' });
                }
            } finally {
                this.submittingRent = false;
            }
        },
        openPayActionPopup(paymentObj, evt) {
            this.actionPayment = paymentObj;
            if (evt && evt.currentTarget) {
                const rect = evt.currentTarget.getBoundingClientRect();
                const menuHeight = 110;
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
            const res = await this.authFetch(`/api/client/payments/${paymentId}/receipt`);
            const result = await res.json();
            if (res.ok && result.data) {
                this.activeReceipt = result.data;
                this.showSubReceiptModal = true;
            } else {
                Swal.fire({ icon: 'error', title: 'Nota Tidak Tersedia', text: result.message || 'Nota hanya tersedia untuk tagihan yang sudah lunas.', confirmButtonColor: '#0D7A5F' });
            }
        },
        async submitConsultation() {
            if (this.consultationGuard && !this.consultationGuard.can_submit) {
                Swal.fire({ icon: 'info', title: 'Pengajuan Aktif Sedang Diproses', text: this.consultationGuard.reason, confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.consultForm.business_type === 'Lainnya' && (this.consultForm.business_type_other || '').trim().length < 3) {
                Swal.fire({ icon: 'warning', title: 'Bidang Usaha Lainnya Wajib Diisi', text: 'Tulis nama sektor atau bidang usaha Anda minimal 3 karakter.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.submittingConsult = true;
            try {
                const res = await this.authFetch('/api/client/consultations', {
                    method: 'POST',
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
                    await this.loadClientPortalData();
                    const ask = await Swal.fire({
                        icon: 'success',
                        title: 'Jadwal Konsultasi Disimpan',
                        text: result.message,
                        showCancelButton: Boolean(waUrl),
                        confirmButtonText: waUrl ? 'Buka WhatsApp SEKA GROUP →' : 'Selesai',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#0D7A5F',
                    });
                    if (ask.isConfirmed && waUrl) {
                        window.open(waUrl, '_blank', 'noopener');
                    }
                } else {
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Tidak Dapat Mengirim', text: firstErr || result.message || 'Periksa kembali isian Anda.', confirmButtonColor: '#0D7A5F' });
                    if (res.status === 429) await this.loadClientPortalData();
                }
            } finally {
                this.submittingConsult = false;
            }
        },
        async submitClientUat(tcId, status) {
            const res = await this.authFetch(`/api/client/uat/${tcId}`, {
                method: 'POST',
                body: JSON.stringify({ status }),
            });
            if (res.ok) {
                await this.loadClientPortalData();
            }
        },
        formatConsultStatus(st) {
            const map = {
                new_lead: 'Pengajuan Baru',
                consultation_scheduled: 'Konsultasi Terjadwal',
                discovery: 'Analisis Kebutuhan Usaha',
                proposal_sent: 'Proposal Dikirim',
                payment_pending: 'Menunggu Pembayaran',
                ready_for_dev: 'Siap Dikerjakan',
                in_dev: 'Sedang Dikembangkan',
                client_uat: 'Pengujian UAT Anda',
                ready_to_deploy: 'Siap Rilis Produksi',
                live: 'Aktif Produksi',
                monthly_service_active: 'Layanan Bulanan Aktif',
            };
            return map[st] || st;
        },
        showSaveSuccessWithPreview(title, text = '', outletId = null) {
            if (!this.user || this.user.role !== 'owner') {
                Swal.fire({ icon: 'success', title, text, timer: 1500, showConfirmButton: false });
                return;
            }
            Swal.fire({
                icon: 'success',
                title,
                text,
                showDenyButton: true,
                confirmButtonText: 'Selesai',
                denyButtonText: 'Lihat di Preview Kasir',
                confirmButtonColor: '#0D7A5F',
                denyButtonColor: '#0D2620',
                timer: 7000,
                timerProgressBar: true,
            }).then((res) => {
                if (res.isDenied) {
                    this.openPosPreviewModal(outletId || this.selectedOutletId || null);
                }
            });
        },
        get posPreviewIframeUrl() {
            const oid = this.previewModalOutletId ? `&outlet_id=${encodeURIComponent(this.previewModalOutletId)}` : '';
            return `/pos?preview=1&embed=1${oid}&v=${this.previewModalVersion}`;
        },
        openPosPreviewModal(outletId = null) {
            if (!this.user || this.user.role !== 'owner') return;
            this.mobileMenuOpen = false;
            const list = this.outlets || [];
            const fallbackId = this.selectedOutletId || (list[0] ? list[0].id : '');
            this.previewModalOutletId = outletId ? String(outletId) : String(fallbackId || '');
            this.previewIframeLoading = true;
            this.previewModalVersion += 1;
            this.openDropdown = null;
            this.showPosPreviewModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        selectPosPreviewOutlet(outletId) {
            this.previewModalOutletId = String(outletId || '');
            this.openDropdown = null;
            this.previewIframeLoading = true;
            this.previewModalVersion += 1;
        },
        reloadPosPreview() {
            this.previewIframeLoading = true;
            this.previewModalVersion += 1;
        },
        closePosPreviewModal() {
            this.showPosPreviewModal = false;
            this.previewIframeLoading = false;
            if (this.openDropdown === 'previewOutlet') this.openDropdown = null;
        },
        installKasirApk(mode = 'webapk') {
            if (!this.user || this.user.role !== 'owner') return;
            if (!this.business || !this.business.can_install_apk) {
                Swal.fire({
                    icon: 'info',
                    title: 'Paket Langganan Belum Aktif',
                    text: 'Aktifkan paket langganan terlebih dahulu di menu Langganan & Tagihan untuk memasang aplikasi kasir pada perangkat outlet.',
                    confirmButtonText: 'Buka Langganan & Tagihan',
                    showCancelButton: true,
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#0D7A5F',
                }).then((res) => {
                    if (res.isConfirmed) this.setTab('billing');
                });
                return;
            }
            if (typeof window.triggerTandhaInstallApk === 'function') {
                return window.triggerTandhaInstallApk(mode);
            }
        },
        logout() { localStorage.removeItem('pos_token'); localStorage.removeItem('pos_user'); window.location.href = '/login'; },
        formatRupiah(num) { return 'Rp ' + Number(num || 0).toLocaleString('id-ID'); },
        formatDate(str) { return str ? new Date(str).toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'; }
    }
}
</script>
@endpush
