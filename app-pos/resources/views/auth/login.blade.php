@extends('layouts.app')

@section('title', 'Masuk & Daftar Bisnis — Tandha by SEKA GROUP')

@php
    $turnstile = app(\App\Services\TurnstileService::class);
    $antibotDriver = $turnstile->activeDriver();
    $turnstileActive = $turnstile->isActive();
    $turnstileSiteKey = $turnstile->siteKey();
    $isTurnstileSandbox = $antibotDriver === 'turnstile' && str_starts_with((string) $turnstileSiteKey, '1x00000000');
@endphp

@section('content')
<div class="min-h-screen lg:h-[100dvh] lg:overflow-hidden grid grid-cols-1 bg-[#F7FAF9]" :class="isKasirAppMode ? 'lg:grid-cols-[1.08fr_0.92fr]' : 'lg:grid-cols-[0.9fr_1.1fr]'" x-data="authPage()" x-init="initAuthPage()">

    <!-- =====================================================================
         BRANDING / TERMINAL INFO PANEL — SLATE #0D2620 (Fixed on Desktop, Moves to Right on APK App Mode)
         ===================================================================== -->
    <aside :class="isKasirAppMode ? 'lg:order-2 lg:border-b-0 lg:border-l order-2 border-t lg:border-t-0' : 'lg:border-b-0 lg:border-r order-2 lg:order-1 border-t lg:border-t-0'" class="relative bg-[#0D2620] text-white p-6 sm:p-10 lg:p-12 xl:p-14 flex flex-col justify-between lg:h-[100dvh] lg:overflow-y-auto custom-scrollbar border-[#143B32]">
        <!-- Brand Header (Non-clickable App Identity when in APK Mode; Link to Home in Web Portal) -->
        <div class="relative z-10">
            <a x-show="!isKasirAppMode" href="/" class="inline-flex items-center gap-3">
                <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-10 h-10 object-contain">
                <div class="leading-none">
                    <span class="block text-lg font-extrabold tracking-[0.06em] uppercase text-white">TANDHA</span>
                    <span class="block text-[10px] font-bold tracking-[0.08em] text-[#34D399] mt-0.5">by SEKA GROUP</span>
                    <span class="block text-[11px] font-medium text-slate-300 mt-1.5">Point of Sale &amp; Manajemen Cabang</span>
                </div>
            </a>
            <div x-show="isKasirAppMode" x-cloak class="inline-flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#143B32] border border-[#34D399]/30 p-1.5 flex items-center justify-center shrink-0">
                    <img src="/assets/Logo%20Tandha.svg" alt="Tandha Kasir" class="w-full h-full object-contain">
                </div>
                <div class="leading-none">
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-extrabold tracking-[0.04em] uppercase text-white">TANDHA KASIR</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#34D399] text-[#0D2620] uppercase tracking-wider">Aplikasi Kasir</span>
                    </div>
                    <span class="block text-[10px] font-bold tracking-[0.08em] text-[#34D399] mt-0.5">by SEKA GROUP <span class="font-medium tracking-normal text-slate-300">· Terminal Transaksi Cabang</span></span>
                </div>
            </div>
        </div>

        <!-- Product Context (Website Pitch vs Cashier App Terminal Guidance) -->
        <div class="relative z-10 my-10 lg:my-0 max-w-lg space-y-6">
            <div x-show="!isKasirAppMode">
                <span class="text-xs font-bold tracking-wider uppercase text-[#34D399] block mb-2.5">Portal Akun Usaha Tandha</span>
                <h1 class="text-2xl sm:text-3xl lg:text-[2.1rem] font-extrabold leading-[1.2] tracking-tight text-white">
                    Satu akun bisnis untuk kasir, kontrol stok cabang, dan konsultasi penambahan fitur.
                </h1>
                <p class="mt-3.5 text-sm leading-relaxed text-slate-300">
                    Masuk ke akun terdaftar atau selesaikan pendaftaran bisnis baru dalam 3 langkah singkat untuk menyewa paket template maupun memesan sistem POS sesuai alur usaha Anda.
                </p>
            </div>

            <div x-show="isKasirAppMode" x-cloak>
                <span class="text-xs font-bold tracking-wider uppercase text-[#34D399] block mb-2.5">Terminal Kasir Cabang (APK)</span>
                <h1 class="text-2xl sm:text-3xl lg:text-[2rem] font-extrabold leading-[1.2] tracking-tight text-white">
                    Fokus transaksi kasir cepat, buka shift, dan cetak struk outlet.
                </h1>
                <p class="mt-3.5 text-sm leading-relaxed text-slate-300">
                    Aplikasi ini dikhususkan untuk perangkat kasir di meja pembayaran. Masuk menggunakan akun <b>Kasir (Cashier)</b> yang dibuat oleh Pemilik Usaha pada outlet Anda.
                </p>
            </div>

            <div x-show="!isKasirAppMode" class="border-t border-[#143B32] pt-5 space-y-4 text-xs">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#143B32] border border-white/15 text-[#34D399] flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-white">Isolasi Data &amp; Proteksi Anti-Bot</div>
                        <div class="text-slate-300 mt-0.5 leading-relaxed">Data tiap entitas bisnis dan outlet terpisah ketat, dilindungi autentikasi token &amp; Tandha Shield Anti-Bot.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#143B32] border border-white/15 text-[#34D399] flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-white">Sewa Paket Instan atau Custom SEKA GROUP</div>
                        <div class="text-slate-300 mt-0.5 leading-relaxed">Pilih paket bulanan jika cocok dengan template, atau jadwalkan konsultasi fitur khusus dari akun yang sama.</div>
                    </div>
                </div>
            </div>

            <div x-show="isKasirAppMode" x-cloak class="border-t border-[#143B32] pt-5 space-y-3.5 text-xs">
                <div class="p-3.5 rounded-xl bg-[#143B32]/85 border border-white/10 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#0D2620] border border-[#34D399]/30 text-[#34D399] flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-white">Kontrol Shift &amp; Kas Laci</div>
                        <div class="text-slate-300 mt-0.5 leading-relaxed">Setiap transaksi tunai, QRIS, transfer &amp; stok cabang otomatis tercatat ke shift kasir yang aktif.</div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-[#143B32]/85 border border-white/10 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#0D2620] border border-[#34D399]/30 text-[#34D399] flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-white">Terkunci Khusus Role Kasir</div>
                        <div class="text-slate-300 mt-0.5 leading-relaxed">Akun Owner, Admin, dan pendaftaran bisnis dinonaktifkan di APK ini agar operasional toko tetap terpisah.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collapsible Testing Role Drawer & Footer -->
        <div class="relative z-10 space-y-4">
            <div class="border-t border-[#143B32] pt-4">
                <button type="button" @click="showDemoDrawer = !showDemoDrawer" class="text-[11px] font-semibold text-slate-300 hover:text-[#34D399] flex items-center gap-1.5 transition">
                    <i data-lucide="key-round" class="w-3.5 h-3.5 text-[#34D399]"></i>
                    <span x-text="isKasirAppMode ? 'Akun Simulasi Kasir (Uji Coba)' : 'Akses Akun Simulasi / Uji Coba Role'"></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="showDemoDrawer ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="showDemoDrawer" x-cloak class="mt-3 grid gap-2" :class="isKasirAppMode ? 'grid-cols-1 max-w-xs' : 'grid-cols-2 sm:grid-cols-3'">
                    <button type="button" @click="quickLogin('kasir@kopi.id', 'password123', isKasirAppMode ? '/pos?app=kasir' : '/pos')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Kasir POS</div>
                        <div class="text-[10px] text-slate-300">Layar Transaksi Outlet</div>
                    </button>
                    <button x-show="!isKasirAppMode" type="button" @click="quickLogin('owner@kopi.id', 'password123', '/dashboard')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Owner Bisnis</div>
                        <div class="text-[10px] text-slate-300">Dashboard &amp; Laba</div>
                    </button>
                    <button x-show="!isKasirAppMode" type="button" @click="quickLogin('admin@kopi.id', 'password123', '/dashboard')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Admin Toko</div>
                        <div class="text-[10px] text-slate-300">Stok &amp; Produk</div>
                    </button>
                    <button x-show="!isKasirAppMode" type="button" @click="quickLogin('superadmin@pos.id', 'password123', '/dashboard')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Super Admin</div>
                        <div class="text-[10px] text-slate-300">Monitor Semua Klien</div>
                    </button>
                    <button x-show="!isKasirAppMode" type="button" @click="quickLogin('klien@usaha.id', 'password123', '/dashboard?tab=billing')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Akun Klien Baru</div>
                        <div class="text-[10px] text-slate-300">Langganan / Konsultasi</div>
                    </button>
                    <button x-show="!isKasirAppMode" type="button" @click="quickLogin('michael@kopisenopati.com', 'password123', '/dashboard?tab=consultation')" class="px-2.5 py-2 bg-[#143B32] hover:border-[#0D7A5F] border border-white/15 rounded-lg text-left transition">
                        <div class="text-[11px] font-bold text-white">Klien Konsultasi</div>
                        <div class="text-[10px] text-slate-300">Lacak UAT &amp; Status</div>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#647C76]">
                <span>© 2026 Tandha — Developed by SEKA GROUP</span>
                <span x-text="isKasirAppMode ? 'Aplikasi Kasir Cabang (Stand-alone)' : 'Multi-Outlet POS System'"></span>
            </div>
        </div>
    </aside>

    <!-- =====================================================================
         AUTH PANEL (Right side scrolls independently on Desktop, Left on Kasir APK App Mode)
         ===================================================================== -->
    <main id="auth-main-scroll" :class="isKasirAppMode ? 'order-1 lg:order-1 bg-[#F7FAF9] justify-center' : 'order-1 lg:order-2 bg-white justify-between'" class="flex flex-col p-5 sm:p-10 lg:p-14 lg:h-[100dvh] lg:overflow-y-auto">
        <!-- Website Top Bar (Strictly Hidden in Kasir APK Mode — No back to Beranda / Fitur / Harga / Konsultasi) -->
        <div x-show="!isKasirAppMode" class="max-w-xl w-full mx-auto flex flex-wrap items-center justify-between gap-3 pb-5 border-b border-[#E5E0D5] text-xs font-semibold">
            <a href="/" class="text-[#3D544E] hover:text-[#0D2620] inline-flex items-center gap-1.5 transition">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Beranda Tandha</span>
            </a>
            <div class="flex items-center gap-5 text-[#3D544E]">
                <a href="/fitur" class="hover:text-[#0D7A5F] transition">Fitur POS</a>
                <a href="/harga" class="hover:text-[#0D7A5F] transition">Harga</a>
                <a href="/contact" class="hover:text-[#0D7A5F] transition">Konsultasi</a>
            </div>
        </div>

        <!-- Form Area (Clean White App Card in Kasir APK Mode, Flat Canvas on Website) -->
        <div class="w-full mx-auto space-y-6" :class="isKasirAppMode ? 'max-w-md bg-white p-6 sm:p-8 rounded-2xl border border-[#E5E0D5] shadow-sm my-auto' : 'max-w-xl my-8'">

            <!-- Logged-In Banner -->
            <div x-show="alreadyLoggedIn" class="p-5 bg-[#0D2620] text-white rounded-xl border border-[#143B32] space-y-4" x-cloak>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#34D399]">Sedang Login</span>
                    </div>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-[#143B32] border border-white/15 text-slate-200" x-text="currentUser.role ? currentUser.role.toUpperCase() : ''"></span>
                </div>
                <div>
                    <div class="text-base font-bold text-white" x-text="currentUser.name"></div>
                    <div class="text-xs text-slate-300 mt-0.5" x-text="currentUser.email + (currentUser.business ? ' • ' + currentUser.business.name : '')"></div>
                </div>
                <div class="flex flex-wrap gap-2.5 pt-1">
                    <a :href="resolveRedirectForUser(currentUser, redirectTarget)" class="flex-1 py-2.5 px-4 bg-[#34D399] hover:bg-[#D97706] text-slate-950 rounded-lg text-xs font-bold text-center transition">
                        Lanjutkan ke Akun Saya →
                    </a>
                    <button type="button" @click="logoutCurrent()" class="py-2.5 px-4 bg-[#143B32] border border-white/15 text-slate-300 hover:text-rose-400 rounded-lg text-xs font-semibold transition">
                        Keluar / Ganti Akun
                    </button>
                </div>
            </div>

            <!-- Kasir APK Guard Notice (Refined Architectural Alert Card) -->
            <div x-show="apkBlockedNotice" class="relative overflow-hidden p-4 pl-5 bg-white border border-amber-200/90 rounded-xl shadow-xs text-xs text-slate-700 flex items-start gap-3" x-cloak>
                <div class="absolute inset-y-0 left-0 w-1 bg-amber-500"></div>
                <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                </div>
                <div class="space-y-0.5 leading-relaxed">
                    <div class="font-bold text-[#0D2620]">Akses Dibatasi Khusus Kasir</div>
                    <p class="text-slate-600" x-text="apkBlockedNotice"></p>
                </div>
            </div>

            <!-- Heading + Segmented Tab -->
            <div>
                <div x-show="isKasirAppMode && tab !== 'forgot'" x-cloak class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#E6F4EF] border border-[#0D7A5F]/20 text-[11px] font-bold text-[#0D7A5F] mb-3">
                    <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="w-3.5 h-3.5 object-contain">
                    <span>Tandha Kasir · Mode Aplikasi</span>
                </div>
                <div x-show="tab === 'forgot'" x-cloak class="mb-3">
                    <button type="button" @click="backToLoginTab()"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0D7A5F] hover:text-[#095C47] transition">
                        <span>← Kembali ke Masuk Akun</span>
                    </button>
                </div>
                <h2 class="text-2xl font-extrabold text-[#0D2620] tracking-tight"
                    x-text="tab === 'forgot' ? (forgotStep === 'request' ? 'Lupa Kata Sandi' : 'Atur Kata Sandi Baru') : (isKasirAppMode ? 'Login Kasir Cabang' : (tab === 'login' ? 'Masuk ke Akun Tandha' : 'Daftarkan Bisnis Baru'))"></h2>
                <p class="text-sm text-[#3D544E] mt-1"
                    x-text="tab === 'forgot' ? (forgotStep === 'request' ? 'Masukkan email akun terdaftar Anda untuk menerima 6 digit kode verifikasi reset kata sandi.' : 'Masukkan 6 digit kode yang terkirim ke email Anda beserta kata sandi baru.') : (isKasirAppMode ? 'Masukkan email dan kata sandi akun Kasir Anda untuk membuka terminal penjualan.' : (tab === 'login' ? 'Masukkan email dan kata sandi akun Anda untuk masuk ke portal klien, kasir, atau dashboard.' : 'Lengkapi identitas PIC, profil perusahaan, dan pengaturan awal outlet pertama Anda.'))"></p>

                <div x-show="!isKasirAppMode && tab !== 'forgot'" class="mt-5 p-1 bg-[#F3EFE6] border border-[#E5E0D5] rounded-xl grid grid-cols-2 gap-1">
                    <button type="button" @click="tab = 'login'"
                        :class="tab === 'login' ? 'bg-white text-[#0D2620] shadow-sm border border-[#E5E0D5]' : 'text-[#3D544E] hover:text-[#0D2620] border border-transparent'"
                        class="py-2.5 px-4 rounded-lg text-xs font-bold transition">
                        Masuk Akun
                    </button>
                    <button type="button" @click="tab = 'register'"
                        :class="tab === 'register' ? 'bg-white text-[#0D2620] shadow-sm border border-[#E5E0D5]' : 'text-[#3D544E] hover:text-[#0D2620] border border-transparent'"
                        class="py-2.5 px-4 rounded-lg text-xs font-bold transition">
                        Daftar Bisnis Baru
                    </button>
                </div>
            </div>

            <!-- =================================================================
                 TAB 1: LOGIN FORM
                 ================================================================= -->
            <form x-show="tab === 'login'" @submit.prevent="submitLogin()" class="space-y-4">
                <div>
                    <label for="login-email" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Email Akun Bisnis</label>
                    <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                        <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="mail" class="w-4 h-4"></i></span>
                        <input id="login-email" type="email" x-model="loginData.email" required autocomplete="email" placeholder="nama@perusahaan.co.id"
                            class="w-full py-3 pr-4 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="login-password" class="block text-[13px] font-semibold text-[#0D2620]">Kata Sandi</label>
                        <button type="button" @click="openForgotPassword()" class="text-[11px] font-semibold text-[#0D7A5F] hover:text-[#095C47] hover:underline transition">Lupa kata sandi?</button>
                    </div>
                    <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                        <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="lock" class="w-4 h-4"></i></span>
                        <input id="login-password" :type="showLoginPassword ? 'text' : 'password'" x-model="loginData.password" required autocomplete="current-password" placeholder="Masukkan kata sandi"
                            class="w-full py-3 pr-10 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                        <button type="button" @click="showLoginPassword = !showLoginPassword" class="pr-3.5 pl-2 text-slate-300 hover:text-[#3D544E] focus:outline-none">
                            <i x-show="!showLoginPassword" data-lucide="eye" class="w-4 h-4"></i>
                            <i x-show="showLoginPassword" data-lucide="eye-off" class="w-4 h-4" x-cloak></i>
                        </button>
                    </div>
                </div>

                @if($turnstileActive)
                    @include('auth.partials.antibot-slider', ['ctx' => 'login', 'antibotDriver' => $antibotDriver, 'isTurnstileSandbox' => $isTurnstileSandbox])
                @endif

                <button type="submit" :disabled="loading"
                    class="w-full py-3.5 px-5 bg-[#0D7A5F] hover:bg-[#095C47] text-white font-bold rounded-xl text-sm shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!loading" class="inline-flex items-center gap-2">
                        <span x-text="isKasirAppMode ? 'Buka Terminal Kasir' : 'Masuk ke Akun'"></span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                    <span x-show="loading" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                </button>

                <div x-show="!isKasirAppMode" class="space-y-4">
                    <div class="relative flex py-1.5 items-center">
                        <div class="flex-grow border-t border-[#E5E0D5]"></div>
                        <span class="flex-shrink mx-3 text-[11px] font-semibold text-[#647C76]">ATAU</span>
                        <div class="flex-grow border-t border-[#E5E0D5]"></div>
                    </div>

                    <a href="/auth/google"
                        class="w-full py-3 px-4 bg-white hover:bg-[#F7FAF9] border border-[#D1C9B8] text-[#0D2620] font-semibold rounded-xl text-xs shadow-xs transition flex items-center justify-center gap-2.5">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.8C6.2 7.2 8.9 5 12 5z"/>
                            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.6l3.7 2.9c2.2-2 3.7-5 3.7-8.7z"/>
                            <path fill="#FBBC05" d="M5.3 14.8c-.2-.8-.4-1.6-.4-2.5s.2-1.7.4-2.5L1.6 7C.6 9 0 10.6 0 12.3s.6 3.3 1.6 5.3l3.7-2.8z"/>
                            <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-2.9l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.1-6.7-5l-3.7 2.9C3.5 20.9 7.4 23.5 12 23.5z"/>
                        </svg>
                        <span>Masuk dengan Akun Google</span>
                    </a>
                </div>
            </form>

            <!-- =================================================================
                 TAB 1B: INLINE FORGOT / RESET PASSWORD (NO POPUP MODAL)
                 ================================================================= -->
            <div x-show="tab === 'forgot'" x-cloak class="space-y-4">
                <!-- 2-Step Progress Pill -->
                <div class="p-1 bg-[#F3EFE6] border border-[#E5E0D5] rounded-xl grid grid-cols-2 gap-1 text-xs font-bold">
                    <button type="button" @click="forgotStep = 'request'"
                        :class="forgotStep === 'request' ? 'bg-white text-[#0D2620] shadow-sm border border-[#E5E0D5]' : 'text-[#3D544E] hover:text-[#0D2620] border border-transparent'"
                        class="py-2.5 px-3 rounded-lg transition">
                        1. Kirim Kode Email
                    </button>
                    <button type="button" @click="forgotStep = 'reset'"
                        :class="forgotStep === 'reset' ? 'bg-white text-[#0D2620] shadow-sm border border-[#E5E0D5]' : 'text-[#3D544E] hover:text-[#0D2620] border border-transparent'"
                        class="py-2.5 px-3 rounded-lg transition">
                        2. Kata Sandi Baru
                    </button>
                </div>

                <!-- Step 1: Request Reset Code -->
                <form x-show="forgotStep === 'request'" @submit.prevent="submitForgotPassword()" class="space-y-4">
                    <div>
                        <label for="forgot-email-request" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Email Akun Terdaftar</label>
                        <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                            <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="mail" class="w-4 h-4"></i></span>
                            <input id="forgot-email-request" type="email" x-model="forgotEmail" required autocomplete="email" placeholder="nama@perusahaan.co.id"
                                class="w-full py-3 pr-4 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                        </div>
                    </div>

                    <button type="submit" :disabled="forgotLoading"
                        class="w-full py-3.5 px-5 bg-[#0D7A5F] hover:bg-[#095C47] text-white font-bold rounded-xl text-sm shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!forgotLoading" class="inline-flex items-center gap-2">
                            <span>Kirim Kode Reset via Email</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </span>
                        <span x-show="forgotLoading" class="inline-flex items-center gap-2">
                            <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                            <span>Mengirim Kode...</span>
                        </span>
                    </button>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                        <button type="button" @click="forgotStep = 'reset'" class="font-semibold text-[#0D7A5F] hover:text-[#095C47] hover:underline transition">
                            Sudah punya kode 6 digit? Masukkan di sini →
                        </button>
                        <button type="button" @click="backToLoginTab()" class="font-medium text-[#3D544E] hover:text-[#0D2620] transition">
                            Batal
                        </button>
                    </div>
                </form>

                <!-- Step 2: Enter 6-Digit Code & New Password -->
                <form x-show="forgotStep === 'reset'" @submit.prevent="submitResetPassword()" class="space-y-4" x-cloak>
                    <div x-show="forgotMailNotice" x-cloak class="p-3.5 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 leading-relaxed flex items-start gap-2.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                        <span x-text="forgotMailNotice"></span>
                    </div>
                    <div x-show="!forgotMailNotice" class="p-3.5 bg-[#E6F4EF] border border-[#0D7A5F]/25 rounded-xl text-xs text-[#0D2620] leading-relaxed">
                        Masukkan 6 digit kode verifikasi yang dikirim ke email Anda beserta kata sandi baru. Jika belum masuk, periksa folder <b>Spam / Promosi</b>.
                    </div>

                    <div>
                        <label for="forgot-email-reset" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Email Akun</label>
                        <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                            <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="mail" class="w-4 h-4"></i></span>
                            <input id="forgot-email-reset" type="email" x-model="forgotEmail" required autocomplete="email" placeholder="nama@perusahaan.co.id"
                                class="w-full py-3 pr-4 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="reset-code" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kode Reset (6 Digit)</label>
                        <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                            <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="key-round" class="w-4 h-4"></i></span>
                            <input id="reset-code" type="text" inputmode="numeric" maxlength="6" x-model="resetCode" required placeholder="000000"
                                class="w-full py-3 pr-4 bg-transparent text-sm font-semibold tabular-nums tracking-[0.25em] text-[#0D2620] placeholder-slate-400 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="reset-password" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kata Sandi Baru</label>
                        <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                            <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="lock" class="w-4 h-4"></i></span>
                            <input id="reset-password" :type="showResetPassword ? 'text' : 'password'" x-model="resetPassword" required minlength="6" autocomplete="new-password" placeholder="Min. 6 karakter"
                                class="w-full py-3 pr-10 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                            <button type="button" @click="showResetPassword = !showResetPassword" class="pr-3.5 pl-2 text-slate-300 hover:text-[#3D544E] focus:outline-none">
                                <i x-show="!showResetPassword" data-lucide="eye" class="w-4 h-4"></i>
                                <i x-show="showResetPassword" data-lucide="eye-off" class="w-4 h-4" x-cloak></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="reset-password-confirm" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl shadow-xs transition focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                            <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="lock" class="w-4 h-4"></i></span>
                            <input id="reset-password-confirm" :type="showResetPassword ? 'text' : 'password'" x-model="resetPasswordConfirm" required minlength="6" autocomplete="new-password" placeholder="Ulangi kata sandi baru"
                                class="w-full py-3 pr-4 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                        <button type="button" @click="forgotStep = 'request'"
                            class="px-4 py-3.5 bg-[#F3EFE6] hover:bg-[#EAE3D5] text-[#3D544E] rounded-xl text-xs font-bold transition">
                            ← Kirim Ulang Kode
                        </button>
                        <button type="submit" :disabled="forgotLoading"
                            class="flex-1 py-3.5 px-5 bg-[#0D7A5F] hover:bg-[#095C47] text-white font-bold rounded-xl text-sm shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                            <span x-show="!forgotLoading">Simpan Kata Sandi Baru</span>
                            <span x-show="forgotLoading" class="inline-flex items-center gap-2">
                                <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- =================================================================
                 TAB 2: 3-STEP DETAILED REGISTRATION (1 USER = 1 ACCOUNT)
                 ================================================================= -->
            <div x-show="tab === 'register' && alreadyLoggedIn" x-cloak class="p-6 bg-[#F7FAF9] border border-[#E5E0D5] rounded-xl space-y-3 text-xs text-[#3D544E]">
                <div class="font-bold text-sm text-[#0D2620] flex items-center gap-2">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-[#0D7A5F]"></i>
                    <span>Pendaftaran Dinonaktifkan — Anda Sudah Memiliki Akun</span>
                </div>
                <p class="leading-relaxed">
                    Satu pengguna hanya diperbolehkan mendaftar satu kali. Anda sedang login sebagai <b class="text-[#0D2620]" x-text="currentUser.name"></b> (<span x-text="currentUser.email"></span>).
                </p>
                <div>
                    <a :href="redirectTarget || '/dashboard'" class="inline-block px-4 py-2 bg-[#0D2620] hover:bg-[#143B32] text-white font-bold rounded-lg transition">
                        Buka Dashboard Saya →
                    </a>
                </div>
            </div>

            <form x-show="tab === 'register' && !alreadyLoggedIn" @submit.prevent="submitRegister()" class="space-y-5" x-cloak>
                <!-- Google OAuth Status or Option -->
                <div x-show="googlePendingToken" class="p-3.5 bg-[#E6F4EF] border border-[#0D7A5F]/30 rounded-xl flex items-start justify-between gap-3 text-xs text-[#0D2620]" x-cloak>
                    <div class="flex items-start gap-2.5">
                        <img x-show="googleAvatar" :src="googleAvatar" alt="Google Avatar" class="w-8 h-8 rounded-full border border-[#0D7A5F]/30 object-cover shrink-0">
                        <div>
                            <div class="font-bold text-[#0D7A5F]">Akun Google Terverifikasi</div>
                            <div class="text-[11px] text-[#3D544E] mt-0.5">Email <b x-text="regData.email"></b> sudah terverifikasi lewat Google. Anda tetap wajib melengkapi seluruh data identitas PIC, profil perusahaan, dan outlet di bawah ini.</div>
                        </div>
                    </div>
                    <button type="button" @click="clearGooglePending()" class="text-[11px] font-semibold text-rose-700 hover:underline shrink-0">Batal</button>
                </div>

                <div x-show="!googlePendingToken" class="flex flex-wrap items-center justify-between gap-2 p-3 bg-white border border-[#E5E0D5] rounded-xl text-xs">
                    <span class="text-[#3D544E]">Ingin daftar dengan email Google Anda?</span>
                    <a href="/auth/google" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-[#D1C9B8] hover:border-[#0D7A5F] font-semibold text-[#0D2620] transition">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.8C6.2 7.2 8.9 5 12 5z"/>
                            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.6l3.7 2.9c2.2-2 3.7-5 3.7-8.7z"/>
                            <path fill="#FBBC05" d="M5.3 14.8c-.2-.8-.4-1.6-.4-2.5s.2-1.7.4-2.5L1.6 7C.6 9 0 10.6 0 12.3s.6 3.3 1.6 5.3l3.7-2.8z"/>
                            <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-2.9l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.1-6.7-5l-3.7 2.9C3.5 20.9 7.4 23.5 12 23.5z"/>
                        </svg>
                        <span>Hubungkan Google</span>
                    </a>
                </div>

                <!-- Stepper -->
                <div class="grid grid-cols-3 gap-2 pb-4 border-b border-[#E5E0D5]">
                    <button type="button" @click="regStep = 1" class="text-left">
                        <div class="h-1.5 rounded-full mb-1.5 transition" :class="regStep >= 1 ? 'bg-[#0D7A5F]' : 'bg-slate-200'"></div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider" :class="regStep === 1 ? 'text-[#0D7A5F]' : 'text-slate-300'">Identitas PIC</span>
                    </button>
                    <button type="button" @click="if(regStep > 1 || validateStep1(false)) regStep = 2" class="text-left">
                        <div class="h-1.5 rounded-full mb-1.5 transition" :class="regStep >= 2 ? 'bg-[#0D7A5F]' : 'bg-slate-200'"></div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider" :class="regStep === 2 ? 'text-[#0D7A5F]' : 'text-slate-300'">02 • Profil Usaha</span>
                    </button>
                    <button type="button" @click="if(regStep === 3 || (validateStep1(false) && validateStep2(false))) regStep = 3" class="text-left">
                        <div class="h-1.5 rounded-full mb-1.5 transition" :class="regStep >= 3 ? 'bg-[#0D7A5F]' : 'bg-slate-200'"></div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider" :class="regStep === 3 ? 'text-[#0D7A5F]' : 'text-slate-300'">03 • Outlet &amp; Akun</span>
                    </button>
                </div>

                <!-- STEP 1 -->
                <div x-show="regStep === 1" class="space-y-4">
                    <div>
                        <div class="text-sm font-bold text-[#0D2620]">Identitas Penanggung Jawab (PIC / Pemilik)</div>
                        <div class="text-xs text-[#647C76]">NIK, email, dan nomor WhatsApp diverifikasi unik agar 1 pengguna hanya memiliki 1 akun.</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-owner" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Nama Lengkap Sesuai KTP <span class="text-[#0D7A5F]">*</span></label>
                            <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                                <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="user" class="w-4 h-4"></i></span>
                                <input id="reg-owner" type="text" x-model="regData.owner_name" required placeholder="Budi Hartono" class="w-full py-2.5 pr-3.5 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label for="reg-nik" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">NIK KTP (16 Digit) <span class="text-[#0D7A5F]">*</span></label>
                            <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                                <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="id-card" class="w-4 h-4"></i></span>
                                <input id="reg-nik" type="text" inputmode="numeric" maxlength="16" x-model="regData.nik" required placeholder="3578xxxxxxxxxxxx" class="w-full py-2.5 pr-3.5 bg-transparent text-sm tabular-nums text-[#0D2620] placeholder-slate-400 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative" @click.outside="if (openRegDropdown === 'job') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Jabatan <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('job')"
                                class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="regData.job_title"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'job' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'job'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in jobTitleOptions" :key="opt">
                                    <button type="button" @click="regData.job_title = opt; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.job_title === opt ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt"></span>
                                        <span x-show="regData.job_title === opt" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label for="reg-phone" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">No. WhatsApp Aktif <span class="text-[#0D7A5F]">*</span></label>
                            <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                                <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="phone" class="w-4 h-4"></i></span>
                                <input id="reg-phone" type="tel" x-model="regData.phone" required placeholder="0812xxxxxxxx" class="w-full py-2.5 pr-3.5 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="reg-email" class="block text-[13px] font-semibold text-[#0D2620]">Email Bisnis Resmi <span class="text-[#0D7A5F]">*</span></label>
                                <span x-show="googlePendingToken" class="text-[11px] font-semibold text-[#0D7A5F]" x-cloak>Terverifikasi via Google ✓</span>
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1 relative flex items-center border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10"
                                    :class="googlePendingToken ? 'bg-slate-100' : 'bg-white'">
                                    <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="mail" class="w-4 h-4"></i></span>
                                    <input id="reg-email" type="email" x-model="regData.email" :readonly="Boolean(googlePendingToken)" required autocomplete="email" placeholder="owner@perusahaan.co.id" class="w-full py-2.5 pr-3.5 bg-transparent text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none">
                                </div>
                                <button x-show="!googlePendingToken" type="button" @click="sendVerificationCode()" :disabled="sendingOtp || otpCooldown > 0"
                                    class="px-3.5 py-2.5 bg-[#E6F4EF] hover:bg-[#0D7A5F] text-[#0D7A5F] hover:text-white border border-[#0D7A5F]/30 rounded-xl text-xs font-bold whitespace-nowrap transition disabled:opacity-50">
                                    <span x-show="!sendingOtp && otpCooldown === 0" x-text="otpSent ? 'Kirim Ulang Kode' : 'Kirim Kode Email'"></span>
                                    <span x-show="sendingOtp" x-cloak>Mengirim...</span>
                                    <span x-show="!sendingOtp && otpCooldown > 0" x-cloak x-text="'Tunggu ' + otpCooldown + 'd'"></span>
                                </button>
                            </div>
                        </div>

                        <div x-show="!googlePendingToken" x-cloak>
                            <label for="reg-otp" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kode Verifikasi Email (6 Digit) <span class="text-[#0D7A5F]">*</span></label>
                            <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                                <span class="pl-3.5 pr-2.5 text-slate-300 flex items-center"><i data-lucide="key-round" class="w-4 h-4"></i></span>
                                <input id="reg-otp" type="text" inputmode="numeric" maxlength="6" x-model="regData.verification_code" :required="!googlePendingToken" placeholder="Masukkan 6 digit kode dari email"
                                    class="w-full py-2.5 pr-3.5 bg-transparent text-sm tabular-nums tracking-widest text-[#0D2620] placeholder-slate-400 placeholder:tracking-normal focus:outline-none">
                            </div>
                            <p class="text-[11px] text-[#647C76] mt-1">Email terverifikasi memastikan akun aktif &amp; dapat digunakan untuk fitur lupa kata sandi.</p>
                        </div>
                    </div>

                    <button type="button" @click="goToRegStep(2)" class="w-full py-3 px-5 bg-[#0D2620] hover:bg-[#143B32] text-white font-bold rounded-xl text-sm transition flex items-center justify-center gap-2">
                        <span>Lanjut ke Profil Perusahaan</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- STEP 2 -->
                <div x-show="regStep === 2" class="space-y-4" x-cloak>
                    <div>
                        <div class="text-sm font-bold text-[#0D2620]">Profil Perusahaan &amp; Lokasi Usaha</div>
                        <div class="text-xs text-[#647C76]">Menentukan identitas entitas usaha, legalitas opsional, serta alamat resmi outlet Anda.</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="relative" @click.outside="if (openRegDropdown === 'legal') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Badan Usaha <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('legal')"
                                class="w-full py-2.5 px-3 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="regData.legal_type"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'legal' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'legal'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in legalTypeOptions" :key="opt">
                                    <button type="button" @click="regData.legal_type = opt; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.legal_type === opt ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt"></span>
                                        <span x-show="regData.legal_type === opt" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="reg-business" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Nama Perusahaan / Merek <span class="text-[#0D7A5F]">*</span></label>
                            <input id="reg-business" type="text" x-model="regData.business_name" required placeholder="Kopi Nusantara Sejahtera" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative" @click.outside="if (openRegDropdown === 'category') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Bidang Usaha <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('category')"
                                class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="regData.business_category"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'category' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'category'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in businessCategoryOptions" :key="opt">
                                    <button type="button" @click="regData.business_category = opt; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.business_category === opt ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt"></span>
                                        <span x-show="regData.business_category === opt" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                            <div x-show="regData.business_category === 'Lainnya'" class="mt-2" x-cloak>
                                <input type="text" x-model="regData.business_category_other" placeholder="Tulis bidang usaha Anda (contoh: Laundry / Petshop / Toko Bangunan)"
                                    class="w-full py-2 px-3 bg-white border border-[#D1C9B8] rounded-xl text-xs text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                            </div>
                        </div>
                        <div class="relative" @click.outside="if (openRegDropdown === 'scale') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Perkiraan Cabang <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('scale')"
                                class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="selectedScaleLabel"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'scale' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'scale'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in outletEstimateOptions" :key="opt.value">
                                    <button type="button" @click="regData.outlet_count_estimate = opt.value; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.outlet_count_estimate === opt.value ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt.label"></span>
                                        <span x-show="regData.outlet_count_estimate === opt.value" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-nib" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">NIB <span class="text-[11px] font-normal text-slate-300">(Opsional)</span></label>
                            <input id="reg-nib" type="text" x-model="regData.nib" placeholder="Nomor Induk Berusaha" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm tabular-nums text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                        <div>
                            <label for="reg-npwp" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">NPWP <span class="text-[11px] font-normal text-slate-300">(Opsional)</span></label>
                            <input id="reg-npwp" type="text" x-model="regData.npwp" placeholder="09.xxx.xxx.x-xxx.000" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm tabular-nums text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="reg-province" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Provinsi <span class="text-[#0D7A5F]">*</span></label>
                            <input id="reg-province" type="text" x-model="regData.province" required placeholder="Jawa Timur" class="w-full py-2.5 px-3 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                        <div>
                            <label for="reg-city" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kota / Kab. <span class="text-[#0D7A5F]">*</span></label>
                            <input id="reg-city" type="text" x-model="regData.city" required placeholder="Kota Surabaya" class="w-full py-2.5 px-3 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                        <div>
                            <label for="reg-postal" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kode Pos</label>
                            <input id="reg-postal" type="text" maxlength="10" x-model="regData.postal_code" placeholder="60281" class="w-full py-2.5 px-3 bg-white border border-[#D1C9B8] rounded-xl text-sm tabular-nums text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                    </div>

                    <div>
                        <label for="reg-address" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Alamat Lengkap Usaha <span class="text-[#0D7A5F]">*</span></label>
                        <input id="reg-address" type="text" x-model="regData.address" required placeholder="Jl. Raya Darmo No. 12, Wonokromo" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                    </div>

                    <div class="grid grid-cols-[auto_1fr] gap-3 pt-1">
                        <button type="button" @click="regStep = 1" class="py-3 px-4 bg-[#F3EFE6] border border-[#E5E0D5] text-[#3D544E] font-bold rounded-xl text-xs hover:bg-slate-200 transition">← Kembali</button>
                        <button type="button" @click="goToRegStep(3)" class="py-3 px-5 bg-[#0D2620] hover:bg-[#143B32] text-white font-bold rounded-xl text-sm transition flex items-center justify-center gap-2">
                            <span>Lanjut ke Outlet &amp; Keamanan</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div x-show="regStep === 3" class="space-y-4" x-cloak>
                    <div>
                        <div class="text-sm font-bold text-[#0D2620]">Outlet Pertama, Pajak POS &amp; Kata Sandi</div>
                        <div class="text-xs text-[#647C76]">Menyiapkan outlet utama dan kredensial keamanan akun Anda.</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label for="reg-outlet" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Nama Outlet Pertama <span class="text-[#0D7A5F]">*</span></label>
                            <input id="reg-outlet" type="text" x-model="regData.outlet_name" required placeholder="Outlet Pusat Surabaya" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] placeholder-slate-400 focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                        <div>
                            <label for="reg-outlet-code" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Kode Outlet</label>
                            <input id="reg-outlet-code" type="text" maxlength="20" x-model="regData.outlet_code" placeholder="SBY-01" class="w-full py-2.5 px-3 bg-white border border-[#D1C9B8] rounded-xl text-sm tabular-nums uppercase text-[#0D2620] focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative" @click.outside="if (openRegDropdown === 'timezone') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Zona Waktu <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('timezone')"
                                class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="selectedTimezoneLabel"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'timezone' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'timezone'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in timezoneOptions" :key="opt.value">
                                    <button type="button" @click="regData.timezone = opt.value; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.timezone === opt.value ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt.label"></span>
                                        <span x-show="regData.timezone === opt.value" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="relative" @click.outside="if (openRegDropdown === 'tax') openRegDropdown = null">
                            <label class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">Pajak Default Struk <span class="text-[#0D7A5F]">*</span></label>
                            <button type="button" @click="toggleRegDropdown('tax')"
                                class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] hover:border-[#0D7A5F] rounded-xl text-sm text-[#0D2620] flex items-center justify-between gap-2 transition focus:outline-none">
                                <span class="truncate" x-text="selectedTaxLabel"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="openRegDropdown === 'tax' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="openRegDropdown === 'tax'" x-transition class="absolute left-0 right-0 mt-1.5 bg-white border border-[#D1C9B8] rounded-xl shadow-lg z-40 py-1 max-h-52 overflow-y-auto custom-scrollbar">
                                <template x-for="opt in taxOptions" :key="opt.value">
                                    <button type="button" @click="regData.tax_percentage = opt.value; openRegDropdown = null"
                                        class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-[#F7FAF9] transition"
                                        :class="regData.tax_percentage === opt.value ? 'font-bold text-[#0D7A5F] bg-[#E6F4EF]/50' : 'text-[#0D2620]'">
                                        <span x-text="opt.label"></span>
                                        <span x-show="regData.tax_percentage === opt.value" class="text-[#0D7A5F]">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-password" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">
                                <span x-text="googlePendingToken ? 'Kata Sandi Lokal (Opsional)' : 'Kata Sandi'"></span>
                                <span x-show="!googlePendingToken" class="text-[#0D7A5F]">*</span>
                            </label>
                            <div class="relative flex items-center bg-white border border-[#D1C9B8] rounded-xl focus-within:border-[#0D7A5F] focus-within:ring-4 focus-within:ring-[#0D7A5F]/10">
                                <input id="reg-password" :type="showRegPassword ? 'text' : 'password'" x-model="regData.password" :required="!googlePendingToken" minlength="6" :placeholder="googlePendingToken ? 'Boleh kosong jika pakai Google' : 'Min. 6 karakter'" class="w-full py-2.5 pl-3.5 pr-9 bg-transparent text-sm text-[#0D2620] focus:outline-none">
                                <button type="button" @click="showRegPassword = !showRegPassword" class="pr-3 text-slate-300 hover:text-[#3D544E]">
                                    <i x-show="!showRegPassword" data-lucide="eye" class="w-4 h-4"></i>
                                    <i x-show="showRegPassword" data-lucide="eye-off" class="w-4 h-4" x-cloak></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="reg-password-confirm" class="block text-[13px] font-semibold text-[#0D2620] mb-1.5">
                                <span>Konfirmasi Sandi</span>
                                <span x-show="!googlePendingToken || regData.password" class="text-[#0D7A5F]">*</span>
                            </label>
                            <input id="reg-password-confirm" :type="showRegPassword ? 'text' : 'password'" x-model="regData.password_confirmation" :required="!googlePendingToken || Boolean(regData.password)" minlength="6" placeholder="Ulangi kata sandi" class="w-full py-2.5 px-3.5 bg-white border border-[#D1C9B8] rounded-xl text-sm text-[#0D2620] focus:outline-none focus:border-[#0D7A5F]">
                        </div>
                    </div>

                    <label class="p-3.5 bg-[#F7FAF9] border border-[#E5E0D5] rounded-xl flex items-start gap-2.5 text-xs text-[#3D544E] cursor-pointer">
                        <input type="checkbox" x-model="regData.agree_terms" required class="mt-0.5 accent-[#0D7A5F]">
                        <span>Saya menyatakan data perusahaan &amp; PIC di atas benar, dan memahami bahwa <b>1 NIK, 1 email, dan 1 nomor WhatsApp</b> hanya berlaku untuk 1 akun bisnis.</span>
                    </label>

                    @if($turnstileActive)
                        @include('auth.partials.antibot-slider', ['ctx' => 'register', 'antibotDriver' => $antibotDriver, 'isTurnstileSandbox' => $isTurnstileSandbox])
                    @endif

                    <div class="grid grid-cols-[auto_1fr] gap-3 pt-1">
                        <button type="button" @click="regStep = 2" class="py-3.5 px-4 bg-[#F3EFE6] border border-[#E5E0D5] text-[#3D544E] font-bold rounded-xl text-xs hover:bg-slate-200 transition">← Kembali</button>
                        <button type="submit" :disabled="loading" class="w-full py-3.5 px-5 bg-[#0D7A5F] hover:bg-[#095C47] text-white font-bold rounded-xl text-sm shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                            <span x-show="!loading" class="inline-flex items-center gap-2">
                                <span>Selesaikan Pendaftaran Bisnis</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </span>
                            <span x-show="loading" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        </button>
                    </div>
                </div>
            </form>

        </div>

        <!-- Bottom Link (Hidden in Kasir APK Mode so Cashier stays inside App) -->
        <div x-show="!isKasirAppMode" class="max-w-xl w-full mx-auto pt-5 border-t border-[#E5E0D5] flex flex-wrap items-center justify-between gap-2 text-xs text-[#647C76]">
            <span>Tandha • Developed by SEKA GROUP</span>
            <a href="/contact" class="font-semibold text-[#0D7A5F] hover:text-[#095C47] transition">Butuh Bantuan / Konsultasi? →</a>
        </div>
        <div x-show="isKasirAppMode" x-cloak class="max-w-md w-full mx-auto pt-4 text-center text-[11px] text-[#647C76]">
            Butuh akun kasir atau reset akses cabang? Hubungi <b>Pemilik Usaha (Owner)</b> melalui portal web Tandha.
        </div>
    </main>
</div>
@endsection

@push('scripts')
@if($antibotDriver === 'turnstile')
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
@endif
<script>
function authPage() {
    const params = new URLSearchParams(window.location.search);
    const antibotDriver = @json($antibotDriver);
    const turnstileActive = @json($turnstileActive);
    const turnstileSiteKey = @json($turnstileSiteKey);
    const isStandaloneApp = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isKasirApp = params.get('app') === 'kasir' || isStandaloneApp;

    const emptyAntiBotState = () => ({
        progress: 0,
        dragging: false,
        verified: false,
        verifying: false,
        loadingImage: false,
        token: '',
        challengeId: '',
        bgImage: '',
        pieceImage: '',
        pieceY: 56,
        imgWidth: 640,
        imgHeight: 232,
        pieceSize: 92,
        dragPoints: 0,
        keySteps: 0,
        startTime: 0,
        error: '',
    });

    return {
        isKasirAppMode: isKasirApp,
        apkBlockedNotice: '',
        tab: isKasirApp ? 'login' : (params.get('tab') === 'register' ? 'register' : 'login'),
        redirectTarget: params.get('redirect') || '',
        existingToken: localStorage.getItem('pos_token') || '',
        currentUser: JSON.parse(localStorage.getItem('pos_user') || '{}'),
        showLoginPassword: false,
        showRegPassword: false,
        showDemoDrawer: false,
        antibotDriver: antibotDriver,
        turnstileActive: turnstileActive,
        honeypotField: '',
        cfLoginToken: '',
        cfRegToken: '',
        loginWidgetId: null,
        regWidgetId: null,
        antiBot: {
            login: emptyAntiBotState(),
            register: emptyAntiBotState(),
        },
        activeDragCtx: null,
        dragStartX: 0,
        dragMaxTravel: 220,
        dragListenersBound: false,
        initTurnstile() {
            if (!this.turnstileActive) return;

            if (this.antibotDriver === 'tandha') {
                this.bindAntiBotPointerListeners();
                this.ensureAntiBotChallenge('login');
                return;
            }

            if (this.antibotDriver !== 'turnstile' || !turnstileSiteKey) return;
            const mountWidgets = () => {
                if (!window.turnstile) {
                    setTimeout(mountWidgets, 200);
                    return;
                }
                const loginEl = document.getElementById('cf-turnstile-login');
                if (loginEl && this.loginWidgetId === null) {
                    this.loginWidgetId = window.turnstile.render(loginEl, {
                        sitekey: turnstileSiteKey,
                        size: 'flexible',
                        theme: 'light',
                        callback: (token) => { this.cfLoginToken = token; },
                        'expired-callback': () => { this.cfLoginToken = ''; },
                        'error-callback': () => { this.cfLoginToken = ''; },
                    });
                }
                const regEl = document.getElementById('cf-turnstile-register');
                if (regEl && this.regWidgetId === null) {
                    this.regWidgetId = window.turnstile.render(regEl, {
                        sitekey: turnstileSiteKey,
                        size: 'flexible',
                        theme: 'light',
                        callback: (token) => { this.cfRegToken = token; },
                        'expired-callback': () => { this.cfRegToken = ''; },
                        'error-callback': () => { this.cfRegToken = ''; },
                    });
                }
            };
            mountWidgets();
        },
        bindAntiBotPointerListeners() {
            if (this.dragListenersBound) return;
            this.dragListenersBound = true;
            window.addEventListener('pointermove', (e) => {
                if (!this.activeDragCtx) return;
                const ctx = this.activeDragCtx;
                const state = this.antiBot[ctx];
                if (!state || !state.dragging) return;
                const dx = Math.max(0, e.clientX - this.dragStartX);
                state.progress = Math.min(100, Math.round((dx / this.dragMaxTravel) * 100));
                state.dragPoints += 1;
            });
            const endDrag = () => {
                if (!this.activeDragCtx) return;
                const ctx = this.activeDragCtx;
                this.activeDragCtx = null;
                const state = this.antiBot[ctx];
                if (!state) return;
                state.dragging = false;
                if (state.progress >= 8 && state.dragPoints >= 3 && state.challengeId) {
                    this.completeAntiBotChallenge(ctx, 'pointer');
                } else {
                    state.progress = 0;
                }
            };
            window.addEventListener('pointerup', endDrag);
            window.addEventListener('pointercancel', endDrag);
        },
        antiBotThumbOffset(ctx) {
            const state = this.antiBot[ctx];
            const el = document.getElementById('antibot-track-' + ctx);
            const maxTravel = el ? Math.max(80, el.clientWidth - 44) : 220;
            const pct = state.verified ? 100 : Math.max(0, state.progress || 0);
            return Math.round((pct / 100) * maxTravel);
        },
        antiBotPieceStyle(ctx) {
            const state = this.antiBot[ctx];
            const w = state.imgWidth || 640;
            const h = state.imgHeight || 232;
            const p = state.pieceSize || 92;
            const x = ((state.progress || 0) / 100) * (w - p);
            return `top:${(((state.pieceY || 0) / h) * 100).toFixed(3)}%;left:${((x / w) * 100).toFixed(3)}%;width:${((p / w) * 100).toFixed(3)}%;height:${((p / h) * 100).toFixed(3)}%;`;
        },
        async ensureAntiBotChallenge(ctx, forceRefresh = false) {
            const state = this.antiBot[ctx];
            if (!state) return;
            if (!forceRefresh && state.challengeId && state.bgImage) return;
            state.loadingImage = true;
            try {
                const { ok, result } = await this.postJsonSafe('/api/antibot/challenge', { context: ctx });
                if (ok && result.data && result.data.challenge_id) {
                    state.challengeId = result.data.challenge_id;
                    state.bgImage = result.data.bg_image || '';
                    state.pieceImage = result.data.piece_image || '';
                    state.pieceY = Number(result.data.piece_y ?? 56);
                    state.imgWidth = Number(result.data.img_width ?? 640);
                    state.imgHeight = Number(result.data.img_height ?? 232);
                    state.pieceSize = Number(result.data.piece_size ?? 92);
                    state.progress = 0;
                }
            } catch (_) {
            } finally {
                state.loadingImage = false;
            }
        },
        refreshAntiBotChallenge(ctx) {
            const state = this.antiBot[ctx];
            if (!state || state.verifying || state.loadingImage) return;
            state.error = '';
            state.progress = 0;
            state.keySteps = 0;
            this.ensureAntiBotChallenge(ctx, true);
        },
        startAntiBotDrag(ctx, e) {
            const state = this.antiBot[ctx];
            if (!state || state.verified || state.verifying || state.loadingImage) return;
            const track = document.getElementById('antibot-track-' + ctx);
            this.dragMaxTravel = track ? Math.max(80, track.clientWidth - 44) : 220;
            this.activeDragCtx = ctx;
            this.dragStartX = e.clientX;
            state.dragging = true;
            state.error = '';
            state.progress = 0;
            state.dragPoints = 1;
            state.startTime = performance.now();
            if (!state.challengeId) {
                this.ensureAntiBotChallenge(ctx, true);
            }
        },
        stepAntiBotKeyboard(ctx, delta) {
            const state = this.antiBot[ctx];
            if (!state || state.verified || state.verifying || state.loadingImage) return;
            if (!state.startTime) state.startTime = performance.now();
            state.error = '';
            state.keySteps += 1;
            state.progress = Math.max(0, Math.min(100, (state.progress || 0) + delta));
        },
        async submitAntiBotKeyboard(ctx) {
            const state = this.antiBot[ctx];
            if (!state || state.verified || state.verifying || !state.challengeId) return;
            if (state.keySteps < 2) {
                state.error = 'Gunakan panah kanan untuk menggeser potongan ke lubang';
                return;
            }
            await this.completeAntiBotChallenge(ctx, 'keyboard');
        },
        async completeAntiBotChallenge(ctx, inputMethod = 'pointer') {
            const state = this.antiBot[ctx];
            if (!state || state.verifying || !state.challengeId) return;
            state.verifying = true;
            state.error = '';
            try {
                const maxImgX = Math.max(1, (state.imgWidth || 640) - (state.pieceSize || 92));
                const submittedX = Math.round(((state.progress || 0) / 100) * maxImgX);
                const durationMs = Math.max(160, Math.round(performance.now() - (state.startTime || performance.now())));
                const { ok, result, errorMessage } = await this.postJsonSafe('/api/antibot/verify', {
                    challenge_id: state.challengeId,
                    x: submittedX,
                    drag_points: state.dragPoints,
                    key_steps: state.keySteps,
                    duration_ms: durationMs,
                    input_method: inputMethod,
                    company_website: this.honeypotField || '',
                });
                state.challengeId = '';
                state.keySteps = 0;
                state.startTime = 0;
                if (ok && result.data && result.data.verification_token) {
                    state.verified = true;
                    state.progress = 100;
                    state.token = result.data.verification_token;
                    if (ctx === 'login') this.cfLoginToken = state.token;
                    if (ctx === 'register') this.cfRegToken = state.token;
                } else {
                    state.verified = false;
                    state.progress = 0;
                    state.token = '';
                    state.error = errorMessage || 'Belum pas, coba lagi pada gambar baru';
                    this.ensureAntiBotChallenge(ctx, true);
                }
            } catch (_) {
                state.verified = false;
                state.progress = 0;
                state.token = '';
                state.challengeId = '';
                state.keySteps = 0;
                state.startTime = 0;
                state.error = 'Koneksi gagal, memuat gambar baru';
                this.ensureAntiBotChallenge(ctx, true);
            } finally {
                state.verifying = false;
            }
        },
        resetTurnstile(type) {
            if (this.antibotDriver === 'tandha') {
                const state = this.antiBot[type];
                if (state) {
                    Object.assign(state, emptyAntiBotState());
                    this.ensureAntiBotChallenge(type, true);
                }
                if (type === 'login') this.cfLoginToken = '';
                if (type === 'register') this.cfRegToken = '';
                return;
            }
            if (!window.turnstile) return;
            if (type === 'login' && this.loginWidgetId !== null) {
                this.cfLoginToken = '';
                window.turnstile.reset(this.loginWidgetId);
            }
            if (type === 'register' && this.regWidgetId !== null) {
                this.cfRegToken = '';
                window.turnstile.reset(this.regWidgetId);
            }
        },
        get alreadyLoggedIn() {
            return Boolean(this.existingToken && this.currentUser && this.currentUser.id);
        },
        logoutCurrent() {
            localStorage.removeItem('pos_token');
            localStorage.removeItem('pos_user');
            this.existingToken = '';
            this.currentUser = {};
        },
        loading: false,
        regStep: 1,
        openRegDropdown: null,
        jobTitleOptions: ['Pemilik Usaha (Owner)', 'Direktur / Komisaris', 'Manajer Operasional', 'Admin / Finance'],
        legalTypeOptions: ['Perorangan / UMKM', 'CV', 'PT', 'Koperasi / Yayasan'],
        businessCategoryOptions: ['F&B / Restoran / Kafe', 'Retail / Minimarket / Grosir', 'Apotek / Klinik / Kesehatan', 'Fashion / Butik', 'Jasa / Bengkel / Barbershop', 'Lainnya'],
        outletEstimateOptions: [
            { value: '1 Outlet', label: '1 Outlet (Tunggal)' },
            { value: '2-5 Cabang', label: '2–5 Cabang' },
            { value: 'Lebih dari 5 Cabang', label: 'Lebih dari 5 Cabang' },
        ],
        timezoneOptions: [
            { value: 'Asia/Jakarta', label: 'WIB (Asia/Jakarta)' },
            { value: 'Asia/Makassar', label: 'WITA (Asia/Makassar)' },
            { value: 'Asia/Jayapura', label: 'WIT (Asia/Jayapura)' },
        ],
        taxOptions: [
            { value: '11', label: 'PPN 11% (PKP)' },
            { value: '10', label: 'PB1 10% (Restoran / Kafe)' },
            { value: '0', label: '0% (Non-Pajak / UMKM)' },
        ],
        toggleRegDropdown(name) {
            this.openRegDropdown = this.openRegDropdown === name ? null : name;
        },
        get selectedScaleLabel() {
            const f = this.outletEstimateOptions.find((o) => o.value === this.regData.outlet_count_estimate);
            return f ? f.label : this.regData.outlet_count_estimate;
        },
        get selectedTimezoneLabel() {
            const f = this.timezoneOptions.find((o) => o.value === this.regData.timezone);
            return f ? f.label : this.regData.timezone;
        },
        get selectedTaxLabel() {
            const f = this.taxOptions.find((o) => o.value === String(this.regData.tax_percentage));
            return f ? f.label : 'PPN 11% (PKP)';
        },
        sendingOtp: false,
        otpSent: false,
        otpCooldown: 0,
        otpTimer: null,
        googlePendingToken: '',
        googleAvatar: '',
        showResetPassword: false,
        forgotStep: 'request',
        forgotLoading: false,
        forgotMailNotice: '',
        forgotEmail: '',
        resetCode: '',
        resetPassword: '',
        resetPasswordConfirm: '',
        loginData: { email: '', password: '' },
        regData: {
            owner_name: '',
            nik: '',
            job_title: 'Pemilik Usaha (Owner)',
            phone: '',
            email: '',
            verification_code: '',
            legal_type: 'Perorangan / UMKM',
            business_name: '',
            business_category: 'F&B / Restoran / Kafe',
            business_category_other: '',
            outlet_count_estimate: '1 Outlet',
            nib: '',
            npwp: '',
            province: '',
            city: '',
            postal_code: '',
            address: '',
            outlet_name: '',
            outlet_code: 'OUT-01',
            timezone: 'Asia/Jakarta',
            tax_percentage: '11',
            password: '',
            password_confirmation: '',
            agree_terms: false,
        },
        initAuthPage() {
            if (this.isKasirAppMode) {
                this.tab = 'login';
                if (this.existingToken && this.currentUser && this.currentUser.role && this.currentUser.role !== 'cashier') {
                    this.logoutCurrent();
                }
                const blockedRole = new URLSearchParams(window.location.search).get('blocked_role');
                if (blockedRole) {
                    this.apkBlockedNotice = `Aplikasi Kasir (APK) hanya mengizinkan login akun Kasir (Cashier). Sesi ${blockedRole.toUpperCase()} telah ditutup — silakan masuk memakai akun Kasir outlet Anda.`;
                    window.history.replaceState({}, document.title, '/login?app=kasir');
                }
            }
            this.initTurnstile();
            this.handleUrlQueryParams();
        },
        resolveRedirectForUser(userObj, explicitRedirect = null) {
            if (!userObj) return explicitRedirect || (this.isKasirAppMode ? '/pos?app=kasir' : '/dashboard?tab=billing');
            if (this.isKasirAppMode && userObj.role === 'cashier') return '/pos?app=kasir';
            if (userObj.role === 'superadmin') return '/dashboard';
            if (userObj.role === 'cashier') return '/pos';
            if (userObj.role === 'admin') return '/dashboard';
            if (explicitRedirect) return explicitRedirect;
            const biz = userObj.business;
            if (biz && (biz.has_active_subscription || biz.can_install_apk)) {
                return '/dashboard';
            }
            if (biz && biz.service_type === 'custom_pos') {
                return '/dashboard?tab=consultation';
            }
            return '/dashboard?tab=billing';
        },
        async handleUrlQueryParams() {
            if (this.isKasirAppMode) return;
            const p = new URLSearchParams(window.location.search);
            const err = p.get('google_error');
            if (err) {
                const msgMap = {
                    not_configured: 'Login Google belum dikonfigurasi di server (GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET).',
                    access_denied: 'Izin akses akun Google dibatalkan oleh pengguna.',
                    invalid_state: 'Sesi otentikasi Google tidak valid atau kedaluwarsa. Silakan coba lagi.',
                    token_exchange_failed: 'Gagal memverifikasi otorisasi Google.',
                    profile_failed: 'Gagal mengambil alamat email dari akun Google.',
                    account_suspended: 'Akun atau bisnis Anda sedang dibekukan. Hubungi admin Tandha.',
                };
                Swal.fire({ icon: 'error', title: 'Google Login Gagal', text: msgMap[err] || 'Gagal menghubungkan akun Google.', confirmButtonColor: '#0D7A5F' });
                window.history.replaceState({}, document.title, window.location.pathname);
                return;
            }
            const nonce = p.get('google_exchange') || p.get('google_pending');
            if (!nonce) return;

            try {
                const res = await fetch(`/api/auth/google/exchange/${encodeURIComponent(nonce)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await res.json();
                window.history.replaceState({}, document.title, window.location.pathname);
                if (!res.ok || !result.data) {
                    Swal.fire({ icon: 'error', title: 'Sesi Google Kedaluwarsa', text: result.message || 'Silakan ulangi login dengan Google.', confirmButtonColor: '#0D7A5F' });
                    return;
                }
                if (result.data.type === 'login') {
                    localStorage.setItem('pos_token', result.data.token);
                    localStorage.setItem('pos_user', JSON.stringify(result.data.user));
                    window.location.href = this.resolveRedirectForUser(result.data.user, this.redirectTarget);
                } else if (result.data.type === 'pending_registration') {
                    this.tab = 'register';
                    this.regStep = 1;
                    this.googlePendingToken = result.data.google_pending_token;
                    this.googleAvatar = result.data.avatar || '';
                    this.regData.email = result.data.email || '';
                    if (!this.regData.owner_name) this.regData.owner_name = result.data.name || '';
                    Swal.fire({
                        icon: 'info',
                        title: 'Lengkapi Data Pendaftaran',
                        text: `Email Google (${this.regData.email}) terhubung. Anda tetap wajib melengkapi identitas PIC, profil perusahaan, dan data outlet.`,
                        confirmButtonColor: '#0D7A5F',
                    });
                }
            } catch (e) {
                console.error(e);
            }
        },
        clearGooglePending() {
            this.googlePendingToken = '';
            this.googleAvatar = '';
            this.regData.email = '';
        },
        startOtpCooldown(sec = 60) {
            if (this.otpTimer) clearInterval(this.otpTimer);
            this.otpCooldown = sec;
            this.otpTimer = setInterval(() => {
                this.otpCooldown = Math.max(0, this.otpCooldown - 1);
                if (this.otpCooldown === 0) clearInterval(this.otpTimer);
            }, 1000);
        },
        async postJsonSafe(url, payload) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const raw = await res.text();
            let result = {};
            try {
                result = raw ? JSON.parse(raw) : {};
            } catch (_) {
                result = {
                    message: res.status === 429
                        ? 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'
                        : 'Terjadi kesalahan pada server (' + res.status + '). Periksa konfigurasi email atau log server.',
                };
            }
            const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
            return { ok: res.ok, status: res.status, result, errorMessage: firstErr || result.message || null };
        },
        showOtpSentAlert(result, defaultTitle) {
            if (result.mail_mode && result.mail_notice) {
                Swal.fire({ icon: 'info', title: 'Mode Log Lokal Aktif', text: result.mail_notice, confirmButtonColor: '#0D7A5F' });
                return;
            }
            Swal.fire({ icon: 'success', title: defaultTitle, text: result.message, confirmButtonColor: '#0D7A5F' });
        },
        async sendVerificationCode() {
            const email = (this.regData.email || '').trim();
            if (!email || !email.includes('@')) {
                Swal.fire({ icon: 'warning', title: 'Email Tidak Valid', text: 'Masukkan alamat email bisnis aktif terlebih dahulu.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.sendingOtp = true;
            try {
                const { ok, result, errorMessage } = await this.postJsonSafe('/api/email/send-verification', {
                    email,
                    owner_name: this.regData.owner_name || '',
                });
                if (ok) {
                    this.otpSent = true;
                    this.startOtpCooldown(60);
                    this.showOtpSentAlert(result, 'Kode Terkirim');
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Mengirim Kode', text: errorMessage || 'Periksa email atau konfigurasi SMTP server.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Gagal menghubungi server.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.sendingOtp = false;
            }
        },
        openForgotPassword() {
            this.forgotEmail = this.loginData.email || '';
            this.resetCode = '';
            this.resetPassword = '';
            this.resetPasswordConfirm = '';
            this.showResetPassword = false;
            this.forgotMailNotice = '';
            this.forgotStep = 'request';
            this.tab = 'forgot';
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                const scroller = document.getElementById('auth-main-scroll');
                if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },
        backToLoginTab() {
            this.tab = 'login';
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
                const scroller = document.getElementById('auth-main-scroll');
                if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },
        async submitForgotPassword() {
            this.forgotLoading = true;
            this.forgotMailNotice = '';
            try {
                const { ok, result, errorMessage } = await this.postJsonSafe('/api/password/forgot', {
                    email: this.forgotEmail,
                });
                if (ok) {
                    this.forgotMailNotice = result.mail_notice || '';
                    this.forgotStep = 'reset';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    this.showOtpSentAlert(result, 'Kode Reset Dikirim');
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal Mengirim Kode', text: errorMessage || 'Periksa email atau konfigurasi SMTP server.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Gagal menghubungi server.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.forgotLoading = false;
            }
        },
        async submitResetPassword() {
            if (this.resetPassword !== this.resetPasswordConfirm) {
                Swal.fire({ icon: 'warning', title: 'Kata Sandi Tidak Cocok', text: 'Kata sandi baru dan konfirmasi harus sama.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.forgotLoading = true;
            try {
                const { ok, result, errorMessage } = await this.postJsonSafe('/api/password/reset', {
                    email: this.forgotEmail,
                    code: this.resetCode,
                    password: this.resetPassword,
                    password_confirmation: this.resetPasswordConfirm,
                });
                if (ok) {
                    this.loginData.email = this.forgotEmail;
                    this.forgotMailNotice = '';
                    this.backToLoginTab();
                    Swal.fire({ icon: 'success', title: 'Kata Sandi Diperbarui', text: result.message, confirmButtonColor: '#0D7A5F' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Reset Ditolak', text: errorMessage || 'Kode tidak valid.', confirmButtonColor: '#0D7A5F' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Gagal menghubungi server.', confirmButtonColor: '#0D7A5F' });
            } finally {
                this.forgotLoading = false;
            }
        },
        validateStep1(showAlert = true) {
            const nikClean = (this.regData.nik || '').replace(/\D+/g, '');
            const otpClean = (this.regData.verification_code || '').replace(/\D+/g, '');
            if (!this.regData.owner_name.trim() || nikClean.length !== 16 || !this.regData.phone.trim() || !this.regData.email.trim()) {
                if (showAlert) {
                    Swal.fire({ icon: 'warning', title: 'Lengkapi Identitas PIC', text: 'Pastikan Nama Lengkap, NIK (16 digit angka), No. WhatsApp, dan Email Bisnis sudah terisi benar.', confirmButtonColor: '#0D7A5F' });
                }
                return false;
            }
            if (!this.googlePendingToken && otpClean.length !== 6) {
                if (showAlert) {
                    Swal.fire({ icon: 'warning', title: 'Verifikasi Email Diperlukan', text: 'Klik "Kirim Kode Email" lalu masukkan 6 digit kode verifikasi yang dikirim ke email Anda.', confirmButtonColor: '#0D7A5F' });
                }
                return false;
            }
            this.regData.nik = nikClean;
            this.regData.verification_code = otpClean;
            return true;
        },
        validateStep2(showAlert = true) {
            if (!this.regData.business_name.trim() || !this.regData.province.trim() || !this.regData.city.trim() || !this.regData.address.trim()) {
                if (showAlert) {
                    Swal.fire({ icon: 'warning', title: 'Lengkapi Profil Usaha', text: 'Nama Perusahaan, Provinsi, Kota/Kabupaten, dan Alamat Lengkap wajib diisi.', confirmButtonColor: '#0D7A5F' });
                }
                return false;
            }
            return true;
        },
        goToRegStep(step) {
            if (step === 2 && !this.validateStep1(true)) return;
            if (step === 3 && (!this.validateStep1(true) || !this.validateStep2(true))) return;
            this.regStep = step;
            if (step === 3 && this.turnstileActive && this.antibotDriver === 'tandha') {
                this.ensureAntiBotChallenge('register');
            }
        },
        async quickLogin(email, password, redirectUrl) {
            this.tab = 'login';
            this.loginData.email = email;
            this.loginData.password = password;
            await this.submitLogin(redirectUrl);
        },
        async submitLogin(redirectUrl = null) {
            if (this.turnstileActive && !this.cfLoginToken) {
                const msg = this.antibotDriver === 'tandha'
                    ? 'Harap geser tombol verifikasi keamanan Tandha Shield terlebih dahulu.'
                    : 'Harap selesaikan verifikasi keamanan terlebih dahulu.';
                Swal.fire({ icon: 'warning', title: 'Verifikasi Keamanan', text: msg, confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.loading = true;
            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        ...this.loginData,
                        app_context: this.isKasirAppMode ? 'kasir_apk' : 'web',
                        cf_turnstile_response: this.cfLoginToken,
                        company_website: this.honeypotField,
                    }),
                });
                const result = await res.json();
                if (res.ok) {
                    if (this.isKasirAppMode && result.data && result.data.user && result.data.user.role !== 'cashier') {
                        this.logoutCurrent();
                        Swal.fire({ icon: 'error', title: 'Khusus Akun Kasir', text: 'Aplikasi Kasir (APK) hanya untuk akun Kasir (Cashier).', confirmButtonColor: '#0D7A5F' });
                        return;
                    }
                    localStorage.setItem('pos_token', result.data.token);
                    localStorage.setItem('pos_user', JSON.stringify(result.data.user));
                    const target = redirectUrl || this.resolveRedirectForUser(result.data.user, this.redirectTarget);
                    window.location.href = target;
                } else {
                    this.resetTurnstile('login');
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Login Gagal', text: firstErr || result.message || 'Periksa email dan password', confirmButtonColor: '#0D7A5F' });
                }
            } catch (err) {
                this.resetTurnstile('login');
                Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#0D7A5F' });
            } finally {
                this.loading = false;
            }
        },
        async submitRegister() {
            if (!this.validateStep1(true) || !this.validateStep2(true)) return;
            if ((!this.googlePendingToken || this.regData.password) && this.regData.password !== this.regData.password_confirmation) {
                Swal.fire({ icon: 'warning', title: 'Kata Sandi Tidak Cocok', text: 'Kata sandi dan konfirmasi kata sandi harus sama.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (!this.regData.agree_terms) {
                Swal.fire({ icon: 'warning', title: 'Konfirmasi Diperlukan', text: 'Centang pernyataan kebenaran data dan aturan 1 akun terlebih dahulu.', confirmButtonColor: '#0D7A5F' });
                return;
            }
            if (this.turnstileActive && !this.cfRegToken) {
                const msg = this.antibotDriver === 'tandha'
                    ? 'Harap geser tombol verifikasi keamanan Tandha Shield terlebih dahulu.'
                    : 'Harap selesaikan verifikasi keamanan terlebih dahulu.';
                Swal.fire({ icon: 'warning', title: 'Verifikasi Keamanan', text: msg, confirmButtonColor: '#0D7A5F' });
                return;
            }
            this.loading = true;
            try {
                const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
                if (this.existingToken) {
                    headers['Authorization'] = `Bearer ${this.existingToken}`;
                }
                const payload = {
                    ...this.regData,
                    business_category: (this.regData.business_category === 'Lainnya' && (this.regData.business_category_other || '').trim())
                        ? ('Lainnya: ' + this.regData.business_category_other.trim())
                        : this.regData.business_category,
                    cf_turnstile_response: this.cfRegToken,
                    company_website: this.honeypotField,
                };
                if (this.googlePendingToken) {
                    payload.google_pending_token = this.googlePendingToken;
                }
                const res = await fetch('/api/register', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify(payload),
                });
                const result = await res.json();
                if (res.ok) {
                    localStorage.setItem('pos_token', result.data.token);
                    localStorage.setItem('pos_user', JSON.stringify(result.data.user));
                    Swal.fire({
                        icon: 'success',
                        title: 'Akun Bisnis Berhasil Dibuat',
                        text: 'Akun Anda siap digunakan. Pilih paket langganan untuk membuka penuh seluruh akses kasir cabang atau jadwalkan konsultasi fitur kapan saja.',
                        confirmButtonColor: '#0D7A5F',
                    }).then(() => {
                        window.location.href = this.redirectTarget || '/dashboard?tab=billing';
                    });
                } else {
                    this.resetTurnstile('register');
                    const firstErr = result.errors ? Object.values(result.errors).flat()[0] : null;
                    Swal.fire({ icon: 'error', title: 'Registrasi Ditolak', text: firstErr || result.message || 'Data tidak valid', confirmButtonColor: '#0D7A5F' });
                }
            } catch (err) {
                this.resetTurnstile('register');
                Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#0D7A5F' });
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
