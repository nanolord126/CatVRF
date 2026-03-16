<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_payroll(): void
    {
        $this->post('/admin/payrolls', [
            'amount' => 5000,
            'employee_id' => $this->user->id
        ]);
        $this->assertDatabaseHas('payrolls', [
            'amount' => 5000
        ]);
    }

    public function test_can_list_payrolls(): void
    {
        Payroll::factory(5)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/payrolls');
        $response->assertOk();
    }
}