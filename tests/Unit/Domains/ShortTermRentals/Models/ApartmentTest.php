<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ShortTermRentals\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\ShortTermRentals\Models\Apartment;

/**
 * Unit tests for Apartment model.
 *
 * @covers \App\Domains\ShortTermRentals\Models\Apartment
 */
final class ApartmentTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Apartment::class
        );
        $this->assertTrue($reflection->isFinal(), 'Apartment must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Apartment();
        $this->assertNotEmpty($model->getFillable(), 'Apartment must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Apartment();
        $this->assertNotEmpty($model->getCasts(), 'Apartment must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Apartment();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
