<?php

namespace App\Services;

use App\Models\RecoveryLot;
use App\Models\RecoveryLotConsumption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecoveryService
{
    /**
     * Consume recovered solvent in another batch (Validation 19).
     * Supports reuse across multiple batches (A generates 100L; B uses 40; C uses 30; remaining 30).
     */
    public function consume(int $recoveryLotId, float $qty, int $consumingBatchId, ?int $consumingStageId = null): RecoveryLotConsumption
    {
        return DB::transaction(function () use ($recoveryLotId, $qty, $consumingBatchId, $consumingStageId) {
            $lot = RecoveryLot::where('id', $recoveryLotId)->lockForUpdate()->firstOrFail();

            if ($lot->status === 'Exhausted' || (float)$lot->available_qty <= 0) {
                throw new RuntimeException("Validation Error (19): Duplicate recovered solvent consumption blocked. Lot {$lot->recovery_lot_number} is already exhausted.");
            }

            if ((float)$lot->available_qty < $qty) {
                throw new RuntimeException("Validation Error (19): Cannot consume {$qty} L. Only {$lot->available_qty} L available in Recovery Lot {$lot->recovery_lot_number}.");
            }

            $lot->consumed_qty = (float)$lot->consumed_qty + $qty;
            $lot->available_qty = (float)$lot->available_qty - $qty;
            $lot->remaining_qty = (float)$lot->remaining_qty - $qty;

            if ($lot->available_qty <= 0) {
                $lot->status = 'Exhausted';
            }
            $lot->save();

            $consumption = RecoveryLotConsumption::create([
                'recovery_lot_id' => $lot->id,
                'consuming_batch_id' => $consumingBatchId,
                'consuming_stage_id' => $consumingStageId,
                'consumed_qty' => $qty,
                'consumed_date' => now()->toDateString(),
                'created_by' => Auth::id() ?? 1,
            ]);

            AuditService::log('Inventory', 'Consume Recovered Solvent', "Lot: {$lot->recovery_lot_number}", null, [
                'consuming_batch_id' => $consumingBatchId,
                'consumed_qty' => $qty,
                'remaining_qty' => $lot->remaining_qty,
            ], 'Consumed recovered solvent in production batch');

            return $consumption;
        });
    }
}
