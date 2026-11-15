<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\BatchMaterialAllocation;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchReconciliation;
use App\Models\BatchStageExecution;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\ExpenseAllocation;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Material;
use App\Models\MonthlyExpense;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\QcRequest;
use App\Models\QcRequestResult;
use App\Models\QcTest;
use App\Models\Recipe;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockLedger;
use App\Models\StockLot;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first() ?? User::first();
        $plant = Plant::first();
        $whRm = Warehouse::where('code', 'WH-RM')->first() ?? Warehouse::first();
        $whFg = Warehouse::where('code', 'WH-FG')->first() ?? Warehouse::first();
        $whWip = Warehouse::where('code', 'WH-WIP')->first() ?? Warehouse::first();
        $kg = Uom::where('uom_code', 'KG')->first();
        $ltr = Uom::where('uom_code', 'LTR')->first();
        $product = Product::first();
        $recipe = Recipe::with('versions.stages.stage')->first();
        $version = $recipe ? $recipe->versions->first() : null;

        // 1. Storage Locations
        $locRm = StorageLocation::firstOrCreate(
            ['location_code' => 'RM-A1'],
            ['name' => 'Rack A-1', 'warehouse_id' => $whRm->id, 'rack_no' => 'A', 'bin_no' => '1', 'status' => 'Active']
        );
        $locFg = StorageLocation::firstOrCreate(
            ['location_code' => 'FG-B1'],
            ['name' => 'Bay 01', 'warehouse_id' => $whFg->id, 'rack_no' => 'B', 'bin_no' => '1', 'status' => 'Active']
        );

        // 2. Suppliers
        $sup1 = Supplier::firstOrCreate(
            ['code' => 'SUP-001'],
            [
                'name' => 'Himalayan Herbal Extracts Ltd',
                'contact_person' => 'Rajesh Sharma',
                'mobile' => '9876543210',
                'email' => 'sales@himalayanherbs.com',
                'address' => 'Plot 42, Industrial Area, Haridwar, Uttarakhand',
                'gstin' => '05AAACH1234F1Z1',
                'pan' => 'AAACH1234F',
                'payment_terms' => '30 Days Net',
                'status' => 'Active',
            ]
        );

        $sup2 = Supplier::firstOrCreate(
            ['code' => 'SUP-002'],
            [
                'name' => 'Apex Organic Solvents Pvt Ltd',
                'contact_person' => 'Vikram Patel',
                'mobile' => '9822012345',
                'email' => 'orders@apexsolvents.com',
                'address' => 'GIDC Estate, Ankleshwar, Gujarat',
                'gstin' => '24AABCA5678K1Z5',
                'pan' => 'AABCA5678K',
                'payment_terms' => '15 Days Net',
                'status' => 'Active',
            ]
        );

        // 3. Customers
        $cust1 = Customer::firstOrCreate(
            ['code' => 'CUST-001'],
            [
                'name' => 'BioNutra Formulations Pvt Ltd',
                'contact_person' => 'Dr. Anita Desai',
                'mobile' => '9988776655',
                'email' => 'procurement@bionutra.com',
                'billing_address' => 'Tech Park, Whitefield, Bengaluru, Karnataka - 560066',
                'shipping_address' => 'Unit 3, KIADB Industrial Area, Hoskote, Bengaluru',
                'gstin' => '29AABCB9876L1Z2',
                'pan' => 'AABCB9876L',
                'payment_terms' => '30 Days Net',
                'credit_limit' => 2000000,
                'status' => 'Active',
            ]
        );

        // 4. Materials & Stock Lots
        $rawMat = Material::where('material_code', 'RM-GARC-RIND')->first() ?? Material::first();
        $solventMat = Material::where('material_code', 'CH-IPA-SOL')->first();

        $lotRm = null;
        if ($rawMat) {
            $lotRm = StockLot::firstOrCreate(
                ['lot_number' => 'LOT-RM-2026-001'],
                [
                    'material_id' => $rawMat->id,
                    'warehouse_id' => $whRm->id,
                    'storage_location_id' => $locRm->id,
                    'supplier_id' => $sup1->id,
                    'received_qty' => 5000.00,
                    'reserved_qty' => 1000.00,
                    'consumed_qty' => 0.00,
                    'returned_qty' => 0.00,
                    'available_qty' => 4000.00,
                    'unit_cost' => 120.00,
                    'qc_status' => 'QC Approved',
                    'mfg_date' => Carbon::now()->subMonths(1)->toDateString(),
                    'expiry_date' => Carbon::now()->addYears(2)->toDateString(),
                    'status' => 'Active',
                ]
            );

            StockLedger::firstOrCreate(
                ['transaction_number' => 'OPENING-RM-001'],
                [
                    'transaction_date' => Carbon::now()->subMonths(1),
                    'material_id' => $rawMat->id,
                    'warehouse_id' => $whRm->id,
                    'stock_lot_id' => $lotRm->id,
                    'transaction_type' => 'Opening Stock',
                    'inward_qty' => 5000.00,
                    'outward_qty' => 0.00,
                    'balance_qty' => 5000.00,
                    'unit_cost' => 120.00,
                    'total_cost' => 600000.00,
                    'reference_no' => 'OPENING-RM-001',
                    'created_by' => $admin->id,
                ]
            );
        }

        if ($solventMat) {
            StockLot::firstOrCreate(
                ['lot_number' => 'LOT-SLV-2026-001'],
                [
                    'material_id' => $solventMat->id,
                    'warehouse_id' => $whRm->id,
                    'storage_location_id' => $locRm->id,
                    'supplier_id' => $sup2->id,
                    'received_qty' => 10000.00,
                    'reserved_qty' => 3000.00,
                    'consumed_qty' => 0.00,
                    'returned_qty' => 0.00,
                    'available_qty' => 7000.00,
                    'unit_cost' => 65.00,
                    'qc_status' => 'QC Approved',
                    'mfg_date' => Carbon::now()->subMonths(1)->toDateString(),
                    'expiry_date' => Carbon::now()->addYears(1)->toDateString(),
                    'status' => 'Active',
                ]
            );
        }

        // FG Stock Lot
        $lotFg = null;
        if ($product) {
            $lotFg = StockLot::firstOrCreate(
                ['lot_number' => 'LOT-FG-2026-001'],
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $whFg->id,
                    'storage_location_id' => $locFg->id,
                    'received_qty' => 500.00,
                    'reserved_qty' => 100.00,
                    'consumed_qty' => 0.00,
                    'returned_qty' => 0.00,
                    'available_qty' => 400.00,
                    'unit_cost' => 850.00,
                    'qc_status' => 'QC Approved',
                    'mfg_date' => Carbon::now()->subWeeks(2)->toDateString(),
                    'expiry_date' => Carbon::now()->addYears(2)->toDateString(),
                    'status' => 'Active',
                ]
            );
        }

        // 5. Purchase Requisition
        $pr = PurchaseRequisition::firstOrCreate(
            ['pr_number' => 'PR-2026-0001'],
            [
                'request_date' => Carbon::now()->subWeeks(3)->toDateString(),
                'department_id' => $plant->id ?? 1,
                'requested_by' => $admin->id,
                'priority' => 'High',
                'reason' => 'Quarterly raw material restocking for Garcinia production',
                'status' => 'Approved',
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subWeeks(3),
            ]
        );

        if ($rawMat) {
            PurchaseRequisitionItem::firstOrCreate(
                ['purchase_requisition_id' => $pr->id, 'material_id' => $rawMat->id],
                ['required_qty' => 5000.00, 'uom_id' => $kg->id, 'required_date' => Carbon::now()->addDays(7)->toDateString()]
            );
        }

        // 6. Purchase Order
        $po = PurchaseOrder::firstOrCreate(
            ['po_number' => 'PO-2026-0001'],
            [
                'purchase_requisition_id' => $pr->id,
                'supplier_id' => $sup1->id,
                'po_date' => Carbon::now()->subWeeks(2)->toDateString(),
                'expected_delivery_date' => Carbon::now()->subDays(5)->toDateString(),
                'delivery_address' => 'Sattva Botanical Factory, Plant 1',
                'payment_terms' => '30 Days Net',
                'terms_conditions' => 'Standard commercial terms as per agreement.',
                'subtotal' => 600000.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 600000.00,
                'cgst_amount' => 15000.00,
                'sgst_amount' => 15000.00,
                'igst_amount' => 0.00,
                'total_amount' => 630000.00,
                'status' => 'Approved',
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subWeeks(2),
            ]
        );

        $poItem = null;
        if ($rawMat) {
            $poItem = PurchaseOrderItem::firstOrCreate(
                ['purchase_order_id' => $po->id, 'material_id' => $rawMat->id],
                [
                    'quantity' => 5000.00,
                    'received_qty' => 5000.00,
                    'uom_id' => $kg->id,
                    'rate' => 120.00,
                    'discount_percent' => 0.00,
                    'taxable_amount' => 600000.00,
                    'gst_rate' => 5.00,
                    'gst_amount' => 30000.00,
                    'total_amount' => 630000.00,
                    'remarks' => 'Grade A Dried Rind',
                ]
            );
        }

        // 7. GRN
        $grn = Grn::firstOrCreate(
            ['grn_number' => 'GRN-2026-0001'],
            [
                'purchase_order_id' => $po->id,
                'supplier_id' => $sup1->id,
                'receipt_date' => Carbon::now()->subDays(10)->toDateString(),
                'warehouse_id' => $whRm->id,
                'invoice_number' => 'INV-SUP-8891',
                'invoice_date' => Carbon::now()->subDays(12)->toDateString(),
                'qc_status' => 'QC Approved',
                'status' => 'Completed',
                'remarks' => 'Consignment in good condition. Moisture tested.',
                'received_by' => $admin->id,
            ]
        );

        if ($poItem && $rawMat) {
            GrnItem::firstOrCreate(
                ['grn_id' => $grn->id, 'purchase_order_item_id' => $poItem->id],
                [
                    'material_id' => $rawMat->id,
                    'po_qty' => 5000.00,
                    'received_qty' => 5000.00,
                    'accepted_qty' => 5000.00,
                    'rejected_qty' => 0.00,
                    'shortage_excess' => 0.00,
                    'lot_number' => 'LOT-RM-2026-001',
                    'mfg_date' => Carbon::now()->subMonths(1)->toDateString(),
                    'expiry_date' => Carbon::now()->addYears(2)->toDateString(),
                    'warehouse_id' => $whRm->id,
                    'qc_required' => true,
                    'qc_status' => 'QC Approved',
                    'rate' => 120.00,
                ]
            );
        }

        // 8. Production Plan
        $plan = ProductionPlan::firstOrCreate(
            ['plan_number' => 'PP-2026-0001'],
            [
                'product_id' => $product ? $product->id : 1,
                'recipe_id' => $recipe ? $recipe->id : 1,
                'recipe_version_id' => $version ? $version->id : 1,
                'planned_qty' => 150.00,
                'uom_id' => $kg->id,
                'planned_start_date' => Carbon::now()->subDays(4)->toDateString(),
                'required_completion_date' => Carbon::now()->addDays(2)->toDateString(),
                'plant_id' => $plant->id ?? 1,
                'warehouse_id' => $whRm->id,
                'priority' => 'High',
                'remarks' => 'Batch planned for BioNutra scheduled delivery',
                'created_by' => $admin->id,
                'status' => 'Approved',
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(4),
            ]
        );

        // 9. Production Batch
        $batch = Batch::firstOrCreate(
            ['batch_number' => 'BAT-2026-0001'],
            [
                'production_plan_id' => $plan->id,
                'product_id' => $product ? $product->id : 1,
                'recipe_id' => $recipe ? $recipe->id : 1,
                'recipe_version_id' => $version ? $version->id : 1,
                'planned_qty' => 150.00,
                'actual_qty' => 148.50,
                'uom_id' => $kg->id,
                'production_date' => Carbon::now()->subDays(3)->toDateString(),
                'planned_start_date' => Carbon::now()->subDays(3)->toDateString(),
                'planned_end_date' => Carbon::now()->addDays(1)->toDateString(),
                'actual_start_time' => Carbon::now()->subDays(3)->setTime(8, 0),
                'actual_end_time' => null,
                'plant_id' => $plant->id ?? 1,
                'supervisor_id' => $admin->id,
                'supervisor_name' => 'Admin User',
                'operator_count' => 4,
                'status' => 'In Progress',
                'remarks' => 'Standard commercial extraction batch',
            ]
        );

        // Batch stage executions & allocations
        $firstStageExec = null;
        $firstStage = null;
        if ($version && $version->stages) {
            foreach ($version->stages as $stg) {
                if (!$firstStage) {
                    $firstStage = $stg;
                }
                $status = ($stg->sequence_no <= 2) ? 'Completed' : (($stg->sequence_no == 3) ? 'In Progress' : 'Pending');
                $exec = BatchStageExecution::firstOrCreate(
                    ['batch_id' => $batch->id, 'recipe_stage_id' => $stg->id],
                    [
                        'execution_number' => $stg->sequence_no,
                        'stage_name' => $stg->stage ? $stg->stage->name : 'Stage ' . $stg->sequence_no,
                        'sequence_no' => $stg->sequence_no,
                        'occurrence_no' => $stg->occurrence_no ?? 1,
                        'execution_type' => 'Original',
                        'status' => $status,
                        'qc_status' => 'Approved',
                        'start_time' => Carbon::now()->subDays(3),
                        'completed_at' => $status === 'Completed' ? Carbon::now()->subDays(2) : null,
                    ]
                );
                if (!$firstStageExec) {
                    $firstStageExec = $exec;
                }
            }
        }

        if ($firstStage && $rawMat) {
            BatchMaterialAllocation::firstOrCreate(
                ['batch_id' => $batch->id, 'recipe_stage_id' => $firstStage->id, 'material_id' => $rawMat->id],
                [
                    'stock_lot_id' => $lotRm ? $lotRm->id : null,
                    'uom_id' => $kg->id,
                    'allocated_qty' => 1000.00,
                    'allocated_at' => Carbon::now()->subDays(3),
                    'allocated_by' => $admin->id,
                ]
            );

            BatchMaterialConsumption::firstOrCreate(
                ['batch_id' => $batch->id, 'recipe_stage_id' => $firstStage->id, 'material_id' => $rawMat->id],
                [
                    'stock_lot_id' => $lotRm ? $lotRm->id : null,
                    'allocated_qty' => 1000.00,
                    'actual_consumed_qty' => 1000.00,
                    'returned_qty' => 0.00,
                    'net_consumed_qty' => 1000.00,
                    'uom_id' => $kg->id,
                    'entered_by' => $admin->id,
                ]
            );
        }

        // Batch Reconciliation record
        BatchReconciliation::firstOrCreate(
            ['batch_id' => $batch->id],
            [
                'total_input_qty' => 4000.00,
                'raw_material_qty' => 1000.00,
                'solvent_qty' => 3000.00,
                'water_qty' => 0.00,
                'chemical_qty' => 0.00,
                'other_input_qty' => 0.00,
                'total_output_qty' => 3908.50,
                'fg_qty' => 148.50,
                'wip_qty' => 490.00,
                'cake_qty' => 0.00,
                'liquid_qty' => 0.00,
                'recovered_solvent_qty' => 2420.00,
                'by_product_qty' => 0.00,
                'spent_qty' => 850.00,
                'waste_qty' => 0.00,
                'material_returns_qty' => 0.00,
                'unaccounted_difference_qty' => 91.50,
                'reconciled_at' => Carbon::now(),
                'reconciled_by' => $admin->id,
                'remarks' => 'Process mass balance verified without hardcoded loss rules.',
            ]
        );

        // Batch Cost record
        BatchCost::firstOrCreate(
            ['batch_id' => $batch->id],
            [
                'direct_material_cost' => 120000.00,
                'labour_cost' => 8400.00,
                'electricity_cost' => 14500.00,
                'maintenance_cost' => 5000.00,
                'equipment_cost' => 3200.00,
                'misc_cost' => 2000.00,
                'other_cost' => 0.00,
                'total_batch_cost' => 153100.00,
                'finished_qty' => 148.50,
                'cost_per_unit' => 1030.98,
                'calculated_at' => Carbon::now(),
            ]
        );

        // 10. QC Request
        if ($firstStageExec) {
            $qcReq = QcRequest::firstOrCreate(
                ['qc_request_number' => 'QC-REQ-2026-0001'],
                [
                    'batch_id' => $batch->id,
                    'batch_stage_execution_id' => $firstStageExec->id,
                    'stage_name' => 'Final Extraction & Assay',
                    'request_date' => Carbon::now()->subDays(2),
                    'submitted_by' => $admin->id,
                    'status' => 'Approved',
                    'decision_at' => Carbon::now()->subDay(),
                    'decision_by' => $admin->id,
                    'decision_remarks' => 'Assay and moisture meet specification standards.',
                ]
            );

            $qcTest = QcTest::first();
            if ($qcTest) {
                QcRequestResult::firstOrCreate(
                    ['qc_request_id' => $qcReq->id, 'qc_test_id' => $qcTest->id],
                    [
                        'test_name' => $qcTest->name,
                        'test_method' => 'HPLC',
                        'result_type' => 'Numeric',
                        'uom' => '%',
                        'expected_min' => '60.0',
                        'expected_max' => '65.0',
                        'actual_value' => '61.4',
                        'result_status' => 'Pass',
                        'remarks' => 'Assay 61.4% complies with standard 60% specification',
                    ]
                );
            }
        }

        // 11. Sales Order
        $so = SalesOrder::firstOrCreate(
            ['so_number' => 'SO-2026-0001'],
            [
                'customer_id' => $cust1->id,
                'order_date' => Carbon::now()->subDays(5)->toDateString(),
                'delivery_date' => Carbon::now()->addDays(5)->toDateString(),
                'payment_terms' => '30 Days Net',
                'subtotal' => 150000.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 150000.00,
                'cgst_amount' => 9000.00,
                'sgst_amount' => 9000.00,
                'igst_amount' => 0.00,
                'total_amount' => 168000.00,
                'status' => 'Confirmed',
                'created_by' => $admin->id,
            ]
        );

        $soItem = null;
        if ($product) {
            $soItem = SalesOrderItem::firstOrCreate(
                ['sales_order_id' => $so->id, 'product_id' => $product->id],
                [
                    'item_name' => $product->name,
                    'quantity' => 100.00,
                    'dispatched_qty' => 100.00,
                    'uom_id' => $kg->id,
                    'rate' => 1500.00,
                    'discount_percent' => 0.00,
                    'taxable_amount' => 150000.00,
                    'gst_rate' => 12.00,
                    'gst_amount' => 18000.00,
                    'total_amount' => 168000.00,
                ]
            );
        }

        // 12. Dispatch
        $dsp = Dispatch::firstOrCreate(
            ['dispatch_number' => 'DSP-2026-0001'],
            [
                'sales_order_id' => $so->id,
                'customer_id' => $cust1->id,
                'dispatch_date' => Carbon::now()->subDays(2)->toDateString(),
                'transport_details' => 'SafeXpress Logistics (LR-882910)',
                'vehicle_number' => 'KA-01-MJ-9988',
                'invoice_reference' => 'INV-2026-0001',
                'status' => 'Dispatched',
                'remarks' => 'Handle with care - food grade certified packaging',
                'created_by' => $admin->id,
            ]
        );

        if ($soItem && $product && $lotFg) {
            DispatchItem::firstOrCreate(
                ['dispatch_id' => $dsp->id, 'sales_order_item_id' => $soItem->id],
                [
                    'product_id' => $product->id,
                    'stock_lot_id' => $lotFg->id,
                    'quantity' => 100.00,
                    'uom_id' => $kg->id,
                ]
            );
        }

        // 13. Sales Invoice
        $inv = SalesInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-0001'],
            [
                'customer_id' => $cust1->id,
                'sales_order_id' => $so->id,
                'dispatch_id' => $dsp->id,
                'invoice_date' => Carbon::now()->subDays(2)->toDateString(),
                'due_date' => Carbon::now()->addDays(28)->toDateString(),
                'taxable_amount' => 150000.00,
                'discount_amount' => 0.00,
                'cgst_amount' => 9000.00,
                'sgst_amount' => 9000.00,
                'igst_amount' => 0.00,
                'total_amount' => 168000.00,
                'paid_amount' => 68000.00,
                'balance_amount' => 100000.00,
                'payment_status' => 'Partially Paid',
                'created_by' => $admin->id,
            ]
        );

        if ($product) {
            SalesInvoiceItem::firstOrCreate(
                ['sales_invoice_id' => $inv->id, 'product_id' => $product->id],
                [
                    'item_name' => $product->name,
                    'quantity' => 100.00,
                    'uom_id' => $kg->id,
                    'rate' => 1500.00,
                    'discount_percent' => 0.00,
                    'taxable_amount' => 150000.00,
                    'gst_rate' => 12.00,
                    'gst_amount' => 18000.00,
                    'total_amount' => 168000.00,
                ]
            );
        }

        // 14. Customer Receipt
        CustomerReceipt::firstOrCreate(
            ['receipt_number' => 'RCPT-2026-0001'],
            [
                'customer_id' => $cust1->id,
                'sales_invoice_id' => $inv->id,
                'receipt_date' => Carbon::now()->subDay()->toDateString(),
                'amount' => 68000.00,
                'payment_mode' => 'Bank Transfer',
                'reference_number' => 'UTR9988776655',
                'remarks' => 'Part payment received via HDFC Bank',
                'created_by' => $admin->id,
            ]
        );

        // 15. Purchase Invoice & Supplier Payment
        $pi = PurchaseInvoice::firstOrCreate(
            ['internal_invoice_number' => 'PINV-2026-0001'],
            [
                'supplier_invoice_number' => 'SUP-INV-8891',
                'supplier_id' => $sup1->id,
                'purchase_order_id' => $po->id,
                'grn_id' => $grn->id,
                'invoice_date' => Carbon::now()->subDays(8)->toDateString(),
                'taxable_amount' => 600000.00,
                'cgst_amount' => 15000.00,
                'sgst_amount' => 15000.00,
                'igst_amount' => 0.00,
                'total_amount' => 630000.00,
                'paid_amount' => 300000.00,
                'balance_amount' => 330000.00,
                'payment_status' => 'Partially Paid',
                'created_by' => $admin->id,
            ]
        );

        SupplierPayment::firstOrCreate(
            ['payment_number' => 'PMT-2026-0001'],
            [
                'supplier_id' => $sup1->id,
                'purchase_invoice_id' => $pi->id,
                'payment_date' => Carbon::now()->subDays(3)->toDateString(),
                'amount' => 300000.00,
                'payment_mode' => 'Bank Transfer',
                'reference_number' => 'RTGS77112233',
                'remarks' => '50% advance against PO delivery',
                'created_by' => $admin->id,
            ]
        );

        // 16. Monthly Expense & Expense Allocation
        MonthlyExpense::firstOrCreate(
            ['expense_number' => 'EXP-2026-0001'],
            [
                'month' => (int)Carbon::now()->format('m'),
                'year' => (int)Carbon::now()->format('Y'),
                'expense_category' => 'Electricity',
                'amount' => 45000.00,
                'description' => 'Factory power bill for extraction plant',
                'entered_by' => $admin->id,
                'entry_date' => Carbon::now()->toDateString(),
                'status' => 'Approved',
            ]
        );

        ExpenseAllocation::firstOrCreate(
            ['month' => (int)Carbon::now()->format('m'), 'year' => (int)Carbon::now()->format('Y'), 'expense_category' => 'Electricity'],
            [
                'total_expense' => 45000.00,
                'eligible_batch_count' => 1,
                'allocation_method' => 'Batch Weight',
                'allocated_amount' => 14500.00,
                'allocation_date' => Carbon::now()->toDateString(),
                'posted_by' => $admin->id,
                'status' => 'Posted',
            ]
        );
    }
}
