<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Seka Yasa POS — Sistem Kasir & Layanan Custom')</title>

    <!-- PWA WebAPK Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1A1208">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="POS Kasir">
    <link rel="apple-touch-icon" href="/icon-192.png">

    <!-- Typography: Plus Jakarta Sans (400, 500, 600, 700) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        espresso: {
                            DEFAULT: '#1A1208',
                            surface: '#23190D',
                        },
                        cream: {
                            DEFAULT: '#FAF7F2',
                            alt: '#F3EDE4',
                        },
                        soga: {
                            DEFAULT: '#775229',
                            dark: '#583B1B',
                            tint: '#F2EBE1',
                        },
                        amberx: {
                            DEFAULT: '#F7B05F',
                            hover: '#E89E4A',
                        },
                        umber: '#524538',
                        ivory: '#F7F3EC',
                        sand: '#C4B5A2',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- QRCode.js (Dynamic QRIS Renderer) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- SweetAlert2 -->
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
            --line-dark: rgba(247, 176, 95, 0.22);
        }

        [x-cloak] { display: none !important; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--cream);
            color: var(--text-primary);
        }

        .hairline-soga {
            border-color: var(--line-light);
        }

        .hairline-dark {
            border-color: var(--line-dark);
        }

        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                margin: 0;
                padding: 10px;
                font-family: 'Courier New', Courier, monospace;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased font-sans bg-[#FAF7F2] text-[#1A1208]">
    @yield('content')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration error:', err));
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
