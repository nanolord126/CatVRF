<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FashionService.
 *
 * @covers \App\Domains\Fashion\Domain\Services\FashionService
 */
final class FashionServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FashionService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FashionService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FashionService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getCartForUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fashion\Domain\Services\FashionService::class, 'getCartForUser'),
            'FashionService must implement getCartForUser()'
        );
    }

    public function test_reserveItem_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fashion\Domain\Services\FashionService::class, 'reserveItem'),
            'FashionService must implement reserveItem()'
        );
    }

    public function test_calculateB2BPrice_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fashion\Domain\Services\FashionService::class, 'calculateB2BPrice'),
            'FashionService must implement calculateB2BPrice()'
        );
    }

    public function test_getDisplayPrice_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fashion\Domain\Services\FashionService::class, 'getDisplayPrice'),
            'FashionService must implement getDisplayPrice()'
        );
    }

}
