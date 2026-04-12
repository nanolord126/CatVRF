<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Marketplace\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MarketplaceListing model.
 *
 * @covers \App\Domains\Marketplace\Models\MarketplaceListing
 */
final class MarketplaceListingTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Marketplace\Models\MarketplaceListing::class
        );
        $this->assertTrue($reflection->isFinal(), 'MarketplaceListing must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Marketplace\Models\MarketplaceListing();
        $this->assertNotEmpty($model->getFillable(), 'MarketplaceListing must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Marketplace\Models\MarketplaceListing();
        $this->assertNotEmpty($model->getCasts(), 'MarketplaceListing must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Marketplace\Models\MarketplaceListing();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
