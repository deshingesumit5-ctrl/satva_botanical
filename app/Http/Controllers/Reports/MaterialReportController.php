<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\StockLot;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class MaterialReportController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLot::with(['material.baseUom', 'warehouse', 'storageLocation'])
            ->whereNotNull('material_id');

        if ($materialId = $request->input('material_id')) {
            $query->where('material_id', $materialId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lots = $query->latest()->paginate(25)->withQueryString();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('reports.material', compact('lots', 'materials', 'warehouses'));
    }

    public function export(Request $request)
    {
        $lots = StockLot::with(['material', 'warehouse'])->whereNotNull('material_id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="material_stock_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($lots) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Lot Number', 'Material Code', 'Material Name', 'Warehouse', 'Available Qty', 'Unit Cost', 'QC Status', 'Expiry Date']);
            foreach ($lots as $l) {
                fputcsv($file, [
                    $l->lot_number,
                    $l->material ? $l->material->material_code : '',
                    $l->material ? $l->material->name : '',
                    $l->warehouse ? $l->warehouse->name : '',
                    $l->available_qty,
                    $l->unit_cost,
                    $l->qc_status,
                    $l->expiry_date,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
