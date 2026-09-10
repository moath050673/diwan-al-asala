<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')->get(['id', 'name', 'slug', 'description', 'image']);
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);
        return response()->json(['success' => true, 'data' => ['id' => $category->id]], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);
        Category::where('id', $id)->update($data);
        return response()->json(['success' => true, 'message' => 'تم تحديث التصنيف']);
    }

    public function destroy($id)
    {
        Category::where('id', $id)->update(['status' => 'inactive']);
        return response()->json(['success' => true, 'message' => 'تم إخفاء التصنيف']);
    }
}
