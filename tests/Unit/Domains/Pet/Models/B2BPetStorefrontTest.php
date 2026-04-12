<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Pet\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BPetStorefront model.
 *
 * @covers \App\Domains\Pet\Models\B2BPetStorefront
 */
final class B2BPetStorefrontTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pet\Models\B2BPetStorefront::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BPetStorefront must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Pet\Models\B2BPetStorefront();
        $this->assertNotEmpty($model->getFillable(), 'B2BPetStorefront must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Pet\Models\B2BPetStorefront();
        $this->assertNotEmpty($model->getCasts(), 'B2BPetStorefront must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Pet\Models\B2BPetStorefront();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
