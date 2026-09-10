<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * بيانات منتجات تجريبية (Placeholder) لأغراض التطوير والمعاينة فقط.
 * يمكن حذفها بأمان من لوحة التحكم بعد إضافة منتجات حقيقية.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $zabad = Category::where('slug', 'zabad')->first();
        $bakhoor = Category::where('slug', 'bakhoor')->first();
        $perfume = Category::where('slug', 'perfume')->first();

        $products = [
            ['category_id' => $perfume->id, 'name' => 'عطر الأصالة الملكي', 'slug' => 'oud-alasala-royal', 'sku' => 'PRF-001', 'description' => 'عطر شرقي فاخر بتركيبة أصيلة تجمع بين العود والمسك.', 'price' => 15000, 'old_price' => 18000, 'stock_quantity' => 12, 'featured' => true],
            ['category_id' => $bakhoor->id, 'name' => 'بخور فاخر', 'slug' => 'bakhoor-fakher', 'sku' => 'BKH-001', 'description' => 'بخور يمني أصيل معد يدويًا من أجود أنواع العود.', 'price' => 8500, 'old_price' => null, 'stock_quantity' => 20, 'featured' => true],
            ['category_id' => $zabad->id, 'name' => 'زباد أصلي', 'slug' => 'zabad-asli', 'sku' => 'ZBD-001', 'description' => 'زباد طبيعي فاخر يُستخدم كثابت للعطور.', 'price' => 22000, 'old_price' => 25000, 'stock_quantity' => 5, 'featured' => true],
            ['category_id' => $perfume->id, 'name' => 'عطر شرقي فاخر', 'slug' => 'oud-sharqi-fakher', 'sku' => 'PRF-002', 'description' => 'مزيج شرقي دافئ من الفانيليا والعنبر.', 'price' => 12000, 'old_price' => null, 'stock_quantity' => 0, 'featured' => false],
            ['category_id' => $bakhoor->id, 'name' => 'بخور يمني فاخر', 'slug' => 'bakhoor-yamani', 'sku' => 'BKH-002', 'description' => 'بخور يمني تقليدي بنكهة مميزة.', 'price' => 9500, 'old_price' => 11000, 'stock_quantity' => 15, 'featured' => false],
            ['category_id' => $zabad->id, 'name' => 'خلطة متجر ديوان الأصالة', 'slug' => 'diwan-mix', 'sku' => 'MIX-001', 'description' => 'خلطة حصرية تجمع الزباد والعود والمسك.', 'price' => 30000, 'old_price' => null, 'stock_quantity' => 8, 'featured' => true],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['sku' => $p['sku']], $p);
        }
    }
}
