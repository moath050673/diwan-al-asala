<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'method', 'amount', 'transaction_number', 'receipt_image', 'status', 'notes'];

    const STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    // مسار الإيصال الداخلي لا يُرسل للواجهة؛ بدلًا منه receipt_url (مسار API محمي بتسجيل الدخول)
    protected $hidden = ['receipt_image'];

    protected $appends = ['receipt_url'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected function receiptUrl(): Attribute
    {
        return Attribute::get(fn () => $this->receipt_image ? "/api/payments/{$this->id}/receipt" : null);
    }

    /**
     * مسار الملف على القرص الخاص (local). يدعم أيضًا القيم القديمة بصيغة
     * /storage/receipts/x.jpg التي كانت تُحفظ على القرص العام قبل النقل.
     */
    public function receiptStoragePath(): ?string
    {
        if (!$this->receipt_image) {
            return null;
        }

        return ltrim(preg_replace('#^/?storage/#', '', $this->receipt_image), '/');
    }
}
