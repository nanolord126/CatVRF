<?php

declare(strict_types=1);

namespace Tests\Sanity;

use Tests\BaseTestCase;
use App\Models\User;
use App\Models\Tenant;

final class SanityTest extends BaseTestCase
{
    public function test_can_create_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_can_create_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => $tenant->name,
        ]);
    }

    public function test_authentication_works(): void
    {
        $response = $this->authenticatedGet('/api/user')
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'email', 'tenant_id']);
    }

    public function test_authorization_works(): void
    {
        $response = $this->authenticatedGet('/api/admin/users')
            ->assertStatus(403); // Regular user should not have access
    }

    public function test_fraud_check_works(): void
    {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 10000,
            'payment_method' => 'card',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['fraud_score']);
    }
}
