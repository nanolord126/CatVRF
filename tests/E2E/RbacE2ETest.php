<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Enums\Role;
use App\Models\TenantUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $ownerUser;
    private User $managerUser;
    private User $employeeUser;
    private string $ownerToken;
    private string $managerToken;
    private string $employeeToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create users with different roles
        $this->ownerUser = User::factory()->create();
        $this->managerUser = User::factory()->create();
        $this->employeeUser = User::factory()->create();

        // Assign roles
        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->ownerUser->id,
            'role' => Role::ADMIN->value,
        ]);

        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->managerUser->id,
            'role' => Role::MANAGER->value,
        ]);

        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->employeeUser->id,
            'role' => Role::EMPLOYEE->value,
        ]);

        // Create tokens
        $this->ownerToken = $this->ownerUser->createToken('owner')->plainTextToken;
        $this->managerToken = $this->managerUser->createToken('manager')->plainTextToken;
        $this->employeeToken = $this->employeeUser->createToken('employee')->plainTextToken;
    }

    /**
     * Test: Owner can view CRM data
     */
    public function test_owner_can_view_crm(): void
    {
        // Owner should have access
        $response = $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->getJson('/api/v1/crm/dashboard');

        $this->assertTrue($response->status() < 400);
    }

    /**
     * Test: Manager can view CRM data
     */
    public function test_manager_can_view_crm(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/crm/dashboard');

        $this->assertTrue($response->status() < 400);
    }

    /**
     * Test: Employee cannot view CRM data (403)
     */
    public function test_employee_cannot_view_crm(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
            ->getJson('/api/v1/crm/dashboard');

        $this->assertEquals(403, $response->status());
    }

    /**
     * Test: Only owner can manage payouts
     */
    public function test_only_owner_can_manage_payouts(): void
    {
        $payoutData = [
            'amount' => 100000,
            'bank_account' => '40817810100001234567',
        ];

        // Owner can initiate
        $ownerResponse = $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson('/api/v1/payouts', $payoutData);
        $this->assertTrue($ownerResponse->status() < 400);

        // Manager cannot
        $managerResponse = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/payouts', $payoutData);
        $this->assertEquals(403, $managerResponse->status());

        // Employee cannot
        $employeeResponse = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
            ->postJson('/api/v1/payouts', $payoutData);
        $this->assertEquals(403, $employeeResponse->status());
    }

    /**
     * Test: Owner can manage team members
     */
    public function test_owner_can_manage_team(): void
    {
        $newUser = User::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson('/api/v1/team/invite', [
                'email' => $newUser->email,
                'role' => Role::EMPLOYEE->value,
            ]);

        $this->assertTrue($response->status() < 400);
    }

    /**
     * Test: Manager cannot manage team
     */
    public function test_manager_cannot_manage_team(): void
    {
        $newUser = User::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/team/invite', [
                'email' => $newUser->email,
                'role' => Role::EMPLOYEE->value,
            ]);

        $this->assertEquals(403, $response->status());
    }

    /**
     * Test: User's role is enforced in database
     */
    public function test_user_role_persistence(): void
    {
        $tenantUser = TenantUser::where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->managerUser->id)
            ->first();

        $this->assertNotNull($tenantUser);
        $this->assertEquals(Role::MANAGER->value, $tenantUser->role);
    }

    /**
     * Test: Unauthorized user cannot access tenant data
     */
    public function test_unauthorized_user_cannot_access(): void
    {
        $unauthorizedUser = User::factory()->create();
        $unauthorizedToken = $unauthorizedUser->createToken('unauthorized')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$unauthorizedToken}")
            ->getJson('/api/v1/crm/dashboard');

        $this->assertTrue($response->status() >= 400);
    }
}
