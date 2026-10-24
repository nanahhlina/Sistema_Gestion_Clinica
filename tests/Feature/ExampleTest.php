<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_renders_for_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()->assertSee('name="login"', false)->assertSee('name="password"', false);
    }
}
