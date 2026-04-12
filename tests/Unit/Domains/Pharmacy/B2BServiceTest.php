<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Pharmacy;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Pharmacy\Domain\Services\B2BService::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Domain\Services\B2BService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'B2BService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Domain\Services\B2BService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'B2BService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_purchaseBatch_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pharmacy\Domain\Services\B2BService::class, 'purchaseBatch'),
            'B2BService must implement purchaseBatch()'
        );
    }

    public function test_verifyAndExecute_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pharmacy\Domain\Services\B2BService::class, 'verifyAndExecute'),
            'B2BService must implement verifyAndExecute()'
        );
    }

}
