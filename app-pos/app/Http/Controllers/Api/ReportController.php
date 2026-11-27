<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Business Dashboard Overview (FR-045)
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses ringkasan hanya tersedia untuk akun bisnis.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_view_dashboard'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses ringkasan dashboard untuk Admin.'], 403);
            }
        }
        $businessId = $user->business_id;
        $requestedOutletId = $request->query('outlet_id');
        if ($requestedOutletId) {
            Outlet::where('business_id', $businessId)->findOrFail($requestedOutletId);
            if ($user->role === 'cashier' && $user->outlet_id && (int) $requestedOutletId !== (int) $user->outlet_id) {
                return response()->json(['status' => 'error', 'message' => 'Kasir hanya dapat melihat ringkasan outlet tempat bertugas.'], 403);
            }
        }
        $outletId = $user->role === 'cashier' ? ($user->outlet_id ?: $requestedOutletId) : $requestedOutletId;

        $today = Carbon::today();

        $baseQuery = Transaction::where('business_id', $businessId)
            ->where('payment_status', 'paid');

        if ($outletId) {
            $baseQuery->where('outlet_id', $outletId);
        }

        // 1. Today Metrics
        $todayQuery = (clone $baseQuery)->whereDate('created_at', $today);
        $todaySales = (float) $todayQuery->sum('total_amount');
        $todayTransactions = (int) $todayQuery->count();
        $todayAvgBasket = $todayTransactions > 0 ? round($todaySales / $todayTransactions) : 0;

        // 2. Low Stock Count
        $lowStockCount = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('inventory', function ($q) use ($outletId) {
                if ($outletId) {
                    $q->where('outlet_id', $outletId);
                }
                $q->whereRaw('inventory.stock_quantity <= products.min_stock_alert');
            })
            ->count();

        // 3. Top 5 Best Sellers
        $topProducts = TransactionItem::whereHas('transaction', function ($q) use ($businessId, $outletId) {
                $q->where('business_id', $businessId)->where('payment_status', 'paid');
                if ($outletId) $q->where('outlet_id', $outletId);
            })
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 4. Payment Method Breakdown (Last 30 Days)
        $paymentBreakdown = Payment::whereHas('transaction', function ($q) use ($businessId, $outletId) {
                $q->where('business_id', $businessId)->where('payment_status', 'paid');
                if ($outletId) $q->where('outlet_id', $outletId);
            })
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount_paid - change_amount) as total_amount'))
            ->groupBy('payment_method')
            ->get();

        // 5. Last 7 Days Sales Trend
        $salesTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $daySum = (clone $baseQuery)
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            $salesTrend[] = [
                'date' => $date->format('d M'),
                'raw_date' => $date->format('Y-m-d'),
                'total' => (float) $daySum,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'today_sales' => $todaySales,
                'today_transactions' => $todayTransactions,
                'today_avg_basket' => $todayAvgBasket,
                'low_stock_count' => $lowStockCount,
                'top_products' => $topProducts,
                'payment_breakdown' => $paymentBreakdown,
                'sales_trend' => $salesTrend,
            ],
        ]);
    }

    /**
     * Sales Report (FR-046)
     */
    public function salesReport(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Akses laporan penjualan khusus Owner atau Admin.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_view_sales_report'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses laporan penjualan untuk Admin.'], 403);
            }
        }
        $businessId = $user->business_id;
        $outletId = $request->query('outlet_id');
        if ($outletId) {
            Outlet::where('business_id', $businessId)->findOrFail($outletId);
        }
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $query = Transaction::where('business_id', $businessId)
            ->where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->with(['items', 'payment', 'cashier', 'outlet']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($cashierId = $request->query('cashier_id')) {
            User::where('business_id', $businessId)->findOrFail($cashierId);
            $query->where('cashier_id', $cashierId);
        }

        $transactions = $query->latest()->get();

        $totalSales = $transactions->sum('total_amount');
        $totalDiscount = $transactions->sum('discount_total');
        $totalTax = $transactions->sum('tax_amount');
        $totalTxn = $transactions->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_transactions' => $totalTxn,
                    'total_sales' => (float) $totalSales,
                    'total_discount' => (float) $totalDiscount,
                    'total_tax' => (float) $totalTax,
                ],
                'transactions' => $transactions,
            ],
        ]);
    }

    /**
     * Profit Report (FR-048)
     */
    public function profitReport(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json(['status' => 'error', 'message' => 'Laporan modal (HPP) dan laba kotor hanya dapat diakses oleh Pemilik Bisnis (Owner).'], 403);
        }
        $businessId = $user->business_id;
        $outletId = $request->query('outlet_id', $user->outlet_id);
        if ($outletId) {
            Outlet::where('business_id', $businessId)->findOrFail($outletId);
        }
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $items = TransactionItem::whereHas('transaction', function ($q) use ($businessId, $outletId, $startDate, $endDate) {
            $q->where('business_id', $businessId)
              ->where('payment_status', 'paid')
              ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            if ($outletId) $q->where('outlet_id', $outletId);
        })
        ->select(
            'product_name',
            DB::raw('SUM(quantity) as qty_sold'),
            DB::raw('AVG(buy_price) as avg_buy_price'),
            DB::raw('AVG(sell_price) as avg_sell_price'),
            DB::raw('SUM(buy_price * quantity) as total_cogs'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('SUM(subtotal - (buy_price * quantity)) as gross_profit')
        )
        ->groupBy('product_name')
        ->orderByDesc('gross_profit')
        ->get();

        $totalRevenue = $items->sum('total_revenue');
        $totalCogs = $items->sum('total_cogs');
        $totalGrossProfit = $items->sum('gross_profit');
        $marginPercentage = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_cogs' => (float) $totalCogs,
                    'total_gross_profit' => (float) $totalGrossProfit,
                    'profit_margin_percentage' => $marginPercentage,
                ],
                'products' => $items,
            ],
        ]);
    }
}
