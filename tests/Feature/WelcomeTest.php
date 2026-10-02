<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomeTest extends TestCase
{
    public function test_welcome_is_public_and_contains_siap_and_login_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('SIAP');
        $response->assertSee(route('login'), false);
    }
}
