<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'message' => 'required|string',
        ]);
        ContactMessage::create($data);
        return response()->json(['success' => true, 'message' => 'تم إرسال رسالتك بنجاح'], 201);
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => ContactMessage::orderBy('created_at', 'desc')->get()]);
    }
}
