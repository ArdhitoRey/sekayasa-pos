<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ClientLead;
use App\Models\Consultation;
use App\Models\DeploymentRun;
use App\Models\DeploymentTarget;
use App\Models\DiscoverySession;
use App\Models\Outlet;
use App\Models\ProjectContract;
use App\Models\ProjectRequirement;
use App\Models\Proposal;
use App\Models\ServiceSubscription;
use App\Models\UatTestCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Demo Client User 0: Fresh Client Account (Belum memilih sewa template atau konsultasi custom)
        $freshBiz = Business::create([
            'name' => 'Warung Maju Jaya',
            'owner_name' => 'Rizky Pratama',
            'email' => 'klien@usaha.id',
            'phone' => '081234561234',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11.00,
            'is_active' => true,
            'service_type' => 'none',
        ]);
        $freshOutlet = Outlet::create([
            'business_id' => $freshBiz->id,
            'name' => 'Outlet Utama',
            'address' => 'Yogyakarta',
            'is_active' => true,
        ]);
        User::create([
            'business_id' => $freshBiz->id,
            'outlet_id' => $freshOutlet->id,
            'name' => 'Rizky Pratama',
            'email' => 'klien@usaha.id',
            'phone' => '081234561234',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Demo Client User 1: Kopi Senopati Artisan (Linked to Custom POS Project)
        $senopatiBiz = Business::create([
            'name' => 'Kopi Senopati Artisan',
            'owner_name' => 'Michael Chandra',
            'email' => 'michael@kopisenopati.com',
            'phone' => '081198765432',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11.00,
            'is_active' => true,
            'service_type' => 'custom_pos',
        ]);
        $senopatiOutlet = Outlet::create([
            'business_id' => $senopatiBiz->id,
            'name' => 'Senopati Flagship',
            'address' => 'Jakarta Selatan',
            'is_active' => true,
        ]);
        $senopatiUser = User::create([
            'business_id' => $senopatiBiz->id,
            'outlet_id' => $senopatiOutlet->id,
            'name' => 'Michael Chandra',
            'email' => 'michael@kopisenopati.com',
            'phone' => '081198765432',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // -------------------------------------------------------------
        // CLIENT 1: Kopi Senopati Artisan (Status: Ready to Deploy / Live)
        // -------------------------------------------------------------
        $client1 = ClientLead::create([
            'user_id' => $senopatiUser->id,
            'business_id' => $senopatiBiz->id,
            'client_name' => 'Michael Chandra',
            'business_name' => 'Kopi Senopati Artisan',
            'email' => 'michael@kopisenopati.com',
            'phone' => '081198765432',
            'business_type' => 'F&B (Coffee Shop Specialty & Roastery)',
            'source' => 'Portal Akun Klien',
            'status' => 'ready_to_deploy',
            'notes' => 'Membutuhkan custom POS tablet Android dengan integrasi kitchen display & auto recipe deduction.',
            'template_gap_notes' => 'Template standar belum memiliki pemotongan otomatis resep gramatur susu/biji kopi dan split bill tunai+QRIS.',
            'preferred_consultation_at' => now()->subDays(25),
        ]);

        Consultation::create([
            'lead_id' => $client1->id,
            'scheduled_at' => now()->subDays(25),
            'pic_name' => 'Michael Chandra (Owner)',
            'consultation_notes' => 'Klien mengeluhkan sistem lama sering selisih stok susu dan biji kopi. Butuh resep otomatis per cup.',
            'status' => 'completed',
        ]);

        DiscoverySession::create([
            'lead_id' => $client1->id,
            'as_is_summary' => 'Pencatatan kasir menggunakan aplikasi kasir generic offline, stok biji dihitung manual di excel tiap malam.',
            'to_be_summary' => 'POS web & mobile khusus barista, terhubung ke kitchen display dan auto-deduct gramatur susu + espresso bean.',
            'business_flow_notes' => 'Kasir input order -> Barista monitor order -> Stok gramatur berkurang realtime -> Struk cetak thermal 80mm.',
            'target_hardware' => 'Tablet Android 10 inch, Printer Thermal Bluetooth 80mm ESC/POS, Kitchen Tablet 10 inch.',
            'budget_estimation' => 35000000,
        ]);

        $req1 = ProjectRequirement::create([
            'lead_id' => $client1->id,
            'req_code' => 'REQ-KS-001',
            'title' => 'Custom Recipe Auto-Deduct',
            'description' => 'Setiap 1 cup Latte otomatis mengurangi 18g biji kopi gayo dan 200ml fresh milk.',
            'priority' => 'critical',
            'category' => 'inventory',
            'is_approved' => true,
        ]);

        $req2 = ProjectRequirement::create([
            'lead_id' => $client1->id,
            'req_code' => 'REQ-KS-002',
            'title' => 'Split Bill & Custom Payment Method',
            'description' => 'Mendukung pembayaran split antara Tunai dan QRIS dalam 1 nomor transaksi.',
            'priority' => 'high',
            'category' => 'sales',
            'is_approved' => true,
        ]);

        $req3 = ProjectRequirement::create([
            'lead_id' => $client1->id,
            'req_code' => 'REQ-KS-003',
            'title' => 'Cashier Shift Variance Enforcement',
            'description' => 'Kasir wajib menghitung uang fisik laci sebelum tutup shift, selisih wajib dilaporkan.',
            'priority' => 'high',
            'category' => 'staff',
            'is_approved' => true,
        ]);

        $proposal1 = Proposal::create([
            'lead_id' => $client1->id,
            'proposal_number' => 'PROP-2026-001-KS',
            'version' => 1,
            'development_cost' => 35000000,
            'monthly_maintenance_cost' => 1500000,
            'deliverables' => 'Aplikasi POS Custom, Integrasi Tablet Dapur, Setup VPS Ubuntu, Training Staf, SLA Support 12 Jam.',
            'timeline_weeks' => 4,
            'status' => 'approved',
        ]);

        ProjectContract::create([
            'lead_id' => $client1->id,
            'proposal_id' => $proposal1->id,
            'contract_number' => 'AGR-DEV-2026-001',
            'type' => 'development',
            'total_amount' => 35000000,
            'is_signed' => true,
            'signed_at' => now()->subDays(20),
            'payment_status' => 'verified_100_percent',
            'payment_verified_at' => now()->subDays(19),
            'payment_reference' => 'BCA-TRF-98237192837 (LUNAS 100%)',
        ]);

        ProjectContract::create([
            'lead_id' => $client1->id,
            'proposal_id' => $proposal1->id,
            'contract_number' => 'AGR-SVC-2026-001',
            'type' => 'monthly_service',
            'total_amount' => 1500000,
            'is_signed' => true,
            'signed_at' => now()->subDays(2),
            'payment_status' => 'verified_100_percent',
            'payment_verified_at' => now()->subDays(1),
            'payment_reference' => 'BCA-RECURRING-001',
        ]);

        // UAT Cases (All Pass)
        UatTestCase::create([
            'lead_id' => $client1->id,
            'requirement_id' => $req1->id,
            'test_title' => 'Pengurangan stok resep otomatis',
            'scenario' => 'Kasir memesan 2 cup Latte, periksa sisa stok susu dan kopi di backend.',
            'expected_result' => 'Stok susu berkurang 400ml dan biji kopi berkurang 36g secara akurat.',
            'status' => 'pass',
            'client_feedback' => 'Pengurangan akurat dan realtime.',
            'tested_at' => now()->subDays(3),
        ]);

        UatTestCase::create([
            'lead_id' => $client1->id,
            'requirement_id' => $req2->id,
            'test_title' => 'Split Bill Tunai + QRIS',
            'scenario' => 'Total tagihan Rp 100.000, bayar Rp 50.000 tunai dan Rp 50.000 QRIS.',
            'expected_result' => 'Struk mencetak rincian kedua pembayaran dan status lunas.',
            'status' => 'pass',
            'client_feedback' => 'Sesuai ekspektasi kasir.',
            'tested_at' => now()->subDays(3),
        ]);

        // Targets
        $targetStaging1 = DeploymentTarget::create([
            'lead_id' => $client1->id,
            'environment' => 'staging',
            'provider' => 'render_preview',
            'host_url' => 'https://pos-staging-kopisenopati.onrender.com',
            'git_repo_url' => 'git@github.com:client-projects/pos-kopi-senopati.git',
            'git_branch' => 'staging',
            'has_backup_schedule' => false,
            'tls_ssl_active' => true,
            'is_active' => true,
        ]);

        $targetProd1 = DeploymentTarget::create([
            'lead_id' => $client1->id,
            'environment' => 'production',
            'provider' => 'vps_dedicated',
            'host_url' => 'https://pos.kopisenopati.com',
            'git_repo_url' => 'git@github.com:client-projects/pos-kopi-senopati.git',
            'git_branch' => 'main',
            'has_backup_schedule' => true,
            'tls_ssl_active' => true,
            'is_active' => true,
        ]);

        DeploymentRun::create([
            'lead_id' => $client1->id,
            'target_id' => $targetStaging1->id,
            'environment' => 'staging',
            'release_version' => 'v1.0.0-rc1',
            'commit_sha' => 'c89fa12',
            'operator_name' => 'DevOps Engineer',
            'status' => 'successful',
            'log_output' => "[STAGING PIPELINE]\n✓ Checkout code c89fa12\n✓ Composer install --no-dev\n✓ Run PHPUnit tests (14 passed)\n✓ Database migrated\n✓ Health check HTTP 200 OK\nDeployment Completed Successfully in 38s.",
            'backup_reference' => 'STG-BAK-20261118-01',
            'started_at' => now()->subDays(4),
            'completed_at' => now()->subDays(4)->addMinutes(1),
        ]);

        // -------------------------------------------------------------
        // CLIENT 2: Butik Gaya Nusantara (Status: In Development)
        // -------------------------------------------------------------
        $client2 = ClientLead::create([
            'client_name' => 'Dewi Anggraini',
            'business_name' => 'Butik Gaya Nusantara',
            'email' => 'dewi@gayanusantara.id',
            'phone' => '081233445566',
            'business_type' => 'Retail (Fashion & Batik Modern)',
            'source' => 'Website Inquiry',
            'status' => 'in_dev',
            'notes' => 'Membutuhkan POS barcode dengan variasi Ukuran (S, M, L, XL) dan Warna.',
        ]);

        $proposal2 = Proposal::create([
            'lead_id' => $client2->id,
            'proposal_number' => 'PROP-2026-002-BGN',
            'version' => 1,
            'development_cost' => 28000000,
            'monthly_maintenance_cost' => 1200000,
            'deliverables' => 'Aplikasi POS Barcode Multi-Varian, Integrasi Scanner USB, Setup Cloud VPS, Garansi 3 Bulan.',
            'timeline_weeks' => 3,
            'status' => 'approved',
        ]);

        ProjectContract::create([
            'lead_id' => $client2->id,
            'proposal_id' => $proposal2->id,
            'contract_number' => 'AGR-DEV-2026-002',
            'type' => 'development',
            'total_amount' => 28000000,
            'is_signed' => true,
            'signed_at' => now()->subDays(7),
            'payment_status' => 'verified_100_percent',
            'payment_verified_at' => now()->subDays(6),
            'payment_reference' => 'MANDIRI-TRF-882736192 (LUNAS 100%)',
        ]);

        // -------------------------------------------------------------
        // CLIENT 3: Apotek Medika Sehat (Status: Payment Pending - LOCKED)
        // -------------------------------------------------------------
        $client3 = ClientLead::create([
            'client_name' => 'Dr. Hendra Gunawan',
            'business_name' => 'Apotek Medika Sehat',
            'email' => 'hendra@medikasehat.com',
            'phone' => '081377889900',
            'business_type' => 'Healthcare / Pharmacy',
            'source' => 'Instagram Ads',
            'status' => 'payment_pending',
            'notes' => 'Scope disetujui, invoice sudah dikirim. Menunggu verifikasi pembayaran 100% sebelum coding dimulai.',
        ]);

        $proposal3 = Proposal::create([
            'lead_id' => $client3->id,
            'proposal_number' => 'PROP-2026-003-AMS',
            'version' => 1,
            'development_cost' => 42000000,
            'monthly_maintenance_cost' => 1800000,
            'deliverables' => 'Aplikasi POS Farmasi, Batch Number & Expiry Date Alert, Resep Dokter Digital, Backup Harian.',
            'timeline_weeks' => 5,
            'status' => 'approved',
        ]);

        ProjectContract::create([
            'lead_id' => $client3->id,
            'proposal_id' => $proposal3->id,
            'contract_number' => 'AGR-DEV-2026-003',
            'type' => 'development',
            'total_amount' => 42000000,
            'is_signed' => true,
            'signed_at' => now()->subDays(2),
            'payment_status' => 'unpaid', // Masih belum bayar! (Payment Gate Lock)
        ]);
    }
}
