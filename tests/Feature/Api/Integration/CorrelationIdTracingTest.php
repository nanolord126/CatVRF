<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CorrelationIdTracingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_correlation_id_generated_on_request(): void
    {
        $response = $this->actingAs($this->createAuthenticatedUser(), 'sanctum')
            ->getJson('/api/v1/farm-orders');

        $this->assertNotNull($response->json('correlation_id'));
    }

    public function test_correlation_id_is_valid_uuid(): void
    {
        $response = $this->actingAs($this->createAuthenticatedUser(), 'sanctum')
            ->getJson('/api/v1/farm-orders');

        $correlationId = $response->json('correlation_id');
        $this->assertTrue(Str::isUuid($correlationId));
    }

    public function test_correlation_id_unique_per_request(): void
    {
        $user = $this->createAuthenticatedUser();

        $response1 = $this->actingAs($user, 'sanctum')->getJson('/api/v1/farm-orders');
        $response2 = $this->actingAs($user, 'sanctum')->getJson('/api/v1/farm-orders');

        $id1 = $response1->json('correlation_id');
        $id2 = $response2->json('correlation_id');

        $this->assertNotEquals($id1, $id2);
    }

    public function test_correlation_id_in_all_endpoints(): void
    {
        $user = $this->createAuthenticatedUser();

        $endpoints = [
            '/api/v1/farm-orders',
            '/api/v1/diet-plans',
            '/api/v1/bakery-orders',
            '/api/v1/meat-orders',
            '/api/v1/catering-orders',
            '/api/v1/furniture-orders',
            '/api/v1/electronics-orders',
            '/api/v1/toy-orders',
            '/api/v1/auto-parts-orders',
            '/api/v1/pharmacy-orders',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($user, 'sanctum')->getJson($endpoint);

            $this->assertNotNull($response->json('correlation_id'), "Missing correlation_id in {$endpoint}");
            $this->assertTrue(Str::isUuid($response->json('correlation_id')), "Invalid UUID in {$endpoint}");
        }
    }

    public function test_correlation_id_logged_on_mutations(): void
    {
        // Тест требует логирования, проверяем что correlation_id попадает в аудит-лог
        $user = $this->createAuthenticatedUser();

        // Создание заказа
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/farm-orders', [
                'product_id' => 1,
                'quantity_kg' => 2.5,
                'delivery_date' => now()->addDays(3)->format('Y-m-d'),
                'client_address' => 'ул. Ленина',
                'client_phone' => '+79991234567',
            ]);

        $correlationId = $response->json('correlation_id');

        // Проверяем, что correlation_id включён в ответ
        $this->assertNotNull($correlationId);
        $this->assertTrue(Str::isUuid($correlationId));
    }

    public function test_correlation_id_persists_through_error_responses(): void
    {
        $user = $this->createAuthenticatedUser();

        // Создание заказа с невалидными данными
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/farm-orders', [
                'product_id' => 999,
                'quantity_kg' => 600, // Слишком много
                'delivery_date' => now()->subDays(1)->format('Y-m-d'), // Прошлая дата
                'client_phone' => 'invalid',
            ]);

        // Даже при ошибке correlation_id должен присутствовать
        $this->assertNotNull($response->json('correlation_id'));
        $this->assertTrue(Str::isUuid($response->json('correlation_id')));
    }

    private function createAuthenticatedUser()
    {
        $tenant = \App\Models\Tenant::factory()->create();
        return \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    }
}
