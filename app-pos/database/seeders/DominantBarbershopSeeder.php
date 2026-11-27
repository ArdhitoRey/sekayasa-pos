<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
use App\Models\ClientLead;
use App\Models\Consultation;
use App\Models\Customer;
use App\Models\DiscoverySession;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProjectRequirement;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DominantBarbershopSeeder extends Seeder
{
    /**
     * Seeder khusus rancangan proyek berikutnya: Usaha Dominant Barbershop.
     * Bersifat idempoten (aman dijalankan ulang dengan `--class=DominantBarbershopSeeder`).
     * Status pipeline ditempatkan pada `discovery` (proyek yang akan dibuat nanti, belum masuk portfolio live).
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Profil Usaha Dominant Barbershop
            $business = Business::updateOrCreate(
                ['email' => 'owner@dominantbarbershop.id'],
                [
                    'name' => 'Dominant Barbershop',
                    'legal_type' => 'Perorangan / UMKM',
                    'business_category' => 'Jasa / Barbershop',
                    'owner_name' => 'Owner Dominant Barbershop',
                    'phone' => '081255008899',
                    'address' => 'Surabaya',
                    'city' => 'Surabaya',
                    'province' => 'Jawa Timur',
                    'postal_code' => '60293',
                    'outlet_count_estimate' => '1 Outlet (Siap Ekspansi Cabang)',
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Jakarta',
                    'tax_percentage' => 0.00,
                    'receipt_footer' => 'Terima kasih telah mempercayakan gaya rambut Anda di Dominant Barbershop!',
                    'is_active' => true,
                    'service_type' => 'custom_pos',
                ]
            );

            // 2. Outlet Utama Dominant Barbershop
            $outletPusat = Outlet::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'outlet_code' => 'DOM-01',
                ],
                [
                    'name' => 'Dominant Barbershop — Flagship',
                    'address' => 'Surabaya, Jawa Timur',
                    'city' => 'Surabaya',
                    'phone' => '081255008899',
                    'is_active' => true,
                ]
            );

            // 3. Akun Pengelola & Kasir Frontdesk
            $owner = User::updateOrCreate(
                ['email' => 'owner@dominantbarbershop.id'],
                [
                    'business_id' => $business->id,
                    'outlet_id' => $outletPusat->id,
                    'name' => 'Owner Dominant Barbershop',
                    'phone' => '081255008899',
                    'nik' => '3578010101950001',
                    'job_title' => 'Pemilik Usaha',
                    'password' => Hash::make('password123'),
                    'role' => 'owner',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'kasir@dominantbarbershop.id'],
                [
                    'business_id' => $business->id,
                    'outlet_id' => $outletPusat->id,
                    'name' => 'Frontdesk Dominant',
                    'phone' => '081255008800',
                    'nik' => '3578010202980002',
                    'job_title' => 'Kasir Frontdesk',
                    'password' => Hash::make('password123'),
                    'role' => 'cashier',
                    'is_active' => true,
                ]
            );

            // 4. Pipeline Project yang Akan Dibuat Nanti (Status: Discovery, bukan Live Portfolio)
            $lead = ClientLead::updateOrCreate(
                ['email' => 'owner@dominantbarbershop.id'],
                [
                    'user_id' => $owner->id,
                    'business_id' => $business->id,
                    'client_name' => 'Owner Dominant Barbershop',
                    'business_name' => 'Dominant Barbershop',
                    'phone' => '081255008899',
                    'business_type' => 'Jasa / Barbershop',
                    'source' => 'Direct Project Pipeline',
                    'status' => 'discovery',
                    'preferred_consultation_at' => now()->addDays(2)->setTime(14, 0),
                    'template_gap_notes' => 'Butuh pemisahan layanan jasa cukur (non-stok) dengan produk retail (pomade/tonic), pilihan kapster per layanan untuk komisi otomatis, riwayat gaya rambut pelanggan, dan catat pengeluaran laci.',
                    'notes' => 'Project real POS berikutnya untuk Usaha Dominant Barbershop (belum dipublikasikan ke portfolio).',
                ]
            );

            Consultation::firstOrCreate(
                [
                    'lead_id' => $lead->id,
                    'pic_name' => 'Tim Analis SEKA GROUP Surabaya',
                ],
                [
                    'scheduled_at' => now()->addDays(2)->setTime(14, 0),
                    'consultation_notes' => 'Pemetaan alur antrean kursi barbershop, skema komisi/bagi hasil kapster per layanan, stok produk styling, dan kebutuhan struk kasir.',
                    'status' => 'scheduled',
                ]
            );

            DiscoverySession::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'as_is_summary' => 'Pencatatan transaksi harian, pembagian komisi kapster, dan stok produk styling masih dicatat terpisah.',
                    'to_be_summary' => 'Aplikasi POS Barbershop terpadu: checkout layanan + produk retail dalam satu nota, pilih kapster di setiap layanan jasa, rekap komisi otomatis, serta kontrol shift & pengeluaran laci.',
                    'business_flow_notes' => 'Layanan Jasa (non-stok) wajib memilih Kapster; Produk Fisik (Pomade/Clay/Tonic) memotong stok inventori outlet; Tutup shift menghitung kas penjualan dikurangi pengeluaran operasional harian.',
                    'target_hardware' => 'Tablet/PC Kasir Frontdesk + Printer Thermal Bluetooth/USB 80mm',
                    'budget_estimation' => 0,
                ]
            );

            $requirements = [
                [
                    'req_code' => 'REQ-DOM-001',
                    'title' => 'Pemisahan Jasa Barbershop (Non-Stok) & Produk Retail (Stok)',
                    'description' => 'Layanan seperti Gentleman Haircut dan Beard Trim tidak memotong inventori, sedangkan penjualan Pomade, Matte Clay, dan Hair Tonic tetap memotong stok outlet dengan Row Lock.',
                    'priority' => 'critical',
                    'category' => 'inventory',
                ],
                [
                    'req_code' => 'REQ-DOM-002',
                    'title' => 'Penugasan Kapster & Kalkulasi Komisi Otomatis per Item Nota',
                    'description' => 'Kasir memilih nama kapster pada setiap item layanan yang dikerjakan agar rekap komisi/bagi hasil kapster harian dan bulanan terbentuk otomatis.',
                    'priority' => 'critical',
                    'category' => 'staff',
                ],
                [
                    'req_code' => 'REQ-DOM-003',
                    'title' => 'Catatan Gaya Rambut Pelanggan & Pengeluaran Operasional Laci',
                    'description' => 'Menyimpan histori potongan pelanggan (fade/layer/preferensi) dan mencatat kas keluar operasional harian dalam shift aktif kasir.',
                    'priority' => 'high',
                    'category' => 'sales',
                ],
            ];

            foreach ($requirements as $req) {
                ProjectRequirement::updateOrCreate(
                    [
                        'lead_id' => $lead->id,
                        'req_code' => $req['req_code'],
                    ],
                    [
                        'title' => $req['title'],
                        'description' => $req['description'],
                        'priority' => $req['priority'],
                        'category' => $req['category'],
                        'is_approved' => false,
                    ]
                );
            }

            // 5. Satuan (Units) & Kategori Khusus Barbershop
            $uSesi = Unit::firstOrCreate(
                ['business_id' => $business->id, 'symbol' => 'Sesi'],
                ['name' => 'Sesi Layanan']
            );
            $uPcs = Unit::firstOrCreate(
                ['business_id' => $business->id, 'symbol' => 'Pcs'],
                ['name' => 'Pieces']
            );
            $uBotol = Unit::firstOrCreate(
                ['business_id' => $business->id, 'symbol' => 'Btl'],
                ['name' => 'Botol']
            );

            $catCukur = Category::firstOrCreate(
                ['business_id' => $business->id, 'slug' => 'layanan-cukur-grooming'],
                [
                    'name' => 'Layanan Cukur & Grooming',
                    'description' => 'Layanan potong rambut pria, anak, dan perapihan kumis/jenggot.',
                ]
            );
            $catTreatment = Category::firstOrCreate(
                ['business_id' => $business->id, 'slug' => 'perawatan-treatment'],
                [
                    'name' => 'Perawatan & Treatment',
                    'description' => 'Creambath, pijat kepala relaksasi, dan pewarnaan rambut.',
                ]
            );
            $catProduk = Category::firstOrCreate(
                ['business_id' => $business->id, 'slug' => 'produk-hair-styling'],
                [
                    'name' => 'Produk Hair Styling',
                    'description' => 'Produk ritel styling rambut (pomade, matte clay, hair tonic).',
                ]
            );

            // 6. Katalog Layanan Jasa & Produk Fisik Dominant Barbershop
            $items = [
                // Jasa Cukur & Grooming
                [
                    'category_id' => $catCukur->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Gentleman Haircut + Hair Wash',
                    'sku' => 'DOM-SVC-001',
                    'barcode' => 'DOMSVC001',
                    'buy_price' => 0,
                    'sell_price' => 50000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],
                [
                    'category_id' => $catCukur->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Kids Haircut',
                    'sku' => 'DOM-SVC-002',
                    'barcode' => 'DOMSVC002',
                    'buy_price' => 0,
                    'sell_price' => 40000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],
                [
                    'category_id' => $catCukur->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Beard Trim & Hot Towel Shaving',
                    'sku' => 'DOM-SVC-003',
                    'barcode' => 'DOMSVC003',
                    'buy_price' => 0,
                    'sell_price' => 30000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],
                [
                    'category_id' => $catCukur->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Full Dominant Package (Haircut + Beard + Tonic)',
                    'sku' => 'DOM-SVC-004',
                    'barcode' => 'DOMSVC004',
                    'buy_price' => 0,
                    'sell_price' => 75000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],

                // Treatment
                [
                    'category_id' => $catTreatment->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Creambath & Scalp Massage',
                    'sku' => 'DOM-TRT-001',
                    'barcode' => 'DOMTRT001',
                    'buy_price' => 10000,
                    'sell_price' => 65000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],
                [
                    'category_id' => $catTreatment->id,
                    'unit_id' => $uSesi->id,
                    'name' => 'Hair Coloring Natural Black',
                    'sku' => 'DOM-TRT-002',
                    'barcode' => 'DOMTRT002',
                    'buy_price' => 35000,
                    'sell_price' => 120000,
                    'min_stock_alert' => 0,
                    'initial_stock' => 9999,
                ],

                // Produk Fisik (Stok Nyata)
                [
                    'category_id' => $catProduk->id,
                    'unit_id' => $uPcs->id,
                    'name' => 'Dominant Matte Clay 100g',
                    'sku' => 'DOM-PRD-001',
                    'barcode' => '89977001001',
                    'buy_price' => 45000,
                    'sell_price' => 85000,
                    'min_stock_alert' => 5,
                    'initial_stock' => 24,
                ],
                [
                    'category_id' => $catProduk->id,
                    'unit_id' => $uPcs->id,
                    'name' => 'Dominant Waterbased Pomade 120g',
                    'sku' => 'DOM-PRD-002',
                    'barcode' => '89977001002',
                    'buy_price' => 40000,
                    'sell_price' => 75000,
                    'min_stock_alert' => 5,
                    'initial_stock' => 30,
                ],
                [
                    'category_id' => $catProduk->id,
                    'unit_id' => $uBotol->id,
                    'name' => 'Cooling Hair Tonic 150ml',
                    'sku' => 'DOM-PRD-003',
                    'barcode' => '89977001003',
                    'buy_price' => 32000,
                    'sell_price' => 65000,
                    'min_stock_alert' => 5,
                    'initial_stock' => 18,
                ],
            ];

            foreach ($items as $item) {
                $defaultImage = str_contains($item['sku'], '-PRD-')
                    ? '/assets/products/pomade.svg'
                    : '/assets/products/haircut.svg';

                $product = Product::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'sku' => $item['sku'],
                    ],
                    [
                        'category_id' => $item['category_id'],
                        'unit_id' => $item['unit_id'],
                        'name' => $item['name'],
                        'barcode' => $item['barcode'],
                        'buy_price' => $item['buy_price'],
                        'sell_price' => $item['sell_price'],
                        'min_stock_alert' => $item['min_stock_alert'],
                        'image_path' => $defaultImage,
                        'is_active' => true,
                    ]
                );

                $inventory = Inventory::firstOrCreate(
                    [
                        'outlet_id' => $outletPusat->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'business_id' => $business->id,
                        'stock_quantity' => $item['initial_stock'],
                    ]
                );

                if ($inventory->wasRecentlyCreated) {
                    StockMovement::create([
                        'business_id' => $business->id,
                        'outlet_id' => $outletPusat->id,
                        'product_id' => $product->id,
                        'type' => 'adjustment_add',
                        'quantity_before' => 0,
                        'quantity_change' => $item['initial_stock'],
                        'quantity_after' => $item['initial_stock'],
                        'reason' => 'Stok Awal Dominant Barbershop',
                        'user_id' => $owner->id,
                    ]);
                }
            }

            // 7. Pelanggan Barbershop (dengan Catatan Gaya Rambut) & Supplier Produk
            Customer::firstOrCreate(
                ['business_id' => $business->id, 'name' => 'Pelanggan Umum (Walk-in)'],
                ['phone' => '-', 'notes' => 'Tamu walk-in tanpa keanggotaan']
            );

            Customer::firstOrCreate(
                ['business_id' => $business->id, 'phone' => '081230004001'],
                [
                    'name' => 'Raka Aditya',
                    'address' => 'Surabaya Timur',
                    'notes' => 'Preferensi cukur: Mid fade 2 jari, bagian atas layer tipis, pakai Matte Clay.',
                ]
            );

            Customer::firstOrCreate(
                ['business_id' => $business->id, 'phone' => '081230004002'],
                [
                    'name' => 'Bagas Permana',
                    'address' => 'Surabaya Barat',
                    'notes' => 'Preferensi cukur: Neat undercut + beard trim, sensitif terhadap pewangi tajam.',
                ]
            );

            Supplier::firstOrCreate(
                ['business_id' => $business->id, 'name' => 'CV Grooming Supply Nusantara'],
                [
                    'contact_name' => 'Reza Mahendra',
                    'phone' => '082144556677',
                    'email' => 'order@groomingsupply.id',
                    'address' => 'Surabaya, Jawa Timur',
                ]
            );
        });
    }
}
