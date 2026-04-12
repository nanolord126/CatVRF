<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FashionBrandService.
 *
 * @covers \App\Domains\Fashion\Domain\Services\FashionBrandService
 */
final class FashionBrandServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionBrandService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FashionBrandService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionBrandService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FashionBrandService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Domain\Services\FashionBrandService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FashionBrandService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBrand_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Fashion\Domain\Services\FashionBrandService::class, 'createBrand'),
            'FashionBrandService must implement createBrand()'
        );
    }

}
