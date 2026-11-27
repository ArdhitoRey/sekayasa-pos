@extends('layouts.site')

@section('title', 'Konsultasi & Kontak — Tandha by SEKA GROUP')

@section('content')

<!-- PAGE HEADER -->
<section class="inner-header">
    <div class="container inner-header-grid reveal-on-scroll is-visible">
        <div>
            <span class="section-index section-index-light">Konsultasi &amp; Kontak</span>
            <h1>Ceritakan proses bisnis POS yang tidak tercakup di paket template.</h1>
        </div>
        <p>
            Masuk ke akun Tandha Anda untuk memilih kebutuhan operasional, melihat rekomendasi awal, dan menyimpan jadwal konsultasi resmi ke halaman akun Anda.
        </p>
    </div>
</section>

<!-- CONTACT & CONSULTATION -->
<section class="inner-content" x-data="consultationWidget()">
    <div class="container">

        <!-- AUTH GATE -->
        <div x-show="!isLoggedIn" class="max-w-3xl mx-auto bg-white border border-[rgba(13,38,32,0.14)] rounded-[14px] p-5 sm:p-8 md:p-10 space-y-6">
            <div>
                <span class="section-index">Autentikasi Akun Bisnis</span>
                <h2 class="split-title">Silakan Masuk atau Daftar Akun Terlebih Dahulu</h2>
                <p class="split-desc">
                    Untuk mengajukan konsultasi penambahan fitur aplikasi Tandha bersama tim SEKA GROUP (baik sebelum maupun setelah berlangganan), silakan masuk ke akun Tandha terlebih dahulu agar jadwal, detail kebutuhan, dan status balasan WhatsApp tercatat di halaman akun Anda.
                </p>
            </div>

            <div class="border-t border-[rgba(13,38,32,0.12)] pt-6 flex flex-col sm:flex-row gap-3">
                <a href="/login?redirect=/contact" class="btn btn-jade btn-lg flex-1">
                    Masuk ke Akun Saya →
                </a>
                <a href="/login?tab=register&redirect=/contact" class="btn btn-mint btn-lg flex-1">
                    Daftar Bisnis Baru →
                </a>
            </div>
        </div>

        <!-- LOGGED-IN CONSULTATION WORKSPACE -->
        <div x-show="isLoggedIn" class="space-y-8" x-cloak>
            <div class="bg-[#0D2620] text-white border border-[rgba(52,211,153,0.3)] rounded-[14px] p-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 bg-[#34D399] text-[#0D2620] rounded-full">Sedang Login</span>
                    <div class="text-sm font-bold mt-1.5">
                        <span x-text="loggedInUser.name"></span>
                        <span class="text-[#B8D0C9] font-normal" x-text="' — ' + (loggedInUser.business ? loggedInUser.business.name : loggedInUser.email)"></span>
                    </div>
                </div>
                <a href="/dashboard?tab=consultation" class="btn btn-mint btn-sm">
                    Lihat Riwayat di Akun Saya →
                </a>
            </div>

            <div class="contact-split">
                <div class="diag-panel">
                    <div class="diag-group">
                        <span class="diag-group-title">1. Bidang usaha Anda saat ini</span>
                        <div class="chip-list">
                            <template x-for="opt in sectors" :key="opt">
                                <button type="button" @click="selectedSector = opt"
                                    :class="selectedSector === opt ? 'choice-chip active' : 'choice-chip'"
                                    x-text="opt"></button>
                            </template>
                        </div>
                        <div x-show="selectedSector === 'Lainnya'" class="mt-3" x-cloak>
                            <input type="text" x-model="otherSector" placeholder="Tulis bidang usaha Anda (contoh: Laundry, Toko Bangunan, Petshop)" class="clean-input text-xs">
                        </div>
                    </div>

                    <div class="diag-group">
                        <span class="diag-group-title">2. Kebutuhan POS yang tidak cocok dengan template</span>
                        <div class="chip-list">
                            <template x-for="prob in problems" :key="prob">
                                <button type="button" @click="selectedProblem = prob"
                                    :class="selectedProblem === prob ? 'choice-chip active' : 'choice-chip'"
                                    x-text="prob"></button>
                            </template>
                        </div>
                    </div>

                    <div class="diag-group" style="margin-bottom: 1.5rem;">
                        <span class="diag-group-title">3. Skala outlet &amp; jadwal konsultasi akun</span>
                        <div class="chip-list mb-5">
                            <template x-for="sc in scales" :key="sc">
                                <button type="button" @click="selectedScale = sc"
                                    :class="selectedScale === sc ? 'choice-chip active' : 'choice-chip'"
                                    x-text="sc"></button>
                            </template>
                        </div>

                        <div class="ba-box ba-after mb-5">
                            <strong>Rekomendasi Arsitektur Awal</strong>
                            <p x-text="'Untuk sektor ' + selectedSector + ' (' + selectedScale + '), kami menyarankan pemetaan aturan ' + selectedProblem.toLowerCase() + ' terlebih dahulu agar struktur database kasir & stok langsung sesuai SOP toko Anda.'"></p>
                        </div>

                        <form @submit.prevent="submitConsultationFromContact()" class="space-y-4 pt-4 border-t border-[rgba(13,38,32,0.12)]">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <label class="block font-semibold text-[#3D544E] mb-1">No. WhatsApp Aktif</label>
                                    <input type="text" x-model="phone" required placeholder="0812xxxxxxxx" class="clean-input">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#3D544E] mb-1">Tanggal Konsultasi (Min. H-3)</label>
                                    <input type="date" x-model="preferredDate" :min="minConsultDate" :max="maxConsultDate" required class="clean-input">
                                </div>
                                <div class="relative" @click.outside="openTimeDropdown = false">
                                    <label class="block font-semibold text-[#3D544E] mb-1">Jam Konsultasi (WIB)</label>
                                    <button type="button" @click="openTimeDropdown = !openTimeDropdown"
                                        class="clean-input w-full flex items-center justify-between text-left">
                                        <span x-text="preferredTime + ' WIB'"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openTimeDropdown" x-transition class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg z-40 py-1 max-h-48 overflow-y-auto">
                                        <template x-for="slot in timeSlots" :key="slot">
                                            <button type="button" @click="preferredTime = slot; openTimeDropdown = false"
                                                class="w-full px-3 py-1.5 text-left text-xs flex justify-between hover:bg-slate-50"
                                                :class="preferredTime === slot ? 'bg-slate-50 font-semibold text-emerald-700' : 'text-slate-700'">
                                                <span x-text="slot + ' WIB'"></span><span x-show="preferredTime === slot">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-[#475569]">Jadwal pengajuan minimal 3 hari ke depan (<b>H-3</b>) agar tim SEKA GROUP sempat mengulas kebutuhan Anda terlebih dahulu (jam operasional 09:00–17:00 WIB).</p>

                            <div class="text-xs">
                                <label class="block font-semibold text-[#3D544E]">Catatan Request Penambahan Fitur (Opsional)</label>
                                <textarea x-model="extraNotes" rows="3" placeholder="Contoh: Kami butuh pemotongan stok bahan baku resep otomatis, harga grosir bertingkat, atau skema komisi staf." class="clean-input"></textarea>
                            </div>

                            <div class="wa-summary-card">
                                <div class="wa-summary-head">Pratinjau Pesan Konsultasi</div>
                                <p class="wa-summary-text" x-text="summaryMessage"></p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button type="submit" :disabled="submitting" class="btn btn-jade btn-lg w-full">
                                    <span x-show="!submitting">Simpan Jadwal ke Akun →</span>
                                    <span x-show="submitting">Menyimpan...</span>
                                </button>
                                <a :href="whatsappUrl" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-lg w-full">
                                    Kirim via WhatsApp →
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <aside class="contact-sidebar">
                    <h3>Tandha by SEKA GROUP</h3>
                    <p>
                        Kami melayani konsultasi pemetaan alur kasir dan stok secara daring (WhatsApp / Google Meet) maupun pertemuan langsung untuk area Surabaya dan sekitarnya.
                    </p>

                    <div class="contact-meta-list mb-8">
                        <div class="contact-meta-item">
                            <small>Lokasi Studio</small>
                            <strong>Surabaya, Jawa Timur, Indonesia</strong>
                        </div>
                        <div class="contact-meta-item">
                            <small>Jam Operasional</small>
                            <strong>Senin – Sabtu, 08.30 – 17.30 WIB</strong>
                        </div>
                        <div class="contact-meta-item">
                            <small>Sesi Konsultasi Awal</small>
                            <strong>Gratis untuk pemilik akun Tandha terdaftar</strong>
                        </div>
                    </div>

                    <div class="map-card">
                        <div class="map-card-header">
                            <span><i class="map-dot"></i>Titik Lokasi — Surabaya</span>
                            <span style="color: var(--jade); font-size: 0.78rem;">Google Maps</span>
                        </div>
                        <div class="map-frame-wrap map-tall">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.364153806206!2d112.70115105678568!3d-7.312925154070691!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fd5b5a50f49b%3A0x52f29005d0094cb!2sZewu!5e0!3m2!1sid!2sid!4v1795262423745!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" title="Peta Lokasi SEKA GROUP Surabaya"></iframe>
                        </div>
                    </div>
                </aside>
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
    const pad = (n) => String(n).padStart(2, '0');
    const toLocalDate = (daysAhead) => {
        const d = new Date();
        d.setDate(d.getDate() + daysAhead);
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    };
    return {
        token: token,
        isLoggedIn: Boolean(token && user && user.id),
        loggedInUser: user,
        sectors: ['F&B / Restoran / Kafe', 'Retail / Toko / Grosir', 'Apotek / Klinik', 'Jasa / Bengkel / Barbershop', 'Lainnya'],
        scales: ['1 Outlet', '2–5 Cabang', 'Lebih dari 5 Cabang'],
        timeSlots: ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        problems: [
            'Resep & pemotongan bahan baku otomatis per porsi',
            'Harga bertingkat grosir/eceran & diskon khusus',
            'Integrasi timbangan, batch kadaluarsa, atau komisi staf',
            'Penyesuaian alur kasir & approval cabang khusus',
        ],
        selectedSector: 'F&B / Restoran / Kafe',
        otherSector: '',
        selectedScale: '2–5 Cabang',
        selectedProblem: 'Resep & pemotongan bahan baku otomatis per porsi',
        phone: user.phone || '',
        minConsultDate: toLocalDate(3),
        maxConsultDate: toLocalDate(90),
        preferredDate: toLocalDate(3),
        preferredTime: '10:00',
        openTimeDropdown: false,
        extraNotes: '',
        submitting: false,
        get resolvedSectorLabel() {
            return (this.selectedSector === 'Lainnya' && (this.otherSector || '').trim())
                ? ('Lainnya: ' + this.otherSector.trim())
                : this.selectedSector;
        },
        get summaryMessage() {
            const notesPart = this.extraNotes ? `\n• Detail Tambahan: ${this.extraNotes}` : '';
            return `Halo Tim Tandha by SEKA GROUP, saya ${this.loggedInUser.name || ''}.\n• Sektor Usaha: ${this.resolvedSectorLabel}\n• Skala Outlet: ${this.selectedScale}\n• Jadwal Pilihan (min. H-3): ${this.preferredDate} pukul ${this.preferredTime} WIB\n• Kebutuhan Fitur: ${this.selectedProblem}${notesPart}\n\nMohon konfirmasi melalui WhatsApp ini. Terima kasih.`;
        },
        get whatsappUrl() {
            const waNumber = @json(config('services.whatsapp.sekayasa_number', '6281234567890'));
            return `https://wa.me/${waNumber}?text=${encodeURIComponent(this.summaryMessage)}`;
        },
        async submitConsultationFromContact() {
            if (this.selectedSector === 'Lainnya' && (this.otherSector || '').trim().length < 3) {
                Swal.fire({ icon: 'warning', title: 'Bidang Usaha Lainnya', text: 'Mohon tulis bidang usaha Anda terlebih dahulu.', confirmButtonColor: '#0D7A5F' });
                return;
            }
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
                        business_type_other: this.selectedSector === 'Lainnya' ? this.otherSector : null,
                        scale: this.selectedScale,
                        phone: this.phone,
                        preferred_date: this.preferredDate,
                        preferred_time: this.preferredTime,
                        template_gap_notes: notes,
                    }),
                });
                const result = await res.json();
                if (res.ok) {
                    const waTarget = result.whatsapp_url || this.whatsappUrl;
                    window.open(waTarget, '_blank', 'noopener');
                    Swal.fire({
                        icon: 'success',
                        title: 'Konsultasi Tersimpan & Dialihkan ke WhatsApp!',
                        text: result.message,
                        confirmButtonText: 'Ke Halaman Akun',
                        confirmButtonColor: '#0D7A5F',
                    }).then(() => {
                        window.location.href = '/dashboard?tab=consultation';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: result.message || 'Periksa kembali isian Anda', confirmButtonColor: '#0D7A5F' });
                }
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endpush
