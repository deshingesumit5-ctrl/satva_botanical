<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Rejected', 'Active', 'Inactive'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('recipe_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->string('version_number', 20); // e.g. V1.0, V2.0
            $table->decimal('reference_batch_size', 12, 4)->nullable();
            $table->foreignId('batch_uom_id')->constrained('uoms');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Rejected', 'Active', 'Inactive'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->timestamps();
            $table->unique(['recipe_id', 'version_number']);
        });

        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_version_id')->constrained('recipe_versions')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages');
            $table->unsignedInteger('sequence_no');
            $table->unsignedInteger('occurrence_no')->default(1);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_conditional')->default(false);
            $table->text('condition_rule')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->text('repeat_config')->nullable();
            $table->boolean('qc_required')->default(false);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_stage_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities');
            $table->unsignedInteger('sequence_no');
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('standard_duration_minutes')->nullable();
            $table->boolean('start_end_time_required')->default(true);
            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recipe_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('recipe_stage_activity_id')->nullable()->constrained('recipe_stage_activities')->nullOnDelete();
            $table->foreignId('parameter_id')->constrained('parameters');
            $table->string('target_value', 50)->nullable();
            $table->string('min_value', 50)->nullable();
            $table->string('max_value', 50)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->string('entry_frequency', 50)->default('Once');
            $table->string('validation_type', 50)->default('Range');
            $table->enum('deviation_action', ['Warning', 'Block', 'QC Approval Required'])->default('Warning');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('display_sequence')->default(1);
            $table->timestamps();
        });

        Schema::create('recipe_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->enum('direction', ['input', 'output']);
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('material_category', 50)->nullable();
            $table->decimal('standard_qty', 12, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms');
            $table->enum('quantity_basis', ['Fixed', 'Reference Ratio', 'Percentage'])->default('Reference Ratio');
            $table->decimal('percentage_basis', 8, 2)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('lot_tracking')->default(true);
            $table->boolean('multiple_lot_support')->default(true);
            $table->enum('output_type', ['WIP', 'Finished', 'Recovered', 'By-product', 'Spent', 'Waste', 'Cake', 'Liquid'])->nullable();
            $table->unsignedBigInteger('next_stage_id')->nullable();
            $table->boolean('is_reusable')->default(false);
            $table->boolean('is_saleable')->default(false);
            $table->boolean('qc_required')->default(false);
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recipe_equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_qc_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_stage_id')->constrained('recipe_stages')->cascadeOnDelete();
            $table->foreignId('qc_test_id')->constrained('qc_tests')->cascadeOnDelete();
            $table->string('min_value', 50)->nullable();
            $table->string('max_value', 50)->nullable();
            $table->string('target_value', 50)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('blocks_next_stage')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_qc_tests');
        Schema::dropIfExists('recipe_equipments');
        Schema::dropIfExists('recipe_materials');
        Schema::dropIfExists('recipe_parameters');
        Schema::dropIfExists('recipe_stage_activities');
        Schema::dropIfExists('recipe_stages');
        Schema::dropIfExists('recipe_versions');
        Schema::dropIfExists('recipes');
    }
};
