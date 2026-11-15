<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('batch_id')->constrained('batches');
            $table->foreignId('product_id')->constrained('products');
            $table->string('fg_lot_number', 50)->index();
            $table->decimal('actual_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->date('mfg_date');
            $table->date('expiry_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('qc_status', ['QC Hold', 'QC Approved', 'QC Rejected'])->default('QC Approved');
            $table->foreignId('received_by')->constrained('users');
            $table->date('receipt_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', [
                'Draft', 'Confirmed', 'In Progress', 
                'Partially Dispatched', 'Fully Dispatched', 'Invoiced', 'Cancelled'
            ])->default('Draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained('stock_lots')->nullOnDelete();
            $table->string('item_name', 150);
            $table->decimal('quantity', 12, 4);
            $table->decimal('dispatched_qty', 12, 4)->default(0);
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

        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number', 50)->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('dispatch_date');
            $table->string('transport_details', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('invoice_reference', 50)->nullable();
            $table->enum('status', ['Dispatched', 'Delivered', 'Cancelled'])->default('Dispatched');
            $table->foreignId('created_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->decimal('quantity', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->timestamps();
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('dispatch_id')->nullable()->constrained('dispatches')->nullOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['Unpaid', 'Partially Paid', 'Paid'])->default('Unpaid');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('item_name', 150);
            $table->decimal('quantity', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('rate', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_invoice_number', 50);
            $table->string('internal_invoice_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('grn_id')->nullable()->constrained('grns')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->date('invoice_date');
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['Unpaid', 'Partially Paid', 'Paid'])->default('Unpaid');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_mode', ['Bank Transfer', 'Cheque', 'Cash', 'UPI'])->default('Bank Transfer');
            $table->string('reference_number', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->decimal('amount', 12, 2);
            $table->date('receipt_date');
            $table->enum('payment_mode', ['Bank Transfer', 'Cheque', 'Cash', 'UPI'])->default('Bank Transfer');
            $table->string('reference_number', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('credit_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number', 50)->unique();
            $table->enum('note_type', ['Credit Note', 'Debit Note']);
            $table->enum('party_type', ['Customer', 'Supplier']);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('invoice_reference', 50)->nullable();
            $table->date('note_date');
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->enum('expense_category', [
                'Labour', 'Electricity', 'Equipment-related', 
                'Maintenance', 'Miscellaneous', 'Other Manufacturing Expenses'
            ]);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('entered_by')->constrained('users');
            $table->date('entry_date');
            $table->enum('status', ['Draft', 'Approved', 'Posted'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('expense_category', 50);
            $table->decimal('total_expense', 12, 2);
            $table->unsignedInteger('eligible_batch_count');
            $table->string('allocation_method', 60)->default('Equal per completed batch');
            $table->decimal('allocated_amount', 12, 2);
            $table->date('allocation_date');
            $table->foreignId('posted_by')->constrained('users');
            $table->enum('status', ['Posted', 'Reallocated'])->default('Posted');
            $table->timestamps();
            $table->unique(['month', 'year', 'expense_category', 'status'], 'exp_alloc_unique');
        });

        Schema::create('expense_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_allocation_id')->constrained('expense_allocations')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('batch_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->unique()->constrained('batches')->cascadeOnDelete();
            $table->decimal('direct_material_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('electricity_cost', 12, 2)->default(0);
            $table->decimal('maintenance_cost', 12, 2)->default(0);
            $table->decimal('equipment_cost', 12, 2)->default(0);
            $table->decimal('misc_cost', 12, 2)->default(0);
            $table->decimal('other_cost', 12, 2)->default(0);
            $table->decimal('total_batch_cost', 12, 2)->default(0);
            $table->decimal('finished_qty', 12, 4)->default(0);
            $table->decimal('cost_per_unit', 12, 4)->default(0);
            $table->dateTime('calculated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_costs');
        Schema::dropIfExists('expense_allocation_items');
        Schema::dropIfExists('expense_allocations');
        Schema::dropIfExists('monthly_expenses');
        Schema::dropIfExists('credit_debit_notes');
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('dispatch_items');
        Schema::dropIfExists('dispatches');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('finished_goods_receipts');
    }
};
