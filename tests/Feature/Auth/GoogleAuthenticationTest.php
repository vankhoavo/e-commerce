<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_requires_oauth_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.redirect' => null,
        ]);

        $response = $this->get(route('google.redirect'));

        $response->assertServiceUnavailable();
    }

    public function test_google_redirect_generates_state_and_redirects_to_google(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);

        $response = $this->get(route('google.redirect'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $response->headers->get('Location'));
        $this->assertNotEmpty(session('google_oauth_state'));
    }

    public function test_google_callback_rejects_invalid_state(): void
    {
        $response = $this->get(route('google.callback', [
            'state' => 'invalid-state',
            'code' => 'fake-code',
        ]));

        $response->assertStatus(419);
    }
}
