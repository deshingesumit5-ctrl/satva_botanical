<?php

namespace App\Console\Commands;

use App\Models\BatchStageExecution;
use App\Services\QcService;
use Illuminate\Console\Command;

class BackfillMissingQcRequests extends Command
{
    protected $signature = 'qc:backfill-missing-requests';

    protected $description = 'Create missing QC requests idempotently for stage executions already in QC Pending status.';

    public function handle(QcService $qcService): int
    {
        $missingExecutions = BatchStageExecution::with(['batch', 'recipeStage'])
            ->where('status', 'QC Pending')
            ->whereDoesntHave('qcRequest')
            ->orderBy('id')
            ->get();

        if ($missingExecutions->isEmpty()) {
            $this->info('No missing QC requests found for QC Pending stage executions.');
            return self::SUCCESS;
        }

        $createdCount = 0;
        foreach ($missingExecutions as $execution) {
            $qcReq = $qcService->createForStageExecution(
                $execution,
                $execution->batch?->supervisor_id ?? 1
            );
            $createdCount++;
            $this->line("Created QC Request {$qcReq->qc_request_number} for Batch {$execution->batch?->batch_number}, Stage Execution #{$execution->id} ({$execution->stage_name}).");
        }

        $this->info("Backfill complete. Created {$createdCount} missing QC request(s).");
        return self::SUCCESS;
    }
}
