@extends('layouts.site')

@section('title', 'Konsultasi Kebutuhan — Seka Yasa POS Custom Studio')

@section('content')

<!-- Dark Inner Header -->
<section class="bg-[#1A1208] text-[#F7F3EC] py-20 border-b border-[rgba(247,176,95,0.16)]">
    <div class="sy-container">
        <div class="sy-index text-[#F7B05F] mb-3">01 / Widget Konsultasi & Pemetaan Awal</div>
        <h1 class="sy-h1 max-w-3xl">Rangkum Kebutuhan Sistem Kasir Anda dalam Tiga Langkah</h1>
        <p class="mt-4 text-[1.05rem] leading-[1.7] text-[#C4B5A2] max-w-[680px]">
            Pilih profil usaha, skala outlet, dan kendala utama di lapangan untuk menghasilkan ringkasan konsultasi otomatis serta mencatat proyek Anda di Portal Internal.
        </p>
    </div>
</section>

<!-- 3-Click Consultation Widget (#FAF7F2) -->
<section class="bg-[#FAF7F2] py-20" x-data="consultationWidget()">
    <div class="sy-container">
        <div class="sy-split">
            <!-- Left: 3-Click Selector -->
            <div class="space-y-10">
                <!-- Klik 1 -->
                <div>
                    <div class="sy-index text-[#775229] mb-2">Langkah 01 / Sektor Bisnis</div>
                    <h2 class="sy-h3 text-[#1A1208] mb-4">Apa bidang usaha utama Anda saat ini?</h2>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <template x-for="opt in sectors" :key="opt">
                            <button type="button" @click="selectedSector = opt"
                                :class="selectedSector === opt ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.22)] hover:border-[#775229]'"
                                class="p-3.5 rounded-md border text-left font-semibold transition">
                                <span x-text="opt"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Klik 2 -->
                <div class="pt-8 border-t border-[rgba(119,82,41,0.18)]">
                    <div class="sy-index text-[#775229] mb-2">Langkah 02 / Skala Outlet & Perangkat</div>
                    <h2 class="sy-h3 text-[#1A1208] mb-4">Berapa cabang & perangkat kasir yang beroperasi?</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <template x-for="sc in scales" :key="sc">
                            <button type="button" @click="selectedScale = sc"
                                :class="selectedScale === sc ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.22)] hover:border-[#775229]'"
                                class="p-3.5 rounded-md border text-left font-semibold transition">
                                <span x-text="sc"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Klik 3 -->
                <div class="pt-8 border-t border-[rgba(119,82,41,0.18)]">
                    <div class="sy-index text-[#775229] mb-2">Langkah 03 / Fokus Masalah Lapangan</div>
                    <h2 class="sy-h3 text-[#1A1208] mb-4">Kendala apa yang paling mendesak untuk diselesaikan?</h2>
                    <div class="grid grid-cols-1 gap-2.5 text-sm">
                        <template x-for="prob in problems" :key="prob">
                            <button type="button" @click="selectedProblem = prob"
                                :class="selectedProblem === prob ? 'bg-[#1A1208] text-[#F7B05F] border-[#1A1208]' : 'bg-[#F3EDE4] text-[#1A1208] border-[rgba(119,82,41,0.22)] hover:border-[#775229]'"
                                class="p-3.5 rounded-md border text-left font-semibold transition">
                                <span x-text="prob"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right: Live WhatsApp Summary Box (--soga-tint #F2EBE1) + Lead Inquiry Form -->
            <div class="sy-split-right space-y-6">
                <!-- WhatsApp Summary Preview (#F2EBE1) -->
                <div class="bg-[#F2EBE1] border border-[rgba(119,82,41,0.28)] rounded-lg p-6">
                    <div class="sy-index text-[#775229] mb-2">Pratinjau Rangkuman Konsultasi</div>
                    <h3 class="sy-h3 text-[#1A1208] mb-3">Ringkasan Otomatis Siap Kirim</h3>
                    <div class="bg-[#FAF7F2] border border-[rgba(119,82,41,0.18)] rounded p-4 font-mono text-xs text-[#1A1208] whitespace-pre-line leading-relaxed" x-text="summaryMessage"></div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <a :href="whatsappUrl" target="_blank" class="flex-1 py-3 px-4 rounded-md bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] text-xs font-bold text-center transition">
                            Kirim Rangkuman ke WhatsApp →
                        </a>
                    </div>
                </div>

                <!-- Account Gate / Direct Consultation Action -->
                <div class="bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded-lg p-6">
                    <div class="sy-index text-[#775229] mb-1">Alur Wajib: Login / Registrasi Akun Klien</div>
                    <h3 class="sy-h3 text-[#1A1208] mb-2">Sewa Layanan atau Ajukan Konsultasi Custom</h3>
                    <p class="text-xs text-[#524538] leading-relaxed mb-5">
                        Untuk menyewa layanan template POS maupun mengajukan konsultasi proses bisnis custom, Anda wajib masuk atau mendaftarkan akun terlebih dahulu agar seluruh jadwal, proposal, dan progres proyek tercatat rapi di halaman akun Anda.
                    </p>

                    <div x-show="!isLoggedIn" class="flex flex-col sm:flex-row gap-3">
                        <a href="/login?redirect=/client" class="flex-1 py-3 px-4 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded-md text-xs font-bold text-center transition">
                            Masuk ke Akun Klien →
                        </a>
                        <a href="/login?tab=register&redirect=/client" class="flex-1 py-3 px-4 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] rounded-md text-xs font-bold text-center transition">
                            Daftar Akun Baru →
                        </a>
                    </div>

                    <div x-show="isLoggedIn" class="space-y-3" x-cloak>
                        <div class="p-3 bg-[#FAF7F2] border border-[rgba(119,82,41,0.2)] rounded text-xs">
                            Masuk sebagai: <b x-text="loggedInUser.name"></b> (<span class="font-mono text-[#775229]" x-text="loggedInUser.email"></span>)
                        </div>
                        <a href="/client" class="block w-full py-3 px-4 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] rounded-md text-xs font-bold text-center transition">
                            Buka Halaman Akun Saya (Sewa / Konsultasi Custom) →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
function consultationWidget() {
    const token = localStorage.getItem('pos_token');
    const user = JSON.parse(localStorage.getItem('pos_user') || '{}');
    return {
        isLoggedIn: Boolean(token),
        loggedInUser: user,
        sectors: ['F&B / Restoran / Kafe', 'Retail / Butik / Minimarket', 'Apotek / Kesehatan', 'Jasa / Bengkel / Barbershop'],
        scales: ['1 Outlet (Tunggal)', '2–5 Cabang', 'Lebih dari 5 Cabang'],
        problems: [
            'Stok gudang & resep sering selisih akibat pencatatan manual',
            'Kasir perlu aplikasi Android cepat + kontrol ketat selisih shift',
            'Butuh sistem POS custom karena proses bisnis tidak sesuai template',
        ],
        selectedSector: 'F&B / Restoran / Kafe',
        selectedScale: '2–5 Cabang',
        selectedProblem: 'Butuh sistem POS custom karena proses bisnis tidak sesuai template',
        get summaryMessage() {
            return `Halo Tim Seka Yasa,\nSaya ingin berkonsultasi mengenai sistem POS custom:\n• Sektor Usaha: ${this.selectedSector}\n• Skala Outlet: ${this.selectedScale}\n• Fokus Masalah: ${this.selectedProblem}\n\nMohon jadwalkan sesi discovery awal.`;
        },
        get whatsappUrl() {
            return `https://wa.me/6281298765432?text=${encodeURIComponent(this.summaryMessage)}`;
        }
    }
}
</script>
@endpush
