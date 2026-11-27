<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tandha by SEKA GROUP — Aplikasi Kasir & Kontrol Cabang')</title>
    <link rel="icon" type="image/svg+xml" href="/assets/Logo%20Tandha.svg?v=5">
    <link rel="icon" type="image/png" sizes="192x192" href="/tandha-icon-192.png?v=5">
    <link rel="shortcut icon" href="/favicon.ico?v=5">

    <!-- PWA WebAPK Meta Tags -->
    <link rel="manifest" href="/manifest.json?v=5">
    <meta name="theme-color" content="#0D2620">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Tandha Kasir">
    <link rel="apple-touch-icon" href="/tandha-maskable-192.png?v=5">

    <!-- Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Manrope"', '"Plus Jakarta Sans"', 'ui-sans-serif', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F7FAF9;
            color: #0D2620;
        }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.28); border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.5); }
        aside .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.25); }
        aside .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.45); }

        @keyframes tandhaIndeterminate {
            0% { transform: translateX(-100%); width: 35%; }
            50% { transform: translateX(65%); width: 55%; }
            100% { transform: translateX(220%); width: 35%; }
        }
        .tandha-progress-bar {
            animation: tandhaIndeterminate 1.25s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        .font-tabular { font-variant-numeric: tabular-nums; }

        /* Refined Modals & SweetAlert2 (Eliminates Harsh White Outlines on Dark Backdrops) */
        .tandha-modal-card {
            border-color: rgba(13, 38, 32, 0.34) !important;
            box-shadow: 0 24px 54px -12px rgba(6, 20, 16, 0.48), 0 0 0 1px rgba(13, 38, 32, 0.16) !important;
            outline: none !important;
        }
        .swal2-container {
            backdrop-filter: blur(4px);
            background: rgba(13, 38, 32, 0.62) !important;
        }
        .swal2-popup {
            border: 1px solid rgba(13, 38, 32, 0.3) !important;
            border-radius: 16px !important;
            padding: 1.5rem 1.5rem 1.35rem !important;
            background: #FFFFFF !important;
            color: #0D2620 !important;
            box-shadow: 0 24px 56px -12px rgba(6, 20, 16, 0.5), 0 0 0 1px rgba(13, 38, 32, 0.14) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            outline: none !important;
        }
        .swal2-popup:focus,
        .swal2-popup *:focus {
            outline: none !important;
        }
        .swal2-title {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            color: #0D2620 !important;
            padding-top: 0.35rem !important;
        }
        .swal2-html-container {
            font-size: 0.8125rem !important;
            line-height: 1.6 !important;
            color: #475569 !important;
            margin-top: 0.55rem !important;
        }
        .swal2-actions {
            gap: 0.5rem !important;
            margin-top: 1.25rem !important;
        }
        .swal2-styled {
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            padding: 0.58rem 1.15rem !important;
            box-shadow: none !important;
        }
        .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(13, 122, 95, 0.24) !important;
        }

        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt *,
            #printable-sub-receipt, #printable-sub-receipt * { visibility: visible; }
            #printable-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                margin: 0;
                padding: 10px;
                font-family: 'Courier New', Courier, monospace;
            }
            #printable-sub-receipt {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 720px;
                margin: 0 auto;
                padding: 24px;
                background: #ffffff;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased font-sans bg-[#F7FAF9] text-[#0D2620]">
    @yield('content')

    <script>
        window.__tandhaInstallPrompt = null;
        window.__tandhaPromptWaiters = [];
        window.__tandhaHasPhysicalApk = @json(is_file(public_path('downloads/tandha-kasir.apk')) || !empty(config('app.kasir_apk_download_url')));
        window.__tandhaCanInstallApk = false;

        window.tandhaEscapeHtml = function (value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.__tandhaInstallPrompt = e;
            while (window.__tandhaPromptWaiters.length > 0) {
                const resolve = window.__tandhaPromptWaiters.shift();
                resolve(e);
            }
        });

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
        }

        window.waitForTandhaInstallPrompt = function (timeoutMs = 1500) {
            if (window.__tandhaInstallPrompt) {
                return Promise.resolve(window.__tandhaInstallPrompt);
            }
            return new Promise((resolve) => {
                let settled = false;
                const finish = (ev) => {
                    if (settled) return;
                    settled = true;
                    resolve(ev || null);
                };
                window.__tandhaPromptWaiters.push(finish);
                setTimeout(() => finish(window.__tandhaInstallPrompt), timeoutMs);
            });
        };

        window.triggerTandhaInstallApk = async function (mode = 'webapk') {
            if (!window.__tandhaCanInstallApk) {
                await Swal.fire({
                    icon: 'info',
                    title: 'Paket Langganan Belum Aktif',
                    text: 'Aktifkan paket langganan terlebih dahulu di menu Langganan & Tagihan untuk memasang aplikasi kasir pada perangkat outlet Anda.',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#0D7A5F',
                });
                return;
            }

            if (mode === 'apk') {
                if (window.__tandhaHasPhysicalApk) {
                    window.location.href = '/download/kasir-apk';
                    return;
                }
                const apkMissingRes = await Swal.fire({
                    icon: 'info',
                    title: 'File .APK Android Belum Tersedia',
                    html: '<p class="text-sm text-slate-600 leading-relaxed">File biner Android (<code>public/downloads/tandha-kasir.apk</code>) belum diunggah ke server. Anda tetap bisa langsung memasang versi <b>WebAPK / PWA</b> dari browser sekarang dengan ikon Tandha resmi.</p>',
                    showCancelButton: true,
                    confirmButtonText: 'Install WebAPK (PWA) Sekarang',
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#0D7A5F',
                });
                if (apkMissingRes.isConfirmed) {
                    return window.triggerTandhaInstallApk('webapk');
                }
                return;
            }

            const isLocalHost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
            if (window.location.protocol !== 'https:' && !isLocalHost) {
                window.location.href = 'https://' + window.location.host + window.location.pathname + window.location.search;
                return;
            }

            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            if (isStandalone) {
                window.open('/pos?app=kasir', '_blank');
                return;
            }

            const promptEvent = await window.waitForTandhaInstallPrompt(1500);
            if (promptEvent) {
                window.__tandhaInstallPrompt = null;
                await promptEvent.prompt();
                const choice = await promptEvent.userChoice;
                if (choice && choice.outcome === 'accepted') {
                    Swal.fire({
                        icon: 'success',
                        title: 'WebAPK Kasir Terpasang',
                        text: 'Aplikasi Kasir Tandha siap digunakan dari layar utama perangkat. Buka aplikasi dan masuk menggunakan akun Kasir cabang Anda.',
                        confirmButtonColor: '#0D7A5F',
                    });
                }
                return;
            }

            const res = await Swal.fire({
                icon: 'info',
                title: 'Buka Aplikasi Kasir',
                text: 'Pemasangan otomatis langsung dari tombol ditahan oleh browser ini (umumnya karena aplikasi sudah pernah terpasang, mode Incognito, atau menggunakan Safari). Anda tetap bisa langsung membuka terminal kasir sekarang.',
                showCancelButton: true,
                confirmButtonText: 'Buka Terminal Kasir',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#0D7A5F',
            });
            if (res.isConfirmed) {
                window.open('/pos?app=kasir', '_blank');
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
