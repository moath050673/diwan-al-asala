<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\User;
use App\Services\DatabaseBackup;
use App\Services\ErrorAlerter;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'bakhoor'], ['name' => 'بخور']);

        return Product::create([
            'category_id' => $cat->id, 'name' => 'بخور ملكي', 'slug' => 'p-'.uniqid(), 'sku' => 'S'.uniqid(),
            'price' => 2500, 'stock_quantity' => 4, 'description' => 'بخور يمني فاخر <b>أصلي</b>', ...$attributes,
        ]);
    }

    private function user(string $role = 'admin', string $status = 'active'): User
    {
        return User::create(['name' => 'U', 'email' => uniqid().'@t.com', 'password' => 'secret12345', 'role' => $role, 'status' => $status]);
    }

    // ---------- Security ----------

    public function test_security_headers_and_noindex_for_admin()
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertHeader('Content-Security-Policy');

        $this->get('/admin')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('noindex', false);
    }

    public function test_pages_do_not_depend_on_sessions()
    {
        // Laravel Cloud may set SESSION_DRIVER=database — pages must still work without a sessions table
        config(['session.driver' => 'database']);

        foreach (['/', '/products', '/robots.txt', '/sitemap.xml', '/admin'] as $url) {
            $this->get($url)->assertOk()->assertCookieMissing(config('session.cookie'))->assertCookieMissing('XSRF-TOKEN');
        }
    }

    public function test_deactivated_user_token_is_rejected_and_revoked()
    {
        $user = $this->user();
        $token = $user->createToken('t')->plainTextToken;
        $user->update(['status' => 'inactive']);

        $this->withToken($token)->getJson('/api/orders')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_change_password_requires_strong_password()
    {
        $user = $this->user();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/change-password', ['currentPassword' => 'secret12345', 'newPassword' => 'short'])
            ->assertStatus(422)->assertJsonValidationErrors('newPassword');
        $this->withToken($token)->postJson('/api/auth/change-password', ['currentPassword' => 'secret12345', 'newPassword' => 'onlyletterspassword'])
            ->assertStatus(422);

        $this->withToken($token)->postJson('/api/auth/change-password', ['currentPassword' => 'secret12345', 'newPassword' => 'NewStrongPass2026'])
            ->assertOk();
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    public function test_login_is_limited_per_email_across_ips()
    {
        $user = $this->user();
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret12345'])
            ->assertStatus(429);
    }

    // ---------- SEO ----------

    public function test_product_page_has_server_side_seo()
    {
        $product = $this->product();
        ProductImage::create(['product_id' => $product->id, 'image_url' => '/media/products/a.webp', 'sort_order' => 0]);

        $this->get("/product/{$product->id}")->assertOk()
            ->assertSee('<title>بخور ملكي | متجر ديوان الأصالة</title>', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('/media/products/a.webp', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"YER"', false)
            ->assertDontSee('<b>أصلي</b>', false);

        $this->get('/product/999999')->assertNotFound();
        $hidden = $this->product(['status' => 'inactive']);
        $this->get("/product/{$hidden->id}")->assertNotFound();
    }

    public function test_home_has_store_schema_and_private_pages_are_noindex()
    {
        $this->get('/')->assertSee('"@type":"Store"', false)->assertSee('og:title', false);
        $this->get('/checkout')->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_robots_and_sitemap()
    {
        $product = $this->product();
        $hidden = $this->product(['status' => 'inactive']);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertDontSee('Disallow: /api');

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('product', $product->id), false)
            ->assertDontSee(route('product', $hidden->id).'<', false)
            ->assertSee(route('about'), false);

        // a new product appears immediately (cache is invalidated on save)
        $new = $this->product();
        $this->get('/sitemap.xml')->assertSee(route('product', $new->id).'<', false);
    }

    // ---------- Image optimization ----------

    public function test_uploaded_images_are_resized_converted_and_get_thumbnails()
    {
        if (!app(ImageOptimizer::class)->available()) {
            $this->markTestSkipped('GD not loaded (run: php -d extension=gd artisan test)');
        }
        Storage::fake('public');

        $product = $this->product();
        $token = $this->user()->createToken('t')->plainTextToken;
        $big = UploadedFile::fake()->image('big.jpg', 3000, 2000);

        $res = $this->withToken($token)->post("/api/products/{$product->id}/images", ['images' => [$big]], ['Accept' => 'application/json'])
            ->assertCreated();

        $image = ProductImage::findOrFail($res->json('data.images.0.id'));
        $ext = app(ImageOptimizer::class)->supportsWebp() ? 'webp' : 'jpg';
        $this->assertStringEndsWith(".{$ext}", $image->image_url);
        $this->assertStringContainsString('/products/thumbs/', $image->thumb_url);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get(str_replace('/storage/', '', $image->image_url)));
        $this->assertSame([1600, 1067], [$w, $h]);
        [$tw] = getimagesizefromstring(Storage::disk('public')->get(str_replace('/storage/', '', $image->thumb_url)));
        $this->assertSame(600, $tw);

        $this->getJson("/api/products/{$product->id}")->assertJsonPath('data.thumb', $image->thumb_url);

        // deleting the image removes both files
        $this->withToken($token)->deleteJson("/api/products/{$product->id}/images/{$image->id}")->assertOk();
        Storage::disk('public')->assertMissing(str_replace('/storage/', '', $image->image_url));
        Storage::disk('public')->assertMissing(str_replace('/storage/', '', $image->thumb_url));
    }

    public function test_images_with_huge_dimensions_are_rejected()
    {
        $product = $this->product();
        $token = $this->user()->createToken('t')->plainTextToken;
        $huge = UploadedFile::fake()->createWithContent('huge.png', $this->pngHeader(9000, 100));

        $this->withToken($token)->post("/api/products/{$product->id}/images", ['images' => [$huge]], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    /** Minimal PNG header (getimagesize only reads the IHDR chunk). */
    private function pngHeader(int $w, int $h): string
    {
        $ihdr = pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0);
        return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
    }

    // ---------- Backups ----------

    public function test_encrypted_backup_restores_data_including_binary_files()
    {
        $product = $this->product();
        Setting::set('store_name', 'قبل');
        $binary = random_bytes(2048);
        Storage::disk('db_public')->put('products/x.bin', $binary);

        $service = app(DatabaseBackup::class);
        $file = tempnam(sys_get_temp_dir(), 'bk');
        $stats = $service->create($file, 'backup-pass-123');
        $this->assertTrue($service->isEncrypted($file));
        $this->assertGreaterThan(0, $stats['rows']);

        // data changes after the backup
        $product->update(['name' => 'تغيّر']);
        $this->product(['name' => 'منتج جديد']);
        Setting::set('store_name', 'بعد');
        Storage::disk('db_public')->delete('products/x.bin');

        try {
            $service->restore($file, 'wrong-password');
            $this->fail('Wrong password must fail');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('password', $e->getMessage());
        }
        $this->assertSame('بعد', Setting::get('store_name')); // unchanged after failed restore

        $service->restore($file, 'backup-pass-123');
        unlink($file);

        $this->assertSame(1, Product::count());
        $this->assertSame('بخور ملكي', $product->fresh()->name);
        $this->assertSame('قبل', Setting::get('store_name'));
        $this->assertSame($binary, Storage::disk('db_public')->get('products/x.bin'));
    }

    public function test_backup_command_saves_to_disk_and_keeps_limit()
    {
        Storage::fake('local');
        config(['store.backup.keep' => 2, 'store.backup.password' => null, 'store.backup.telegram' => false]);
        $this->product();

        for ($i = 0; $i < 3; $i++) {
            $this->artisan('backup:run')->assertSuccessful();
            $this->travel(1)->seconds();
        }
        $this->assertCount(2, Storage::disk('local')->files('backups'));

        $this->artisan('backup:restore', ['source' => 'latest', '--force' => true])->assertSuccessful();
        $this->assertSame(1, Product::count());
    }

    public function test_backup_to_telegram_requires_password_and_refuses_database_disk()
    {
        config(['store.backup.telegram' => true, 'store.backup.password' => null]);
        $this->artisan('backup:run')->assertFailed();

        config(['store.backup.telegram' => false, 'store.backup.disk' => 'db_private']);
        $this->artisan('backup:run')->assertFailed();
    }

    // ---------- Monitoring ----------

    public function test_errors_are_alerted_on_telegram_once_per_period()
    {
        Cache::store('file')->flush();
        config(['store.error_alerts.telegram' => true, 'store.telegram.bot_token' => '123:ABC', 'store.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $alerter = app(ErrorAlerter::class);
        $e = new \RuntimeException('Database is down');
        $alerter->alert($e);
        $alerter->alert($e);

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => str_contains($r['text'], 'Database is down'));
        Cache::store('file')->flush();
    }

    public function test_production_stores_uploads_in_database_by_default()
    {
        $original = [$_SERVER['APP_ENV'] ?? null, $_ENV['APP_ENV'] ?? null];
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'production';
        putenv('APP_ENV=production');

        try {
            $config = require config_path('store.php');
        } finally {
            [$_SERVER['APP_ENV'], $_ENV['APP_ENV']] = $original;
            putenv('APP_ENV='.$original[0]);
        }

        // cloud hosts wipe the server disk on every deploy — never default to it in production
        $this->assertSame('db_public', $config['disks']['images']);
        $this->assertSame('db_private', $config['disks']['receipts']);
    }

    public function test_health_command_runs()
    {
        $this->artisan('app:health')->assertFailed(); // no admin account yet

        $this->user();
        $code = \Illuminate\Support\Facades\Artisan::call('app:health');
        $this->assertSame(0, $code, \Illuminate\Support\Facades\Artisan::output());
    }
}
