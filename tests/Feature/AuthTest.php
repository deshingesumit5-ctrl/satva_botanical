<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sattva Botanical');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'Admin@12345',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::firstOrCreate(
            ['username' => 'inactive_tester'],
            [
                'user_id' => 'USR-TEST-001',
                'name' => 'Inactive Tester',
                'email' => 'inactive@example.com',
                'mobile' => '9000000001',
                'password' => Hash::make('Secret@123'),
                'department_id' => 1,
                'role_id' => 1,
                'status' => 'Inactive',
            ]
        );

        $response = $this->post('/login', [
            'login' => 'inactive_tester',
            'password' => 'Secret@123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_authenticated_user_can_view_profile_and_update(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put('/profile', [
            'name' => 'Super Administrator',
            'email' => 'admin@sattvabotanical.local',
            'mobile' => '9999988888',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Super Administrator',
        ]);

        $user->name = 'Admin User';
        $user->save();
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->get('/change-password');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put('/change-password', [
            'current_password' => 'Admin@12345',
            'password' => 'Admin@New12345',
            'password_confirmation' => 'Admin@New12345',
        ]);

        $updateResponse->assertRedirect();

        // Restore password for other tests
        $user->password = Hash::make('Admin@12345');
        $user->save();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
