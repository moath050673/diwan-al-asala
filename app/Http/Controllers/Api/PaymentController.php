<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    // عام — بيانات جيب/كريمي المعروضة للعميل في صفحة الدفع
    public function settings()
    {
        $data = Setting::many(['jib_enabled', 'jib_account_name', 'jib_account_number',
                               'kareemi_enabled', 'kareemi_account_name', 'kareemi_account_number', 'cod_enabled']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ---------- Admin only ----------
    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['success' => false, 'message' => 'سجل الدفع غير موجود'], 404);
        return response()->json(['success' => true, 'data' => $payment]);
    }

    /**
     * صورة إيصال الدفع — محمية (admin/staff فقط). الإيصالات تحتوي بيانات مالية
     * وشخصية للعملاء لذلك لم تعد متاحة عبر رابط عام في /storage.
     */
    public function receipt($id)
    {
        $payment = Payment::find($id);
        $path = $payment?->receiptStoragePath();

        // القرص الخاص أولًا، ثم العام للملفات القديمة التي لم تُنقل بعد (قبل تشغيل الـ migration)
        foreach (array_unique([config('store.disks.receipts'), 'local', 'public']) as $disk) {
            if ($path && Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path, null, [
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'لا يوجد إيصال لهذا الدفع'], 404);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Payment::STATUSES)],
            'notes' => 'nullable|string|max:2000',
        ]);

        $payment = Payment::with('order')->find($id);
        if (!$payment) return response()->json(['success' => false, 'message' => 'سجل الدفع غير موجود'], 404);

        DB::transaction(function () use ($payment, $data) {
            $payment->update(['status' => $data['status'], 'notes' => $data['notes'] ?? null]);

            if (in_array($data['status'], ['paid', 'failed']) && $payment->order) {
                $payment->order->update(['payment_status' => $data['status']]);
            }
        });

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الدفع']);
    }
}
