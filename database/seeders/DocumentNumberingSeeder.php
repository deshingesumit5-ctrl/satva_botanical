<?php

namespace Database\Seeders;

use App\Models\DocumentNumbering;
use Illuminate\Database\Seeder;

class DocumentNumberingSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['document_type' => 'PR', 'prefix' => 'PR', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'PO', 'prefix' => 'PO', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'GRN', 'prefix' => 'GRN', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'LOT', 'prefix' => 'LOT', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'PROD_PLAN', 'prefix' => 'PP', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'BATCH', 'prefix' => 'B', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'WIP', 'prefix' => 'WIP', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'REC', 'prefix' => 'REC', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'QC', 'prefix' => 'QC', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'RWK', 'prefix' => 'RWK', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'FGR', 'prefix' => 'FGR', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'SO', 'prefix' => 'SO', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'DISPATCH', 'prefix' => 'DSP', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'SALES_INV', 'prefix' => 'INV', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'PURCHASE_INV', 'prefix' => 'PINV', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'PAYMENT', 'prefix' => 'PAY', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'RECEIPT', 'prefix' => 'RCT', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'CDN', 'prefix' => 'CDN', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'EXPENSE', 'prefix' => 'EXP', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'ALLOCATION', 'prefix' => 'ALC', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'RECIPE', 'prefix' => 'RCP', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'TRANSFER', 'prefix' => 'TRF', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
            ['document_type' => 'ADJUSTMENT', 'prefix' => 'ADJ', 'starting_number' => 1, 'number_format' => '{PREFIX}/{FY}/{NUM}'],
        ];

        foreach ($types as $item) {
            DocumentNumbering::updateOrCreate(
                ['document_type' => $item['document_type']],
                [
                    'prefix' => $item['prefix'],
                    'starting_number' => $item['starting_number'],
                    'current_number' => 0,
                    'financial_year_reset' => true,
                    'number_format' => $item['number_format'],
                    'status' => 'Active',
                ]
            );
        }
    }
}
