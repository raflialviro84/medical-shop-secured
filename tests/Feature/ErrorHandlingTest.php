<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_unknown_url_returns_not_found(): void
    {
        $response = $this->get('/route-that-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Not Found!');
    }
}
