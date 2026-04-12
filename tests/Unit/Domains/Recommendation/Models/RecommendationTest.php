<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Recommendation\Models;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Recommendation\Models\Recommendation::class
        );
        $this->assertTrue($reflection->isFinal(), 'Recommendation must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Recommendation\Models\Recommendation();
        $this->assertNotEmpty($model->getFillable(), 'Recommendation must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Recommendation\Models\Recommendation();
        $this->assertNotEmpty($model->getCasts(), 'Recommendation must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Recommendation\Models\Recommendation();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
