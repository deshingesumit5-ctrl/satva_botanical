<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RecoveryLot;
use Illuminate\Http\Request;

class RecoveryReportController extends Controller
{
    public function index(Request $request)
    {
        $query = RecoveryLot::with(['sourceBatch.product', 'stage', 'warehouse']);

        $lots = $query->latest()->paginate(25)->withQueryString();

        return view('reports.recovery', compact('lots'));
    }

    public function export(Request $request)
    {
        $lots = RecoveryLot::with(['sourceBatch.product', 'stage', 'warehouse'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="solvent_recovery_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($lots) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Lot Number', 'Source Batch', 'Solvent', 'Generated Qty', 'Consumed Qty', 'Remaining Qty', 'Status']);
            foreach ($lots as $l) {
                fputcsv($file, [
                    $l->recovery_lot_number,
                    $l->sourceBatch ? $l->sourceBatch->batch_no : '',
                    $l->solvent_name,
                    $l->generated_qty,
                    $l->consumed_qty,
                    $l->remaining_qty,
                    $l->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
