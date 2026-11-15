<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate an atomic, unique document number with lockForUpdate.
     */
    public function generate(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {
            $config = DocumentNumbering::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (!$config) {
                // Auto create fallback default if not seeded
                $prefix = strtoupper(substr($documentType, 0, 3));
                $config = DocumentNumbering::create([
                    'document_type' => $documentType,
                    'prefix' => $prefix,
                    'starting_number' => 1,
                    'current_number' => 0,
                    'financial_year_reset' => true,
                    'number_format' => '{PREFIX}/{FY}/{NUM}',
                    'status' => 'Active',
                ]);
                $config = DocumentNumbering::where('document_type', $documentType)->lockForUpdate()->first();
            }

            $nextNumber = ($config->current_number > 0) ? $config->current_number + 1 : $config->starting_number;
            $config->current_number = $nextNumber;
            $config->save();

            // Financial year calculation (e.g. 26-27)
            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            if ($month >= 4) {
                $fy = substr($year, -2) . '-' . substr($year + 1, -2);
            } else {
                $fy = substr($year - 1, -2) . '-' . substr($year, -2);
            }

            $paddedNum = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $formatted = str_replace(
                ['{PREFIX}', '{FY}', '{NUM}'],
                [$config->prefix, $fy, $paddedNum],
                $config->number_format
            );

            return $formatted;
        });
    }
}
