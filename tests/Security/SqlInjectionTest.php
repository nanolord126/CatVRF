<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class SqlInjectionTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_sql_injection_in_search_parameter(): void
    {
        $maliciousInput = "'; DROP TABLE users; --";

        $response = $this->authenticatedGet("/api/search?q={$maliciousInput}")
            ->assertStatus(200);

        // Verify users table still exists
        $this->assertDatabaseHas('users', [
            'email' => $this->user->email,
        ]);
    }

    public function test_sql_injection_in_id_parameter(): void
    {
        $maliciousInput = "1 OR 1=1";

        $response = $this->authenticatedGet("/api/users/{$maliciousInput}")
            ->assertStatus(404); // Should return 404, not all users
    }

    public function test_sql_injection_in_order_by(): void
    {
        $maliciousInput = "id; DROP TABLE users--";

        $response = $this->authenticatedGet("/api/products?order={$maliciousInput}")
            ->assertStatus(400); // Should reject invalid order parameter

        // Verify users table still exists
        $this->assertDatabaseHas('users', [
            'email' => $this->user->email,
        ]);
    }
}
