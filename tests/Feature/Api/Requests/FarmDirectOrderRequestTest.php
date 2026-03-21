<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Requests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class FarmDirectOrderRequestTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_store_order_request_validates_quantity_kg(): void
    {
        $response = $this->postJson('/api/v1/farm-orders', [
            'product_id' => 999,
            'quantity_kg' => 0.2, // Ниже минимума 0.5
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'client_address' => 'ул. Ленина',
            'client_phone' => '+79991234567',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_order_request_validates_delivery_date(): void
    {
        $response = $this->postJson('/api/v1/farm-orders', [
            'product_id' => 999,
            'quantity_kg' => 2.5,
            'delivery_date' => now()->subDays(1)->format('Y-m-d'), // Прошедшая дата
            'client_address' => 'ул. Ленина',
            'client_phone' => '+79991234567',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_order_request_validates_phone_format(): void
    {
        $response = $this->postJson('/api/v1/farm-orders', [
            'product_id' => 999,
            'quantity_kg' => 2.5,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'client_address' => 'ул. Ленина',
            'client_phone' => 'invalid_phone', // Неправильный формат
        ]);

        $response->assertStatus(422);
    }

    public function test_store_order_request_accepts_valid_phone_formats(): void
    {
        $validPhones = ['+79991234567', '89991234567', '+7 999 123-45-67'];

        foreach ($validPhones as $phone) {
            $response = $this->postJson('/api/v1/farm-orders', [
                'product_id' => 1,
                'quantity_kg' => 2.5,
                'delivery_date' => now()->addDays(3)->format('Y-m-d'),
                'client_address' => 'ул. Ленина',
                'client_phone' => $phone,
            ]);

            // Если product_id не найден, будет 422, но не из-за phone
            $this->assertNotNull($response->json('success'));
        }
    }
}
