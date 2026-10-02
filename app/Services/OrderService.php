<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * OrderService — كل منطق حساب الأسعار والمخزون يتم هنا فقط.
 * لا يُسمح أبدًا بالوثوق بالسعر أو الإجمالي القادم من المتصفح (راجع البند 38 من المتطلبات الأصلية).
 */
class OrderService
{
    public function createOrder(array $payload): Order
    {
        $items = $payload['items'] ?? [];
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'لا يمكن إنشاء طلب بدون منتجات']);
        }
        $method = $payload['paymentMethod'] ?? null;
        if (!array_key_exists((string) $method, Order::PAYMENT_LABELS)) {
            throw ValidationException::withMessages(['paymentMethod' => 'طريقة الدفع غير صحيحة']);
        }
        // طريقة دفع معطّلة من الإعدادات (القيمة '0') لا تُقبل حتى لو أُرسلت مباشرة للـ API
        if ((string) Setting::get("{$method}_enabled", '1') === '0') {
            throw ValidationException::withMessages(['paymentMethod' => 'طريقة الدفع هذه غير متاحة حاليًا']);
        }

        // دمج الأسطر المكررة لنفس المنتج — وإلا يُفحص المخزون لكل سطر على حدة
        // فيمكن طلب [5 + 5] من منتج مخزونه 5 ويصبح المخزون سالبًا.
        $quantities = [];
        foreach ($items as $item) {
            $productId = (int) $item['productId'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }
        ksort($quantities); // قفل الصفوف بترتيب ثابت يقلل احتمالية الـ deadlock بين طلبين متزامنين

        return DB::transaction(function () use ($payload, $quantities) {
            $subtotal = 0;
            $resolvedItems = [];

            $products = Product::whereIn('id', array_keys($quantities))
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (!$product) {
                    throw ValidationException::withMessages(['items' => "منتج غير موجود ({$productId})"]);
                }
                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages(['items' => "الكمية المطلوبة من \"{$product->name}\" غير متوفرة في المخزون"]);
                }

                $lineTotal = (float) $product->price * $quantity;
                $subtotal += $lineTotal;

                $resolvedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $lineTotal,
                ];
            }

            // التوصيل اختياري: يُضاف سعره فقط إذا طلبه العميل، وإلا فالطلب استلام من المتجر
            $withDelivery = filter_var($payload['delivery'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $shippingCost = $withDelivery ? (float) Setting::get('shipping_cost_sanaa', 0) : 0.0;
            $total = $subtotal + $shippingCost;
            $paymentStatus = $payload['paymentMethod'] === 'cod' ? 'cash_on_delivery' : 'pending';
            $fullAddress = $withDelivery
                ? trim(($payload['city'] ?? '').' - '.($payload['area'] ?? '').' - '.($payload['address'] ?? ''), ' -')
                : Order::PICKUP_ADDRESS;
            // الموقع من الخريطة يُحفظ مع التوصيل فقط (لا معنى له عند الاستلام من المتجر)
            $hasLocation = $withDelivery && isset($payload['locationLat'], $payload['locationLng']);

            // إيجاد العميل بحسب رقم الهاتف، أو إنشاء سجل جديد له إذا كانت أول مرة يطلب فيها
            $customer = Customer::firstOrCreate(
                ['phone' => $payload['customerPhone']],
                [
                    'name' => $payload['customerName'],
                    'whatsapp' => $payload['customerWhatsapp'] ?? $payload['customerPhone'],
                    'city' => $payload['city'] ?? null,
                    'area' => $payload['area'] ?? null,
                    'address' => $payload['address'] ?? null,
                ]
            );
            $customer->update([
                'name' => $payload['customerName'],
                'whatsapp' => $payload['customerWhatsapp'] ?? $payload['customerPhone'],
                'city' => $payload['city'] ?? $customer->city,
                'area' => $payload['area'] ?? $customer->area,
                'address' => $payload['address'] ?? $customer->address,
            ]);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'delivery' => $withDelivery,
                'total' => $total,
                'payment_method' => $payload['paymentMethod'],
                'payment_status' => $paymentStatus,
                'order_status' => 'pending',
                'customer_name' => $payload['customerName'],
                'customer_phone' => $payload['customerPhone'],
                'customer_address' => $fullAddress,
                'location_lat' => $hasLocation ? round((float) $payload['locationLat'], 7) : null,
                'location_lng' => $hasLocation ? round((float) $payload['locationLng'], 7) : null,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($resolvedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);

                $item['product']->decrement('stock_quantity', $item['quantity']);
            }

            if ($payload['paymentMethod'] !== 'cod') {
                Payment::create([
                    'order_id' => $order->id,
                    'method' => $payload['paymentMethod'],
                    'amount' => $total,
                    'transaction_number' => $payload['transactionNumber'] ?? null,
                    'receipt_image' => $payload['receiptImage'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * تغيير حالة الطلب مع مزامنة المخزون:
     * - إلغاء الطلب ← تعود الكميات إلى المخزون
     * - إعادة تفعيل طلب ملغي ← تُخصم الكميات مجددًا (بعد التأكد من توفرها)
     */
    public function changeStatus(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($status === 'cancelled' && !$order->stock_released_at) {
                $this->releaseStock($order);
            } elseif ($status !== 'cancelled' && $order->stock_released_at) {
                $this->reserveStock($order);
            }

            $order->update(['order_status' => $status]);

            return $order;
        });
    }

    /**
     * حذف طلبات (مثل الطلبات التجريبية) مع إرجاع كمياتها للمخزون اختياريًا،
     * وحذف صور إيصالاتها، وحذف العملاء الذين لم يعد لديهم أي طلب.
     *
     * @return array{orders:int, customers:int}
     */
    public function deleteOrders(array $ids, bool $restock = true, bool $deleteOrphanCustomers = true): array
    {
        $receipts = [];

        $result = DB::transaction(function () use ($ids, $restock, $deleteOrphanCustomers, &$receipts) {
            $orders = Order::with(['items', 'payments'])->whereIn('id', $ids)->lockForUpdate()->get();
            $customerIds = $orders->pluck('customer_id')->filter()->unique();

            foreach ($orders as $order) {
                if ($restock && !$order->stock_released_at) {
                    $this->releaseStock($order);
                }
                foreach ($order->payments as $payment) {
                    if ($path = $payment->receiptStoragePath()) $receipts[] = $path;
                }
                $order->delete(); // order_items و payments تُحذف تلقائيًا (cascade)
            }

            $deletedCustomers = 0;
            if ($deleteOrphanCustomers && $customerIds->isNotEmpty()) {
                $deletedCustomers = Customer::whereIn('id', $customerIds)->doesntHave('orders')->delete();
            }

            return ['orders' => $orders->count(), 'customers' => $deletedCustomers];
        });

        // حذف الملفات بعد نجاح العملية فقط (لا يمكن التراجع عن حذف ملف داخل transaction)
        foreach ($receipts as $path) {
            foreach (array_unique([config('store.disks.receipts'), 'local', 'public']) as $disk) {
                Storage::disk($disk)->delete($path);
            }
        }

        return $result;
    }

    private function releaseStock(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
        }
        $order->update(['stock_released_at' => now()]);
    }

    private function reserveStock(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
            if ($product && $product->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages(['status' => "لا يمكن إعادة تفعيل الطلب: الكمية المتوفرة من \"{$product->name}\" غير كافية"]);
            }
            $product?->decrement('stock_quantity', $item->quantity);
        }
        $order->update(['stock_released_at' => null]);
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'DA-'.substr((string) now()->timestamp, -6).random_int(10000, 99999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}