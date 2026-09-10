<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // Guest checkout — لا حاجة لتسجيل الدخول
    public function store(Request $request)
    {
        // عند الإرسال كـ FormData (لأجل رفع صورة الإيصال)، يصل حقل items كنص JSON
        // بدل مصفوفة حقيقية — نفكّه هنا قبل التحقق من الصحة.
        if ($request->has('items') && is_string($request->input('items'))) {
            $request->merge(['items' => json_decode($request->input('items'), true) ?? []]);
        }

        $data = $request->validate([
            'customerName' => 'required|string|max:150',
            'customerPhone' => 'required|string|max:30',
            'city' => 'required|string|max:100',
            'area' => 'nullable|string|max:100',
            'address' => 'required|string',
            'notes' => 'nullable|string',
            'paymentMethod' => 'required|in:cod,jib,kareemi',
            'transactionNumber' => 'nullable|string',
            'receipt' => 'nullable|image|max:4096',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // تخزين صورة الإيصال فعليًا على القرص (storage/app/public/receipts) وحفظ
        // رابطها العام في receiptImage — هذا هو الحقل الذي يقرأه OrderService.
        if ($request->hasFile('receipt')) {
            $data['receiptImage'] = '/storage/'.$request->file('receipt')->store('receipts', 'public');
        }

        $order = $this->orderService->createOrder($data);

        return response()->json(['success' => true, 'data' => [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'items' => $order->items->map(fn ($i) => ['name' => $i->product_name, 'qty' => $i->quantity]),
            'total' => (float) $order->total,
            'customerName' => $order->customer_name,
            'customerPhone' => $order->customer_phone,
            'customerAddress' => $order->customer_address,
            'paymentMethodLabel' => Order::PAYMENT_LABELS[$order->payment_method],
        ]], 201);
    }

    // ---------- Admin only ----------
    public function index(Request $request)
    {
        $query = Order::query();
        if ($status = $request->query('status')) $query->where('order_status', $status);

        $orders = $query->orderBy('created_at', 'desc')->paginate($request->query('limit', 20));

        $data = $orders->getCollection()->map(fn ($o) => [
            'id' => $o->id, 'orderNumber' => $o->order_number, 'customerName' => $o->customer_name,
            'customerPhone' => $o->customer_phone, 'total' => (float) $o->total,
            'paymentMethod' => $o->payment_method, 'paymentStatus' => $o->payment_status,
            'orderStatus' => $o->order_status, 'createdAt' => $o->created_at,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $order = Order::with(['items', 'payments'])->find($id);
        if (!$order) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled']);
        Order::where('id', $id)->update(['order_status' => $data['status']]);
        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }
}
