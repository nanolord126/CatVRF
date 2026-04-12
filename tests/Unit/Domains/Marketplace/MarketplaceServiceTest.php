<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Marketplace;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MarketplaceService.
 *
 * @covers \App\Domains\Marketplace\Domain\Services\MarketplaceService
 */
final class MarketplaceServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Marketplace\Domain\Services\MarketplaceService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MarketplaceService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Marketplace\Domain\Services\MarketplaceService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MarketplaceService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Marketplace\Domain\Services\MarketplaceService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MarketplaceService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Marketplace\Domain\Services\MarketplaceService::class, 'create'),
            'MarketplaceService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Marketplace\Domain\Services\MarketplaceService::class, 'update'),
            'MarketplaceService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Marketplace\Domain\Services\MarketplaceService::class, 'delete'),
            'MarketplaceService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Marketplace\Domain\Services\MarketplaceService::class, 'list'),
            'MarketplaceService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Marketplace\Domain\Services\MarketplaceService::class, 'getById'),
            'MarketplaceService must implement getById()'
        );
    }

}
