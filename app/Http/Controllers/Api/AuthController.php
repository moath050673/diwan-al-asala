<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || $user->status !== 'active' || !Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        $token = $user->createToken('admin-panel')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user->only('id', 'name', 'email', 'role'),
                'mustChangePassword' => $user->must_change_password,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()->only('id', 'name', 'email', 'role')]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($data['currentPassword'], $user->password)) {
            throw ValidationException::withMessages(['currentPassword' => 'كلمة المرور الحالية غير صحيحة']);
        }

        $user->update(['password' => Hash::make($data['newPassword']), 'must_change_password' => false]);

        return response()->json(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح']);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج']);
    }
}
