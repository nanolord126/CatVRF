<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_employee(): void
    {
        $this->post('/admin/employees', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe'
        ]);
    }

    public function test_can_list_employees(): void
    {
        Employee::factory(20)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        $response = $this->get('/admin/employees');
        $response->assertOk();
    }
}