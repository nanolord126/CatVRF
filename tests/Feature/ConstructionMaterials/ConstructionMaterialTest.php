<?php declare(strict_types=1);

namespace Tests\Feature\ConstructionMaterials;

use Tests\TestCase;
use App\Domains\ConstructionMaterials\Models\ConstructionMaterial;
use App\Domains\ConstructionMaterials\Models\MaterialOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class ConstructionMaterialTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['tenant_id' => 1]);
    }

    public function test_can_create_material(): void
    {
        $material = ConstructionMaterial::factory()->create(['tenant_id' => 1]);

        $this->assertDatabaseHas('construction_materials', [
            'id' => $material->id,
            'sku' => $material->sku,
            'tenant_id' => 1,
        ]);
    }

    public function test_can_order_material(): void
    {
        $material = ConstructionMaterial::factory()->create([
            'tenant_id' => 1,
            'current_stock' => 100,
            'price' => 50000,
        ]);

        $order = MaterialOrder::create([
            'tenant_id' => 1,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'material_id' => $material->id,
            'user_id' => $this->user->id,
            'quantity' => 10,
            'unit_price' => $material->price,
            'total_price' => $material->price * 10,
            'status' => 'pending',
            'delivery_address' => 'Test Address, 123',
        ]);

        $this->assertDatabaseHas('material_orders', [
            'id' => $order->id,
            'material_id' => $material->id,
            'quantity' => 10,
            'total_price' => 500000,
        ]);
    }

    public function test_cannot_order_more_than_stock(): void
    {
        $material = ConstructionMaterial::factory()->create([
            'tenant_id' => 1,
            'current_stock' => 5,
            'price' => 50000,
        ]);

        $this->assertFalse($material->current_stock >= 10);
        $this->assertTrue($material->current_stock >= 5);
    }

    public function test_material_low_stock_flag(): void
    {
        $material = ConstructionMaterial::factory()->lowStock()->create([
            'tenant_id' => 1,
            'min_stock_threshold' => 10,
        ]);

        $this->assertTrue($material->isLowStock());
    }

    public function test_material_high_stock_flag(): void
    {
        $material = ConstructionMaterial::factory()->highStock()->create([
            'tenant_id' => 1,
            'min_stock_threshold' => 50,
        ]);

        $this->assertFalse($material->isLowStock());
    }

    public function test_order_delivery_date_update(): void
    {
        $material = ConstructionMaterial::factory()->create(['tenant_id' => 1]);
        $order = MaterialOrder::factory()->create([
            'material_id' => $material->id,
            'status' => 'pending',
        ]);

        $order->update([
            'status' => 'delivered',
            'delivery_date' => now(),
        ]);

        $this->assertTrue($order->isDelivered());
        $this->assertNotNull($order->delivery_date);
    }
}
