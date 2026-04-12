<?php declare(strict_types=1);

namespace Tests\Unit\Domains\EventPlanning\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Event model.
 *
 * @covers \App\Domains\EventPlanning\Models\Event
 */
final class EventTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\EventPlanning\Models\Event::class
        );
        $this->assertTrue($reflection->isFinal(), 'Event must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\EventPlanning\Models\Event();
        $this->assertNotEmpty($model->getFillable(), 'Event must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\EventPlanning\Models\Event();
        $this->assertNotEmpty($model->getCasts(), 'Event must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\EventPlanning\Models\Event();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
