@extends('layouts.app')

@section('title', 'Masuk Sistem — Seka Yasa POS')

@section('content')
<div class="min-h-screen bg-[#1A1208] text-[#F7F3EC] flex flex-col justify-between p-6" x-data="authPage()">

    <!-- Top Back Link -->
    <div class="max-w-5xl mx-auto w-full flex items-center justify-between py-2">
        <a href="/" class="text-xs font-semibold text-[#C4B5A2] hover:text-[#F7B05F] transition flex items-center gap-2">
            ← Kembali ke Beranda Seka Yasa
        </a>
        <a href="/contact" class="text-xs font-semibold text-[#F7B05F] hover:underline">
            Konsultasi Kebutuhan →
        </a>
    </div>

    <!-- Centered Editorial Card -->
    <div class="max-w-md w-full mx-auto bg-[#FAF7F2] text-[#1A1208] rounded-lg border border-[rgba(247,176,95,0.28)] overflow-hidden my-8">
        <!-- Header -->
        <div class="bg-[#23190D] px-8 py-7 text-[#F7F3EC] border-b border-[rgba(247,176,95,0.18)]">
            <div class="text-xs font-semibold tracking-wider text-[#F7B05F] mb-1">00 / Akses Operasional</div>
            <h1 class="text-2xl font-bold tracking-tight">Seka Yasa POS</h1>
            <p class="text-[#C4B5A2] text-xs mt-1">Masuk sebagai Kasir, Admin Toko, Owner, atau Akun Klien</p>
        </div>

        <div class="p-8">
            <!-- Already Logged In Panel -->
            <div x-show="alreadyLoggedIn" class="mb-6 p-4 bg-[#1A1208] text-[#F7F3EC] rounded-md border border-[rgba(247,176,95,0.4)] space-y-3" x-cloak>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-[#F7B05F] text-[#1A1208] rounded">Sedang Login</span>
                    <span class="text-[11px] font-mono text-[#C4B5A2]" x-text="currentUser.role ? currentUser.role.toUpperCase() : ''"></span>
                </div>
                <div>
                    <div class="text-sm font-bold" x-text="currentUser.name"></div>
                    <div class="text-xs text-[#C4B5A2]" x-text="currentUser.email + (currentUser.business ? ' • ' + currentUser.business.name : '')"></div>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <a :href="redirectTarget || '/client'" class="flex-1 py-2 px-3 bg-[#F7B05F] hover:bg-[#E89E4A] text-[#1A1208] rounded text-xs font-bold text-center transition">
                        Lanjutkan Sesi →
                    </a>
                    <button type="button" @click="logoutCurrent()" class="py-2 px-3 bg-[#23190D] border border-[rgba(247,176,95,0.3)] text-[#C4B5A2] hover:text-rose-300 rounded text-xs font-semibold transition">
                        Keluar / Ganti Akun
                    </button>
                </div>
            </div>

            <!-- Tabs: Login vs Register -->
            <div class="flex border-b border-[rgba(119,82,41,0.18)] mb-6">
                <button @click="tab = 'login'" :class="tab === 'login' ? 'border-[#775229] text-[#1A1208] font-bold' : 'border-transparent text-[#524538] hover:text-[#1A1208]'" class="flex-1 py-2 text-center border-b-2 text-sm transition">
                    Masuk Akun
                </button>
                <button @click="tab = 'register'" :class="tab === 'register' ? 'border-[#775229] text-[#1A1208] font-bold' : 'border-transparent text-[#524538] hover:text-[#1A1208]'" class="flex-1 py-2 text-center border-b-2 text-sm transition">
                    Daftar Bisnis Baru
                </button>
            </div>

            <!-- Login Form -->
            <form x-show="tab === 'login'" @submit.prevent="submitLogin()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Email</label>
                    <input type="email" x-model="loginData.email" required class="w-full px-3.5 py-2.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm text-[#1A1208] focus:outline-none focus:border-[#775229]" placeholder="nama@toko.com">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Password</label>
                    <input type="password" x-model="loginData.password" required class="w-full px-3.5 py-2.5 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm text-[#1A1208] focus:outline-none focus:border-[#775229]" placeholder="••••••••">
                </div>

                <button type="submit" :disabled="loading" class="w-full py-3 bg-[#775229] hover:bg-[#583B1B] text-[#FAF7F2] font-bold rounded-md text-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!loading">Masuk ke Sistem</span>
                    <span x-show="loading" class="animate-spin rounded-full h-4 w-4 border-2 border-[#FAF7F2] border-t-transparent"></span>
                </button>
            </form>

            <!-- Register Business Form -->
            <form x-show="tab === 'register'" @submit.prevent="submitRegister()" class="space-y-3" x-cloak>
                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Nama Bisnis / Toko</label>
                    <input type="text" x-model="regData.business_name" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm focus:outline-none focus:border-[#775229]" placeholder="Contoh: Kopi Nusantara">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Nama Pemilik (Owner)</label>
                    <input type="text" x-model="regData.owner_name" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm focus:outline-none focus:border-[#775229]" placeholder="Contoh: Budi Hartono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Email Bisnis</label>
                    <input type="email" x-model="regData.email" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm focus:outline-none focus:border-[#775229]" placeholder="owner@kopinusantara.id">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#524538] uppercase mb-1">Password</label>
                    <input type="password" x-model="regData.password" required class="w-full px-3 py-2 bg-[#F3EDE4] border border-[rgba(119,82,41,0.25)] rounded text-sm focus:outline-none focus:border-[#775229]" placeholder="Minimal 6 karakter">
                </div>

                <button type="submit" :disabled="loading" class="w-full mt-2 py-3 bg-[#1A1208] hover:bg-[#23190D] text-[#F7B05F] font-bold rounded-md text-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!loading">Daftar Bisnis Baru</span>
                    <span x-show="loading" class="animate-spin rounded-full h-4 w-4 border-2 border-[#F7B05F] border-t-transparent"></span>
                </button>
            </form>

            <!-- Quick Demo Access -->
            <div class="mt-8 pt-6 border-t border-[rgba(119,82,41,0.18)]">
                <p class="text-[11px] font-semibold text-[#775229] uppercase tracking-wider mb-3">Akses Cepat Demo Operasional</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="quickLogin('kasir@kopi.id', 'password123', '/pos')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Kasir POS</div>
                        <div class="text-[10px] text-[#524538]">Layar Transaksi</div>
                    </button>
                    <button type="button" @click="quickLogin('owner@kopi.id', 'password123', '/dashboard')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Owner Bisnis</div>
                        <div class="text-[10px] text-[#524538]">Dashboard & Laba</div>
                    </button>
                    <button type="button" @click="quickLogin('admin@kopi.id', 'password123', '/dashboard')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Admin Toko</div>
                        <div class="text-[10px] text-[#524538]">Produk & Inventori</div>
                    </button>
                    <button type="button" @click="quickLogin('superadmin@pos.id', 'password123', '/dashboard')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Super Admin</div>
                        <div class="text-[10px] text-[#524538]">Platform Admin</div>
                    </button>
                    <button type="button" @click="quickLogin('klien@usaha.id', 'password123', '/client')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Akun Klien Baru</div>
                        <div class="text-[10px] text-[#524538]">Sewa / Konsultasi</div>
                    </button>
                    <button type="button" @click="quickLogin('michael@kopisenopati.com', 'password123', '/client')" class="px-3 py-2.5 bg-[#F2EBE1] hover:bg-[#F3EDE4] border border-[rgba(119,82,41,0.22)] rounded text-left transition">
                        <div class="text-xs font-bold text-[#1A1208]">Klien Proyek Custom</div>
                        <div class="text-[10px] text-[#524538]">Lacak UAT & Status</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center text-xs text-[#C4B5A2]/70 py-2">
        Seka Yasa POS Custom Studio • Warm Javanese Modern Architecture
    </div>
</div>
@endsection

@push('scripts')
<script>
function authPage() {
    const params = new URLSearchParams(window.location.search);
    return {
        tab: params.get('tab') === 'register' ? 'register' : 'login',
        redirectTarget: params.get('redirect') || '',
        existingToken: localStorage.getItem('pos_token') || '',
        currentUser: JSON.parse(localStorage.getItem('pos_user') || '{}'),
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
        loginData: { email: 'klien@usaha.id', password: 'password123' },
        regData: { business_name: '', owner_name: '', email: '', password: '' },
        async quickLogin(email, password, redirectUrl) {
            this.loginData.email = email;
            this.loginData.password = password;
            await this.submitLogin(redirectUrl);
        },
        async submitLogin(redirectUrl = null) {
            this.loading = true;
            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.loginData),
                });
                const result = await res.json();
                if (res.ok) {
                    localStorage.setItem('pos_token', result.data.token);
                    localStorage.setItem('pos_user', JSON.stringify(result.data.user));
                    const biz = result.data.user.business;
                    let defaultTarget = '/client';
                    if (result.data.user.role === 'cashier') {
                        defaultTarget = '/pos';
                    } else if (biz && biz.service_type === 'template_saas') {
                        defaultTarget = '/dashboard';
                    }
                    let target = redirectUrl || this.redirectTarget || defaultTarget;
                    window.location.href = target;
                } else {
                    Swal.fire({ icon: 'error', title: 'Login Gagal', text: result.message || 'Periksa email dan password', confirmButtonColor: '#775229' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#775229' });
            } finally {
                this.loading = false;
            }
        },
        async submitRegister() {
            this.loading = true;
            try {
                const res = await fetch('/api/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.regData),
                });
                const result = await res.json();
                if (res.ok) {
                    localStorage.setItem('pos_token', result.data.token);
                    localStorage.setItem('pos_user', JSON.stringify(result.data.user));
                    Swal.fire({
                        icon: 'success',
                        title: 'Akun Berhasil Dibuat',
                        text: 'Silakan pilih sewa layanan template POS atau ajukan konsultasi custom di halaman akun Anda.',
                        confirmButtonColor: '#775229',
                    }).then(() => {
                        window.location.href = this.redirectTarget || '/client';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Registrasi Gagal', text: result.message || 'Data tidak valid', confirmButtonColor: '#775229' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.message, confirmButtonColor: '#775229' });
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
