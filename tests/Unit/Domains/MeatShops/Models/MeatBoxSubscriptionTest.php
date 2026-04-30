<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\MeatShops\Models\MeatBoxSubscription;

/**
 * Unit tests for MeatBoxSubscription model.
 *
 * @covers \App\Domains\MeatShops\Models\MeatBoxSubscription
 */
final class MeatBoxSubscriptionTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            MeatBoxSubscription::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatBoxSubscription must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new MeatBoxSubscription();
        $this->assertNotEmpty($model->getFillable(), 'MeatBoxSubscription must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new MeatBoxSubscription();
        $this->assertNotEmpty($model->getCasts(), 'MeatBoxSubscription must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new MeatBoxSubscription();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
