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
        // ترتيب ثابت (الأقدم أولًا) — بدونه يختلف ترتيب العرض حسب قاعدة البيانات
        $categories = Category::where('status', 'active')->orderBy('id')->get(['id', 'name', 'slug', 'description', 'image']);
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string']);
        $category = Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
        ]);
        return response()->json(['success' => true, 'data' => ['id' => $category->id]], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);
        $category->update($data);
        return response()->json(['success' => true, 'message' => 'تم تحديث التصنيف']);
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->update(['status' => 'inactive']);
        return response()->json(['success' => true, 'message' => 'تم إخفاء التصنيف']);
    }

    /**
     * slug فريد — تصنيفان بنفس الاسم (أو اسم لا ينتج slug) كانا يسببان خطأ 500
     * بسبب القيد unique على العمود.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
