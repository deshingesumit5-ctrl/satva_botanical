<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\CompanySetting;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductionReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['product', 'recipeVersion', 'stageExecutions']);

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('planned_start_date', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('planned_start_date', '<=', $toDate);
        }

        $batches = $query->latest()->paginate(20)->withQueryString();
        $products = Product::where('status', 'Active')->orderBy('name')->get();

        return view('reports.production', compact('batches', 'products'));
    }

    public function export(Request $request)
    {
        $query = Batch::with(['product', 'recipeVersion']);
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $batches = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="production_report_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($batches) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Batch Number', 'Product', 'Planned Qty', 'Actual Qty', 'Status', 'Start Date', 'End Date']);
            foreach ($batches as $b) {
                fputcsv($file, [
                    $b->batch_no,
                    $b->product ? $b->product->name : '',
                    $b->planned_qty,
                    $b->actual_qty,
                    $b->status,
                    $b->planned_start_date,
                    $b->actual_end_time,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Request $request)
    {
        $batches = Batch::with(['product', 'recipeVersion'])->latest()->take(50)->get();
        $company = CompanySetting::first();

        $pdf = Pdf::loadView('reports.production-pdf', compact('batches', 'company'));
        return $pdf->download("Production_Report_" . date('Y-m-d') . ".pdf");
    }
}
