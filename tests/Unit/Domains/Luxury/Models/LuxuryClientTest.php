<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Luxury\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LuxuryClient model.
 *
 * @covers \App\Domains\Luxury\Models\LuxuryClient
 */
final class LuxuryClientTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Luxury\Models\LuxuryClient::class
        );
        $this->assertTrue($reflection->isFinal(), 'LuxuryClient must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryClient();
        $this->assertNotEmpty($model->getFillable(), 'LuxuryClient must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryClient();
        $this->assertNotEmpty($model->getCasts(), 'LuxuryClient must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryClient();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
