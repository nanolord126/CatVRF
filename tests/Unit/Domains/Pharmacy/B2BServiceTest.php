<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Pharmacy;

use PHPUnit\Framework\TestCase;
use App\Domains\Pharmacy\Domain\Services\B2BService;

/**
 * Unit tests for B2BService.
 *
 * @covers \App\Domains\Pharmacy\Domain\Services\B2BService
 */
final class B2BServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            B2BService::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            B2BService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'B2BService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            B2BService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'B2BService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_purchase_batch_method_exists(): void
    {
        $this->assertTrue(
            method_exists(B2BService::class, 'purchaseBatch'),
            'B2BService must implement purchaseBatch()'
        );
    }

    public function test_verify_and_execute_method_exists(): void
    {
        $this->assertTrue(
            method_exists(B2BService::class, 'verifyAndExecute'),
            'B2BService must implement verifyAndExecute()'
        );
    }
}
