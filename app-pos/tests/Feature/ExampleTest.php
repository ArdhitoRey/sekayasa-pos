<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        foreach (['/', '/fitur', '/harga', '/about', '/contact', '/login', '/client', '/pos', '/dashboard'] as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }

        $this->get('/projects')->assertStatus(404);
        $this->get('/portal')->assertStatus(404);
    }
}
