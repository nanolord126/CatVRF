<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Integration;

use App\Domains\FarmDirect\Models\FarmDirectOrder;
use App\Domains\HealthyFood\Models\DietPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantScopingTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::factory()->create();
        $this->tenant2 = Tenant::factory()->create();

        $this->user1 = User::factory()->create(['tenant_id' => $this->tenant1->id]);
        $this->user2 = User::factory()->create(['tenant_id' => $this->tenant2->id]);
    }

    public function test_user1_cannot_see_user2_orders(): void
    {
        // Создаём заказы для обоих тенантов
        FarmDirectOrder::factory(3)->create(['tenant_id' => $this->tenant1->id]);
        FarmDirectOrder::factory(3)->create(['tenant_id' => $this->tenant2->id]);

        // User1 смотрит свои заказы
        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/farm-orders');

        $orderTenantIds = collect($response->json('data'))
            ->pluck('tenant_id')
            ->unique();

        // Должны быть видны только заказы tenant1
        $this->assertTrue($orderTenantIds->contains($this->tenant1->id));
        $this->assertFalse($orderTenantIds->contains($this->tenant2->id));
    }

    public function test_user2_cannot_see_user1_orders(): void
    {
        FarmDirectOrder::factory(3)->create(['tenant_id' => $this->tenant1->id]);
        FarmDirectOrder::factory(3)->create(['tenant_id' => $this->tenant2->id]);

        // User2 смотрит свои заказы
        $response = $this->actingAs($this->user2, 'sanctum')
            ->getJson('/api/v1/farm-orders');

        $orderTenantIds = collect($response->json('data'))
            ->pluck('tenant_id')
            ->unique();

        // Должны быть видны только заказы tenant2
        $this->assertTrue($orderTenantIds->contains($this->tenant2->id));
        $this->assertFalse($orderTenantIds->contains($this->tenant1->id));
    }

    public function test_admin_can_list_only_their_tenant_data(): void
    {
        $admin1 = User::factory()->admin()->create(['tenant_id' => $this->tenant1->id]);
        FarmDirectOrder::factory(5)->create(['tenant_id' => $this->tenant1->id]);
        FarmDirectOrder::factory(5)->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($admin1, 'sanctum')
            ->getJson('/api/v1/farm-orders');

        $count = count($response->json('data'));

        // Admin видит только 5 заказов своего тенанта
        $this->assertEquals(5, $count);
    }

    public function test_cross_tenant_data_leak_prevented_on_show(): void
    {
        $order1 = FarmDirectOrder::factory()->create(['tenant_id' => $this->tenant1->id]);
        $order2 = FarmDirectOrder::factory()->create(['tenant_id' => $this->tenant2->id]);

        // User1 пытается получить заказ User2
        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson("/api/v1/farm-orders/{$order2->id}");

        // Должна быть ошибка 404 или 403
        $this->assertIn($response->status(), [403, 404]);
    }

    public function test_diet_plans_tenant_scoping(): void
    {
        $plan1 = DietPlan::factory()->create(['tenant_id' => $this->tenant1->id]);
        $plan2 = DietPlan::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/diet-plans');

        $planIds = collect($response->json('data'))->pluck('id');

        $this->assertContains($plan1->id, $planIds);
        $this->assertNotContains($plan2->id, $planIds);
    }

    public function test_update_cross_tenant_order_fails(): void
    {
        $order2 = FarmDirectOrder::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->putJson("/api/v1/farm-orders/{$order2->id}", [
                'quantity_kg' => 10,
            ]);

        $this->assertIn($response->status(), [403, 404]);
    }

    public function test_delete_cross_tenant_order_fails(): void
    {
        $order2 = FarmDirectOrder::factory()->create(['tenant_id' => $this->tenant2->id]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->deleteJson("/api/v1/farm-orders/{$order2->id}");

        $this->assertIn($response->status(), [403, 404]);

        // Заказ не должен быть удалён
        $this->assertFalse($order2->fresh()->trashed());
    }
}
