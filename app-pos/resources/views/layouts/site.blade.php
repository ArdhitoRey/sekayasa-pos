<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tandha by SEKA GROUP — Aplikasi Kasir, Stok Cabang & Solusi POS Bisnis')</title>
    <link rel="icon" type="image/svg+xml" href="/assets/Logo%20Tandha.svg?v=5">
    <link rel="icon" type="image/png" sizes="192x192" href="/tandha-icon-192.png?v=5">
    <link rel="shortcut icon" href="/favicon.ico?v=5">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        window.tailwind = {
            config: {
                corePlugins: {
                    preflight: false,
                    container: false
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/css/tandha.css?v=12">

    <style>
        [x-cloak] { display: none !important; }
        .swal2-container {
            backdrop-filter: blur(4px);
            background: rgba(13, 38, 32, 0.62) !important;
        }
        .swal2-popup {
            border: 1px solid rgba(13, 38, 32, 0.3) !important;
            border-radius: 16px !important;
            box-shadow: 0 24px 56px -12px rgba(6, 20, 16, 0.5), 0 0 0 1px rgba(13, 38, 32, 0.14) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            outline: none !important;
        }
        .swal2-popup:focus, .swal2-popup *:focus { outline: none !important; }
        .swal2-styled {
            border-radius: 8px !important;
            font-weight: 600 !important;
            box-shadow: none !important;
        }
        .swal2-styled:focus { box-shadow: 0 0 0 3px rgba(13, 122, 95, 0.24) !important; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="scroll-progress-bar" id="scroll-progress"></div>

    <!-- FLOATING OVAL NAVBAR (TANDHA EMERALD PANDAN) -->
    <div x-data="siteHeaderAuth()" x-init="initAuth()" @click.outside="mobileNavOpen = false">
        <header class="site-nav">
            <div class="container nav-inner">
                <a href="/" class="nav-brand" aria-label="Tandha by SEKA GROUP Beranda">
                    <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="nav-logo-mark">
                    <img src="/assets/tandha-text.svg" alt="Tandha by SEKA GROUP" class="nav-logo-text">
                </a>

                <nav class="nav-links" aria-label="Menu Utama">
                    <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">Beranda</a>
                    <a href="/fitur" class="{{ request()->is('fitur') ? 'active' : '' }}">Fitur POS</a>
                    <a href="/harga" class="{{ request()->is('harga') ? 'active' : '' }}">Harga</a>
                    <a href="/contact" class="{{ request()->is('contact') ? 'active' : '' }}">Konsultasi</a>
                </nav>

                <div class="nav-actions">
                    <template x-if="!isLoggedIn">
                        <a href="/login?redirect=/dashboard?tab=billing" class="btn btn-mint btn-sm {{ request()->is('login') ? 'active-cta' : '' }}">
                            Masuk / Daftar
                        </a>
                    </template>

                    <template x-if="isLoggedIn">
                        <div class="flex items-center gap-2" x-cloak>
                            <span class="nav-login-badge" title="Akun aktif sedang login">
                                <i class="dot"></i>
                                <span x-text="user.name ? user.name.split(' ')[0] : 'Login'"></span>
                            </span>
                            <a href="/dashboard" class="btn btn-mint btn-sm">
                                Dashboard Saya
                            </a>
                            <button type="button" @click="logout()" class="nav-logout-btn" title="Keluar dari akun" aria-label="Keluar">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Mobile Quick Action + Hamburger -->
                <div class="nav-mobile-right">
                    <a x-show="!isLoggedIn" href="/login?redirect=/dashboard?tab=billing" class="btn btn-mint btn-sm" style="padding: 0.42rem 0.78rem; font-size: 0.72rem;">
                        Masuk
                    </a>
                    <a x-show="isLoggedIn" x-cloak href="/dashboard" class="btn btn-mint btn-sm" style="padding: 0.42rem 0.78rem; font-size: 0.72rem;">
                        Dashboard
                    </a>
                    <button type="button" @click="mobileNavOpen = !mobileNavOpen" class="nav-mobile-toggle" aria-label="Buka Menu Navigasi" :aria-expanded="mobileNavOpen">
                        <svg x-show="!mobileNavOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                        <svg x-show="mobileNavOpen" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile Slide-Down Drawer Menu -->
        <div x-show="mobileNavOpen" x-transition.opacity.duration.150ms x-cloak class="nav-mobile-drawer">
            <a href="/" @click="mobileNavOpen = false" class="{{ request()->is('/') ? 'active' : '' }}">Beranda</a>
            <a href="/fitur" @click="mobileNavOpen = false" class="{{ request()->is('fitur') ? 'active' : '' }}">Fitur POS</a>
            <a href="/harga" @click="mobileNavOpen = false" class="{{ request()->is('harga') ? 'active' : '' }}">Harga &amp; Paket</a>
            <a href="/contact" @click="mobileNavOpen = false" class="{{ request()->is('contact') ? 'active' : '' }}">Konsultasi Fitur</a>
            <div class="nav-mobile-drawer-divider"></div>
            <template x-if="!isLoggedIn">
                <a href="/login?redirect=/dashboard?tab=billing" class="nav-mobile-cta">Masuk / Daftar Bisnis →</a>
            </template>
            <template x-if="isLoggedIn">
                <div class="space-y-2">
                    <a href="/dashboard" class="nav-mobile-cta">Buka Dashboard Saya →</a>
                    <button type="button" @click="logout()" class="nav-mobile-logout">Keluar Akun (<span x-text="user.name ? user.name.split(' ')[0] : ''"></span>)</button>
                </div>
            </template>
        </div>
    </div>

    <main>
        @yield('content')
    </main>

    <!-- 4-COLUMN POS FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <div>
                    <a href="/" class="nav-brand">
                        <img src="/assets/Logo%20Tandha.svg" alt="Tandha" class="nav-logo-mark">
                        <img src="/assets/tandha-text.svg" alt="Tandha by SEKA GROUP" class="nav-logo-text">
                    </a>
                    <p class="footer-desc">
                        Tandha by SEKA GROUP — Aplikasi kasir Point of Sale, manajemen stok multi-outlet, dan layanan konsultasi alur POS bisnis.
                    </p>
                </div>

                <div class="footer-col">
                    <h4>Produk Tandha</h4>
                    <ul>
                        <li><a href="/">Beranda</a></li>
                        <li><a href="/fitur">Fitur Aplikasi POS</a></li>
                        <li><a href="/harga">Harga &amp; Paket Sewa</a></li>
                        <li><a href="/about">Tentang Tandha</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Modul POS</h4>
                    <ul>
                        <li><a href="/fitur#kasir">Terminal Kasir &amp; Shift</a></li>
                        <li><a href="/fitur#stok">Stok Multi-Cabang &amp; HPP</a></li>
                        <li><a href="/fitur#akun">Portal Akun Terisolasi</a></li>
                        <li><a href="/contact">Konsultasi &amp; Request Fitur</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Portal Akun</h4>
                    <ul>
                        <li><a href="/client">Halaman Akun Klien</a></li>
                        <li><a href="/pos">Terminal Kasir POS</a></li>
                        <li><a href="/dashboard">Dashboard Pemilik</a></li>
                        <li><a href="/login?redirect=/client">Masuk / Daftar Bisnis</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© 2026 Tandha POS — Product by SEKA GROUP, Surabaya.</span>
                <span>Point of Sale &amp; Multi-Outlet Inventory Platform</span>
            </div>
        </div>
    </footer>

    <script>
        function siteHeaderAuth() {
            return {
                mobileNavOpen: false,
                token: localStorage.getItem('pos_token') || '',
                user: JSON.parse(localStorage.getItem('pos_user') || '{}'),
                get isLoggedIn() {
                    return Boolean(this.token && this.user && this.user.id);
                },
                async initAuth() {
                    if (!this.token) return;
                    try {
                        const res = await fetch('/api/me', {
                            headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' },
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.status === 'success') {
                                this.user = data.data;
                                localStorage.setItem('pos_user', JSON.stringify(data.data));
                            }
                        } else if (res.status === 401) {
                            this.clearSession();
                        }
                    } catch (e) {}
                },
                clearSession() {
                    this.token = '';
                    this.user = {};
                    localStorage.removeItem('pos_token');
                    localStorage.removeItem('pos_user');
                },
                async logout() {
                    if (this.token) {
                        try {
                            await fetch('/api/logout', {
                                method: 'POST',
                                headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' },
                            });
                        } catch (e) {}
                    }
                    this.clearSession();
                    window.location.href = '/login';
                }
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();

            const megaWrap = document.getElementById('nav-mega-wrap');
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && megaWrap) megaWrap.classList.remove('mega-open');
            });

            const revealEls = document.querySelectorAll('.reveal-on-scroll');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) entry.target.classList.add('is-visible');
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
                revealEls.forEach((el) => observer.observe(el));
            } else {
                revealEls.forEach((el) => el.classList.add('is-visible'));
            }

            const nav = document.querySelector('.site-nav');
            const progressBar = document.getElementById('scroll-progress');
            function onScroll() {
                const scrollY = window.scrollY;
                if (nav) nav.classList.toggle('nav-scrolled', scrollY > 40);
                if (progressBar) {
                    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                    const pct = docHeight > 0 ? Math.min(100, (scrollY / docHeight) * 100) : 0;
                    progressBar.style.width = `${pct}%`;
                }
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            // Hero Mint Cursor Spotlight
            const hero = document.getElementById('hero-stage');
            const glow = document.getElementById('hero-cursor-glow');
            if (hero && glow) {
                hero.addEventListener('mousemove', (e) => {
                    const rect = hero.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    glow.style.setProperty('--mx', `${x.toFixed(2)}%`);
                    glow.style.setProperty('--my', `${y.toFixed(2)}%`);
                });
            }

            const filterBtns = document.querySelectorAll('[data-case-filter]');
            const filterItems = document.querySelectorAll('[data-case-cat]');
            if (filterBtns.length) {
                filterBtns.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const cat = btn.getAttribute('data-case-filter');
                        filterBtns.forEach((b) => b.classList.toggle('active', b === btn));
                        filterItems.forEach((item) => {
                            const match = cat === 'all' || item.getAttribute('data-case-cat') === cat;
                            item.classList.toggle('hidden', !match);
                        });
                    });
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
