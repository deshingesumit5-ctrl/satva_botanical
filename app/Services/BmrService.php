<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

class BmrService
{
    /**
     * Assemble full BMR report dataset.
     */
    public function getBmrData(Batch $batch): array
    {
        $batch->load([
            'product.baseUom',
            'recipe',
            'recipeVersion.batchUom',
            'plant',
            'supervisor',
            'holds.heldByUser',
            'holds.resumedByUser',
            'requirements.material',
            'requirements.uom',
            'allocations.material',
            'allocations.stockLot',
            'allocations.uom',
            'consumptions.material',
            'consumptions.stockLot',
            'consumptions.uom',
            'stageExecutions.recipeStage.stage',
            'stageExecutions.equipment',
            'stageExecutions.activityExecutions',
            'stageExecutions.parameterReadings',
            'stageExecutions.outputs.uom',
            'stageExecutions.qcRequest.results.qcTest',
            'stageExecutions.qcRequest.decisionByUser',
            'reworks.requestedByUser',
            'reconciliation.reconciledByUser',
            'fgReceipts.warehouse',
            'cost',
        ]);

        $company = CompanySetting::first();

        return [
            'company' => $company,
            'batch' => $batch,
            'product' => $batch->product,
            'recipe' => $batch->recipe,
            'version' => $batch->recipeVersion,
            'supervisor' => $batch->supervisor,
            'holds' => $batch->holds,
            'requirements' => $batch->requirements,
            'allocations' => $batch->allocations,
            'consumptions' => $batch->consumptions,
            'stageExecutions' => $batch->stageExecutions,
            'reworks' => $batch->reworks,
            'reconciliation' => $batch->reconciliation,
            'fgReceipts' => $batch->fgReceipts,
            'cost' => $batch->cost,
        ];
    }

    /**
     * Generate BMR PDF document.
     */
    public function generatePdf(Batch $batch)
    {
        $data = $this->getBmrData($batch);
        $pdf = Pdf::loadView('bmr.pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }
}
