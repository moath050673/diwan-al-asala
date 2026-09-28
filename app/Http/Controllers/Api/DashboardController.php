<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function stats()
    {
        // استعلام واحد لكل جدول بدل 8 استعلامات منفصلة
        $orders = Order::toBase()->selectRaw("
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'cash_on_delivery') THEN total ELSE 0 END), 0) as total_sales,
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as today_orders,
            SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as new_orders,
            SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
        ", [today()])->first();

        $products = Product::toBase()->where('status', 'active')->selectRaw('
            COUNT(*) as products_count,
            SUM(CASE WHEN stock_quantity <= 3 THEN 1 ELSE 0 END) as low_stock_count
        ')->first();

        $salesByDay = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')->orderBy('date')->get();

        return response()->json(['success' => true, 'data' => [
            'totalSales' => (float) $orders->total_sales,
            'todayOrders' => (int) $orders->today_orders,
            'newOrders' => (int) $orders->new_orders,
            'processingOrders' => (int) $orders->processing_orders,
            'deliveredOrders' => (int) $orders->delivered_orders,
            'productsCount' => (int) $products->products_count,
            'lowStockCount' => (int) $products->low_stock_count,
            'customersCount' => Customer::count(),
            'salesByDay' => $salesByDay,
        ]]);
    }
}
