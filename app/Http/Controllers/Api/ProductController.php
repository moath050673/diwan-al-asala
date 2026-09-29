<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private ProductImageStorage $images) {}

    public function index(Request $request)
    {
        $query = Product::query()->active()->with(['category', 'images']);

        if ($category = $this->queryText($request, 'category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }
        if ($q = $this->queryText($request, 'q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if (is_numeric($min = $request->query('minPrice'))) $query->where('price', '>=', $min);
        if (is_numeric($max = $request->query('maxPrice'))) $query->where('price', '<=', $max);

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate($this->perPage($request));

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
            // نسخة مصغّرة محسّنة لبطاقات المنتجات (الصور القديمة: الصورة الأصلية)
            'thumb' => $primary ? $this->resolveImageUrl($primary->thumb_url ?: $primary->image_url) : null,
            // كل الصور بالترتيب (لصفحة تفاصيل المنتج لاحقًا)
            'images' => $images->map(fn ($img) => $this->resolveImageUrl($img->image_url))->values(),
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        return ProductImage::resolveUrl($path);
    }

    // ---------- Admin only ----------

    /** كل المنتجات (النشطة والمخفية) للوحة التحكم */
    public function adminIndex(Request $request)
    {
        $query = Product::with(['category', 'images'])->orderBy('status')->orderByDesc('id');
        if ($q = $this->queryText($request, 'q')) {
            $query->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }

        $data = $query->paginate($this->perPage($request, 100))->getCollection()
            ->map(fn ($p) => $this->adminTransform($p));

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function adminShow($id)
    {
        $product = Product::with(['category', 'images'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $this->adminTransform($product)]);
    }

    private function adminTransform(Product $p): array
    {
        return [
            ...$this->transform($p),
            'categoryId' => $p->category_id,
            'description' => $p->description,
            'status' => $p->status,
            'weight' => $p->weight,
            'size' => $p->size,
            'imageItems' => $p->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $this->resolveImageUrl($img->image_url),
            ])->values(),
        ];
    }

    public function store(SaveProductRequest $request)
    {
        $attributes = $request->toAttributes();

        $product = Product::create([
            ...$attributes,
            // Str::random يمنع تصادم slug عند إنشاء منتجين بنفس الاسم في نفس الثانية
            'slug' => Str::slug($attributes['name']).'-'.time().'-'.Str::lower(Str::random(4)),
            'sku' => $attributes['sku'] ?? $this->generateSku(),
            'featured' => $attributes['featured'] ?? false,
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $product->id]], 201);
    }

    private function generateSku(): string
    {
        do {
            $sku = 'DA-'.strtoupper(Str::random(6));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    public function update(SaveProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->toAttributes());

        return response()->json(['success' => true, 'message' => 'تم تحديث المنتج']);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->update(['status' => 'inactive']);
        return response()->json(['success' => true, 'message' => 'تم إخفاء المنتج']);
    }

    public const MAX_IMAGES = 10;

    /** رفع صورة أو عدة صور (images[]) — أول صورة للمنتج تصبح الرئيسية */
    public function addImage(Request $request, $id)
    {
        $product = Product::withCount('images')->findOrFail($id);

        // images[] (عدة صور) أو image (صورة واحدة — للتوافق مع الاستدعاء القديم)
        $files = $request->file('images') ?? array_filter([$request->file('image')]);

        validator(['images' => $files], [
            'images' => 'required|array|min:1|max:'.self::MAX_IMAGES,
            'images.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ], [
            'images.*.dimensions' => 'أبعاد الصورة كبيرة جدًا (الحد الأقصى 8000 بكسل)',
            'images.required' => 'اختر صورة واحدة على الأقل',
            'images.*.mimes' => 'الصور المسموحة: JPG أو PNG أو WEBP',
            'images.*.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت',
        ])->validate();
        if ($product->images_count + count($files) > self::MAX_IMAGES) {
            return response()->json(['success' => false, 'message' => 'الحد الأقصى '.self::MAX_IMAGES.' صور لكل منتج'], 422);
        }

        $nextOrder = (int) $product->images()->max('sort_order') + ($product->images_count ? 1 : 0);
        $created = [];
        foreach ($files as $file) {
            $created[] = ProductImage::create([
                'product_id' => $product->id,
                ...$this->images->store($file), // image_url + thumb_url (محسّنة تلقائيًا)
                'sort_order' => $nextOrder++,
            ]);
        }

        return response()->json(['success' => true, 'data' => [
            'imageUrl' => $created[0]->image_url,
            'images' => collect($created)->map(fn ($img) => ['id' => $img->id, 'url' => $img->image_url]),
        ]], 201);
    }

    public function deleteImage($id, $imageId)
    {
        $image = ProductImage::where('product_id', $id)->findOrFail($imageId);

        $this->images->delete($image->image_url);
        $this->images->delete($image->thumb_url);
        $image->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الصورة']);
    }

    /** جعل صورة هي الرئيسية (تظهر في بطاقة المنتج) */
    public function setPrimaryImage($id, $imageId)
    {
        $product = Product::findOrFail($id);
        $primary = $product->images()->findOrFail($imageId);

        DB::transaction(function () use ($product, $primary) {
            $order = 1;
            foreach ($product->images()->where('id', '!=', $primary->id)->get() as $img) {
                $img->update(['sort_order' => $order++]);
            }
            $primary->update(['sort_order' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'تم تعيين الصورة الرئيسية']);
    }
}
