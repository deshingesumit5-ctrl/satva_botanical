<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_number', 50)->unique();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('recipe_id')->constrained('recipes');
            $table->foreignId('recipe_version_id')->constrained('recipe_versions');
            $table->decimal('planned_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->date('planned_start_date');
            $table->date('required_completion_date');
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['Draft', 'Approved', 'Rejected'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->foreignId('production_plan_id')->nullable()->constrained('production_plans')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('recipe_id')->constrained('recipes');
            $table->foreignId('recipe_version_id')->constrained('recipe_versions');
            $table->decimal('planned_qty', 12, 4);
            $table->decimal('actual_qty', 12, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->date('production_date');
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->foreignId('supervisor_id')->constrained('users');
            $table->string('supervisor_name', 100)->nullable();
            $table->unsignedInteger('operator_count')->default(1);
            $table->enum('status', [
                'Draft', 'Planned', 'Approved', 'Ready for Production', 
                'In Progress', 'On Hold', 'QC Pending', 'Rework Required', 
                'Completed', 'Rejected', 'Cancelled'
            ])->default('Planned');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->text('hold_reason');
            $table->dateTime('held_at');
            $table->foreignId('held_by')->constrained('users');
            $table->text('resume_reason')->nullable();
            $table->dateTime('resumed_at')->nullable();
            $table->foreignId('resumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamps();
        });

        Schema::create('batch_material_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('planned_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('allocated_qty', 12, 4)->default(0);
            $table->decimal('consumed_qty', 12, 4)->default(0);
            $table->decimal('returned_qty', 12, 4)->default(0);
            $table->enum('status', ['Pending', 'Partially Allocated', 'Fully Allocated', 'Consumed'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('batch_material_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('allocated_qty', 12, 4);
            $table->dateTime('allocated_at');
            $table->foreignId('allocated_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('batch_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->decimal('allocated_qty', 12, 4)->default(0);
            $table->decimal('actual_consumed_qty', 12, 4)->default(0);
            $table->decimal('returned_qty', 12, 4)->default(0);
            $table->decimal('net_consumed_qty', 12, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('batch_stage_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages');
            $table->unsignedInteger('execution_number')->default(1);
            $table->string('stage_name', 100);
            $table->unsignedInteger('sequence_no');
            $table->unsignedInteger('occurrence_no')->default(1);
            $table->enum('execution_type', ['Original', 'Rework', 'Repeat'])->default('Original');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->nullOnDelete();
            $table->string('actual_operator_name', 100)->nullable();
            $table->unsignedInteger('operator_count')->default(1);
            $table->enum('status', [
                'Pending', 'Ready', 'In Progress', 'QC Pending', 
                'Approved', 'Rework Required', 'Completed', 'Skipped', 'On Hold'
            ])->default('Pending');
            $table->enum('qc_status', ['Not Required', 'Pending', 'Approved', 'Rejected', 'Rework Required'])->default('Not Required');
            $table->text('supervisor_remarks')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_activity_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_stage_execution_id')->constrained('batch_stage_executions')->cascadeOnDelete();
            $table->foreignId('recipe_stage_activity_id')->constrained('recipe_stage_activities');
            $table->string('activity_name', 100);
            $table->unsignedInteger('sequence_no');
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->unsignedInteger('actual_duration_minutes')->default(0);
            $table->string('operator_name', 100)->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Completed'])->default('Pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_parameter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_stage_execution_id')->constrained('batch_stage_executions')->cascadeOnDelete();
            $table->foreignId('recipe_parameter_id')->constrained('recipe_parameters');
            $table->string('parameter_name', 100);
            $table->string('parameter_type', 30)->default('Numeric');
            $table->string('uom', 30)->nullable();
            $table->string('target_value', 50)->nullable();
            $table->string('min_value', 50)->nullable();
            $table->string('max_value', 50)->nullable();
            $table->string('actual_value', 100)->nullable();
            $table->boolean('is_within_range')->default(true);
            $table->dateTime('entered_at')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_stage_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_stage_execution_id')->constrained('batch_stage_executions')->cascadeOnDelete();
            $table->enum('output_type', ['WIP', 'Finished', 'Recovered', 'By-product', 'Spent', 'Waste', 'Cake', 'Liquid']);
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('output_name', 150);
            $table->decimal('actual_qty', 12, 4);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->string('lot_or_wip_no', 60)->nullable();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->boolean('qc_required')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_requests', function (Blueprint $table) {
            $table->id();
            $table->string('qc_request_number', 50)->unique();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('batch_stage_execution_id')->constrained('batch_stage_executions')->cascadeOnDelete();
            $table->string('stage_name', 100);
            $table->dateTime('request_date');
            $table->foreignId('submitted_by')->constrained('users');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Rework Required'])->default('Pending');
            $table->dateTime('decision_at')->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_remarks')->nullable();
            $table->text('rework_instructions')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_request_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_request_id')->constrained('qc_requests')->cascadeOnDelete();
            $table->foreignId('qc_test_id')->constrained('qc_tests');
            $table->string('test_name', 100);
            $table->string('test_method', 100)->nullable();
            $table->string('result_type', 30)->default('Numeric');
            $table->string('uom', 30)->nullable();
            $table->string('expected_min', 50)->nullable();
            $table->string('expected_max', 50)->nullable();
            $table->string('expected_value', 50)->nullable();
            $table->string('actual_value', 100)->nullable();
            $table->enum('result_status', ['Pass', 'Fail'])->default('Pass');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('rework_records', function (Blueprint $table) {
            $table->id();
            $table->string('rework_number', 50)->unique();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('original_stage_execution_id')->constrained('batch_stage_executions');
            $table->foreignId('rework_stage_execution_id')->nullable()->constrained('batch_stage_executions')->nullOnDelete();
            $table->enum('rework_type', ['Stage Rework', 'Activity Rework', 'Repeated Stage']);
            $table->text('rework_reason');
            $table->foreignId('qc_request_id')->nullable()->constrained('qc_requests')->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('additional_duration_minutes')->default(0);
            $table->decimal('additional_material_cost', 12, 2)->default(0);
            $table->enum('status', ['Pending', 'In Progress', 'Completed'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('batch_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->unique()->constrained('batches')->cascadeOnDelete();
            $table->decimal('total_input_qty', 12, 4)->default(0);
            $table->decimal('raw_material_qty', 12, 4)->default(0);
            $table->decimal('water_qty', 12, 4)->default(0);
            $table->decimal('chemical_qty', 12, 4)->default(0);
            $table->decimal('solvent_qty', 12, 4)->default(0);
            $table->decimal('other_input_qty', 12, 4)->default(0);
            $table->decimal('total_output_qty', 12, 4)->default(0);
            $table->decimal('fg_qty', 12, 4)->default(0);
            $table->decimal('wip_qty', 12, 4)->default(0);
            $table->decimal('cake_qty', 12, 4)->default(0);
            $table->decimal('liquid_qty', 12, 4)->default(0);
            $table->decimal('recovered_solvent_qty', 12, 4)->default(0);
            $table->decimal('by_product_qty', 12, 4)->default(0);
            $table->decimal('spent_qty', 12, 4)->default(0);
            $table->decimal('waste_qty', 12, 4)->default(0);
            $table->decimal('material_returns_qty', 12, 4)->default(0);
            $table->decimal('unaccounted_difference_qty', 12, 4)->default(0);
            $table->dateTime('reconciled_at');
            $table->foreignId('reconciled_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_reconciliations');
        Schema::dropIfExists('rework_records');
        Schema::dropIfExists('qc_request_results');
        Schema::dropIfExists('qc_requests');
        Schema::dropIfExists('batch_stage_outputs');
        Schema::dropIfExists('batch_parameter_readings');
        Schema::dropIfExists('batch_activity_executions');
        Schema::dropIfExists('batch_stage_executions');
        Schema::dropIfExists('batch_material_consumptions');
        Schema::dropIfExists('batch_material_allocations');
        Schema::dropIfExists('batch_material_requirements');
        Schema::dropIfExists('batch_holds');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('production_plans');
    }
};
