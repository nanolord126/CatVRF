<?php declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Listing model.
 *
 * @covers \App\Domains\RealEstate\Models\Listing
 */
final class ListingTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Models\Listing::class
        );
        $this->assertTrue($reflection->isFinal(), 'Listing must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\RealEstate\Models\Listing();
        $this->assertNotEmpty($model->getFillable(), 'Listing must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\RealEstate\Models\Listing();
        $this->assertNotEmpty($model->getCasts(), 'Listing must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\RealEstate\Models\Listing();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
