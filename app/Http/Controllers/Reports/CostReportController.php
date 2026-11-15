<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BatchCost;
use Illuminate\Http\Request;

class CostReportController extends Controller
{
    public function index(Request $request)
    {
        $query = BatchCost::with(['batch.product']);

        $costs = $query->latest()->paginate(25)->withQueryString();

        return view('reports.cost', compact('costs'));
    }

    public function export(Request $request)
    {
        $costs = BatchCost::with(['batch.product'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cost_report_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($costs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Batch Number', 'Product', 'Material Cost', 'Labour Cost', 'Overhead Cost', 'Total Cost', 'Actual FG Qty', 'Cost Per Unit']);
            foreach ($costs as $c) {
                fputcsv($file, [
                    $c->batch ? $c->batch->batch_no : '',
                    $c->batch && $c->batch->product ? $c->batch->product->name : '',
                    $c->raw_material_cost,
                    $c->labour_cost,
                    $c->overhead_cost,
                    $c->total_batch_cost,
                    $c->batch ? $c->batch->actual_qty : 0,
                    $c->cost_per_unit,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
