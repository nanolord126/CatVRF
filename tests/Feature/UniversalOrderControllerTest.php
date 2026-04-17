<?php declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Laravel\Sanctum\Sanctum;

final class UniversalOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'tenant_id' => 1,
        ]);
    }

    public function test_create_b2c_order(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'vertical' => 'beauty',
            'items' => [
                [
                    'product_type' => 'beauty_product',
                    'product_id' => 1,
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
            ],
            'delivery_address' => 'Test Address',
            'payment_method' => 'wallet',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order_id',
                    'order_uuid',
                    'status',
                    'total',
                    'payment_status',
                    'is_b2b',
                ],
                'correlation_id',
            ]);

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $user->tenant_id,
            'vertical' => 'beauty',
            'is_b2b' => false,
        ]);
    }

    public function test_create_b2b_order(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'vertical' => 'beauty',
            'items' => [
                [
                    'product_type' => 'beauty_product',
                    'product_id' => 1,
                    'quantity' => 50,
                    'unit_price' => 50000,
                ],
            ],
            'inn' => '123456789012',
            'business_card_id' => 'BC-test123',
            'delivery_address' => 'Test Address',
            'payment_method' => 'b2b_credit',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $user->tenant_id,
            'vertical' => 'beauty',
            'is_b2b' => true,
            'inn' => '123456789012',
        ]);
    }

    public function test_get_order_by_uuid(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'tenant_id' => $user->tenant_id,
            'vertical' => 'food',
            'is_b2b' => false,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                ],
            ]);
    }

    public function test_list_orders(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        Order::factory()->count(5)->create([
            'tenant_id' => $user->tenant_id,
            'vertical' => 'beauty',
            'is_b2b' => false,
        ]);

        $response = $this->getJson('/api/v1/orders?vertical=beauty');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);
    }

    public function test_idempotency(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $idempotencyKey = Str::uuid()->toString();
        $orderData = [
            'vertical' => 'beauty',
            'items' => [
                [
                    'product_type' => 'beauty_product',
                    'product_id' => 1,
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
            ],
            'payment_method' => 'wallet',
            'idempotency_key' => $idempotencyKey,
        ];

        $response1 = $this->postJson('/api/v1/orders', $orderData);
        $response1->assertStatus(200);

        $response2 = $this->postJson('/api/v1/orders', $orderData);
        $response2->assertStatus(200);

        $this->assertEquals(
            $response1->json('data.order_id'),
            $response2->json('data.order_id')
        );
    }
}
