<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Recommendation\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Recommendation\Models\Recommendation;

/**
 * Unit tests for Recommendation model.
 *
 * @covers \App\Domains\Recommendation\Models\Recommendation
 */
final class RecommendationTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Recommendation::class
        );
        $this->assertTrue($reflection->isFinal(), 'Recommendation must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Recommendation();
        $this->assertNotEmpty($model->getFillable(), 'Recommendation must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Recommendation();
        $this->assertNotEmpty($model->getCasts(), 'Recommendation must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Recommendation();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
