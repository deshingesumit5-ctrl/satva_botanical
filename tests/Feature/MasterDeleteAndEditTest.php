<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Parameter;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QcTest;
use App\Models\Role;
use App\Models\Stage;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

class MasterDeleteAndEditTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_plant_edit_and_delete(): void
    {
        $plant = Plant::create([
            'code' => 'PL-TMP-' . uniqid(),
            'name' => 'Temp Plant ' . uniqid(),
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Plant ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.plants.update', $plant), [
            'name' => $newName,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('plants', ['id' => $plant->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.plants.destroy', $plant));
        $resDel->assertRedirect(route('masters.plants.index'));
        $this->assertSoftDeleted('plants', ['id' => $plant->id]);
    }

    public function test_department_edit_and_delete(): void
    {
        $plant = Plant::first();
        $dept = Department::create([
            'code' => 'DEP-TMP-' . uniqid(),
            'name' => 'Temp Dept ' . uniqid(),
            'plant_id' => $plant ? $plant->id : null,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Dept ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.departments.update', $dept), [
            'name' => $newName,
            'department_head' => 'Dr. Sharma',
            'plant_id' => $plant ? $plant->id : null,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.departments.destroy', $dept));
        $resDel->assertRedirect(route('masters.departments.index'));
        $this->assertSoftDeleted('departments', ['id' => $dept->id]);
    }

    public function test_warehouse_edit_and_delete(): void
    {
        $plant = Plant::first();
        $wh = Warehouse::create([
            'code' => 'WH-TMP-' . uniqid(),
            'name' => 'Temp Warehouse ' . uniqid(),
            'plant_id' => $plant ? $plant->id : null,
            'type' => 'Raw Material',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Warehouse ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.warehouses.update', $wh), [
            'name' => $newName,
            'type' => 'Finished Goods',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('warehouses', ['id' => $wh->id, 'name' => $newName, 'type' => 'Finished Goods']);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.warehouses.destroy', $wh));
        $resDel->assertRedirect(route('masters.warehouses.index'));
        $this->assertSoftDeleted('warehouses', ['id' => $wh->id]);
    }

    public function test_storage_location_edit_and_delete(): void
    {
        $wh = Warehouse::first();
        $loc = StorageLocation::create([
            'warehouse_id' => $wh->id,
            'location_code' => 'LOC-TMP-' . uniqid(),
            'name' => 'Temp Location ' . uniqid(),
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Loc ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.locations.update', $loc), [
            'name' => $newName,
            'warehouse_id' => $wh->id,
            'rack_no' => 'R-01',
            'bin_no' => 'B-02',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('storage_locations', ['id' => $loc->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.locations.destroy', $loc));
        $resDel->assertRedirect(route('masters.locations.index'));
        $this->assertSoftDeleted('storage_locations', ['id' => $loc->id]);
    }

    public function test_uom_edit_and_delete(): void
    {
        $uom = Uom::create([
            'uom_code' => 'UTMP' . rand(10, 99),
            'uom_name' => 'Temp Unit ' . uniqid(),
            'category' => 'Count',
            'decimal_precision' => 0,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Unit ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.uoms.update', $uom), [
            'uom_name' => $newName,
            'category' => 'Count',
            'decimal_precision' => 2,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('uoms', ['id' => $uom->id, 'uom_name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.uoms.destroy', $uom));
        $resDel->assertRedirect(route('masters.uoms.index'));
        $this->assertSoftDeleted('uoms', ['id' => $uom->id]);
    }

    public function test_product_category_edit_and_delete(): void
    {
        $cat = ProductCategory::create([
            'code' => 'CAT-TMP-' . uniqid(),
            'name' => 'Temp Cat ' . uniqid(),
            'display_order' => 10,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Cat ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.categories.update', $cat), [
            'name' => $newName,
            'display_order' => 12,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('product_categories', ['id' => $cat->id, 'name' => $newName, 'display_order' => 12]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.categories.destroy', $cat));
        $resDel->assertRedirect(route('masters.categories.index'));
        $this->assertSoftDeleted('product_categories', ['id' => $cat->id]);
    }

    public function test_product_edit_and_delete(): void
    {
        $cat = ProductCategory::first();
        $uom = Uom::first();
        $product = Product::create([
            'product_code' => 'PRD-TMP-' . uniqid(),
            'name' => 'Temp Product ' . uniqid(),
            'product_category_id' => $cat->id,
            'base_uom_id' => $uom->id,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Product ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.products.update', $product), [
            'name' => $newName,
            'product_category_id' => $cat->id,
            'base_uom_id' => $uom->id,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.products.destroy', $product));
        $resDel->assertRedirect(route('masters.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_material_edit_and_delete(): void
    {
        $uom = Uom::first();
        $mat = Material::create([
            'material_code' => 'MAT-TMP-' . uniqid(),
            'name' => 'Temp Material ' . uniqid(),
            'material_category' => 'Raw',
            'material_type' => 'Solid',
            'base_uom_id' => $uom->id,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Material ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.materials.update', $mat), [
            'name' => $newName,
            'material_category' => 'Raw',
            'material_type' => 'Solid',
            'base_uom_id' => $uom->id,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('materials', ['id' => $mat->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.materials.destroy', $mat));
        $resDel->assertRedirect(route('masters.materials.index'));
        $this->assertSoftDeleted('materials', ['id' => $mat->id]);
    }

    public function test_supplier_edit_and_delete(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-TMP-' . uniqid(),
            'name' => 'Temp Supplier ' . uniqid(),
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Supplier ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.suppliers.update', $supplier), [
            'name' => $newName,
            'contact_person' => 'Mr. Ramesh',
            'payment_terms' => '30 Days',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => $newName, 'payment_terms' => '30 Days']);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.suppliers.destroy', $supplier));
        $resDel->assertRedirect(route('masters.suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_customer_edit_and_delete(): void
    {
        $cust = Customer::create([
            'code' => 'CUST-TMP-' . uniqid(),
            'name' => 'Temp Customer ' . uniqid(),
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Customer ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.customers.update', $cust), [
            'name' => $newName,
            'contact_person' => 'Priya Patel',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('customers', ['id' => $cust->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.customers.destroy', $cust));
        $resDel->assertRedirect(route('masters.customers.index'));
        $this->assertSoftDeleted('customers', ['id' => $cust->id]);
    }

    public function test_stage_edit_and_delete(): void
    {
        $stage = Stage::create([
            'stage_code' => 'STG-TMP-' . uniqid(),
            'stage_name' => 'Temp Stage ' . uniqid(),
            'category' => 'Processing',
            'duration_uom' => 'Hours',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Stage ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.stages.update', $stage), [
            'stage_name' => $newName,
            'category' => 'Processing',
            'duration_uom' => 'Hours',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('stages', ['id' => $stage->id, 'stage_name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.stages.destroy', $stage));
        $resDel->assertRedirect(route('masters.stages.index'));
        $this->assertSoftDeleted('stages', ['id' => $stage->id]);
    }

    public function test_activity_edit_and_delete(): void
    {
        $activity = Activity::create([
            'code' => 'ACT-TMP-' . uniqid(),
            'name' => 'Temp Activity ' . uniqid(),
            'duration_uom' => 'Minutes',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Activity ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.activities.update', $activity), [
            'name' => $newName,
            'duration_uom' => 'Minutes',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.activities.destroy', $activity));
        $resDel->assertRedirect(route('masters.activities.index'));
        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
    }

    public function test_parameter_edit_and_delete(): void
    {
        $param = Parameter::create([
            'code' => 'PRM-TMP-' . uniqid(),
            'name' => 'Temp Param ' . uniqid(),
            'parameter_type' => 'Numeric',
            'uom' => 'mg/g',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Param ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.parameters.update', $param), [
            'name' => $newName,
            'parameter_type' => 'Numeric',
            'uom' => 'mg/g',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('parameters', ['id' => $param->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.parameters.destroy', $param));
        $resDel->assertRedirect(route('masters.parameters.index'));
        $this->assertSoftDeleted('parameters', ['id' => $param->id]);
    }

    public function test_equipment_edit_and_delete(): void
    {
        $eq = Equipment::create([
            'code' => 'EQ-TMP-' . uniqid(),
            'name' => 'Temp Equipment ' . uniqid(),
            'category' => 'Extractor',
            'equipment_status' => 'Available',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated Equipment ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.equipments.update', $eq), [
            'name' => $newName,
            'category' => 'Extractor',
            'equipment_status' => 'Available',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('equipments', ['id' => $eq->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.equipments.destroy', $eq));
        $resDel->assertRedirect(route('masters.equipments.index'));
        $this->assertSoftDeleted('equipments', ['id' => $eq->id]);
    }

    public function test_qc_test_edit_and_delete(): void
    {
        $test = QcTest::create([
            'test_code' => 'QC-TMP-' . uniqid(),
            'test_name' => 'Temp QC Test ' . uniqid(),
            'test_category' => 'Chemical',
            'result_type' => 'Numeric',
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated QC Test ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('masters.qc-tests.update', $test), [
            'test_name' => $newName,
            'test_category' => 'Chemical',
            'result_type' => 'Numeric',
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('qc_tests', ['id' => $test->id, 'test_name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('masters.qc-tests.destroy', $test));
        $resDel->assertRedirect(route('masters.qc-tests.index'));
        $this->assertSoftDeleted('qc_tests', ['id' => $test->id]);
    }

    public function test_user_edit_and_delete(): void
    {
        $role = Role::where('role_code', '!=', 'super_admin')->first() ?? Role::first();
        $user = User::create([
            'user_id' => 'USR-' . rand(1000, 9999),
            'name' => 'Temp User ' . uniqid(),
            'username' => 'tempuser_' . uniqid(),
            'email' => 'temp_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        // Edit
        $newName = 'Updated User ' . uniqid();
        $resEdit = $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => $newName,
            'email' => $user->email,
            'role_id' => $role->id,
            'status' => 'Active',
        ]);
        $resEdit->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => $newName]);

        // Delete
        $resDel = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));
        $resDel->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_foreign_key_in_use_delete_shows_error_without_500(): void
    {
        // Deleting plant with existing warehouses/users
        $plant = Plant::whereHas('warehouses')->first();
        if ($plant) {
            $response = $this->actingAs($this->admin)->delete(route('masters.plants.destroy', $plant));
            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('plants', ['id' => $plant->id, 'deleted_at' => null]);
        }

        // Deleting material in use
        $mat = Material::where('material_code', 'RM-GARC-RIND')->first();
        if ($mat) {
            $response = $this->actingAs($this->admin)->delete(route('masters.materials.destroy', $mat));
            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('materials', ['id' => $mat->id, 'deleted_at' => null]);
        }
    }
}
