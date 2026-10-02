<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'subtotal', 'shipping_cost', 'delivery', 'discount', 'total',
        'payment_method', 'payment_status', 'order_status',
        'customer_name', 'customer_phone', 'customer_address', 'location_lat', 'location_lng',
        'notes', 'stock_released_at',
    ];

    protected $appends = ['map_url'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'delivery' => 'boolean',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'location_lat' => 'float',
            'location_lng' => 'float',
            'stock_released_at' => 'datetime',
        ];
    }

    /**
     * رابط Google Maps لموقع التوصيل المحدد من الخريطة (null إذا لم يحدده العميل).
     */
    public function getMapUrlAttribute(): ?string
    {
        if ($this->location_lat === null || $this->location_lng === null) {
            return null;
        }

        return 'https://www.google.com/maps?q='.$this->location_lat.','.$this->location_lng;
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

    const PICKUP_ADDRESS = 'استلام من المتجر';

    const PAYMENT_LABELS = [
        'cod' => 'الدفع عند الاستلام',
        'jib' => 'جيب JIB',
        'kareemi' => 'كريمي Kareemi',
    ];
}
