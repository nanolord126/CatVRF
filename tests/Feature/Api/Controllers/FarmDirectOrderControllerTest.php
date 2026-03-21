<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Domains\FarmDirect\Models\Farm;
use App\Domains\FarmDirect\Models\FarmDirectOrder;
use App\Domains\FarmDirect\Models\FarmProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FarmDirectOrderControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $user;
    private Farm $farm;
    private FarmProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->farm = Farm::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = FarmProduct::factory()->create([
            'farm_id' => $this->farm->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_list_orders_returns_200(): void
    {
        FarmDirectOrder::factory(5)
            ->create(['tenant_id' => $this->tenant->id, 'product_id' => $this->product->id]);

        $response = $this->getJson('/api/v1/farm-orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data', 'correlation_id'])
            ->assertJsonPath('success', true);
    }

    public function test_show_order_returns_200(): void
    {
        $order = FarmDirectOrder::factory()
            ->create(['tenant_id' => $this->tenant->id, 'product_id' => $this->product->id]);

        $response = $this->getJson("/api/v1/farm-orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_create_order_with_valid_data_returns_201(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity_kg' => 2.5,
            'delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'client_address' => 'ул. Ленина, 1, кв. 50',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/farm-orders', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity_kg', 2.5);

        $this->assertDatabaseHas('farm_direct_orders', ['product_id' => $this->product->id]);
    }

    public function test_create_order_with_invalid_quantity_fails(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity_kg' => 600, // Превышает максимум 500 кг
            'delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'client_address' => 'ул. Ленина, 1',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/farm-orders', $data);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_update_order_with_valid_data_returns_200(): void
    {
        $order = FarmDirectOrder::factory()
            ->create(['tenant_id' => $this->tenant->id, 'product_id' => $this->product->id]);

        $data = [
            'quantity_kg' => 5.0,
            'delivery_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/v1/farm-orders/{$order->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('farm_direct_orders', [
            'id' => $order->id,
            'quantity_kg' => 5.0,
        ]);
    }

    public function test_delete_order_returns_204(): void
    {
        $order = FarmDirectOrder::factory()
            ->create(['tenant_id' => $this->tenant->id, 'product_id' => $this->product->id]);

        $response = $this->deleteJson("/api/v1/farm-orders/{$order->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('farm_direct_orders', ['id' => $order->id]);
    }

    public function test_correlation_id_present_in_response(): void
    {
        $response = $this->getJson('/api/v1/farm-orders');

        $this->assertNotNull($response->json('correlation_id'));
        $this->assertTrue(Str::isUuid($response->json('correlation_id')));
    }

    public function test_tenant_scoping_enforced(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOrder = FarmDirectOrder::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->getJson('/api/v1/farm-orders');

        $orderIds = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($otherOrder->id, $orderIds);
    }

    public function test_audit_log_created_on_create(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity_kg' => 2.5,
            'delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'client_address' => 'ул. Ленина, 1',
            'client_phone' => '+79991234567',
        ];

        $this->postJson('/api/v1/farm-orders', $data);

        // Проверяем, что запись попадает в audit лог
        $this->assertLogged(
            level: 'info',
            message: 'FarmDirect order created'
        );
    }
}
