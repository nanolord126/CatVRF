<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Domains\Electronics\Models\ElectronicOrder;
use App\Domains\Electronics\Models\ElectronicProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class ElectronicsWarrantyControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $user;
    private ElectronicProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = ElectronicProduct::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_create_electronics_order_with_warranty(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'warranty_months' => 24,
            'serial_number' => 'SN12345678901234',
            'imei' => '123456789012345', // Для мобильных
            'price' => 50000,
        ];

        $response = $this->postJson('/api/v1/electronics-orders', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.warranty_months', 24);
    }

    public function test_submit_warranty_claim(): void
    {
        $order = ElectronicOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
        ]);

        $data = [
            'issue_description' => 'Устройство не включается',
            'photo_url' => 'https://example.com/photo.jpg',
            'claim_type' => 'defect',
        ];

        $response = $this->postJson(
            "/api/v1/electronics-orders/{$order->id}/warranty-claim",
            $data
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('warranty_claims', [
            'electronic_order_id' => $order->id,
            'issue_description' => 'Устройство не включается',
        ]);
    }

    public function test_warranty_claim_validates_claim_type(): void
    {
        $order = ElectronicOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
        ]);

        $data = [
            'issue_description' => 'Проблема',
            'photo_url' => 'https://example.com/photo.jpg',
            'claim_type' => 'invalid_type', // Должен быть: defect, accident, etc
        ];

        $response = $this->postJson(
            "/api/v1/electronics-orders/{$order->id}/warranty-claim",
            $data
        );

        $response->assertStatus(422);
    }

    public function test_electronics_order_validates_serial_number(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'warranty_months' => 12,
            'serial_number' => '', // Обязательно для электроники
            'price' => 30000,
        ];

        $response = $this->postJson('/api/v1/electronics-orders', $data);

        $response->assertStatus(422);
    }

    public function test_electronics_order_warranty_period_validation(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'warranty_months' => 0, // Должно быть > 0
            'serial_number' => 'SN12345678901234',
            'price' => 30000,
        ];

        $response = $this->postJson('/api/v1/electronics-orders', $data);

        $response->assertStatus(422);
    }

    public function test_warranty_claim_after_warranty_expired(): void
    {
        $order = ElectronicOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'warranty_expires_at' => now()->subDays(1), // Истёкла гарантия
        ]);

        $data = [
            'issue_description' => 'Проблема',
            'photo_url' => 'https://example.com/photo.jpg',
            'claim_type' => 'defect',
        ];

        $response = $this->postJson(
            "/api/v1/electronics-orders/{$order->id}/warranty-claim",
            $data
        );

        // Должна быть ошибка: гарантия истекла
        $response->assertStatus(422);
    }
}
