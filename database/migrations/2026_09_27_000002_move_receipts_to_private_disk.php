<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * نقل صور إيصالات الدفع من القرص العام (storage/app/public/receipts — متاح لأي شخص
 * يعرف الرابط) إلى القرص الخاص (storage/app/private/receipts)، وتحديث المسار المخزن.
 * الملفات تُنقل ولا تُحذف؛ down() يعيدها كما كانت.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->where('receipt_image', 'like', '/storage/receipts/%')
            ->orderBy('id')
            ->each(function ($payment) {
                $path = substr($payment->receipt_image, strlen('/storage/'));

                if (Storage::disk('public')->exists($path) && !Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                    Storage::disk('public')->delete($path);
                }

                if (Storage::disk('local')->exists($path)) {
                    DB::table('payments')->where('id', $payment->id)->update(['receipt_image' => $path]);
                }
            });
    }

    public function down(): void
    {
        DB::table('payments')
            ->where('receipt_image', 'like', 'receipts/%')
            ->orderBy('id')
            ->each(function ($payment) {
                $path = $payment->receipt_image;

                if (Storage::disk('local')->exists($path) && !Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->put($path, Storage::disk('local')->get($path));
                    Storage::disk('local')->delete($path);
                }

                DB::table('payments')->where('id', $payment->id)->update(['receipt_image' => '/storage/'.$path]);
            });
    }
};
