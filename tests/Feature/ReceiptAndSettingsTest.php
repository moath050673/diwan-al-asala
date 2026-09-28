<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function placeKareemiOrderWithReceipt(): Payment
    {
        $category = Category::create(['name' => 'C', 'slug' => 'c']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'P', 'slug' => 'p', 'sku' => 'P-1',
            'price' => 100, 'stock_quantity' => 5,
        ]);

        $this->post('/api/orders', [
            'customerName' => 'Test', 'customerPhone' => '777000111', 'city' => 'Sanaa',
            'address' => 'Street', 'paymentMethod' => 'kareemi',
            'items' => json_encode([['productId' => $product->id, 'quantity' => 1]]),
            'receipt' => UploadedFile::fake()->createWithContent('receipt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')),
        ])->assertCreated();

        return Payment::firstOrFail();
    }

    public function test_receipts_are_stored_privately_and_served_only_to_staff()
    {
        Storage::fake('local');
        Storage::fake('public');

        $payment = $this->placeKareemiOrderWithReceipt();

        Storage::disk('local')->assertExists($payment->receipt_image);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertArrayNotHasKey('receipt_image', $payment->toArray());
        $this->assertSame("/api/payments/{$payment->id}/receipt", $payment->receipt_url);

        // زائر غير مسجل
        $this->getJson($payment->receipt_url)->assertUnauthorized();
        // فتح الرابط مباشرة من المتصفح (بدون Accept: application/json) كان يسبب خطأ 500
        $this->get($payment->receipt_url)->assertUnauthorized();

        $admin = User::create([
            'name' => 'A', 'email' => 'a@test.com', 'password' => 'password123', 'role' => 'admin', 'status' => 'active',
        ]);
        $this->withToken($admin->createToken('t')->plainTextToken)
            ->get($payment->receipt_url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_legacy_public_receipt_paths_are_still_served()
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('receipts/old.jpg', 'img');

        $payment = $this->placeKareemiOrderWithReceipt();
        $payment->update(['receipt_image' => '/storage/receipts/old.jpg']);

        $admin = User::create([
            'name' => 'A', 'email' => 'a@test.com', 'password' => 'password123', 'role' => 'staff', 'status' => 'active',
        ]);
        $this->withToken($admin->createToken('t')->plainTextToken)->get($payment->receipt_url)->assertOk();
    }

    public function test_storefront_layout_receives_db_settings()
    {
        Setting::set('shipping_cost_sanaa', '1500');
        Setting::set('whatsapp_number', '967711223344');

        $this->get('/')
            ->assertOk()
            ->assertSee('"shipping_cost_sanaa":"1500"', false)
            ->assertSee('"whatsapp_number":"967711223344"', false);
    }
}
