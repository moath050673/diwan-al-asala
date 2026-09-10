<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalSales = Order::whereIn('payment_status', ['paid', 'cash_on_delivery'])->sum('total');
        $todayOrders = Order::whereDate('created_at', today())->count();
        $newOrders = Order::where('order_status', 'pending')->count();
        $processingOrders = Order::where('order_status', 'processing')->count();
        $deliveredOrders = Order::where('order_status', 'delivered')->count();
        $productsCount = Product::where('status', 'active')->count();
        $lowStockCount = Product::where('status', 'active')->where('stock_quantity', '<=', 3)->count();
        $customersCount = \App\Models\Customer::count();
        
        $salesByDay = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')->orderBy('date')->get();

        return response()->json(['success' => true, 'data' => [
            'totalSales' => (float) $totalSales,
            'todayOrders' => $todayOrders,
            'newOrders' => $newOrders,
            'processingOrders' => $processingOrders,
            'deliveredOrders' => $deliveredOrders,
            'productsCount' => $productsCount,
            'lowStockCount' => $lowStockCount,
            'customersCount' => $customersCount,
            'salesByDay' => $salesByDay,
        ]]);
    }
}
