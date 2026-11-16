<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_can_view_role_index_and_edit_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);

        $role = Role::where('role_code', '!=', 'super_admin')->first();
        if (!$role) {
            $role = Role::create([
                'role_name' => 'QC Specialist',
                'role_code' => 'qc_specialist',
                'description' => 'QC tester',
                'status' => 'Active',
            ]);
        }

        $editResponse = $this->actingAs($this->admin)->get(route('admin.roles.edit', $role));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Role:');
        $editResponse->assertSee($role->role_name);
    }

    public function test_can_create_new_role(): void
    {
        $roleName = 'Lab Technician ' . uniqid();
        $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'role_name' => $roleName,
            'description' => 'Assists senior chemists',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['role_name' => $roleName]);
    }

    public function test_can_update_existing_role_without_unique_rule_collision(): void
    {
        $role = Role::create([
            'role_name' => 'Custom Role ' . uniqid(),
            'role_code' => 'custom_' . uniqid(),
            'description' => 'Initial description',
            'status' => 'Active',
        ]);

        $updatedName = 'Updated Role Name ' . uniqid();
        $response = $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'role_name' => $updatedName,
            'description' => 'Updated description content',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'role_name' => $updatedName,
            'description' => 'Updated description content',
        ]);
    }

    public function test_can_delete_unassigned_role(): void
    {
        $role = Role::create([
            'role_name' => 'Temporary Role ' . uniqid(),
            'role_code' => 'temp_' . uniqid(),
            'description' => 'To be deleted',
            'status' => 'Inactive',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role));
        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_role_in_use_by_users(): void
    {
        $adminRole = Role::where('role_code', 'super_admin')->first();
        $this->assertNotNull($adminRole);

        $response = $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $adminRole));
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }
}
