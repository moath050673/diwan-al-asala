<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $stats = DB::table('orders')
            ->select('customer_id')
            ->selectRaw('COUNT(id) as ordersCount, COALESCE(SUM(total), 0) as lifetimeValue')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id');

        $query = Customer::query()
            ->leftJoinSub($stats, 'order_stats', 'order_stats.customer_id', '=', 'customers.id')
            ->select('customers.*')
            ->selectRaw('COALESCE(order_stats.ordersCount, 0) as ordersCount, COALESCE(order_stats.lifetimeValue, 0) as lifetimeValue');

        if ($q = $request->query('q')) {
            $query->where(fn ($sub) => $sub->where('customers.name', 'like', "%{$q}%")->orWhere('customers.phone', 'like', "%{$q}%"));
        }

        $customers = $query->orderBy('customers.created_at', 'desc')->paginate($this->perPage($request));

        return response()->json(['success' => true, 'data' => $customers->items()]);
    }

    public function show($id)
    {
        $customer = Customer::with('orders')->find($id);
        if (!$customer) return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        return response()->json(['success' => true, 'data' => $customer]);
    }
}
