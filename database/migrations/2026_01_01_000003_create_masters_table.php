<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->enum('type', ['Raw Material', 'Chemical', 'WIP', 'Finished Goods', 'Recovery', 'Waste', 'Rejected']);
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('storage_condition')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_code', 30)->unique();
            $table->string('name', 100);
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('rack_no', 30)->nullable();
            $table->string('bin_no', 30)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('uom_code', 30)->unique();
            $table->string('uom_name', 100);
            $table->enum('category', ['Weight', 'Volume', 'Count', 'Time', 'Measurement']);
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->foreignId('base_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->boolean('is_stock_unit')->default(true);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('display_order')->default(1);
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('product_category_id')->constrained('product_categories');
            $table->string('product_type', 50)->default('Standard');
            $table->text('description')->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->foreignId('base_uom_id')->constrained('uoms');
            $table->foreignId('alternate_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->decimal('conversion_factor', 12, 4)->nullable();
            $table->boolean('batch_tracking')->default(true);
            $table->unsignedInteger('shelf_life')->nullable();
            $table->boolean('manufacturing_date_required')->default(true);
            $table->boolean('expiry_date_required')->default(true);
            $table->text('storage_instructions')->nullable();
            $table->boolean('qc_required')->default(true);
            $table->decimal('min_stock', 12, 4)->default(0);
            $table->boolean('is_saleable')->default(true);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_code', 30)->unique();
            $table->string('name', 150);
            $table->enum('material_category', ['Raw', 'Chemical', 'Solvent', 'Water', 'Consumable', 'Packing']);
            $table->enum('material_type', ['Solid', 'Liquid', 'Powder', 'Gas']);
            $table->string('grade', 50)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->foreignId('base_uom_id')->constrained('uoms');
            $table->foreignId('alternate_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->decimal('conversion_factor', 12, 4)->nullable();
            $table->boolean('lot_tracking')->default(true);
            $table->unsignedInteger('shelf_life')->nullable();
            $table->text('storage_condition')->nullable();
            $table->boolean('qc_required')->default(true);
            $table->decimal('min_stock', 12, 4)->default(0);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->text('bank_details')->nullable();
            $table->json('supplied_material_categories')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('category', 50)->default('General');
            $table->decimal('capacity', 12, 2)->nullable();
            $table->foreignId('capacity_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('location', 100)->nullable();
            $table->enum('equipment_status', ['Available', 'Under Maintenance', 'Calibrating', 'In Use'])->default('Available');
            $table->boolean('calibration_required')->default(false);
            $table->date('last_calibration_date')->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->date('maintenance_due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_code', 30)->unique();
            $table->string('stage_name', 100);
            $table->enum('category', ['Processing', 'Treatment', 'Separation', 'Drying']);
            $table->text('description')->nullable();
            $table->foreignId('default_equipment_id')->nullable()->constrained('equipments')->nullOnDelete();
            $table->unsignedInteger('default_duration')->nullable();
            $table->enum('duration_uom', ['Minutes', 'Hours'])->default('Minutes');
            $table->boolean('activity_required')->default(true);
            $table->boolean('material_input_required')->default(true);
            $table->boolean('material_output_required')->default(true);
            $table->boolean('qc_available')->default(true);
            $table->text('instructions')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('activity_type', 50)->default('Process');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_duration')->nullable();
            $table->enum('duration_uom', ['Minutes', 'Hours'])->default('Minutes');
            $table->boolean('start_time_required')->default(true);
            $table->boolean('end_time_required')->default(true);
            $table->boolean('operator_entry_required')->default(false);
            $table->text('instructions')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->enum('parameter_type', ['Numeric', 'Text', 'Dropdown', 'Boolean']);
            $table->string('uom', 30)->nullable();
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->text('description')->nullable();
            $table->json('options')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('qc_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_code', 30)->unique();
            $table->string('test_name', 100);
            $table->string('test_category', 50)->nullable();
            $table->string('test_method', 100)->nullable();
            $table->enum('result_type', ['Numeric', 'Text', 'Pass-Fail', 'Dropdown']);
            $table->string('uom', 30)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_tests');
        Schema::dropIfExists('parameters');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('storage_locations');
        Schema::dropIfExists('warehouses');
    }
};
