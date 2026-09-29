<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * يحفظ صورة إيصال الدفع على القرص الخاص (RECEIPTS_DISK) بعد ضغطها (1600px، WebP عند توفرها).
 * الإيصال لا يُتاح بأي رابط عام — يُعرض للمدير فقط عبر GET /api/payments/{id}/receipt.
 */
class ReceiptStorage
{
    public function __construct(private ImageOptimizer $optimizer) {}

    /** يعيد المسار داخل القرص (يُخزَّن في payments.receipt_image) */
    public function store(UploadedFile $file): string
    {
        $disk = (string) config('store.disks.receipts');
        $variants = $this->optimizer->variants($file->getRealPath(), ['main' => 1600], 80);

        if (!$variants) {
            return $file->store('receipts', $disk);
        }

        $path = 'receipts/'.Str::random(40).'.'.$variants['main']['extension'];
        Storage::disk($disk)->put($path, $variants['main']['contents']);

        return $path;
    }
}
