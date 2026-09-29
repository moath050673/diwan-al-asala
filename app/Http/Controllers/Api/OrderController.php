<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\NewOrderNotifier;
use App\Services\OrderService;
use App\Services\ReceiptStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // Guest checkout — لا حاجة لتسجيل الدخول
    public function store(StoreOrderRequest $request, NewOrderNotifier $notifier, ReceiptStorage $receipts)
    {
        $data = $request->validated();

        // الإيصال يُضغط ويُخزَّن على القرص الخاص — غير متاح بأي رابط عام،
        // ويُعرض للمدير فقط عبر GET /api/payments/{id}/receipt. OrderService يقرأ receiptImage.
        if ($request->hasFile('receipt')) {
            $data['receiptImage'] = $receipts->store($request->file('receipt'));
        }

        try {
            $order = $this->orderService->createOrder($data);
        } catch (\Throwable $e) {
            // الطلب رُفض (مخزون غير كافٍ...) — لا نترك صورة إيصال يتيمة في التخزين
            if (isset($data['receiptImage'])) {
                Storage::disk(config('store.disks.receipts'))->delete($data['receiptImage']);
            }
            throw $e;
        }

        // الإشعارات (Telegram / بريد) بعد إرسال الرد للعميل — لا تبطئ إتمام الطلب
        app()->terminating(fn () => $notifier->notify($order));

        return response()->json(['success' => true, 'data' => [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'items' => $order->items->map(fn ($i) => ['name' => $i->product_name, 'qty' => $i->quantity]),
            'total' => (float) $order->total,
            'delivery' => $order->delivery,
            'shippingCost' => (float) $order->shipping_cost,
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
        if ($status = $this->queryText($request, 'status', 20)) $query->where('order_status', $status);

        $orders = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($this->perPage($request));

        $data = $orders->getCollection()->map(fn ($o) => [
            'id' => $o->id, 'orderNumber' => $o->order_number, 'customerName' => $o->customer_name,
            'customerPhone' => $o->customer_phone, 'total' => (float) $o->total,
            'paymentMethod' => $o->payment_method, 'paymentStatus' => $o->payment_status,
            'orderStatus' => $o->order_status, 'createdAt' => $o->created_at,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * تستدعيها لوحة التحكم كل بضع ثوانٍ: الطلبات الأحدث من after_id + عدد الطلبات الجديدة (pending).
     */
    public function notifications(Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);

        $new = Order::where('id', '>', $afterId)->orderBy('id')->limit(20)
            ->get(['id', 'order_number', 'customer_name', 'total', 'created_at']);

        return response()->json(['success' => true, 'data' => [
            'latestId' => (int) Order::max('id'),
            'pendingCount' => Order::where('order_status', 'pending')->count(),
            'orders' => $new->map(fn ($o) => [
                'id' => $o->id, 'orderNumber' => $o->order_number,
                'customerName' => $o->customer_name, 'total' => (float) $o->total,
            ]),
        ]]);
    }

    public function show($id)
    {
        $order = Order::with(['items', 'payments'])->find($id);
        if (!$order) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))]]);

        $order = Order::find($id);
        if (!$order) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);

        $this->orderService->changeStatus($order, $data['status']);

        $message = $data['status'] === 'cancelled' ? 'تم إلغاء الطلب وإرجاع الكميات إلى المخزون' : 'تم تحديث حالة الطلب';
        return response()->json(['success' => true, 'message' => $message]);
    }

    // ---------- Admin فقط: حذف طلبات (مثل الطلبات التجريبية) ----------
    public function destroyMany(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
            'restock' => 'sometimes|boolean',
            'deleteCustomers' => 'sometimes|boolean',
        ]);

        $result = $this->orderService->deleteOrders(
            $data['ids'],
            $data['restock'] ?? true,
            $data['deleteCustomers'] ?? true,
        );

        return response()->json([
            'success' => true,
            'message' => "تم حذف {$result['orders']} طلب".($result['customers'] ? " و{$result['customers']} عميل" : ''),
            'data' => $result,
        ]);
    }
}
