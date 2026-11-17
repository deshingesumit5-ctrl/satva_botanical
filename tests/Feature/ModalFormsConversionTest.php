<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModalFormsConversionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('username', 'admin')->firstOrFail();
    }

    public function test_reusable_form_modal_component_and_elevated_confirm_modal_are_present(): void
    {
        $res = $this->actingAs($this->superAdmin)->get(route('masters.products.index'));
        $res->assertStatus(200);
        $res->assertSee('id="sattvaDynamicFormModal"', false);
        $res->assertSee('id="squareConfirmModal"', false);
        $res->assertSee('z-index: 1085', false);
    }

    public function test_ajax_modal_add_edit_and_validation_errors_work_for_masters(): void
    {
        $category = ProductCategory::firstOrFail();
        $uom = Uom::where('uom_code', 'KG')->first() ?? Uom::firstOrFail();

        // 1. Validation error via AJAX returns 422 JSON with field errors
        $invalidRes = $this->actingAs($this->superAdmin)->postJson(route('masters.products.store'), [
            'product_code' => '',
            'name'         => '',
        ]);
        $invalidRes->assertStatus(422);
        $invalidRes->assertJsonStructure(['message', 'errors']);

        // 2. Valid AJAX add product returns 200 JSON and creates record
        $storeRes = $this->actingAs($this->superAdmin)->postJson(route('masters.products.store'), [
            'product_code'        => 'FG-MODAL-901',
            'name'                => 'Modal Test Botanical Extract',
            'product_category_id' => $category->id,
            'base_uom_id'         => $uom->id,
            'hsn_code'            => '13021919',
            'shelf_life'          => 24,
            'min_stock'           => 50,
            'status'              => 'Active',
        ]);
        $storeRes->assertStatus(200);
        $storeRes->assertJson(['success' => true]);

        $product = Product::where('product_code', 'FG-MODAL-901')->firstOrFail();

        // 3. Valid AJAX edit product returns 200 JSON and updates record
        $updateRes = $this->actingAs($this->superAdmin)->putJson(route('masters.products.update', $product), [
            'name'                => 'Updated Modal Botanical Extract',
            'product_category_id' => $category->id,
            'base_uom_id'         => $uom->id,
            'hsn_code'            => '13021919',
            'shelf_life'          => 36,
            'status'              => 'Active',
        ]);
        $updateRes->assertStatus(200);
        $updateRes->assertJson(['success' => true]);

        $product->refresh();
        $this->assertEquals('Updated Modal Botanical Extract', $product->name);
        $this->assertEquals(36, $product->shelf_life);
    }

    public function test_ajax_modal_user_create_with_file_upload_and_edit_works(): void
    {
        Storage::fake('public');
        $role = Role::where('role_code', 'store_manager')->firstOrFail();

        $storeRes = $this->actingAs($this->superAdmin)->postJson(route('admin.users.store'), [
            'name'                  => 'Modal Uploaded User',
            'email'                 => 'modalupload@sattvabotanical.local',
            'username'              => 'modaluploaduser',
            'password'              => 'ModalPass@123',
            'password_confirmation' => 'ModalPass@123',
            'role_id'               => $role->id,
            'status'                => 'Active',
            'profile_image'         => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ]);

        $storeRes->assertStatus(200);
        $storeRes->assertJson(['success' => true]);

        $user = User::where('username', 'modaluploaduser')->firstOrFail();
        $this->assertNotNull($user->profile_image);

        // Edit via AJAX without changing password
        $updateRes = $this->actingAs($this->superAdmin)->putJson(route('admin.users.update', $user), [
            'name'     => 'Modal Edited User',
            'email'    => 'modalupload@sattvabotanical.local',
            'password' => '',
            'role_id'  => $role->id,
            'status'   => 'Active',
        ]);
        $updateRes->assertStatus(200);
        $updateRes->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals('Modal Edited User', $user->name);
    }

    public function test_direct_create_and_edit_urls_continue_working(): void
    {
        $product = Product::firstOrFail();
        $wh = Warehouse::firstOrFail();

        $this->actingAs($this->superAdmin)->get(route('masters.products.create'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('masters.products.edit', $product))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('masters.warehouses.edit', $wh))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('admin.users.create'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('purchase.requisitions.create'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('purchase.orders.create'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('sales.orders.create'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('costing.expenses.create'))->assertStatus(200);
    }
}
