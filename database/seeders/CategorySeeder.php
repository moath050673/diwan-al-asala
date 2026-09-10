<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'الزباد', 'slug' => 'zabad', 'description' => 'زباد طبيعي أصيل'],
            ['name' => 'البخور', 'slug' => 'bakhoor', 'description' => 'بخور يمني فاخر'],
            ['name' => 'العطور', 'slug' => 'perfume', 'description' => 'عطور شرقية أصيلة'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
