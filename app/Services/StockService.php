<?php

namespace App\Services;

use App\Models\StockLot;
use App\Models\StockLedger;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * Post an inward stock movement (GRN or Opening Stock).
     */
    public function inward(array $data): StockLot
    {
        return DB::transaction(function () use ($data) {
            $lotNumber = $data['lot_number'] ?? $this->docService->generate('Stock Lot Number');
            
            $lot = StockLot::create([
                'lot_number' => $lotNumber,
                'material_id' => $data['material_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'storage_location_id' => $data['storage_location_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'grn_id' => $data['grn_id'] ?? null,
                'grn_item_id' => $data['grn_item_id'] ?? null,
                'received_qty' => $data['quantity'],
                'reserved_qty' => 0,
                'consumed_qty' => 0,
                'returned_qty' => 0,
                'available_qty' => $data['available_qty'] ?? $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'qc_status' => $data['qc_status'] ?? 'QC Approved',
                'mfg_date' => $data['mfg_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => 'Active',
            ]);

            // Calculate current total balance in warehouse
            $currentBalance = StockLot::where('material_id', $lot->material_id)
                ->where('warehouse_id', $lot->warehouse_id)
                ->sum('available_qty');

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => $data['transaction_type'] ?? 'GRN Inward',
                'inward_qty' => $data['quantity'],
                'outward_qty' => 0,
                'balance_qty' => $currentBalance,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => (float)$data['quantity'] * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            return $lot;
        });
    }

    /**
     * Reserve/allocate stock from a lot. Validates available stock.
     */
    public function allocate(int $stockLotId, float $qty, string $referenceNo, ?int $batchId = null): StockLot
    {
        return DB::transaction(function () use ($stockLotId, $qty, $referenceNo, $batchId) {
            $lot = StockLot::where('id', $stockLotId)->lockForUpdate()->firstOrFail();

            if ($lot->qc_status === 'QC Rejected') {
                throw new RuntimeException("QC-rejected lot {$lot->lot_number} cannot be allocated.");
            }

            if ($lot->expiry_date && \Carbon\Carbon::parse($lot->expiry_date)->isPast()) {
                throw new RuntimeException("Expired lot {$lot->lot_number} cannot be allocated (Validation 10).");
            }

            if ($lot->qc_status === 'QC Hold') {
                throw new RuntimeException("Lot {$lot->lot_number} is currently in QC Hold and cannot be allocated.");
            }

            if ($lot->status !== 'Active') {
                throw new RuntimeException("Lot {$lot->lot_number} is not active.");
            }

            if ((float)$lot->available_qty < $qty) {
                throw new RuntimeException("Cannot allocate {$qty}. Only {$lot->available_qty} available in lot {$lot->lot_number}.");
            }

            $lot->available_qty = (float)$lot->available_qty - $qty;
            $lot->reserved_qty = (float)$lot->reserved_qty + $qty;
            $lot->save();

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => 'Material Allocation',
                'inward_qty' => 0,
                'outward_qty' => 0, // reserved in place
                'balance_qty' => $lot->available_qty,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => $qty * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => 'Batch',
                'reference_id' => $batchId,
                'reference_no' => $referenceNo,
                'remarks' => "Allocated {$qty} for batch {$referenceNo}",
                'created_by' => Auth::id(),
            ]);

            return $lot;
        });
    }

    /**
     * Consume stock (from reserved allocation or direct).
     */
    public function consume(int $stockLotId, float $qty, string $referenceNo, ?int $batchId = null, bool $fromReserved = true): StockLot
    {
        return DB::transaction(function () use ($stockLotId, $qty, $referenceNo, $batchId, $fromReserved) {
            $lot = StockLot::where('id', $stockLotId)->lockForUpdate()->firstOrFail();

            if ($fromReserved) {
                if ((float)$lot->reserved_qty < $qty) {
                    throw new RuntimeException("Cannot consume {$qty}. Only {$lot->reserved_qty} reserved in lot {$lot->lot_number}.");
                }
                $lot->reserved_qty = (float)$lot->reserved_qty - $qty;
            } else {
                if ((float)$lot->available_qty < $qty) {
                    throw new RuntimeException("Cannot consume {$qty}. Only {$lot->available_qty} available in lot {$lot->lot_number}.");
                }
                $lot->available_qty = (float)$lot->available_qty - $qty;
            }

            $lot->consumed_qty = (float)$lot->consumed_qty + $qty;
            if ($lot->available_qty <= 0 && $lot->reserved_qty <= 0) {
                $lot->status = 'Exhausted';
            }
            $lot->save();

            $currentBalance = StockLot::where('material_id', $lot->material_id)
                ->where('warehouse_id', $lot->warehouse_id)
                ->sum('available_qty');

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => 'Material Consumption',
                'inward_qty' => 0,
                'outward_qty' => $qty,
                'balance_qty' => $currentBalance,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => $qty * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => 'Batch',
                'reference_id' => $batchId,
                'reference_no' => $referenceNo,
                'remarks' => "Consumed {$qty} in batch {$referenceNo}",
                'created_by' => Auth::id(),
            ]);

            return $lot;
        });
    }

    /**
     * Return unused material to the exact lot.
     */
    public function returnStock(int $stockLotId, float $qty, string $referenceNo, ?int $batchId = null): StockLot
    {
        return DB::transaction(function () use ($stockLotId, $qty, $referenceNo, $batchId) {
            $lot = StockLot::where('id', $stockLotId)->lockForUpdate()->firstOrFail();

            $lot->available_qty = (float)$lot->available_qty + $qty;
            $lot->returned_qty = (float)$lot->returned_qty + $qty;
            if ($lot->status === 'Exhausted') {
                $lot->status = 'Active';
            }
            $lot->save();

            $currentBalance = StockLot::where('material_id', $lot->material_id)
                ->where('warehouse_id', $lot->warehouse_id)
                ->sum('available_qty');

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => 'Material Return',
                'inward_qty' => $qty,
                'outward_qty' => 0,
                'balance_qty' => $currentBalance,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => $qty * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => 'Batch',
                'reference_id' => $batchId,
                'reference_no' => $referenceNo,
                'remarks' => "Returned {$qty} from batch {$referenceNo}",
                'created_by' => Auth::id(),
            ]);

            return $lot;
        });
    }

    /**
     * Stock adjustment with mandatory reason and audit.
     */
    public function adjust(int $stockLotId, string $type, float $qty, string $reason, int $userId): StockLot
    {
        return DB::transaction(function () use ($stockLotId, $type, $qty, $reason, $userId) {
            $lot = StockLot::where('id', $stockLotId)->lockForUpdate()->firstOrFail();

            if ($type === 'Subtract') {
                if ((float)$lot->available_qty < $qty) {
                    throw new RuntimeException("Cannot subtract {$qty}. Only {$lot->available_qty} available in lot {$lot->lot_number}.");
                }
                $lot->available_qty = (float)$lot->available_qty - $qty;
                $inward = 0;
                $outward = $qty;
            } else {
                $lot->available_qty = (float)$lot->available_qty + $qty;
                $inward = $qty;
                $outward = 0;
            }

            if ($lot->available_qty <= 0 && $lot->reserved_qty <= 0) {
                $lot->status = 'Exhausted';
            } else {
                $lot->status = 'Active';
            }
            $lot->save();

            $currentBalance = StockLot::where('material_id', $lot->material_id)
                ->where('warehouse_id', $lot->warehouse_id)
                ->sum('available_qty');

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => 'Stock Adjustment',
                'inward_qty' => $inward,
                'outward_qty' => $outward,
                'balance_qty' => $currentBalance,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => $qty * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => 'Adjustment',
                'remarks' => "Adjustment ({$type}): {$reason}",
                'created_by' => $userId,
            ]);

            AuditService::log('Inventory', 'Stock Adjustment', "Lot: {$lot->lot_number}", null, [
                'type' => $type,
                'qty' => $qty,
                'reason' => $reason,
                'new_available' => $lot->available_qty,
            ], $reason);

            return $lot;
        });
    }

    /**
     * Transfer stock between warehouses / storage locations.
     */
    public function transfer(int $sourceLotId, int $destWarehouseId, ?int $destLocationId, float $qty, string $reason, int $userId): array
    {
        return DB::transaction(function () use ($sourceLotId, $destWarehouseId, $destLocationId, $qty, $reason, $userId) {
            $sourceLot = StockLot::where('id', $sourceLotId)->lockForUpdate()->firstOrFail();

            if ((float)$sourceLot->available_qty < $qty) {
                throw new RuntimeException("Cannot transfer {$qty}. Only {$sourceLot->available_qty} available in lot {$sourceLot->lot_number}.");
            }

            // Deduct from source lot
            $sourceLot->available_qty = (float)$sourceLot->available_qty - $qty;
            if ($sourceLot->available_qty <= 0 && $sourceLot->reserved_qty <= 0) {
                $sourceLot->status = 'Exhausted';
            }
            $sourceLot->save();

            $sourceBalance = StockLot::where('material_id', $sourceLot->material_id)
                ->where('product_id', $sourceLot->product_id)
                ->where('warehouse_id', $sourceLot->warehouse_id)
                ->sum('available_qty');

            // Record Outward Stock Ledger
            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $sourceLot->material_id,
                'product_id' => $sourceLot->product_id,
                'stock_lot_id' => $sourceLot->id,
                'transaction_type' => 'Stock Transfer Out',
                'inward_qty' => 0,
                'outward_qty' => $qty,
                'balance_qty' => $sourceBalance,
                'unit_cost' => $sourceLot->unit_cost,
                'total_cost' => $qty * (float)$sourceLot->unit_cost,
                'warehouse_id' => $sourceLot->warehouse_id,
                'storage_location_id' => $sourceLot->storage_location_id,
                'reference_type' => 'Transfer',
                'remarks' => "Transfer out to WH #{$destWarehouseId}: {$reason}",
                'created_by' => $userId,
            ]);

            // Create Destination Lot with same lot_number (or suffixed)
            $destLotNumber = $sourceLot->lot_number;
            $destLot = StockLot::create([
                'lot_number' => $destLotNumber,
                'material_id' => $sourceLot->material_id,
                'product_id' => $sourceLot->product_id,
                'warehouse_id' => $destWarehouseId,
                'storage_location_id' => $destLocationId,
                'supplier_id' => $sourceLot->supplier_id,
                'grn_id' => $sourceLot->grn_id,
                'grn_item_id' => $sourceLot->grn_item_id,
                'received_qty' => $qty,
                'reserved_qty' => 0,
                'consumed_qty' => 0,
                'returned_qty' => 0,
                'available_qty' => $qty,
                'unit_cost' => $sourceLot->unit_cost,
                'qc_status' => $sourceLot->qc_status,
                'mfg_date' => $sourceLot->mfg_date,
                'expiry_date' => $sourceLot->expiry_date,
                'status' => 'Active',
            ]);

            $destBalance = StockLot::where('material_id', $destLot->material_id)
                ->where('product_id', $destLot->product_id)
                ->where('warehouse_id', $destWarehouseId)
                ->sum('available_qty');

            // Record Inward Stock Ledger at Dest
            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $destLot->material_id,
                'product_id' => $destLot->product_id,
                'stock_lot_id' => $destLot->id,
                'transaction_type' => 'Stock Transfer In',
                'inward_qty' => $qty,
                'outward_qty' => 0,
                'balance_qty' => $destBalance,
                'unit_cost' => $destLot->unit_cost,
                'total_cost' => $qty * (float)$destLot->unit_cost,
                'warehouse_id' => $destWarehouseId,
                'storage_location_id' => $destLocationId,
                'reference_type' => 'Transfer',
                'remarks' => "Transfer in from WH #{$sourceLot->warehouse_id}: {$reason}",
                'created_by' => $userId,
            ]);

            AuditService::log('Inventory', 'Stock Transfer', "Lot: {$sourceLot->lot_number}", null, [
                'from_wh' => $sourceLot->warehouse_id,
                'to_wh' => $destWarehouseId,
                'qty' => $qty,
                'reason' => $reason,
            ], $reason);

            return ['source' => $sourceLot, 'destination' => $destLot];
        });
    }

    /**
     * Dispatch stock for sales. Validates available stock.
     */
    public function dispatch(int $stockLotId, float $qty, string $dispatchNo, int $dispatchId): StockLot
    {
        return DB::transaction(function () use ($stockLotId, $qty, $dispatchNo, $dispatchId) {
            $lot = StockLot::where('id', $stockLotId)->lockForUpdate()->firstOrFail();

            if ($lot->qc_status === 'QC Rejected') {
                throw new RuntimeException("QC-rejected lot {$lot->lot_number} cannot be sold or dispatched.");
            }

            if ((float)$lot->available_qty < $qty) {
                throw new RuntimeException("Sale above available stock blocked: Requested {$qty}, available {$lot->available_qty} in lot {$lot->lot_number}.");
            }

            $lot->available_qty = (float)$lot->available_qty - $qty;
            if ($lot->available_qty <= 0 && $lot->reserved_qty <= 0) {
                $lot->status = 'Exhausted';
            }
            $lot->save();

            $currentBalance = StockLot::where('material_id', $lot->material_id)
                ->where('product_id', $lot->product_id)
                ->where('warehouse_id', $lot->warehouse_id)
                ->sum('available_qty');

            StockLedger::create([
                'transaction_date' => now(),
                'transaction_number' => $this->docService->generate('Stock Ledger Entry'),
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'stock_lot_id' => $lot->id,
                'transaction_type' => 'Sales Dispatch',
                'inward_qty' => 0,
                'outward_qty' => $qty,
                'balance_qty' => $currentBalance,
                'unit_cost' => $lot->unit_cost,
                'total_cost' => $qty * (float)$lot->unit_cost,
                'warehouse_id' => $lot->warehouse_id,
                'reference_type' => 'Dispatch',
                'reference_id' => $dispatchId,
                'reference_no' => $dispatchNo,
                'remarks' => "Dispatched {$qty} on {$dispatchNo}",
                'created_by' => Auth::id(),
            ]);

            return $lot;
        });
    }

    /**
     * Calculate lot unit cost based on valuation method (Weighted Average or FIFO).
     */
    public function getLotValuationCost(int $stockLotId): float
    {
        $lot = StockLot::find($stockLotId);
        if (!$lot) {
            return 0.0;
        }

        $method = SystemSetting::getVal('inventory_valuation_method', 'Weighted Average');
        
        if ($method === 'FIFO') {
            return (float)$lot->unit_cost;
        }

        // Weighted Average across active lots for this material/product
        $query = StockLot::where('warehouse_id', $lot->warehouse_id);
        if ($lot->material_id) {
            $query->where('material_id', $lot->material_id);
        } else {
            $query->where('product_id', $lot->product_id);
        }

        $lots = $query->where('available_qty', '>', 0)->get();
        $totalQty = $lots->sum('available_qty');
        if ($totalQty <= 0) {
            return (float)$lot->unit_cost;
        }

        $totalValue = $lots->sum(fn($l) => (float)$l->available_qty * (float)$l->unit_cost);
        return round($totalValue / $totalQty, 2);
    }
}
