<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\QcRequest;
use App\Models\QcRequestResult;
use App\Services\QcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QcController extends Controller
{
    protected QcService $qcService;

    public function __construct(QcService $qcService)
    {
        $this->qcService = $qcService;
    }

    public function dashboard()
    {
        $pendingCount = QcRequest::where('status', 'Pending')->count();
        $approvedCount = QcRequest::where('status', 'Approved')->count();
        $rejectedCount = QcRequest::where('status', 'Rejected')->count();
        $reworkCount = QcRequest::where('status', 'Rework Required')->count();

        $recentRequests = QcRequest::with(['batch.product', 'submittedByUser'])
            ->latest()
            ->take(10)
            ->get();

        return view('qc.dashboard', compact('pendingCount', 'approvedCount', 'rejectedCount', 'reworkCount', 'recentRequests'));
    }

    public function pending(Request $request)
    {
        $query = QcRequest::with(['batch.product', 'stageExecution', 'submittedByUser'])
            ->where('status', 'Pending');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('qc_request_number', 'like', "%{$search}%")
                  ->orWhere('stage_name', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_no', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        return view('qc.pending', compact('requests'));
    }

    public function show(QcRequest $qcRequest)
    {
        $qcRequest->load([
            'batch.product',
            'stageExecution',
            'results.qcTest',
            'submittedByUser',
            'decisionByUser',
        ]);

        return view('qc.show', compact('qcRequest'));
    }

    public function saveResults(Request $request, QcRequest $qcRequest)
    {
        $results = $request->input('results', []);

        foreach ($results as $resultId => $data) {
            $r = QcRequestResult::find($resultId);
            if ($r && $r->qc_request_id === $qcRequest->id) {
                $r->actual_value = $data['actual_value'] ?? null;
                $r->is_pass = isset($data['is_pass']);
                $r->remarks = $data['remarks'] ?? null;
                $r->tested_by = Auth::id();
                $r->tested_at = now();
                $r->save();
            }
        }

        return redirect()->back()->with('success', 'QC test observations saved.');
    }

    public function approve(Request $request, QcRequest $qcRequest)
    {
        try {
            $this->qcService->approve($qcRequest, Auth::id(), $request->input('remarks'));

            return redirect()->route('qc.pending')
                ->with('success', "QC Request {$qcRequest->qc_request_number} Approved! Next production stage is unlocked.");
        } catch (\Exception $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, QcRequest $qcRequest)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->qcService->reject($qcRequest, Auth::id(), $request->input('reason'));

            return redirect()->route('qc.pending')
                ->with('warning', "QC Request {$qcRequest->qc_request_number} REJECTED. The entire manufacturing batch has been marked as Rejected (Validation 15).");
        } catch (\Exception $e) {
            return back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    public function requestRework(Request $request, QcRequest $qcRequest)
    {
        $request->validate([
            'instructions' => 'required|string|max:1000',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $rework = $this->qcService->requestRework(
                $qcRequest,
                Auth::id(),
                $request->input('instructions'),
                $request->input('reason')
            );

            return redirect()->route('production.rework.show', $rework->id)
                ->with('success', "Rework instruction generated ({$rework->rework_number}). A new stage execution has been initialized (Validation 16 & 17).");
        } catch (\Exception $e) {
            return back()->with('error', 'Rework request failed: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $query = QcRequest::with(['batch.product', 'decisionByUser'])
            ->whereIn('status', ['Approved', 'Rejected', 'Rework Required']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('qc_request_number', 'like', "%{$search}%")
                  ->orWhere('decision_remarks', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('qc.history', compact('requests'));
    }
}
