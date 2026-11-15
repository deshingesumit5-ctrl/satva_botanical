<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ReworkRecord;
use Illuminate\Http\Request;

class ReworkController extends Controller
{
    public function index(Request $request)
    {
        $query = ReworkRecord::with(['batch.product', 'stage', 'newExecution', 'requestedByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('rework_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reworks = $query->latest()->paginate(10)->withQueryString();

        return view('production.rework.index', compact('reworks'));
    }

    public function show(ReworkRecord $rework)
    {
        $rework->load([
            'batch.product',
            'stage',
            'newExecution.activities',
            'newExecution.parameters',
            'newExecution.outputs',
            'requestedByUser',
        ]);

        return view('production.rework.show', compact('rework'));
    }
}
