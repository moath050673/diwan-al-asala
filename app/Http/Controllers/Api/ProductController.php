<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->active()->with(['category', 'images']);

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }
        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($min = $request->query('minPrice')) $query->where('price', '>=', $min);
        if ($max = $request->query('maxPrice')) $query->where('price', '<=', $max);

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate($request->query('limit', 20));

        $data = $products->getCollection()->map(fn ($p) => $this->transform($p));

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $product = Product::active()->with(['category', 'images'])->find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404);

        return response()->json(['success' => true, 'data' => [
            ...$this->transform($product),
            'description' => $product->description,
            'weight' => $product->weight,
            'size' => $product->size,
        ]]);
    }

    private function transform(Product $p): array
    {
        // نستخدم علاقة images() الموجودة أصلاً في Model — لا حاجة لأي جدول أو Migration جديد.
        // Product::images() تُرجّع النتائج مرتبة حسب sort_order، لذلك أول عنصر هو الصورة الرئيسية دائمًا.
        $images = $p->relationLoaded('images') ? $p->images : $p->images()->get();
        $primary = $images->first();

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'oldPrice' => $p->old_price ? (float) $p->old_price : null,
            'stock' => $p->stock_quantity,
            'featured' => $p->featured,
            'categoryName' => $p->category->name,
            'category' => $p->category->slug,
            // صورة واحدة رئيسية جاهزة للاستخدام المباشر في بطاقة المنتج
            'image' => $primary ? $this->resolveImageUrl($primary->image_url) : null,
            // كل الصور بالترتيب (لصفحة تفاصيل المنتج لاحقًا)
            'images' => $images->map(fn ($img) => $this->resolveImageUrl($img->image_url))->values(),
        ];
    }

    /**
     * يبني رابطًا صالحًا للاستخدام المباشر في الواجهة الأمامية بغض النظر
     * عن شكل القيمة المخزَّنة في image_url (رابط مطلق، مسار يبدأ بـ /storage،
     * أو حتى مجرد اسم ملف نسبي أُدخل يدويًا من قاعدة البيانات).
     */
    private function resolveImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        if (str_starts_with($path, '/')) return $path;
        return '/storage/'.ltrim($path, '/');
    }

    // ---------- Admin only ----------
    public function store(Request $request)
    {
        $data = $request->validate([
            'categoryId' => 'required|exists:categories,id',
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:60|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'oldPrice' => 'nullable|numeric|min:0',
            'stockQuantity' => 'required|integer|min:0',
            'weight' => 'nullable|string',
            'size' => 'nullable|string',
            'featured' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'category_id' => $data['categoryId'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.time(),
            'sku' => $data['sku'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'old_price' => $data['oldPrice'] ?? null,
            'stock_quantity' => $data['stockQuantity'],
            'weight' => $data['weight'] ?? null,
            'size' => $data['size'] ?? null,
            'featured' => $data['featured'] ?? false,
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $product->id]], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validate([
            'categoryId' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:200',
            'sku' => 'sometimes|string|max:60|unique:products,sku,'.$id,
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'oldPrice' => 'sometimes|nullable|numeric|min:0',
            'stockQuantity' => 'sometimes|integer|min:0',
            'weight' => 'sometimes|nullable|string',
            'size' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'featured' => 'sometimes|boolean',
        ]);

        $map = ['categoryId' => 'category_id', 'oldPrice' => 'old_price', 'stockQuantity' => 'stock_quantity'];
        $update = [];
        foreach ($data as $key => $value) $update[$map[$key] ?? $key] = $value;

        $product->update($update);

        return response()->json(['success' => true, 'message' => 'تم تحديث المنتج']);
    }

    public function destroy($id)
    {
        Product::where('id', $id)->update(['status' => 'inactive']);
        return response()->json(['success' => true, 'message' => 'تم إخفاء المنتج']);
    }

    public function addImage(Request $request, $id)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);

        $path = $request->file('image')->store('products', 'public');
        $image = ProductImage::create(['product_id' => $id, 'image_url' => '/storage/'.$path]);

        return response()->json(['success' => true, 'data' => ['imageUrl' => $image->image_url]], 201);
    }
}
