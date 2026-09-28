<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'subtotal', 'shipping_cost', 'discount', 'total',
        'payment_method', 'payment_status', 'order_status',
        'customer_name', 'customer_phone', 'customer_address', 'notes', 'stock_released_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'stock_released_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    const STATUS_LABELS = [
        'pending' => 'جديد',
        'confirmed' => 'تم التأكيد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];

    const PAYMENT_LABELS = [
        'cod' => 'الدفع عند الاستلام',
        'jib' => 'جيب JIB',
        'kareemi' => 'كريمي Kareemi',
    ];
}
