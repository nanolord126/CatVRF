<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AI\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\AI\Models\AIModel;

/**
 * Unit tests for AIModel model.
 *
 * @covers \App\Domains\AI\Models\AIModel
 */
final class AIModelTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AIModel::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIModel must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new AIModel();
        $this->assertNotEmpty($model->getFillable(), 'AIModel must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new AIModel();
        $this->assertNotEmpty($model->getCasts(), 'AIModel must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new AIModel();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
