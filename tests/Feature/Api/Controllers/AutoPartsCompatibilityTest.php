<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Domains\AutoParts\Models\AutoPartOrder;
use App\Domains\AutoParts\Models\AutoPart;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class AutoPartsCompatibilityTest extends TestCase
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

    public function test_find_compatible_parts_by_valid_vin(): void
    {
        $vin = 'WVWZZZ3CZ9E123456'; // Volkswagen
        $parts = AutoPart::factory(5)
            ->create([
                'tenant_id' => $this->tenant->id,
                'compatible_vins' => json_encode(['Volkswagen', 'Skoda']),
            ]);

        $response = $this->getJson("/api/v1/auto-parts-orders/compatible/{$vin}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonIsArray('data');
    }

    public function test_find_compatible_parts_rejects_invalid_vin(): void
    {
        $invalidVin = 'INVALID_VIN_123';

        $response = $this->getJson("/api/v1/auto-parts-orders/compatible/{$invalidVin}");

        $response->assertStatus(422);
    }

    public function test_find_compatible_parts_vin_formats(): void
    {
        $validVins = [
            'WVWZZZ3CZ9E123456', // Volkswagen (17 символов)
            'JH2RC5004LM200175', // Honda (17 символов)
            'TMBFK47A922044644', // Toyota (17 символов)
        ];

        foreach ($validVins as $vin) {
            $response = $this->getJson("/api/v1/auto-parts-orders/compatible/{$vin}");

            $this->assertIn($response->status(), [200, 404]); // 200 если есть детали, 404 если нет
        }
    }

    public function test_create_auto_part_order_with_vin_validation(): void
    {
        $part = AutoPart::factory()->create(['tenant_id' => $this->tenant->id]);

        $data = [
            'part_id' => $part->id,
            'vin' => 'WVWZZZ3CZ9E123456', // Valid VIN
            'quantity' => 2,
            'delivery_address' => 'ул. Ленина, 1',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/auto-parts-orders', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('auto_part_orders', ['part_id' => $part->id]);
    }

    public function test_auto_part_order_rejects_invalid_vin(): void
    {
        $part = AutoPart::factory()->create(['tenant_id' => $this->tenant->id]);

        $data = [
            'part_id' => $part->id,
            'vin' => 'INVALID_VIN', // Invalid VIN (не 17 символов или содержит недопустимые)
            'quantity' => 2,
            'delivery_address' => 'ул. Ленина, 1',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/auto-parts-orders', $data);

        $response->assertStatus(422);
    }

    public function test_auto_part_order_validates_quantity(): void
    {
        $part = AutoPart::factory()->create(['tenant_id' => $this->tenant->id]);

        $data = [
            'part_id' => $part->id,
            'vin' => 'WVWZZZ3CZ9E123456',
            'quantity' => 0, // Должно быть >= 1
            'delivery_address' => 'ул. Ленина, 1',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/auto-parts-orders', $data);

        $response->assertStatus(422);
    }
}
