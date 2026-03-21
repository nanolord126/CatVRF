<?php

declare(strict_types=1);

namespace Tests\Feature\MedicalSupplies;

use App\Domains\MedicalSupplies\Models\MedicalSupply;
use Database\Factories\MedicalSupplies\MedicalSupplyFactory;
use Tests\TestCase;

final class MedicalSupplyTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_medical_supply(): void
    {
        $supply = MedicalSupplyFactory::new()->create(['tenant_id' => 1]);
        $this->assertDatabaseHas('medical_supplies', ['id' => $supply->id]);
    }

    public function test_equipment_supply(): void
    {
        $supply = MedicalSupplyFactory::new()->equipment()->create(['tenant_id' => 1]);
        $this->assertGreaterThanOrEqual(50000, $supply->price);
    }

    public function test_consumables_supply(): void
    {
        $supply = MedicalSupplyFactory::new()->consumables()->create(['tenant_id' => 1]);
        $this->assertLessThanOrEqual(50000, $supply->price);
    }

    public function test_stock_management(): void
    {
        $supply = MedicalSupplyFactory::new()->create([
            'tenant_id' => 1,
            'current_stock' => 100,
            'min_stock_threshold' => 20,
        ]);
        $this->assertGreaterThanOrEqual($supply->min_stock_threshold, $supply->current_stock);
    }

    public function test_supply_category(): void
    {
        $supply = MedicalSupplyFactory::new()->create([
            'tenant_id' => 1,
            'category' => 'equipment',
        ]);
        $this->assertEquals('equipment', $supply->category);
    }

    public function test_supply_status(): void
    {
        $supply = MedicalSupplyFactory::new()->create(['tenant_id' => 1, 'status' => 'active']);
        $this->assertEquals('active', $supply->status);
    }
}
