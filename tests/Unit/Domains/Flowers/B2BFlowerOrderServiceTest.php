<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Flowers;

use PHPUnit\Framework\TestCase;
use App\Domains\Flowers\Domain\Services\B2BFlowerOrderService;

/**
 * Unit tests for B2BFlowerOrderService.
 *
 * @covers \App\Domains\Flowers\Domain\Services\B2BFlowerOrderService
 */
final class B2BFlowerOrderServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            B2BFlowerOrderService::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BFlowerOrderService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            B2BFlowerOrderService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'B2BFlowerOrderService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            B2BFlowerOrderService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'B2BFlowerOrderService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_b2_b_order_method_exists(): void
    {
        $this->assertTrue(
            method_exists(B2BFlowerOrderService::class, 'createB2BOrder'),
            'B2BFlowerOrderService must implement createB2BOrder()'
        );
    }
}
