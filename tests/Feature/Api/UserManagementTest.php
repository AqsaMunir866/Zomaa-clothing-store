<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        return $admin;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Store Manager',
            'email' => 'store.manager@example.com',
            'password' => 'Str0ng!Passw0rd',
        ], $overrides);
    }

    public function test_guest_cannot_create_a_user(): void
    {
        $this->postJson('/api/users', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_non_admin_cannot_create_a_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/users', $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/users', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('name', 'Store Manager')
            ->assertJsonPath('email', 'store.manager@example.com');

        $user = User::where('email', 'store.manager@example.com')->firstOrFail();

        $this->assertSame(1, (int) $user->is_admin);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Str0ng!Passw0rd', $user->password));
    }

    public function test_duplicate_email_returns_a_validation_error_instead_of_a_server_error(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $this->actingAsAdmin();

        $this->postJson('/api/users', $this->validPayload(['email' => 'taken@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_weak_password_returns_a_validation_error(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/users', $this->validPayload(['password' => 'password']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_name_and_email_are_required(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'manager@example.com',
        ]);
        $originalHash = $user->password;

        $this->putJson("/api/users/{$user->id}", [
            'name' => 'New Name',
            'email' => 'manager@example.com',
            'password' => '',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'New Name');

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_admin_can_update_a_user_password(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'An0ther!Passw0rd',
        ])->assertOk();

        $this->assertTrue(Hash::check('An0ther!Passw0rd', $user->refresh()->password));
    }

    public function test_updating_a_user_to_another_users_email_returns_a_validation_error(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $this->deleteJson("/api/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->deleteJson("/api/users/{$user->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_users_can_be_searched_by_name_or_email(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob Jones', 'email' => 'bob@example.com']);

        $response = $this->getJson('/api/users?search=alice');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'alice@example.com');
    }

    public function test_unknown_sort_fields_do_not_cause_a_server_error(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(3)->create();

        $this->getJson('/api/users?sort_field=password&sort_direction=sideways')
            ->assertOk();
    }

    public function test_users_can_be_sorted_by_name(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['name' => 'Zoe']);
        User::factory()->create(['name' => 'Adam']);

        $response = $this->getJson('/api/users?sort_field=name&sort_direction=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Adam');
    }

    public function test_per_page_is_capped(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users?per_page=100000');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }
}
