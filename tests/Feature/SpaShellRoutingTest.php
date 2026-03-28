<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackWebsiteVisit;
use Tests\TestCase;

class SpaShellRoutingTest extends TestCase
{
    public function test_spa_shell_uses_project_public_base_href(): void
    {
        $this->withoutMiddleware(TrackWebsiteVisit::class);

        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('<base href="/spa/">', false);
    }

    public function test_auth_state_endpoint_returns_guest_state(): void
    {
        $response = $this->getJson('/auth/state');

        $response->assertOk()
            ->assertExactJson([
                'logged_in' => false,
                'role' => null,
            ]);
    }
}
