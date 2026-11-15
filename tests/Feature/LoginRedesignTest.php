<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRedesignTest extends TestCase
{
    public function test_login_page_renders_with_redesigned_layout_and_elements(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Page title
        $response->assertSee('<title>Sign In - Sattva Botanical</title>', false);

        // Preload background image
        $response->assertSee('images/login.bg.jpg');
        $response->assertSee('rel="preload"', false);

        // Heading & subtitle directly above card
        $response->assertSee('Welcome back!');
        $response->assertSee('Sign in to your account to continue');

        // Logo
        $response->assertSee('images/sattva-logo.png');
        $response->assertSee('alt="Sattva Botanicals"', false);

        // Form labels and inputs
        $response->assertSee('Username or Email');
        $response->assertSee('name="login"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('Forgot password?');
        $response->assertSee('Sign in');

        // Modal for forgot password
        $response->assertSee('Please contact your administrator to reset your password.');

        // Verify Default credentials line is completely REMOVED
        $response->assertDontSee('Default: admin / Admin@12345');
        $response->assertDontSee('Default: <strong>admin</strong>', false);

        // Assets exist on disk
        $this->assertFileExists(public_path('images/login-bg.jpg'));
        $this->assertFileExists(public_path('images/sattva-logo.png'));
        $this->assertFileExists(public_path('css/login.css'));
    }

    public function test_invalid_login_retains_username_and_shows_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'login' => 'my_typed_username',
            'password' => 'wrong_password_test',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');

        // Follow redirect and assert old input retained
        $follow = $this->get('/login');
        $follow->assertSee('value="my_typed_username"', false);
        $follow->assertSee('Invalid username/email or password.');
    }

    public function test_inactive_user_is_blocked_with_clear_error(): void
    {
        $inactive = User::firstOrCreate(
            ['username' => 'test_inactive_account'],
            [
                'user_id' => 'USR-INACT-001',
                'name' => 'Inactive User',
                'email' => 'inactive_user@sattvabotanical.local',
                'password' => Hash::make('SecretPass@123'),
                'role_id' => 1,
                'status' => 'Inactive',
            ]
        );

        $response = $this->from('/login')->post('/login', [
            'login' => 'test_inactive_account',
            'password' => 'SecretPass@123',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Your account is inactive. Please contact your system administrator.');

        $follow = $this->get('/login');
        $follow->assertSee('Your account is inactive. Please contact your system administrator.');
    }

    public function test_admin_user_update_saves_email_and_password_and_allows_login(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);

        $originalEmail = $admin->email;
        $newEmail = 'admin_updated_' . time() . '@sattvabotanical.local';
        $newPassword = 'NewSecretPassword@123';

        // Simulate admin updating user 1 in /admin/users/1
        $updateResponse = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => 'Admin User Updated',
            'email' => $newEmail,
            'mobile' => '9999988888',
            'password' => $newPassword,
            'role_id' => $admin->role_id ?? 1,
            'plant_id' => $admin->plant_id,
            'department_id' => $admin->department_id,
            'status' => 'Active',
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));

        // Refresh model from database
        $admin->refresh();
        $this->assertEquals($newEmail, $admin->email);
        $this->assertTrue(Hash::check($newPassword, $admin->password));

        // Logout
        auth()->logout();
        $this->assertGuest();

        // Login with old password must fail
        $failResponse = $this->post('/login', [
            'login' => $newEmail,
            'password' => 'Admin@12345',
        ]);
        $this->assertGuest();
        $failResponse->assertSessionHasErrors('login');

        // Login with new email and new password must succeed
        $successResponse = $this->post('/login', [
            'login' => $newEmail,
            'password' => $newPassword,
        ]);
        $this->assertAuthenticatedAs($admin);
        $successResponse->assertRedirect(route('dashboard'));

        // Restore original credentials
        $admin->email = $originalEmail;
        $admin->name = 'Admin User';
        $admin->password = Hash::make('Admin@12345');
        $admin->save();
    }
}
