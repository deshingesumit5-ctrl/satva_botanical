<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Services\BmrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BmrController extends Controller
{
    protected BmrService $bmrService;

    public function __construct(BmrService $bmrService)
    {
        $this->bmrService = $bmrService;
    }

    public function show(Batch $batch)
    {
        $data = $this->bmrService->getBmrData($batch);
        return view('production.bmr.show', $data);
    }

    public function pdf(Batch $batch)
    {
        $data = $this->bmrService->getBmrData($batch);

        $pdf = Pdf::loadView('production.bmr.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("BMR_{$batch->batch_no}.pdf");
    }
}
