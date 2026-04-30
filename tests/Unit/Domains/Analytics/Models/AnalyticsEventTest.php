<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Analytics\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Analytics\Models\AnalyticsEvent;

/**
 * Unit tests for AnalyticsEvent model.
 *
 * @covers \App\Domains\Analytics\Models\AnalyticsEvent
 */
final class AnalyticsEventTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AnalyticsEvent::class
        );
        $this->assertTrue($reflection->isFinal(), 'AnalyticsEvent must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new AnalyticsEvent();
        $this->assertNotEmpty($model->getFillable(), 'AnalyticsEvent must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new AnalyticsEvent();
        $this->assertNotEmpty($model->getCasts(), 'AnalyticsEvent must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new AnalyticsEvent();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
