<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Geo;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserAddressService.
 *
 * @covers \App\Domains\Geo\Domain\Services\UserAddressService
 */
final class UserAddressServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\UserAddressService::class
        );
        $this->assertTrue($reflection->isFinal(), 'UserAddressService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\UserAddressService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'UserAddressService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\UserAddressService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'UserAddressService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_addOrGetAddress_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\UserAddressService::class, 'addOrGetAddress'),
            'UserAddressService must implement addOrGetAddress()'
        );
    }

    public function test_getAddressHistory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\UserAddressService::class, 'getAddressHistory'),
            'UserAddressService must implement getAddressHistory()'
        );
    }

}
