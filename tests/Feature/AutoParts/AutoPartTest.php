<?php

declare(strict_types=1);

namespace Tests\Feature\AutoParts;

use App\Domains\AutoParts\Models\AutoPart;
use Database\Factories\AutoParts\AutoPartFactory;
use Tests\TestCase;

final class AutoPartTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_auto_part(): void
    {
        $part = AutoPartFactory::new()->create(['tenant_id' => 1]);
        $this->assertDatabaseHas('auto_parts', ['id' => $part->id]);
    }

    public function test_oem_part(): void
    {
        $part = AutoPartFactory::new()->oem()->create(['tenant_id' => 1]);
        $this->assertGreaterThanOrEqual(100000, $part->price);
    }

    public function test_aftermarket_part(): void
    {
        $part = AutoPartFactory::new()->aftermarket()->create(['tenant_id' => 1]);
        $this->assertLessThanOrEqual(100000, $part->price);
    }

    public function test_part_category(): void
    {
        $part = AutoPartFactory::new()->create(['tenant_id' => 1, 'category' => 'engine']);
        $this->assertEquals('engine', $part->category);
    }

    public function test_stock_available(): void
    {
        $part = AutoPartFactory::new()->create(['tenant_id' => 1, 'current_stock' => 50]);
        $this->assertGreaterThan(0, $part->current_stock);
    }

    public function test_part_status_active(): void
    {
        $part = AutoPartFactory::new()->create(['tenant_id' => 1, 'status' => 'active']);
        $this->assertEquals('active', $part->status);
    }
}
