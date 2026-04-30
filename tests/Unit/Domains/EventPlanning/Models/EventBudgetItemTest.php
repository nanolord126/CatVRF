<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\EventPlanning\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\EventPlanning\Models\EventBudgetItem;

/**
 * Unit tests for EventBudgetItem model.
 *
 * @covers \App\Domains\EventPlanning\Models\EventBudgetItem
 */
final class EventBudgetItemTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            EventBudgetItem::class
        );
        $this->assertTrue($reflection->isFinal(), 'EventBudgetItem must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new EventBudgetItem();
        $this->assertNotEmpty($model->getFillable(), 'EventBudgetItem must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new EventBudgetItem();
        $this->assertNotEmpty($model->getCasts(), 'EventBudgetItem must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new EventBudgetItem();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
