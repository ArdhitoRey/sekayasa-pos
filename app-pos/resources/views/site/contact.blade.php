@extends('layouts.site')

@section('title', 'Konsultasi Kebutuhan — Seka Yasa POS Custom Studio')

@section('content')

<!-- Dark Inner Header -->
<section class="bg-[#1A1208] text-[#F7F3EC] py-20 border-b border-[rgba(247,176,95,0.16)]">
    <div class="sy-container">
        <div class="sy-index text-[#F7B05F] mb-3">01 / Konsultasi & Pemetaan Kebutuhan</div>
        <h1 class="sy-h1 max-w-3xl">Rangkum Kebutuhan Sistem Kasir Anda dalam Tiga Langkah</h1>
        <p class="mt-4 text-[1.05rem] leading-[1.7] text-[#C4B5A2] max-w-[680px]">
            Pilih profil usaha, skala outlet, dan kendala utama di lapangan untuk menjadwalkan konsultasi langsung dari akun bisnis Anda.
        </p>
    </div>
</section>

<!-- Consultation Section (#FAF7F2) -->
<section class="bg-[#FAF7F2] py-20" x-data="consultationWidget()">
    <div class="sy-container">

        <!-- AUTHENTICATION GATE: WAJIB LOGIN SEBELUM KONSULTASI -->
        <div x-show="!isLoggedIn" class="max-w-3xl mx-auto bg-[#F3EDE4] border border-[rgba(119,82,41,0.28)] rounded-lg p-8 md:p-10 space-y-6">
            <div>
                <div class="sy-index text-[#775229] mb-2">01 / Autentikasi Wajib Sebelum Konsultasi</div>
                <h2 class="sy-h2 text-[#1A1208]">Silakan Masuk atau Daftar Akun Terlebih Dahulu</h2>
                <p class="mt-3 text-[0.98rem] leading-[1.65] text-[#524538]">
                    Untuk melakukan konsultasi proses bisnis maupun menyewa layanan POS, Anda wajib masuk ke akun terlebih dahulu agar jadwal discovery, catatan ketidaksesuaian template, dan tindak lanjut dari analis kami tercatat di halaman akun Anda.
                </p>
            </div>

            <div class="border-t border-[rgba(119,82,41,0.18)] pt-6 flex flex-col sm:flex-row gap-3">
                <a href="/login?redirect=/contact" class="flex-1 py-3.5 px-6 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] rounded-md text-sm font-bold text-center transition">
                    Masuk ke Akun Saya →
                </a>
                <a href="/login?tab=register&redirect=/contact" class="flex-1 py-3.5 px-6 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] rounded-md text-sm font-bold text-center transition">
                    Daftar Akun Bisnis Baru →
                </a>
            </div>
        </div>

        <!-- LOGGED-IN CONSULTATION WORKSPACE -->
        <div x-show="isLoggedIn" class="space-y-10" x-cloak>
            <!-- Clear Logged-In Banner -->
            <div class="bg-[#1A1208] text-[#F7F3EC] border border-[rgba(247,176,95,0.3)] rounded-lg p-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-[#F7B05F] text-[#1A1208] rounded">Sedang Login</span>
                    <div class="text-sm font-bold mt-1.5">
                        <span x-text="loggedInUser.name"></span>
                        <span class="text-[#C4B5A2] font-normal" x-text="' — ' + (loggedInUser.business ? loggedInUser.business.name : loggedInUser.email)"></span>
                    </div>
                </div>
                <a href="/client" class="px-4 py-2 bg-[#23190D] border border-[rgba(247,176,95,0.35)] hover:border-[#F7B05F] text-[#F7B05F] rounded text-xs font-bold transition">
                    Lihat Riwayat di Halaman Akun →
                </a>
            </div>

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

                <!-- Right: Summary Box + Direct Authenticated Submit -->
                <div class="sy-split-right space-y-6">
                    <div class="bg-[#F2EBE1] border border-[rgba(119,82,41,0.28)] rounded-lg p-6">
                        <div class="sy-index text-[#775229] mb-2">Pratinjau Rangkuman Konsultasi</div>
                        <h3 class="sy-h3 text-[#1A1208] mb-3">Ringkasan Kebutuhan Anda</h3>
                        <div class="bg-[#FAF7F2] border border-[rgba(119,82,41,0.18)] rounded p-4 font-mono text-xs text-[#1A1208] whitespace-pre-line leading-relaxed" x-text="summaryMessage"></div>

                        <div class="mt-4">
                            <a :href="whatsappUrl" target="_blank" class="block w-full py-3 px-4 rounded-md bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] text-xs font-bold text-center transition">
                                Kirim Rangkuman ke WhatsApp →
                            </a>
                        </div>
                    </div>

                    <div class="bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded-lg p-6">
                        <div class="sy-index text-[#775229] mb-1">Simpan Pengajuan ke Akun Anda</div>
                        <h3 class="sy-h3 text-[#1A1208] mb-4">Jadwalkan Sesi Konsultasi Resmi</h3>

                        <form @submit.prevent="submitConsultationFromContact()" class="space-y-3 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">No. WhatsApp</label>
                                    <input type="text" x-model="phone" required placeholder="0812xxxxxxxx" class="w-full px-3 py-2.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#524538] mb-1">Jadwal Konsultasi</label>
                                    <input type="datetime-local" x-model="preferredAt" required class="w-full px-3 py-2.5 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]">
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-[#524538] mb-1">Detail Tambahan Ketidaksesuaian Template (Opsional)</label>
                                <textarea x-model="extraNotes" rows="2" placeholder="Jelaskan alur khusus kasir atau stok Anda..." class="w-full px-3 py-2 bg-[#FAF7F2] border border-[rgba(119,82,41,0.25)] rounded text-[#1A1208]"></textarea>
                            </div>
                            <button type="submit" :disabled="submitting" class="w-full py-3 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] font-bold rounded-md text-xs transition disabled:opacity-50">
                                <span x-show="!submitting">Kirim & Jadwalkan Konsultasi ke Akun Saya →</span>
                                <span x-show="submitting">Menyimpan Jadwal...</span>
                            </button>
                        </form>
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
    const token = localStorage.getItem('pos_token') || '';
    const user = JSON.parse(localStorage.getItem('pos_user') || '{}');
    return {
        token: token,
        isLoggedIn: Boolean(token && user && user.id),
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
        phone: user.phone || '',
        preferredAt: new Date(Date.now() + 86400000).toISOString().slice(0, 16),
        extraNotes: '',
        submitting: false,
        get summaryMessage() {
            return `Halo Tim Seka Yasa,\nSaya ${this.loggedInUser.name || ''} ingin berkonsultasi mengenai sistem POS:\n• Sektor Usaha: ${this.selectedSector}\n• Skala Outlet: ${this.selectedScale}\n• Fokus Masalah: ${this.selectedProblem}\n\nMohon jadwalkan sesi discovery awal.`;
        },
        get whatsappUrl() {
            return `https://wa.me/6281298765432?text=${encodeURIComponent(this.summaryMessage)}`;
        },
        async submitConsultationFromContact() {
            this.submitting = true;
            try {
                const notes = this.extraNotes ? `${this.selectedProblem}. Catatan: ${this.extraNotes}` : this.selectedProblem;
                const res = await fetch('/api/client/consultations', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        business_type: this.selectedSector,
                        scale: this.selectedScale,
                        phone: this.phone,
                        preferred_consultation_at: this.preferredAt,
                        template_gap_notes: notes,
                    }),
                });
                const result = await res.json();
                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Konsultasi Dijadwalkan!',
                        text: result.message,
                        confirmButtonColor: '#775229',
                    }).then(() => {
                        window.location.href = '/client';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Periksa kembali isian Anda', confirmButtonColor: '#775229' });
                }
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endpush
