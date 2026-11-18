<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:127.0.0.1');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sattva Botanical');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->post('/login', [
            'login'    => 'admin@gmail.com',
            'password' => 'admin@123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_legacy_admin_username_and_password_cannot_authenticate(): void
    {
        $response = $this->post('/login', [
            'login'    => 'admin',
            'password' => 'Admin@12345',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_user_seeder_does_not_overwrite_admin_credentials(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $user = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->assertEquals('admin@gmail.com', $user->username);
        $this->assertTrue(Hash::check('admin@123', $user->password));
        $this->assertEquals(0, User::where('username', 'admin')->count());
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login'    => 'admin@gmail.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $roleId = \App\Models\Role::where('role_code', '!=', 'super_admin')->value('id') ?? 2;
        $inactiveUser = User::withTrashed()->updateOrCreate(
            ['username' => 'inactive_tester'],
            [
                'user_id'       => 'USR-TEST-001',
                'name'          => 'Inactive Tester',
                'email'         => 'inactive@example.com',
                'mobile'        => '9000000001',
                'password'      => 'Secret@123',
                'department_id' => 1,
                'role_id'       => $roleId,
                'status'        => 'Inactive',
                'deleted_at'    => null,
            ]
        );

        $response = $this->post('/login', [
            'login'    => 'inactive_tester',
            'password' => 'Secret@123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_authenticated_user_can_view_profile_and_update(): void
    {
        $user = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put('/profile', [
            'name'   => 'Super Administrator',
            'email'  => 'admin@gmail.com',
            'mobile' => '9999988888',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'Super Administrator',
        ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/change-password');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put('/change-password', [
            'current_password'      => 'admin@123',
            'password'              => 'Admin@New12345',
            'password_confirmation' => 'Admin@New12345',
        ]);

        $updateResponse->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('Admin@New12345', $user->password));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('email', 'admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
