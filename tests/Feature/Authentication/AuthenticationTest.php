<?php

namespace Tests\Feature\Authentication;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_root_redirects_authenticated_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/dashboard');
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_valid_credentials_authenticate_the_user(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_only_admins_can_access_admin_dashboard(): void
    {
        $fieldStaff = User::factory()->create(['role' => UserRole::FieldStaff]);
        $this->actingAs($fieldStaff)->get('/admin')->assertForbidden();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_admin_can_create_edit_and_deactivate_a_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Field Operator',
            'email' => 'operator@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'role' => UserRole::FieldStaff->value,
        ])->assertRedirect('/admin/users');

        $managedUser = User::query()->where('email', 'operator@example.com')->firstOrFail();
        $this->assertTrue($managedUser->is_active);

        $this->actingAs($admin)->put('/admin/users/'.$managedUser->id, [
            'name' => 'Updated Operator',
            'email' => 'updated@example.com',
            'role' => UserRole::FieldStaff->value,
        ])->assertRedirect('/admin/users');

        $this->actingAs($admin)->patch('/admin/users/'.$managedUser->id.'/deactivate')
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Updated Operator',
            'email' => 'updated@example.com',
            'is_active' => 0,
        ]);
    }

    public function test_field_staff_cannot_access_user_management(): void
    {
        $fieldStaff = User::factory()->create(['role' => UserRole::FieldStaff]);

        $this->actingAs($fieldStaff)->get('/admin/users')->assertForbidden();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }
}
