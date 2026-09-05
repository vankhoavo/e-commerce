<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
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
