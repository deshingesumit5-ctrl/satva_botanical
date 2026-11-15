<?php

namespace App\Services;

use App\Models\WipRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WipService
{
    /**
     * Consume WIP in a subsequent stage (Validation 18).
     * Supports partial consumption, tracks balance, prevents duplicate/excess consumption.
     */
    public function consume(int $wipId, float $qty, int $consumingBatchId, int $consumingStageId): WipRecord
    {
        return DB::transaction(function () use ($wipId, $qty, $consumingBatchId, $consumingStageId) {
            $wip = WipRecord::where('id', $wipId)->lockForUpdate()->firstOrFail();

            if ($wip->status === 'Fully Consumed' || (float)$wip->available_qty <= 0) {
                throw new RuntimeException("Validation Error (18): Duplicate WIP consumption blocked. WIP {$wip->wip_number} is already fully consumed.");
            }

            if ((float)$wip->available_qty < $qty) {
                throw new RuntimeException("Validation Error (18): Cannot consume {$qty}. Only {$wip->available_qty} available in WIP {$wip->wip_number}.");
            }

            $wip->consumed_qty = (float)$wip->consumed_qty + $qty;
            $wip->available_qty = (float)$wip->available_qty - $qty;

            if ($wip->available_qty <= 0) {
                $wip->status = 'Fully Consumed';
            } else {
                $wip->status = 'Partially Consumed';
            }
            $wip->destination_stage_id = $consumingStageId;
            $wip->save();

            AuditService::log('Production', 'Consume WIP', "WIP: {$wip->wip_number}", null, [
                'consuming_batch_id' => $consumingBatchId,
                'consuming_stage_id' => $consumingStageId,
                'consumed_qty' => $qty,
                'remaining_available' => $wip->available_qty,
            ], 'Consumed intermediate WIP');

            return $wip;
        });
    }
}
