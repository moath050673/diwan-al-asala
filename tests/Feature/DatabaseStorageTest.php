<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ImageOptimizer;
use Tests\TestCase;

/** إعداد Laravel Cloud Starter: الصور والإيصالات داخل قاعدة البيانات */
class DatabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        config(['store.disks.images' => 'db_public', 'store.disks.receipts' => 'db_private']);
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(self::PNG));
    }

    private function adminToken(): string
    {
        return User::create(['name' => 'A', 'email' => 'a@t.com', 'password' => 'password123', 'role' => 'admin', 'status' => 'active'])
            ->createToken('t')->plainTextToken;
    }

    private function product(): Product
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c']);
        return Product::create(['category_id' => $cat->id, 'name' => 'P', 'slug' => 'p', 'sku' => 'P1', 'price' => 1500, 'stock_quantity' => 5]);
    }

    public function test_product_images_are_stored_in_database_and_served_publicly()
    {
        $product = $this->product();

        $url = $this->withToken($this->adminToken())
            ->post("/api/products/{$product->id}/images", ['images' => [$this->png(), $this->png()]], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.images.0.url');

        $this->assertStringStartsWith('/media/products/', $url);
        $perImage = $this->optimizer()->available() ? 2 : 1; // optimized image + thumbnail
        $this->assertSame(2 * $perImage, DB::table('stored_files')->where('bucket', 'public')->count());

        // الزائر يرى الصورة بدون تسجيل دخول
        $res = $this->get($url)->assertOk()->assertHeader('Content-Type', $this->expectedMime());
        $this->assertSame(Storage::disk('db_public')->get(Str::after($url, '/media/')), $res->getContent());

        // تظهر في بيانات المنتج للمتجر
        $this->getJson("/api/products/{$product->id}")->assertJsonPath('data.image', $url);

        // حذف الصورة يحذف الملف من قاعدة البيانات
        $imageId = $product->images()->orderBy('sort_order')->value('id');
        $this->deleteJson("/api/products/{$product->id}/images/{$imageId}")->assertOk();
        $this->assertSame($perImage, DB::table('stored_files')->where('bucket', 'public')->count());
        $this->get($url)->assertNotFound();
    }

    public function test_receipts_are_private_in_database()
    {
        $product = $this->product();

        $this->post('/api/orders', [
            'customerName' => 'T', 'customerPhone' => '777000111', 'city' => 'Sanaa', 'address' => 'St',
            'paymentMethod' => 'kareemi', 'items' => json_encode([['productId' => $product->id, 'quantity' => 1]]),
            'receipt' => $this->png(),
        ])->assertCreated();

        $payment = Payment::firstOrFail();
        $this->assertTrue(Storage::disk('db_private')->exists($payment->receipt_image));

        // ليس متاحًا عبر رابط الصور العامة ولا بدون تسجيل دخول
        $this->get('/media/'.$payment->receipt_image)->assertNotFound();
        $this->get($payment->receipt_url)->assertUnauthorized();

        $this->withToken($this->adminToken())->get($payment->receipt_url)
            ->assertOk()->assertHeader('Content-Type', $this->expectedMime());
    }

    public function test_media_route_rejects_path_traversal()
    {
        $this->get('/media/../.env')->assertNotFound();
        $this->get('/media/products/..%2F..%2F.env')->assertNotFound();
    }

    private function optimizer(): ImageOptimizer
    {
        return app(ImageOptimizer::class);
    }

    /** uploads are converted to WebP when GD supports it */
    private function expectedMime(): string
    {
        return $this->optimizer()->supportsWebp() ? 'image/webp' : 'image/png';
    }
}
