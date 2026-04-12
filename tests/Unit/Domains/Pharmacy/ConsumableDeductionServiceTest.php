<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Pharmacy;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsumableDeductionService.
 *
 * @covers \App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService
 */
final class ConsumableDeductionServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConsumableDeductionService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConsumableDeductionService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConsumableDeductionService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_deduct_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class, 'deduct'),
            'ConsumableDeductionService must implement deduct()'
        );
    }

    public function test___toString_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class, '__toString'),
            'ConsumableDeductionService must implement __toString()'
        );
    }

    public function test_toDebugArray_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pharmacy\Domain\Services\ConsumableDeductionService::class, 'toDebugArray'),
            'ConsumableDeductionService must implement toDebugArray()'
        );
    }

}
