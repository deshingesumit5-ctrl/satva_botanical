<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            // Stock Units
            ['uom_code' => 'KG', 'uom_name' => 'Kilogram', 'category' => 'Weight', 'decimal_precision' => 3, 'is_stock_unit' => true],
            ['uom_code' => 'GM', 'uom_name' => 'Gram', 'category' => 'Weight', 'decimal_precision' => 3, 'is_stock_unit' => true],
            ['uom_code' => 'MT', 'uom_name' => 'Metric Ton', 'category' => 'Weight', 'decimal_precision' => 3, 'is_stock_unit' => true],
            ['uom_code' => 'LTR', 'uom_name' => 'Litre', 'category' => 'Volume', 'decimal_precision' => 2, 'is_stock_unit' => true],
            ['uom_code' => 'ML', 'uom_name' => 'Millilitre', 'category' => 'Volume', 'decimal_precision' => 2, 'is_stock_unit' => true],
            ['uom_code' => 'NOS', 'uom_name' => 'Numbers / Units', 'category' => 'Count', 'decimal_precision' => 0, 'is_stock_unit' => true],
            ['uom_code' => 'DRUM', 'uom_name' => 'Drums', 'category' => 'Count', 'decimal_precision' => 0, 'is_stock_unit' => true],
            ['uom_code' => 'BAG', 'uom_name' => 'Bags', 'category' => 'Count', 'decimal_precision' => 0, 'is_stock_unit' => true],

            // Process & Parameter Units (non-stock)
            ['uom_code' => 'DEG_C', 'uom_name' => 'Degree Celsius (°C)', 'category' => 'Measurement', 'decimal_precision' => 1, 'is_stock_unit' => false],
            ['uom_code' => 'PH', 'uom_name' => 'pH Unit', 'category' => 'Measurement', 'decimal_precision' => 2, 'is_stock_unit' => false],
            ['uom_code' => 'PCT', 'uom_name' => 'Percentage (%)', 'category' => 'Measurement', 'decimal_precision' => 2, 'is_stock_unit' => false],
            ['uom_code' => 'BRIX', 'uom_name' => 'Brix (°Bx)', 'category' => 'Measurement', 'decimal_precision' => 1, 'is_stock_unit' => false],
            ['uom_code' => 'RPM', 'uom_name' => 'Revolutions Per Minute', 'category' => 'Measurement', 'decimal_precision' => 0, 'is_stock_unit' => false],
            ['uom_code' => 'BAR', 'uom_name' => 'Bar (Pressure)', 'category' => 'Measurement', 'decimal_precision' => 2, 'is_stock_unit' => false],
            ['uom_code' => 'MIN', 'uom_name' => 'Minutes', 'category' => 'Time', 'decimal_precision' => 0, 'is_stock_unit' => false],
            ['uom_code' => 'HRS', 'uom_name' => 'Hours', 'category' => 'Time', 'decimal_precision' => 2, 'is_stock_unit' => false],
        ];

        foreach ($uoms as $u) {
            Uom::updateOrCreate(['uom_code' => $u['uom_code']], array_merge($u, ['status' => 'Active']));
        }
    }
}
