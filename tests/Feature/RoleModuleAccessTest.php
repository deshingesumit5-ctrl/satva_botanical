<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleModuleAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    public function test_role_create_and_edit_are_full_pages_with_module_permissions_and_persistence(): void
    {
        // 1. Visit full-page Role Create
        $createRes = $this->actingAs($this->superAdmin)->get(route('admin.roles.create'));
        $createRes->assertStatus(200);
        $createRes->assertSee('ACCESS MANAGEMENT');
        $createRes->assertSee('Add New Role');
        $createRes->assertSee('ROLE INFORMATION');
        $createRes->assertSee('MODULE PERMISSIONS');
        $createRes->assertSee('Back to Roles');

        // Verify all 12 section headers
        foreach ([
            'Masters', 'Recipe', 'Purchase', 'Inventory', 'Production', 'QC',
            'Sales', 'Costing', 'Reports', 'Roles & Access', 'Settings', 'Audit',
        ] as $section) {
            $createRes->assertSee($section);
        }

        // 2. Store new role with selected permissions (including non-view to verify auto-tick view normalization)
        $storeRes = $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
            'role_name'   => 'QA Inspector Test Role',
            'description' => 'Quality Assurance role for testing',
            'status'      => 'Active',
            'permissions' => [
                'qc.dashboard.view',
                'qc.review.approve', // should auto-include qc.review.view
                'masters.qc_tests.view',
            ],
        ]);

        $storeRes->assertRedirect(route('admin.roles.index'));

        $role = Role::where('role_name', 'QA Inspector Test Role')->first();
        $this->assertNotNull($role);
        $this->assertEquals('Active', $role->status);
        $this->assertTrue($role->hasPermissionTo('qc.dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('qc.review.approve'));
        $this->assertTrue($role->hasPermissionTo('qc.review.view'));
        $this->assertTrue($role->hasPermissionTo('masters.qc_tests.view'));
        $this->assertFalse($role->hasPermissionTo('masters.products.view'));

        // 3. Visit Edit Role page and verify saved ticks show again and role code is read-only
        $editRes = $this->actingAs($this->superAdmin)->get(route('admin.roles.edit', $role));
        $editRes->assertStatus(200);
        $editRes->assertSee('Edit Role: QA Inspector Test Role');
        $editRes->assertSee($role->role_code);
        $editRes->assertSee('value="qc.dashboard.view"', false);
        $editRes->assertSee('value="qc.review.approve"', false);
    }

    public function test_permission_matrix_renders_real_checkboxes_and_persists_changes(): void
    {
        $role = Role::create([
            'role_code'   => 'matrix_test_role',
            'role_name'   => 'Matrix Test Role',
            'description' => 'Testing matrix',
            'status'      => 'Active',
        ]);

        $res = $this->actingAs($this->superAdmin)->get(route('admin.permissions.index', ['role_id' => $role->id]));
        $res->assertStatus(200);
        $res->assertSee('Role Permission Matrix');
        $res->assertSee('Plant Master');
        $res->assertSee('masters.plants');
        $res->assertSee('name="permissions[]"', false);

        $saveRes = $this->actingAs($this->superAdmin)->post(route('admin.permissions.save'), [
            'role_id'     => $role->id,
            'permissions' => ['inventory.stock.view', 'inventory.transfers.add'],
        ]);

        $saveRes->assertRedirect(route('admin.permissions.index', ['role_id' => $role->id]));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('inventory.stock.view'));
        $this->assertTrue($role->hasPermissionTo('inventory.transfers.add'));
        $this->assertTrue($role->hasPermissionTo('inventory.transfers.view'));
        $this->assertFalse($role->hasPermissionTo('masters.plants.view'));
    }

    public function test_store_test_role_sees_only_inventory_menus_and_gets_403_on_other_urls(): void
    {
        // 1. Create role "Store Test" with View on Inventory screens only
        $inventoryViewPerms = [
            'inventory.stock.view',
            'inventory.transfers.view',
            'inventory.adjustments.view',
            'inventory.wip.view',
            'inventory.recovery.view',
            'inventory.waste.view',
        ];

        $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
            'role_name'   => 'Store Test',
            'description' => 'Inventory view-only test role',
            'status'      => 'Active',
            'permissions' => $inventoryViewPerms,
        ])->assertRedirect(route('admin.roles.index'));

        $storeRole = Role::where('role_name', 'Store Test')->firstOrFail();

        // 2. Create a user with that role
        $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name'                  => 'Store Test User',
            'email'                 => 'storetest@sattvabotanical.local',
            'username'              => 'storetestuser',
            'password'              => 'StorePass@123',
            'password_confirmation' => 'StorePass@123',
            'role_id'               => $storeRole->id,
            'status'                => 'Active',
        ])->assertRedirect(route('admin.users.index'));

        // Log out Super Admin
        $this->post(route('logout'));
        $this->assertGuest();

        // 3. Log in with that user's email and password
        $loginRes = $this->post(route('login.post'), [
            'login'    => 'storetest@sattvabotanical.local',
            'password' => 'StorePass@123',
        ]);
        $loginRes->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // 4. Assert only Inventory menus show on Dashboard
        $dashRes = $this->get(route('dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Inventory');
        $dashRes->assertSee('Current Stock Summary');
        $dashRes->assertSee('Lot-wise Stock');
        $dashRes->assertSee('Stock Transfers');
        $dashRes->assertSee('WIP Records');

        // Other top-level module menus & links must NOT be visible
        $dashRes->assertDontSee('Plant Master');
        $dashRes->assertDontSee('Product Master');
        $dashRes->assertDontSee('All Recipes');
        $dashRes->assertDontSee('Purchase Requisitions');
        $dashRes->assertDontSee('Purchase Orders (PO)');
        $dashRes->assertDontSee('Production Plans');
        $dashRes->assertDontSee('QC Decision History');
        $dashRes->assertDontSee('Sales Orders');
        $dashRes->assertDontSee('Monthly Plant Expenses');
        $dashRes->assertDontSee('Production Reports');
        $dashRes->assertDontSee('User Master');
        $dashRes->assertDontSee('Role Master');
        $dashRes->assertDontSee('Company Settings');
        // Opening Stock Entry requires inventory.stock.add, so it should also be hidden for View-only
        $dashRes->assertDontSee('Opening Stock Entry');

        // 5. Assert Inventory View URLs return 200
        $this->get(route('inventory.dashboard'))->assertStatus(200);
        $this->get(route('inventory.stock.index'))->assertStatus(200);
        $this->get(route('inventory.stock.lots'))->assertStatus(200);
        $this->get(route('inventory.stock.ledger'))->assertStatus(200);
        $this->get(route('inventory.transfers.index'))->assertStatus(200);
        $this->get(route('inventory.adjustments.index'))->assertStatus(200);
        $this->get(route('inventory.wip.index'))->assertStatus(200);
        $this->get(route('inventory.recovery.index'))->assertStatus(200);
        $this->get(route('inventory.waste.index'))->assertStatus(200);

        // 6. Assert non-permitted write actions on Inventory return 403
        $this->post(route('inventory.transfers.store'), [])->assertStatus(403);
        $this->post(route('inventory.adjustments.store'), [])->assertStatus(403);

        // 7. Assert other module URLs return 403
        $restrictedUrls = [
            route('masters.plants.index'),
            route('masters.products.index'),
            route('masters.materials.index'),
            route('recipe.index'),
            route('purchase.requisitions.index'),
            route('purchase.orders.index'),
            route('purchase.grn.index'),
            route('production.plans.index'),
            route('production.batches.index'),
            route('qc.dashboard'),
            route('sales.orders.index'),
            route('sales.invoices.index'),
            route('costing.expenses.index'),
            route('reports.production'),
            route('admin.users.index'),
            route('admin.roles.index'),
            route('admin.permissions.index'),
            route('admin.audit-trail.index'),
            route('settings.company'),
        ];

        foreach ($restrictedUrls as $url) {
            $res = $this->get($url);
            $res->assertStatus(403, "Expected 403 for URL: {$url}");
            $res->assertSee('Access Denied (403)');
        }
    }
}
