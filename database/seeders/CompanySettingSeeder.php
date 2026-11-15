<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Sattva Botanical Manufacturing Pvt. Ltd.',
                'address' => 'Plot 42, Sector 9, MIDC Industrial Area, Satara - 415004, Maharashtra, India',
                'contact_number' => '+91 2162 250100',
                'email' => 'info@sattvabotanical.com',
                'gstin' => '27AAAAA0000A1Z5',
                'pan' => 'AAAAA0000A',
                'website' => 'https://sattvabotanical.com',
                'default_currency' => 'INR',
                'date_format' => 'd-m-Y',
                'financial_year_start' => '04-01',
                'status' => 'Active',
            ]
        );
    }
}
