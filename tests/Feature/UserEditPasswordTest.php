<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UserEditPasswordTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('email', 'admin@gmail.com')->firstOrFail();
        RateLimiter::clear('login:127.0.0.1');
    }

    protected function createSampleUser(): User
    {
        $role = Role::where('role_code', 'store_manager')->firstOrFail();

        return User::create([
            'user_id'       => 'USR-EDIT-901',
            'name'          => 'Original Name',
            'email'         => 'edituser@sattvabotanical.local',
            'username'      => 'edituser901',
            'mobile'        => '9876500001',
            'password'      => 'OriginalPass@123',
            'role_id'       => $role->id,
            'plant_id'      => Plant::first()?->id,
            'department_id' => Department::first()?->id,
            'status'        => 'Active',
        ]);
    }

    public function test_case_1_blank_password_on_edit_keeps_existing_password_hash_untouched(): void
    {
        $user = $this->createSampleUser();
        $originalPasswordHash = $user->getRawOriginal('password');

        // Verify edit page renders empty password with anti-autofill and readonly username
        $editPage = $this->actingAs($this->superAdmin)->get(route('admin.users.edit', $user));
        $editPage->assertStatus(200);
        $editPage->assertSee('leave blank to keep current');
        $editPage->assertSee('autocomplete="new-password"', false);

        // Submit update with blank password
        $res = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name'                  => 'Original Name',
            'email'                 => $user->email,
            'mobile'                => $user->mobile,
            'password'              => '',
            'password_confirmation' => '',
            'role_id'               => $user->role_id,
            'plant_id'              => $user->plant_id,
            'department_id'         => $user->department_id,
            'status'                => 'Active',
        ]);

        $res->assertRedirect(route('admin.users.index'));

        $user->refresh();
        // Raw password hash in DB must be 100% identical (column not touched)
        $this->assertSame($originalPasswordHash, $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('OriginalPass@123', $user->password));
    }

    public function test_case_2_typing_new_password_validates_hashes_once_and_replaces_old_password(): void
    {
        $user = $this->createSampleUser();

        // Short password fails validation
        $shortRes = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'password'              => 'short1',
            'password_confirmation' => 'short1',
            'role_id'               => $user->role_id,
            'status'                => 'Active',
        ]);
        $shortRes->assertSessionHasErrors('password');

        // Unconfirmed password fails validation
        $mismatchRes = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'password'              => 'BrandNewPass@999',
            'password_confirmation' => 'MismatchPass@999',
            'role_id'               => $user->role_id,
            'status'                => 'Active',
        ]);
        $mismatchRes->assertSessionHasErrors('password');

        // Valid new password updates and hashes once
        $validRes = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'password'              => 'BrandNewPass@999',
            'password_confirmation' => 'BrandNewPass@999',
            'role_id'               => $user->role_id,
            'status'                => 'Active',
        ]);
        $validRes->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertFalse(Hash::check('OriginalPass@123', $user->password));
        $this->assertTrue(Hash::check('BrandNewPass@999', $user->password));

        // Logout admin
        $this->post(route('logout'));

        // Old password must fail login
        RateLimiter::clear('login:127.0.0.1');
        $oldLogin = $this->post(route('login.post'), [
            'login'    => $user->email,
            'password' => 'OriginalPass@123',
        ]);
        $this->assertGuest();
        $oldLogin->assertSessionHasErrors('login');

        // New password must work
        RateLimiter::clear('login:127.0.0.1');
        $newLogin = $this->post(route('login.post'), [
            'login'    => $user->email,
            'password' => 'BrandNewPass@999',
        ]);
        $newLogin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_case_3_editing_name_role_plant_department_or_status_leaves_old_password_working(): void
    {
        $user = $this->createSampleUser();
        $newRole = Role::where('role_code', 'qc_manager')->first() ?? Role::first();
        $newPlant = Plant::orderByDesc('id')->first();
        $newDept = Department::orderByDesc('id')->first();

        // Edit name, role, plant, department, status without touching password
        $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name'          => 'Updated Employee Name',
            'email'         => $user->email,
            'mobile'        => '9998887776',
            'password'      => '',
            'role_id'       => $newRole->id,
            'plant_id'      => $newPlant?->id,
            'department_id' => $newDept?->id,
            'status'        => 'Active',
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertEquals('Updated Employee Name', $user->name);
        $this->assertEquals($newRole->id, $user->role_id);

        // Logout admin and verify old password still logs in
        $this->post(route('logout'));

        RateLimiter::clear('login:127.0.0.1');
        $loginRes = $this->post(route('login.post'), [
            'login'    => $user->username,
            'password' => 'OriginalPass@123',
        ]);
        $loginRes->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
