<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Wallet model.
 *
 * @covers \App\Domains\Wallet\Models\Wallet
 */
final class WalletTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\Models\Wallet::class
        );
        $this->assertTrue($reflection->isFinal(), 'Wallet must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Wallet\Models\Wallet();
        $this->assertNotEmpty($model->getFillable(), 'Wallet must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Wallet\Models\Wallet();
        $this->assertNotEmpty($model->getCasts(), 'Wallet must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Wallet\Models\Wallet();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
