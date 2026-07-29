<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UserFeedbackNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shares_only_the_supported_flash_contract(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->withSession([
                'success' => 'Siker',
                'error' => 'Hiba',
                'warning' => 'Figyelmeztetés',
                'info' => 'Információ',
                'internal_secret' => 'nem publikus',
            ])
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('flash.success', 'Siker')
                ->where('flash.error', 'Hiba')
                ->where('flash.warning', 'Figyelmeztetés')
                ->where('flash.info', 'Információ')
                ->missing('flash.internal_secret'));
    }

    public function test_profile_and_password_updates_return_localized_success_feedback(): void
    {
        $user = User::factory()->create([
            'email' => 'profile@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Updated User',
                'email' => 'profile@example.test',
            ])
            ->assertSessionHas('success', __('profile.messages.updated'));

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHas('success', __('profile.messages.password_updated'));
    }

    public function test_locale_feedback_uses_the_selected_locale(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post('/preferences/locale', ['locale' => 'en'])
            ->assertSessionHas('success', 'The display language was changed successfully.');
    }

    public function test_feedback_is_available_in_hungarian(): void
    {
        $user = User::factory()->create([
            'email' => 'hungarian-profile@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'hu'])
            ->patch('/profile', [
                'name' => 'Magyar Felhasználó',
                'email' => 'hungarian-profile@example.test',
            ])
            ->assertSessionHas('success', 'A profiladatok sikeresen frissültek.');
    }

    public function test_representative_crud_success_messages_are_localized(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Notification User',
            'email' => 'notification@example.test',
            'password' => 'password',
            'roles' => [],
        ]);
        $response->assertSessionHas('success', __('messages.created'));

        $created = User::query()->where('email', 'notification@example.test')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/users/{$created->id}", [
                'name' => 'Updated Notification User',
                'email' => 'notification@example.test',
                'password' => null,
                'roles' => [],
            ])
            ->assertSessionHas('success', __('messages.updated'));

        $this->actingAs($admin)
            ->delete("/admin/users/{$created->id}")
            ->assertSessionHas('success', __('messages.deleted'));
    }

    public function test_validation_and_authorization_failures_do_not_flash_success(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post('/admin/users', [])
            ->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSessionMissing('success');

        $unauthorized = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($unauthorized)
            ->post('/admin/users', [
                'name' => 'Forbidden User',
                'email' => 'forbidden@example.test',
                'password' => 'password',
                'roles' => [],
            ])
            ->assertForbidden()
            ->assertSessionMissing('success');
    }

    public function test_forbidden_inertia_response_is_user_safe(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden()
            ->assertSee('"component":"Error"', false)
            ->assertSee('"status":403', false)
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('Stack trace');
    }
}
