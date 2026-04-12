<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Veterinary\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Pet model.
 *
 * @covers \App\Domains\Veterinary\Models\Pet
 */
final class PetTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Veterinary\Models\Pet::class
        );
        $this->assertTrue($reflection->isFinal(), 'Pet must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Veterinary\Models\Pet();
        $this->assertNotEmpty($model->getFillable(), 'Pet must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Veterinary\Models\Pet();
        $this->assertNotEmpty($model->getCasts(), 'Pet must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Veterinary\Models\Pet();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
