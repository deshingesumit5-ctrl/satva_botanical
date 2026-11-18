<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UserCreationAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('email', 'admin@gmail.com')->firstOrFail();
        RateLimiter::clear('login:127.0.0.1');
    }

    public function test_create_user_form_has_empty_fields_and_anti_autofill_measures(): void
    {
        $res = $this->actingAs($this->superAdmin)->get(route('admin.users.create'));
        $res->assertStatus(200);

        $html = $res->getContent();
        // Verify off-screen decoy inputs exist (and not display:none)
        $this->assertStringContainsString('decoy_username_prevent_autofill', $html);
        $this->assertStringContainsString('decoy_password_prevent_autofill', $html);
        $this->assertStringContainsString('left: -9999px', $html);

        // Verify username has autocomplete="off", value="", readonly, onfocus
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('autocomplete="off"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
        $this->assertStringContainsString('onfocus="this.removeAttribute(\'readonly\');"', $html);
        $this->assertStringContainsString('password-eye-toggle', $html);
    }

    public function test_create_user_validates_password_min_8_and_confirmation(): void
    {
        $role = Role::where('role_code', 'store_manager')->firstOrFail();

        // Short password (< 8)
        $resShort = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name'                  => 'Short Pass User',
            'email'                 => 'short@sattvabotanical.local',
            'username'              => 'shortpass',
            'password'              => 'short1',
            'password_confirmation' => 'short1',
            'role_id'               => $role->id,
            'status'                => 'Active',
        ]);
        $resShort->assertSessionHasErrors('password');

        // Mismatched confirmation
        $resMismatch = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name'                  => 'Mismatch User',
            'email'                 => 'mismatch@sattvabotanical.local',
            'username'              => 'mismatchuser',
            'password'              => 'ValidPass@123',
            'password_confirmation' => 'DifferentPass@123',
            'role_id'               => $role->id,
            'status'                => 'Active',
        ]);
        $resMismatch->assertSessionHasErrors('password');
    }

    public function test_created_user_hashes_password_once_and_can_login_only_when_user_and_role_active(): void
    {
        $role = Role::create([
            'role_code'   => 'qc_only_test',
            'role_name'   => 'QC Only Test',
            'description' => 'QC view only',
            'status'      => 'Active',
        ]);
        \App\Services\PermissionService::syncRolePermissions($role, ['qc.dashboard.view', 'qc.review.view']);

        $res = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name'                  => 'New QC Chemist',
            'email'                 => 'qcchemist@sattvabotanical.local',
            'username'              => 'qcchemist',
            'password'              => 'Chemist@1234',
            'password_confirmation' => 'Chemist@1234',
            'role_id'               => $role->id,
            'status'                => 'Active',
        ]);
        $res->assertRedirect(route('admin.users.index'));

        $user = User::where('username', 'qcchemist')->firstOrFail();

        // Password must be hashed exactly ONCE
        $this->assertNotEquals('Chemist@1234', $user->password);
        $this->assertTrue(Hash::check('Chemist@1234', $user->password));

        // Logout admin
        $this->post(route('logout'));

        // Wrong password must fail
        RateLimiter::clear('login:127.0.0.1');
        $badLogin = $this->post(route('login.post'), [
            'login'    => 'qcchemist@sattvabotanical.local',
            'password' => 'WrongPass@1234',
        ]);
        $this->assertGuest();
        $badLogin->assertSessionHasErrors('login');

        // Login with email and exact password succeeds
        RateLimiter::clear('login:127.0.0.1');
        $goodLogin = $this->post(route('login.post'), [
            'login'    => 'qcchemist@sattvabotanical.local',
            'password' => 'Chemist@1234',
        ]);
        $goodLogin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Only QC module is visible in menu
        $dash = $this->get(route('dashboard'));
        $dash->assertStatus(200);
        $dash->assertSee('QC Dashboard');
        $dash->assertDontSee('Plant Master');
        $dash->assertDontSee('Purchase Orders (PO)');

        // Logout and test inactive role blocks login
        $this->post(route('logout'));
        $role->update(['status' => 'Inactive']);

        RateLimiter::clear('login:127.0.0.1');
        $inactiveRoleLogin = $this->post(route('login.post'), [
            'login'    => 'qcchemist',
            'password' => 'Chemist@1234',
        ]);
        $this->assertGuest();
        $inactiveRoleLogin->assertSessionHasErrors('login');
    }
}
