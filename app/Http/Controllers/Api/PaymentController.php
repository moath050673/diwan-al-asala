<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // عام — بيانات جيب/كريمي المعروضة للعميل في صفحة الدفع
    public function settings()
    {
        $keys = ['jib_enabled', 'jib_account_name', 'jib_account_number',
                 'kareemi_enabled', 'kareemi_account_name', 'kareemi_account_number', 'cod_enabled'];

        $data = collect($keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ---------- Admin only ----------
    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['success' => false, 'message' => 'سجل الدفع غير موجود'], 404);
        return response()->json(['success' => true, 'data' => $payment]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::find($id);
        if (!$payment) return response()->json(['success' => false, 'message' => 'سجل الدفع غير موجود'], 404);

        $payment->update(['status' => $data['status'], 'notes' => $data['notes'] ?? null]);

        if (in_array($data['status'], ['paid', 'failed'])) {
            $payment->order->update(['payment_status' => $data['status']]);
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الدفع']);
    }
}
