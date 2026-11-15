<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::updateOrCreate(
            ['id' => 1],
            [
                'application_name' => 'Sattva Botanical',
                'default_timezone' => 'Asia/Kolkata',
                'default_currency' => 'INR',
                'default_date_format' => 'd-m-Y',
                'decimal_precision' => 2,
                'quantity_precision' => 3,
                'default_language' => 'en',
                'email_notifications' => false,
                'attachment_size_limit_mb' => 15,
                'session_timeout_minutes' => 120,
                'inventory_valuation_method' => 'Weighted Average',
            ]
        );
    }
}
