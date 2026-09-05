<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->staff()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_inactive_admin_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->admin()->inactive()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_is_redirected_away_from_client_pages(): void
    {
        $user = User::factory()->admin()->create();

        foreach ([
            route('home'),
            route('products.index'),
            route('cart.index'),
            route('dashboard'),
            route('profile.edit'),
        ] as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_admin_can_make_json_requests_to_admin_routes(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->getJson(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }
}
