<?php

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\Warehouse;
use App\Models\StorageLocation;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $plant = Plant::first();
        $plantId = $plant ? $plant->id : null;

        $warehouses = [
            ['code' => 'WH-RM', 'name' => 'Raw Material Warehouse', 'type' => 'Raw Material', 'condition' => 'Dry, ambient storage'],
            ['code' => 'WH-CHM', 'name' => 'Chemical & Solvent Store', 'type' => 'Chemical', 'condition' => 'Ventilated, flameproof storage'],
            ['code' => 'WH-WIP', 'name' => 'WIP Intermediate Store', 'type' => 'WIP', 'condition' => 'Controlled temperature'],
            ['code' => 'WH-FG', 'name' => 'Finished Goods Warehouse', 'type' => 'Finished Goods', 'condition' => 'Clean room, temperature controlled'],
            ['code' => 'WH-REC', 'name' => 'Solvent Recovery Area', 'type' => 'Recovery', 'condition' => 'Dedicated recovery holding tanks'],
            ['code' => 'WH-WST', 'name' => 'Waste & Spent Yard', 'type' => 'Waste', 'condition' => 'Segregated industrial waste area'],
            ['code' => 'WH-REJ', 'name' => 'Quarantine & Rejected Store', 'type' => 'Rejected', 'condition' => 'Locked quarantine zone'],
        ];

        foreach ($warehouses as $wh) {
            $warehouse = Warehouse::updateOrCreate(
                ['code' => $wh['code']],
                [
                    'name' => $wh['name'],
                    'type' => $wh['type'],
                    'plant_id' => $plantId,
                    'storage_condition' => $wh['condition'],
                    'status' => 'Active',
                ]
            );

            // Create default location/bins for each warehouse
            StorageLocation::updateOrCreate(
                ['location_code' => "LOC-{$wh['code']}-01"],
                [
                    'name' => "{$wh['name']} Main Rack A",
                    'warehouse_id' => $warehouse->id,
                    'rack_no' => 'RACK-A',
                    'bin_no' => 'BIN-01',
                    'status' => 'Active',
                ]
            );
        }
    }
}
