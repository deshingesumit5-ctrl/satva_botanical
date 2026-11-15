<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\QcRequest;
use Illuminate\Http\Request;

class QcReportController extends Controller
{
    public function index(Request $request)
    {
        $query = QcRequest::with(['batch.product', 'material', 'decisionByUser']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('request_date', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('request_date', '<=', $toDate);
        }

        $requests = $query->latest()->paginate(25)->withQueryString();

        return view('reports.qc', compact('requests'));
    }

    public function export(Request $request)
    {
        $requests = QcRequest::with(['batch.product', 'material', 'decisionByUser'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="qc_report_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Request Number', 'Stage / Type', 'Batch / Material', 'Status', 'Decision Date', 'Decision By', 'Remarks']);
            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->qc_request_number,
                    $r->stage_name ?: $r->stage_type,
                    $r->batch ? $r->batch->batch_no : ($r->material ? $r->material->name : ''),
                    $r->status,
                    $r->decision_at,
                    $r->decisionByUser ? $r->decisionByUser->name : '',
                    $r->decision_remarks,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
