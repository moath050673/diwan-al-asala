<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * تحقق من دور المستخدم (admin / staff) قبل السماح بالوصول لمسارات لوحة التحكم
 * الاستخدام في الراوت: ->middleware('role:admin,staff')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // حساب موقوف: نُبطل رمزه فورًا — إيقاف الحساب يجب أن يطرده من لوحة التحكم حتى لو كان مسجلًا للدخول
        if ($user && $user->status !== 'active') {
            $user->currentAccessToken()?->delete();
            return response()->json(['success' => false, 'message' => 'هذا الحساب موقوف'], 401);
        }

        if (!$user || !in_array($user->role, $roles, true)) {
            return response()->json(['success' => false, 'message' => 'لا تملك صلاحية الوصول إلى هذا المورد'], 403);
        }

        return $next($request);
    }
}
