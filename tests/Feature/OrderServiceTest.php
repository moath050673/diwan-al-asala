<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(int $stock): Product
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . $category->id,
            'sku' => 'TEST-' . $category->id,
            'price' => 100,
            'stock_quantity' => $stock,
        ]);
    }

    private function orderPayload(array|string $items, array $overrides = []): array
    {
        return array_merge([
            'customerName' => 'Test Customer',
            'customerPhone' => '555123456',
            'city' => 'Sanaa',
            'area' => 'Test Area',
            'address' => 'Test Address',
            'paymentMethod' => 'cod',
            'items' => $items,
        ], $overrides);
    }

    public function test_order_creation_creates_only_one_order()
    {
        $product = $this->createProduct(5);

        Setting::create(['setting_key' => 'shipping_cost_sanaa', 'setting_value' => 10]);

        $this->postJson('/api/orders', $this->orderPayload(
            [['productId' => $product->id, 'quantity' => 2]],
            ['customerWhatsapp' => '777000111'],
        ))->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 210);

        $this->assertCount(1, Order::all());
        $this->assertCount(1, Customer::all());
        $this->assertSame(1, OrderItem::where('product_id', $product->id)->count());
        $this->assertEquals(3, $product->fresh()->stock_quantity);
        $this->assertSame('777000111', Customer::first()->whatsapp);
    }

    public function test_order_creation_does_not_allow_insufficient_stock()
    {
        $product = $this->createProduct(1);

        $this->postJson('/api/orders', $this->orderPayload([['productId' => $product->id, 'quantity' => 2]]))
            ->assertUnprocessable();

        $this->assertCount(0, Order::all());
    }

    public function test_duplicate_lines_for_same_product_cannot_exceed_stock()
    {
        $product = $this->createProduct(5);

        $this->postJson('/api/orders', $this->orderPayload([
            ['productId' => $product->id, 'quantity' => 5],
            ['productId' => $product->id, 'quantity' => 5],
        ]))->assertUnprocessable();

        $this->assertCount(0, Order::all());
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    public function test_duplicate_lines_are_merged_into_one_order_item()
    {
        $product = $this->createProduct(5);

        $this->postJson('/api/orders', $this->orderPayload([
            ['productId' => $product->id, 'quantity' => 2],
            ['productId' => $product->id, 'quantity' => 1],
        ]))->assertCreated();

        $this->assertSame(3, OrderItem::first()->quantity);
        $this->assertEquals(2, $product->fresh()->stock_quantity);
    }

    public function test_disabled_payment_method_is_rejected()
    {
        $product = $this->createProduct(5);
        Setting::set('jib_enabled', '0');

        $this->postJson('/api/orders', $this->orderPayload(
            [['productId' => $product->id, 'quantity' => 1]],
            ['paymentMethod' => 'jib'],
        ))->assertUnprocessable()->assertJsonValidationErrors('paymentMethod');
    }

    public function test_svg_receipt_is_rejected()
    {
        Storage::fake('public');
        $product = $this->createProduct(5);

        $svg = UploadedFile::fake()->createWithContent('r.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->post('/api/orders', $this->orderPayload(
            json_encode([['productId' => $product->id, 'quantity' => 1]]),
            ['paymentMethod' => 'kareemi', 'receipt' => $svg],
        ))->assertUnprocessable()->assertJsonValidationErrors('receipt');
    }

    public function test_validation_errors_are_json_even_without_accept_header()
    {
        // الواجهة الأمامية كانت تستقبل إعادة توجيه HTML بدل JSON عند فشل التحقق
        $this->post('/api/orders', [])->assertUnprocessable()->assertJsonStructure(['errors']);
    }
}
