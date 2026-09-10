<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
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
        if (!in_array($payload['paymentMethod'] ?? null, ['cod', 'jib', 'kareemi'])) {
            throw ValidationException::withMessages(['paymentMethod' => 'طريقة الدفع غير صحيحة']);
        }

        return DB::transaction(function () use ($payload, $items) {
            $subtotal = 0;
            $resolvedItems = [];

            foreach ($items as $item) {
                $product = Product::where('id', $item['productId'])->where('status', 'active')->lockForUpdate()->first();

                if (!$product) {
                    throw ValidationException::withMessages(['items' => "منتج غير موجود ({$item['productId']})"]);
                }
                if ($product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => "الكمية المطلوبة من \"{$product->name}\" غير متوفرة في المخزون"]);
                }

                $lineTotal = (float) $product->price * (int) $item['quantity'];
                $subtotal += $lineTotal;

                $resolvedItems[] = [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'price' => $product->price,
                    'total' => $lineTotal,
                ];
            }

            $shippingCost = (float) Setting::get('shipping_cost_sanaa', 0);
            $total = $subtotal + $shippingCost;
            $paymentStatus = $payload['paymentMethod'] === 'cod' ? 'cash_on_delivery' : 'pending';
            $fullAddress = trim(($payload['city'] ?? '').' - '.($payload['area'] ?? '').' - '.($payload['address'] ?? ''), ' -');

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
                'total' => $total,
                'payment_method' => $payload['paymentMethod'],
                'payment_status' => $paymentStatus,
                'order_status' => 'pending',
                'customer_name' => $payload['customerName'],
                'customer_phone' => $payload['customerPhone'],
                'customer_address' => $fullAddress,
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

    public function generateOrderNumber(): string
    {
        return 'DA-'.substr((string) now()->timestamp, -6).random_int(10000, 99999);
    }
}