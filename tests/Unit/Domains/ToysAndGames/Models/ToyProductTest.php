<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ToysAndGames\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ToyProduct model.
 *
 * @covers \App\Domains\ToysAndGames\Models\ToyProduct
 */
final class ToyProductTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ToysAndGames\Models\ToyProduct::class
        );
        $this->assertTrue($reflection->isFinal(), 'ToyProduct must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\ToysAndGames\Models\ToyProduct();
        $this->assertNotEmpty($model->getFillable(), 'ToyProduct must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\ToysAndGames\Models\ToyProduct();
        $this->assertNotEmpty($model->getCasts(), 'ToyProduct must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\ToysAndGames\Models\ToyProduct();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
