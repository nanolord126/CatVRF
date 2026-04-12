<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FarmDirect;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FarmDirectService.
 *
 * @covers \App\Domains\FarmDirect\Domain\Services\FarmDirectService
 */
final class FarmDirectServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmDirectService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FarmDirectService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmDirectService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FarmDirectService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmDirectService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FarmDirectService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmDirectService::class, 'createOrder'),
            'FarmDirectService must implement createOrder()'
        );
    }

    public function test_markShipped_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmDirectService::class, 'markShipped'),
            'FarmDirectService must implement markShipped()'
        );
    }

    public function test_markDelivered_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmDirectService::class, 'markDelivered'),
            'FarmDirectService must implement markDelivered()'
        );
    }

    public function test_getProductsBySeason_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmDirectService::class, 'getProductsBySeason'),
            'FarmDirectService must implement getProductsBySeason()'
        );
    }

    public function test_getVerifiedFarms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmDirectService::class, 'getVerifiedFarms'),
            'FarmDirectService must implement getVerifiedFarms()'
        );
    }

}
