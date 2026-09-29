<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockImagesNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function admin(string $role = 'admin'): static
    {
        $user = User::create(['name' => 'A', 'email' => "{$role}@t.com", 'password' => 'password123', 'role' => $role, 'status' => 'active']);
        return $this->withToken($user->createToken('t')->plainTextToken);
    }

    private function product(int $stock = 10): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'c'], ['name' => 'C']);
        return Product::create(['category_id' => $cat->id, 'name' => 'P', 'slug' => 'p-'.uniqid(), 'sku' => 'S-'.uniqid(), 'price' => 100, 'stock_quantity' => $stock]);
    }

    private function order(Product $product, int $qty = 3): Order
    {
        $this->postJson('/api/orders', [
            'customerName' => 'Test', 'customerPhone' => '777000111', 'city' => 'Sanaa', 'address' => 'St',
            'paymentMethod' => 'cod', 'items' => [['productId' => $product->id, 'quantity' => $qty]],
        ])->assertCreated();

        return Order::latest('id')->first();
    }

    private function png(string $name = 'a.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    public function test_order_decrements_stock_and_cancel_restores_it_once()
    {
        $product = $this->product(10);
        $order = $this->order($product, 3);
        $this->assertSame(7, $product->fresh()->stock_quantity);

        $this->admin()->putJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(10, $product->fresh()->stock_quantity);

        // إلغاء مرة ثانية لا يضيف الكمية مرتين
        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(10, $product->fresh()->stock_quantity);

        // إعادة تفعيل الطلب تخصم الكمية من جديد
        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'confirmed'])->assertOk();
        $this->assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_reactivating_cancelled_order_fails_when_stock_is_gone()
    {
        $product = $this->product(3);
        $order = $this->order($product, 3);
        $this->admin()->putJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();
        $product->update(['stock_quantity' => 1]);

        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'pending'])->assertUnprocessable();
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_admin_can_delete_test_orders_with_restock_and_orphan_customers()
    {
        $product = $this->product(10);
        $a = $this->order($product, 2);
        $b = $this->order($product, 3);
        $this->assertSame(5, $product->fresh()->stock_quantity);

        $this->admin()->postJson('/api/orders/delete', ['ids' => [$a->id, $b->id]])
            ->assertOk()->assertJsonPath('data.orders', 2)->assertJsonPath('data.customers', 1);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_staff_cannot_delete_orders()
    {
        $order = $this->order($this->product(), 1);
        $this->admin('staff')->postJson('/api/orders/delete', ['ids' => [$order->id]])->assertForbidden();
        $this->assertSame(1, Order::count());
    }

    public function test_multiple_images_upload_primary_and_delete()
    {
        Storage::fake('public');
        $product = $this->product();

        $res = $this->admin()->post("/api/products/{$product->id}/images", ['images' => [$this->png('1.png'), $this->png('2.png'), $this->png('3.png')]])
            ->assertCreated();
        $ids = collect($res->json('data.images'))->pluck('id');
        $this->assertCount(3, $ids);

        $this->putJson("/api/products/{$product->id}/images/{$ids[2]}/primary")->assertOk();
        $this->getJson("/api/products/{$product->id}")
            ->assertJsonPath('data.image', ProductImage::find($ids[2])->image_url)
            ->assertJsonCount(3, 'data.images');

        $this->deleteJson("/api/products/{$product->id}/images/{$ids[0]}")->assertOk();
        $this->assertSame(2, $product->images()->count());
        // with GD every image also has an optimized thumbnail
        $this->assertCount(2 * $this->filesPerImage(), Storage::disk('public')->allFiles('products'));
    }

    public function test_images_use_configured_cloud_disk()
    {
        config(['store.disks.images' => 'images_bucket']);
        Storage::fake('images_bucket');
        $product = $this->product();

        $url = $this->admin()->post("/api/products/{$product->id}/images", ['images' => [$this->png()]])
            ->assertCreated()->json('data.images.0.url');

        $this->assertStringStartsWith(Storage::disk('images_bucket')->url(''), $url);
        $this->assertCount($this->filesPerImage(), Storage::disk('images_bucket')->allFiles('products'));

        $imageId = $product->images()->value('id');
        $this->deleteJson("/api/products/{$product->id}/images/{$imageId}")->assertOk();
        $this->assertCount(0, Storage::disk('images_bucket')->allFiles('products'));
    }

    public function test_image_upload_rejects_non_images()
    {
        Storage::fake('public');
        $product = $this->product();
        $this->admin()->post("/api/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_product_can_be_created_without_sku_and_admin_sees_hidden_products()
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c']);
        $id = $this->admin()->postJson('/api/products', [
            'categoryId' => $cat->id, 'name' => 'ميدالية', 'price' => 1500, 'stockQuantity' => 4, 'description' => 'وصف',
        ])->assertCreated()->json('data.id');

        $this->assertNotEmpty(Product::find($id)->sku);

        $this->deleteJson("/api/products/{$id}")->assertOk(); // إخفاء
        $this->getJson('/api/admin/products')->assertOk()
            ->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.status', 'inactive')
            ->assertJsonPath('data.0.description', 'وصف');
    }

    public function test_notifications_endpoint_returns_orders_after_id()
    {
        $product = $this->product();
        $first = $this->order($product, 1);
        $second = $this->order($product, 1);

        $this->admin()->getJson("/api/orders/notifications?after_id={$first->id}")
            ->assertOk()
            ->assertJsonPath('data.latestId', $second->id)
            ->assertJsonPath('data.pendingCount', 2)
            ->assertJsonCount(1, 'data.orders');
    }

    public function test_new_order_sends_telegram_message_when_configured()
    {
        config(['store.telegram.bot_token' => 'TOKEN', 'store.telegram.chat_id' => '111,222']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $order = $this->order($this->product(), 1); // الإشعار يُرسل تلقائيًا بعد الرد

        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'botTOKEN/sendMessage') && str_contains($r['text'], $order->order_number));
    }

    private function filesPerImage(): int
    {
        return app(\App\Services\ImageOptimizer::class)->available() ? 2 : 1;
    }
}
