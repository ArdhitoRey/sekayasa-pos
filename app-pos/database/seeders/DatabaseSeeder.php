<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Subscription Plans
        $planStarter = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 99000,
            'max_outlets' => 1,
            'max_users' => 2,
            'max_products' => 150,
            'features' => ['pos_mobile', 'basic_inventory', 'daily_report'],
            'is_active' => true,
        ]);

        $planBusiness = Plan::create([
            'name' => 'Business',
            'slug' => 'business',
            'price_monthly' => 199000,
            'max_outlets' => 3,
            'max_users' => 6,
            'max_products' => 1000,
            'features' => ['pos_mobile', 'multi_outlet', 'shift_management', 'profit_report', 'stock_transfer'],
            'is_active' => true,
        ]);

        $planPro = Plan::create([
            'name' => 'Pro Enterprise',
            'slug' => 'pro',
            'price_monthly' => 399000,
            'max_outlets' => 10,
            'max_users' => 25,
            'max_products' => 10000,
            'features' => ['all_features', 'api_access', 'priority_support'],
            'is_active' => true,
        ]);

        // 2. Superadmin
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@pos.id',
            'phone' => '081100000000',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // 3. Demo Business
        $business = Business::create([
            'name' => 'Kopi & Mart Barista',
            'owner_name' => 'Budi Santoso',
            'email' => 'owner@kopi.id',
            'phone' => '081298765432',
            'address' => 'Jl. Senopati No. 45, Jakarta Selatan',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11.00,
            'receipt_footer' => 'Terima kasih atas kunjungan Anda! Silakan berkunjung kembali.',
            'is_active' => true,
            'service_type' => 'template_saas',
        ]);

        // 4. Subscription for Business
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $planBusiness->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 5. Outlets
        $outletPusat = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Pusat - Senopati',
            'address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
            'phone' => '021-7201234',
            'is_active' => true,
        ]);

        $outletCabang = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Cabang - Dago Bandung',
            'address' => 'Jl. Ir. H. Juanda No. 120, Dago, Bandung',
            'phone' => '022-2505678',
            'is_active' => true,
        ]);

        // 6. Users for Demo Business
        // Owner
        $owner = User::create([
            'business_id' => $business->id,
            'outlet_id' => $outletPusat->id,
            'name' => 'Budi Santoso',
            'email' => 'owner@kopi.id',
            'phone' => '081298765432',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Store Admin
        $admin = User::create([
            'business_id' => $business->id,
            'outlet_id' => $outletPusat->id,
            'name' => 'Siti Rahma',
            'email' => 'admin@kopi.id',
            'phone' => '081211112222',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Cashier 1 (Senopati)
        $cashier1 = User::create([
            'business_id' => $business->id,
            'outlet_id' => $outletPusat->id,
            'name' => 'Agus Pratama',
            'email' => 'kasir@kopi.id',
            'phone' => '081388889999',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // Cashier 2 (Dago)
        $cashier2 = User::create([
            'business_id' => $business->id,
            'outlet_id' => $outletCabang->id,
            'name' => 'Rina Melati',
            'email' => 'kasir2@kopi.id',
            'phone' => '081377778888',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // 7. Units
        $uCup = Unit::create(['business_id' => $business->id, 'name' => 'Cup Minuman', 'symbol' => 'Cup']);
        $uPcs = Unit::create(['business_id' => $business->id, 'name' => 'Pieces', 'symbol' => 'Pcs']);
        $uPorsi = Unit::create(['business_id' => $business->id, 'name' => 'Porsi', 'symbol' => 'Porsi']);
        $uPack = Unit::create(['business_id' => $business->id, 'name' => 'Pack', 'symbol' => 'Pack']);

        // 8. Categories
        $catCoffee = Category::create(['business_id' => $business->id, 'name' => 'Coffee & Espresso', 'slug' => 'coffee']);
        $catTea = Category::create(['business_id' => $business->id, 'name' => 'Tea & Refreshment', 'slug' => 'tea']);
        $catBakery = Category::create(['business_id' => $business->id, 'name' => 'Pastry & Bakery', 'slug' => 'bakery']);
        $catSnack = Category::create(['business_id' => $business->id, 'name' => 'Food & Snack', 'slug' => 'snack']);

        // 9. Products & Inventory
        $productsData = [
            [
                'category_id' => $catCoffee->id,
                'unit_id' => $uCup->id,
                'name' => 'Espresso Double Shot',
                'sku' => 'EXP-001',
                'barcode' => '8991001001',
                'buy_price' => 7000,
                'sell_price' => 18000,
                'min_stock_alert' => 10,
                'image_path' => '/assets/products/coffee.svg',
                'stock_pusat' => 120,
                'stock_cabang' => 80,
            ],
            [
                'category_id' => $catCoffee->id,
                'unit_id' => $uCup->id,
                'name' => 'Kopi Susu Gula Aren Signature',
                'sku' => 'KPS-002',
                'barcode' => '8991001002',
                'buy_price' => 9500,
                'sell_price' => 22000,
                'min_stock_alert' => 15,
                'image_path' => '/assets/products/coffee.svg',
                'stock_pusat' => 200,
                'stock_cabang' => 150,
            ],
            [
                'category_id' => $catCoffee->id,
                'unit_id' => $uCup->id,
                'name' => 'Caramel Macchiato Ice',
                'sku' => 'CMM-003',
                'barcode' => '8991001003',
                'buy_price' => 12500,
                'sell_price' => 28000,
                'min_stock_alert' => 10,
                'image_path' => '/assets/products/latte.svg',
                'stock_pusat' => 90,
                'stock_cabang' => 60,
            ],
            [
                'category_id' => $catTea->id,
                'unit_id' => $uCup->id,
                'name' => 'Japanese Matcha Latte',
                'sku' => 'MTC-004',
                'barcode' => '8991001004',
                'buy_price' => 11000,
                'sell_price' => 25000,
                'min_stock_alert' => 8,
                'image_path' => '/assets/products/latte.svg',
                'stock_pusat' => 85,
                'stock_cabang' => 50,
            ],
            [
                'category_id' => $catTea->id,
                'unit_id' => $uCup->id,
                'name' => 'Earl Grey Lemon Iced Tea',
                'sku' => 'EGT-005',
                'barcode' => '8991001005',
                'buy_price' => 5500,
                'sell_price' => 16000,
                'min_stock_alert' => 10,
                'image_path' => '/assets/products/latte.svg',
                'stock_pusat' => 110,
                'stock_cabang' => 70,
            ],
            [
                'category_id' => $catBakery->id,
                'unit_id' => $uPcs->id,
                'name' => 'French Croissant Butter',
                'sku' => 'CRS-006',
                'barcode' => '8991001006',
                'buy_price' => 13500,
                'sell_price' => 24000,
                'min_stock_alert' => 5,
                'image_path' => '/assets/products/pastry.svg',
                'stock_pusat' => 35,
                'stock_cabang' => 20,
            ],
            [
                'category_id' => $catBakery->id,
                'unit_id' => $uPcs->id,
                'name' => 'Pain Au Chocolat',
                'sku' => 'PAC-007',
                'barcode' => '8991001007',
                'buy_price' => 15000,
                'sell_price' => 27000,
                'min_stock_alert' => 5,
                'image_path' => '/assets/products/pastry.svg',
                'stock_pusat' => 30,
                'stock_cabang' => 18,
            ],
            [
                'category_id' => $catSnack->id,
                'unit_id' => $uPorsi->id,
                'name' => 'Crispy Truffle Fries',
                'sku' => 'FRF-008',
                'barcode' => '8991001008',
                'buy_price' => 10000,
                'sell_price' => 23000,
                'min_stock_alert' => 10,
                'image_path' => '/assets/products/pastry.svg',
                'stock_pusat' => 50,
                'stock_cabang' => 30,
            ],
            [
                'category_id' => $catCoffee->id,
                'unit_id' => $uPack->id,
                'name' => 'Arabica Gayo Whole Beans 250g',
                'sku' => 'BKG-009',
                'barcode' => '8991001009',
                'buy_price' => 55000,
                'sell_price' => 85000,
                'min_stock_alert' => 5,
                'image_path' => '/assets/products/beans.svg',
                'stock_pusat' => 25,
                'stock_cabang' => 15,
            ],
        ];

        foreach ($productsData as $data) {
            $product = Product::create([
                'business_id' => $business->id,
                'category_id' => $data['category_id'],
                'unit_id' => $data['unit_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'barcode' => $data['barcode'],
                'buy_price' => $data['buy_price'],
                'sell_price' => $data['sell_price'],
                'min_stock_alert' => $data['min_stock_alert'],
                'image_path' => $data['image_path'] ?? '/assets/products/default-product.svg',
                'is_active' => true,
            ]);

            // Stock for Outlet Pusat
            Inventory::create([
                'business_id' => $business->id,
                'outlet_id' => $outletPusat->id,
                'product_id' => $product->id,
                'stock_quantity' => $data['stock_pusat'],
            ]);

            StockMovement::create([
                'business_id' => $business->id,
                'outlet_id' => $outletPusat->id,
                'product_id' => $product->id,
                'type' => 'adjustment_add',
                'quantity_before' => 0,
                'quantity_change' => $data['stock_pusat'],
                'quantity_after' => $data['stock_pusat'],
                'reason' => 'Stok Awal Sistem POS',
                'user_id' => $owner->id,
            ]);

            // Stock for Outlet Cabang
            Inventory::create([
                'business_id' => $business->id,
                'outlet_id' => $outletCabang->id,
                'product_id' => $product->id,
                'stock_quantity' => $data['stock_cabang'],
            ]);

            StockMovement::create([
                'business_id' => $business->id,
                'outlet_id' => $outletCabang->id,
                'product_id' => $product->id,
                'type' => 'adjustment_add',
                'quantity_before' => 0,
                'quantity_change' => $data['stock_cabang'],
                'quantity_after' => $data['stock_cabang'],
                'reason' => 'Stok Awal Sistem POS Cabang',
                'user_id' => $owner->id,
            ]);
        }

        // 10. Customers
        Customer::create([
            'business_id' => $business->id,
            'name' => 'Pelanggan Umum',
            'phone' => '-',
            'notes' => 'Tamu walk-in tanpa membership',
        ]);

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Dimas Wicaksono',
            'phone' => '081234567890',
            'email' => 'dimas@gmail.com',
            'address' => 'Jakarta Selatan',
            'notes' => 'Pelanggan loyal, suka less sugar',
        ]);

        // 11. Supplier
        Supplier::create([
            'business_id' => $business->id,
            'name' => 'PT Roastery Kopi Nusantara',
            'contact_name' => 'Hendra Setiawan',
            'phone' => '082112345678',
            'email' => 'supplier@kopinusantara.com',
            'address' => 'Kawasan Industri Sentul, Bogor',
        ]);

        // 12. Cashier Shift demo for Cashier 1
        CashierShift::create([
            'business_id' => $business->id,
            'outlet_id' => $outletPusat->id,
            'user_id' => $cashier1->id,
            'opened_at' => now()->startOfDay()->addHours(8),
            'initial_cash' => 200000, // Modal Rp 200.000
            'status' => 'open',
        ]);

        // 13. PRD v2 Portal & Deployment Service Seeder
        $this->call(PortalServiceSeeder::class);
    }
}
