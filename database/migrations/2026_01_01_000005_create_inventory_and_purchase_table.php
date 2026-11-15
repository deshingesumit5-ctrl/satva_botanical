<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->unique();
            $table->date('request_date');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->text('reason')->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Rejected', 'Converted'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('required_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->date('required_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Partially Received', 'Fully Received', 'Closed', 'Cancelled'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity', 12, 4);
            $table->decimal('received_qty', 12, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('rate', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('receipt_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->enum('qc_status', ['Pending', 'QC Hold', 'QC Approved', 'QC Rejected'])->default('Pending');
            $table->enum('status', ['Draft', 'Received', 'Completed', 'Cancelled'])->default('Received');
            $table->text('remarks')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('grns')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('po_qty', 12, 4)->default(0);
            $table->decimal('received_qty', 12, 4);
            $table->decimal('accepted_qty', 12, 4)->default(0);
            $table->decimal('rejected_qty', 12, 4)->default(0);
            $table->decimal('shortage_excess', 12, 4)->default(0);
            $table->string('lot_number', 50)->index();
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->boolean('qc_required')->default(true);
            $table->enum('qc_status', ['QC Hold', 'QC Approved', 'QC Rejected'])->default('QC Hold');
            $table->decimal('rate', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->string('lot_number', 50)->index();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('grn_id')->nullable()->constrained('grns')->nullOnDelete();
            $table->foreignId('grn_item_id')->nullable()->constrained('grn_items')->nullOnDelete();
            $table->decimal('received_qty', 12, 4);
            $table->decimal('reserved_qty', 12, 4)->default(0);
            $table->decimal('consumed_qty', 12, 4)->default(0);
            $table->decimal('returned_qty', 12, 4)->default(0);
            $table->decimal('available_qty', 12, 4);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->enum('qc_status', ['QC Hold', 'QC Approved', 'QC Rejected', 'Released'])->default('QC Approved');
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['Active', 'Exhausted', 'Blocked', 'Expired'])->default('Active');
            $table->timestamps();
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->dateTime('transaction_date')->index();
            $table->string('transaction_number', 60)->index();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained('stock_lots')->nullOnDelete();
            $table->string('transaction_type', 50)->index(); // GRN Inward, Material Allocation, Material Issue, Material Consumption, Material Return, etc.
            $table->decimal('inward_qty', 12, 4)->default(0);
            $table->decimal('outward_qty', 12, 4)->default(0);
            $table->decimal('balance_qty', 12, 4)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 60)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->date('transfer_date');
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->decimal('quantity', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->text('reason')->nullable();
            $table->foreignId('transferred_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->date('adjustment_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->enum('adjustment_type', ['Add', 'Subtract']);
            $table->decimal('quantity', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->text('reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('wip_records', function (Blueprint $table) {
            $table->id();
            $table->string('wip_number', 50)->unique();
            $table->unsignedBigInteger('batch_id')->index();
            $table->unsignedBigInteger('source_stage_id')->index();
            $table->unsignedBigInteger('source_execution_id')->index();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('material_name', 150);
            $table->decimal('generated_qty', 12, 4);
            $table->decimal('consumed_qty', 12, 4)->default(0);
            $table->decimal('available_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->enum('qc_status', ['Pending', 'Approved', 'Rejected'])->default('Approved');
            $table->unsignedBigInteger('destination_stage_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('status', ['Available', 'Partially Consumed', 'Fully Consumed'])->default('Available');
            $table->timestamps();
        });

        Schema::create('recovery_lots', function (Blueprint $table) {
            $table->id();
            $table->string('recovery_lot_number', 50)->unique();
            $table->unsignedBigInteger('source_batch_id')->index();
            $table->unsignedBigInteger('source_stage_id')->index();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('generated_qty', 12, 4);
            $table->decimal('available_qty', 12, 4);
            $table->decimal('reserved_qty', 12, 4)->default(0);
            $table->decimal('consumed_qty', 12, 4)->default(0);
            $table->decimal('sold_qty', 12, 4)->default(0);
            $table->decimal('remaining_qty', 12, 4);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('recovery_date');
            $table->enum('qc_status', ['Pending', 'Approved', 'Rejected'])->default('Approved');
            $table->enum('status', ['Active', 'Exhausted'])->default('Active');
            $table->timestamps();
        });

        Schema::create('recovery_lot_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_lot_id')->constrained('recovery_lots')->cascadeOnDelete();
            $table->unsignedBigInteger('consuming_batch_id')->index();
            $table->unsignedBigInteger('consuming_stage_id')->nullable();
            $table->decimal('consumed_qty', 12, 4);
            $table->date('consumed_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id')->index();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->enum('waste_type', ['Spent Material', 'Waste Material', 'Effluent', 'Other']);
            $table->string('item_name', 150);
            $table->decimal('quantity', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('disposal_method', 100)->nullable();
            $table->date('disposal_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_records');
        Schema::dropIfExists('recovery_lot_consumptions');
        Schema::dropIfExists('recovery_lots');
        Schema::dropIfExists('wip_records');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('grns');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
