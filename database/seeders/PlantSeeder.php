<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        Plant::updateOrCreate(
            ['code' => 'PLANT-01'],
            [
                'name' => 'Sattva Botanical Unit 1',
                'address' => 'Plot 42, MIDC Industrial Area, Satara, Maharashtra',
                'contact' => '+91 9876543210',
                'email' => 'plant1@sattvabotanical.local',
                'gstin' => '27AAAAA0000A1Z5',
                'status' => 'Active',
            ]
        );
    }
}
