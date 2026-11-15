<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

class MasterCrudTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_can_view_category_master_and_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('masters.categories.index'));
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->admin)->post(route('masters.categories.store'), [
            'name' => 'Herbal Powders Test',
            'display_order' => 5,
            'status' => 'Active',
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('product_categories', ['name' => 'Herbal Powders Test']);
    }

    public function test_can_view_plant_master_and_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('masters.plants.index'));
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->admin)->post(route('masters.plants.store'), [
            'code' => 'PLANT-TEST',
            'name' => 'Uttarakhand Extraction Facility',
            'address' => 'Haridwar Industrial Estate',
            'status' => 'Active',
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('plants', ['code' => 'PLANT-TEST']);
    }

    public function test_can_view_warehouse_master_and_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('masters.warehouses.index'));
        $response->assertStatus(200);

        $plant = Plant::first();
        $storeResponse = $this->actingAs($this->admin)->post(route('masters.warehouses.store'), [
            'code' => 'WH-TEST',
            'name' => 'Cold Storage Warehouse',
            'plant_id' => $plant->id,
            'type' => 'Raw Material',
            'status' => 'Active',
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('warehouses', ['code' => 'WH-TEST']);
    }

    public function test_cannot_delete_material_in_use_by_stock_or_recipe(): void
    {
        $rawMat = Material::where('material_code', 'RM-GARC-RIND')->first();
        $this->assertNotNull($rawMat);

        $deleteResponse = $this->actingAs($this->admin)->delete(route('masters.materials.destroy', $rawMat->id));
        $deleteResponse->assertRedirect();

        // Should still exist because it has stock lots and recipe usages
        $this->assertDatabaseHas('materials', ['id' => $rawMat->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_product_in_use_by_recipe_or_batch(): void
    {
        $product = Product::where('product_code', 'PRD-GARC-60')->first();
        $this->assertNotNull($product);

        $deleteResponse = $this->actingAs($this->admin)->delete(route('masters.products.destroy', $product->id));
        $deleteResponse->assertRedirect();

        // Should still exist because it has recipes and batches
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }
}
