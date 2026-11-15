<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'CAT-EXT', 'name' => 'Botanical & Herbal Extracts', 'display_order' => 1],
            ['code' => 'CAT-FRT', 'name' => 'Fruit Extracts & Powders', 'display_order' => 2],
            ['code' => 'CAT-OIL', 'name' => 'Essential & Cold Pressed Oils', 'display_order' => 3],
            ['code' => 'CAT-PHY', 'name' => 'Phytochemicals & Isolates', 'display_order' => 4],
            ['code' => 'CAT-SPY', 'name' => 'Spray Dried Herbal Powders', 'display_order' => 5],
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(['code' => $cat['code']], array_merge($cat, ['status' => 'Active']));
        }
    }
}
