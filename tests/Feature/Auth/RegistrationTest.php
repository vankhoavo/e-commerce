<?php

namespace Tests\Feature\Auth;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_registration_screen_includes_team_invitation_context()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Laravel Team']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('register', ['invitation' => $invitation->code]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.teamName', 'Laravel Team'),
        );
    }

    public function test_new_users_can_register_and_are_sent_to_login()
    {
        $password = 'TechStore#2026';

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'registration-success');
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_google_linked_email_can_create_an_email_password_login()
    {
        $password = 'TechStore#2026';
        $user = User::factory()->create([
            'email' => 'google@example.com',
            'google_id' => 'google-test-id',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Google User',
            'email' => 'google@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'google-password-created');
        $this->assertGuest();

        $user->refresh();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame('google-test-id', $user->google_id);
    }

    public function test_google_email_check_reports_linked_account()
    {
        User::factory()->create([
            'email' => 'google@example.com',
            'google_id' => 'google-test-id',
        ]);

        $this->getJson(route('google.check-email', ['email' => 'google@example.com']))
            ->assertOk()
            ->assertJson(['google_linked' => true]);
    }
}
