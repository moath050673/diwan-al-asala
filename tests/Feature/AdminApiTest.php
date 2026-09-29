<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): static
    {
        $user = User::create([
            'name' => 'U', 'email' => "{$role}@test.com", 'password' => 'password123',
            'role' => $role, 'status' => 'active',
        ]);

        return $this->withToken($user->createToken('t')->plainTextToken);
    }

    public function test_settings_update_rejects_unknown_keys()
    {
        $this->actingAsRole('admin')
            ->putJson('/api/settings', ['store_name' => 'X', 'app_key' => 'evil'])
            ->assertUnprocessable();

        $this->assertNull(Setting::get('app_key'));
        $this->assertNull(Setting::get('store_name'));
    }

    public function test_settings_update_rejects_javascript_urls()
    {
        $this->actingAsRole('admin')
            ->putJson('/api/settings', ['facebook_url' => 'javascript:alert(1)'])
            ->assertUnprocessable()->assertJsonValidationErrors('facebook_url');
    }

    public function test_settings_update_saves_valid_values_and_refreshes_cache()
    {
        Setting::set('store_name', 'Old');
        $this->assertSame('Old', Setting::get('store_name'));

        $this->actingAsRole('admin')
            ->putJson('/api/settings', ['store_name' => 'New', 'jib_enabled' => '0'])
            ->assertOk();

        $this->assertSame('New', Setting::get('store_name'));
        $this->assertSame('0', Setting::get('jib_enabled'));
    }

    public function test_settings_cache_works_with_database_store()
    {
        config(['cache.default' => 'database']); // كما على الاستضافة
        Setting::set('shipping_cost_sanaa', '1000');
        $this->assertSame('1000', Setting::get('shipping_cost_sanaa'));

        $this->actingAsRole('admin')->putJson('/api/settings', ['shipping_cost_sanaa' => 1500])->assertOk();
        $this->assertSame('1500', Setting::get('shipping_cost_sanaa'));
    }

    public function test_staff_cannot_update_settings()
    {
        $this->actingAsRole('staff')->putJson('/api/settings', ['store_name' => 'X'])->assertForbidden();
    }

    public function test_updating_status_of_missing_order_returns_404()
    {
        $this->actingAsRole('admin')
            ->putJson('/api/orders/999/status', ['status' => 'confirmed'])
            ->assertNotFound();
    }

    public function test_non_numeric_ids_do_not_match_routes()
    {
        $this->actingAsRole('admin')->getJson('/api/orders/1,id')->assertNotFound();
    }

    public function test_limit_is_capped()
    {
        $this->getJson('/api/products?limit=100000')->assertOk();
        $this->getJson('/api/products?limit=-5')->assertOk();
    }

    public function test_dashboard_stats_returns_aggregates()
    {
        $this->actingAsRole('admin')->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.todayOrders', 0)
            ->assertJsonPath('data.productsCount', 0);
    }

    public function test_login_is_rate_limited()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])->assertStatus(401);
        }
        $this->postJson('/api/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_security_headers_are_present()
    {
        $this->get('/api/health')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
