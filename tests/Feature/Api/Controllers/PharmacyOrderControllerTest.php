<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Domains\Pharmacy\Models\PharmacyOrder;
use App\Domains\Pharmacy\Models\Pharmacy;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PharmacyOrderControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $user;
    private Pharmacy $pharmacy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->pharmacy = Pharmacy::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_create_pharmacy_order_with_prescription(): void
    {
        $data = [
            'pharmacy_id' => $this->pharmacy->id,
            'medicines' => json_encode([
                ['name' => 'Аспирин', 'quantity' => 10, 'price' => 50],
                ['name' => 'Ибупрофен', 'quantity' => 5, 'price' => 75],
            ]),
            'prescription_file' => 'prescription.pdf', // URL или path
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/pharmacy-orders', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pharmacy_orders', ['pharmacy_id' => $this->pharmacy->id]);
    }

    public function test_verify_prescription_endpoint(): void
    {
        $order = PharmacyOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'pharmacy_id' => $this->pharmacy->id,
        ]);

        $response = $this->postJson('/api/v1/pharmacy-orders/verify-prescription', [
            'prescription_id' => $order->id,
            'verified_by' => 'pharmacist_001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_pharmacy_order_validates_medicines_json(): void
    {
        $data = [
            'pharmacy_id' => $this->pharmacy->id,
            'medicines' => 'invalid_json', // Не JSON
            'prescription_file' => 'prescription.pdf',
            'client_phone' => '+79991234567',
        ];

        $response = $this->postJson('/api/v1/pharmacy-orders', $data);

        $response->assertStatus(422);
    }

    public function test_pharmacy_order_validates_phone(): void
    {
        $data = [
            'pharmacy_id' => $this->pharmacy->id,
            'medicines' => json_encode([['name' => 'Аспирин', 'quantity' => 10]]),
            'prescription_file' => 'prescription.pdf',
            'client_phone' => 'invalid', // Неправильный формат
        ];

        $response = $this->postJson('/api/v1/pharmacy-orders', $data);

        $response->assertStatus(422);
    }

    public function test_pharmacy_order_age_verification(): void
    {
        // Если заказ содержит рецептурные препараты, система должна верифицировать возраст
        $data = [
            'pharmacy_id' => $this->pharmacy->id,
            'medicines' => json_encode([
                ['name' => 'Кодеин', 'quantity' => 10, 'is_prescription' => true],
            ]),
            'prescription_file' => 'prescription.pdf',
            'client_phone' => '+79991234567',
            'client_age_verified' => true, // Должен быть >= 18
        ];

        $response = $this->postJson('/api/v1/pharmacy-orders', $data);

        // Если возрастная верификация отсутствует, должна быть ошибка 422
        if (!data_get($data, 'client_age_verified')) {
            $response->assertStatus(422);
        } else {
            $response->assertStatus(201);
        }
    }

    public function test_pharmacy_order_tenant_scoping(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherPharmacy = Pharmacy::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherOrder = PharmacyOrder::factory()->create([
            'tenant_id' => $otherTenant->id,
            'pharmacy_id' => $otherPharmacy->id,
        ]);

        $response = $this->getJson('/api/v1/pharmacy-orders');

        $orderIds = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($otherOrder->id, $orderIds);
    }
}
