<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Seka Yasa POS — Layanan Konsultasi & Sistem Kasir Custom')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --amber: #F7B05F;
            --amber-hover: #E89E4A;
            --soga: #775229;
            --soga-dark: #583B1B;
            --soga-tint: #F2EBE1;
            --espresso: #1A1208;
            --espresso-surface: #23190D;
            --cream: #FAF7F2;
            --cream-alt: #F3EDE4;
            --text-primary: #1A1208;
            --text-secondary: #524538;
            --text-on-dark: #F7F3EC;
            --text-on-dark-muted: #C4B5A2;
            --line-light: rgba(119, 82, 41, 0.18);
        }

        [x-cloak] { display: none !important; }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--cream);
            color: var(--text-primary);
            margin: 0;
        }

        /* Wide Editorial Container (DESIGN_SYSTEM.md Sec 1.5 & 6.3) */
        .sy-container {
            width: 92%;
            max-width: 1540px;
            margin: 0 auto;
        }

        /* Scroll Progress Bar */
        #scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 2px;
            background: var(--amber);
            width: 0%;
            z-index: 60;
            transition: width 0.1s linear;
        }

        /* Typography Rules (DESIGN_SYSTEM.md Sec 3) */
        .sy-h1 {
            font-size: clamp(2.25rem, 3.6vw, 3.5rem);
            font-weight: 700;
            line-height: 1.18;
            letter-spacing: -0.02em;
        }

        .sy-h2 {
            font-size: clamp(1.75rem, 2.5vw, 2.4rem);
            font-weight: 700;
            line-height: 1.24;
            letter-spacing: -0.015em;
        }

        .sy-h3 {
            font-size: clamp(1.25rem, 1.5vw, 1.38rem);
            font-weight: 700;
            line-height: 1.30;
        }

        .sy-index {
            font-size: 0.84rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .sy-body-point {
            font-size: 0.98rem;
            font-weight: 400;
            line-height: 1.60;
            max-width: 560px;
        }

        /* Asymmetric Sticky Split (Pola B) */
        .sy-split {
            display: grid;
            grid-template-columns: 0.72fr 1.08fr;
            gap: 5rem;
            justify-content: space-between;
            align-items: start;
        }

        .sy-split-left {
            position: sticky;
            top: 7.5rem;
            max-width: 440px;
        }

        .sy-split-right {
            max-width: 720px;
            width: 100%;
            justify-self: end;
        }

        /* Alternating Wide Showcases (Pola C) */
        .sy-showcase {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 4.5rem;
            align-items: center;
        }

        .sy-showcase.reverse {
            grid-template-columns: 0.85fr 1.15fr;
        }

        .sy-showcase.reverse .sy-showcase-media {
            order: 2;
        }

        .sy-showcase.reverse .sy-showcase-copy {
            order: 1;
        }

        /* Hero Grid (Pola A) */
        .sy-hero-grid {
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 4.5rem;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .sy-hero-grid,
            .sy-split,
            .sy-showcase,
            .sy-showcase.reverse {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            .sy-split-left {
                position: static;
                max-width: 100%;
            }
            .sy-split-right {
                justify-self: start;
                max-width: 100%;
            }
            .sy-showcase.reverse .sy-showcase-media,
            .sy-showcase.reverse .sy-showcase-copy {
                order: unset;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="scroll-progress"></div>

    <!-- Navbar (Deep Espresso #1A1208) -->
    <header class="sticky top-0 z-50 bg-[#1A1208]/95 backdrop-blur border-b border-[rgba(247,176,95,0.14)]">
        <div class="sy-container h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <!-- Minimal Javanese Geometric Mark -->
                <svg class="w-8 h-8 text-[#F7B05F]" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="4" y="4" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 9L31 20L20 31L9 20L20 9Z" stroke="currentColor" stroke-width="1.75"/>
                    <circle cx="20" cy="20" r="3.5" fill="currentColor"/>
                </svg>
                <div>
                    <span class="block text-base font-bold tracking-tight text-[#F7F3EC] leading-none">SEKA YASA</span>
                    <span class="block text-[11px] font-medium text-[#C4B5A2] tracking-wider uppercase mt-1">POS Custom Studio</span>
                </div>
            </a>

            <!-- Main Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                <a href="/" class="{{ request()->is('/') ? 'text-[#F7B05F] font-semibold' : 'text-[#C4B5A2] hover:text-[#F7F3EC]' }} transition">Beranda</a>
                <a href="/projects" class="{{ request()->is('projects') ? 'text-[#F7B05F] font-semibold' : 'text-[#C4B5A2] hover:text-[#F7F3EC]' }} transition">Karya Sistem</a>
                <a href="/about" class="{{ request()->is('about') ? 'text-[#F7B05F] font-semibold' : 'text-[#C4B5A2] hover:text-[#F7F3EC]' }} transition">Tentang</a>
                <a href="/contact" class="{{ request()->is('contact') ? 'text-[#F7B05F] font-semibold' : 'text-[#C4B5A2] hover:text-[#F7F3EC]' }} transition">Konsultasi</a>
            </nav>

            <!-- Operational Entry Points -->
            <div class="flex items-center gap-3">
                <a href="/client" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-md border border-[rgba(247,176,95,0.28)] text-xs font-semibold text-[#F7F3EC] hover:border-[#F7B05F] hover:text-[#F7B05F] transition">
                    Akun & Konsultasi
                </a>
                <a href="/login?redirect=/client" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] text-xs font-bold transition">
                    Masuk / Daftar Sewa
                </a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer (Deep Espresso #1A1208) -->
    <footer class="bg-[#1A1208] text-[#C4B5A2] border-t border-[rgba(247,176,95,0.16)] py-14">
        <div class="sy-container flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
            <div>
                <div class="flex items-center gap-2.5 text-[#F7F3EC] font-bold text-base">
                    <span>SEKA YASA — POS Custom Studio</span>
                </div>
                <p class="text-sm text-[#C4B5A2] mt-2 max-w-md">
                    Konsultasi alur operasional, pengembangan POS custom sesuai proses bisnis, serta infrastruktur produksi terkelola.
                </p>
            </div>

            <div class="flex flex-wrap gap-6 text-xs font-medium">
                <a href="/" class="hover:text-[#F7B05F] transition">Beranda</a>
                <a href="/projects" class="hover:text-[#F7B05F] transition">Karya Sistem</a>
                <a href="/about" class="hover:text-[#F7B05F] transition">Tentang</a>
                <a href="/contact" class="hover:text-[#F7B05F] transition">Konsultasi</a>
                <span class="text-[#775229]">|</span>
                <a href="/pos" class="text-[#F7B05F] hover:underline">Layar Kasir POS</a>
                <a href="/dashboard" class="text-[#F7B05F] hover:underline">Dashboard Owner</a>
                <a href="/portal" class="text-[#F7B05F] hover:underline">Portal One-Click Deploy</a>
            </div>
        </div>
        <div class="sy-container mt-10 pt-6 border-t border-[rgba(247,176,95,0.12)] flex flex-col sm:flex-row justify-between text-xs text-[#C4B5A2]/70 gap-2">
            <span>© 2026 Seka Yasa POS Custom Development Service.</span>
            <span>Arsitektur Terisolasi Per Klien • Laravel & MySQL Production Standard</span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
        window.addEventListener('scroll', () => {
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrolled = docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0;
            const bar = document.getElementById('scroll-progress');
            if (bar) bar.style.width = scrolled + '%';
        });
    </script>
    @stack('scripts')
</body>
</html>
