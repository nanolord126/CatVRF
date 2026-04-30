<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Freelance\Models\FreelanceOrder;

/**
 * Unit tests for FreelanceOrder model.
 *
 * @covers \App\Domains\Freelance\Models\FreelanceOrder
 */
final class FreelanceOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            FreelanceOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'FreelanceOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new FreelanceOrder();
        $this->assertNotEmpty($model->getFillable(), 'FreelanceOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new FreelanceOrder();
        $this->assertNotEmpty($model->getCasts(), 'FreelanceOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new FreelanceOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
