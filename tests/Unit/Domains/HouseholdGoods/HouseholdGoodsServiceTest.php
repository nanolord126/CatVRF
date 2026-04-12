<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HouseholdGoods;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HouseholdGoodsService.
 *
 * @covers \App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService
 */
final class HouseholdGoodsServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class
        );
        $this->assertTrue($reflection->isFinal(), 'HouseholdGoodsService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'HouseholdGoodsService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'HouseholdGoodsService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class, 'create'),
            'HouseholdGoodsService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class, 'update'),
            'HouseholdGoodsService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class, 'delete'),
            'HouseholdGoodsService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class, 'list'),
            'HouseholdGoodsService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HouseholdGoods\Domain\Services\HouseholdGoodsService::class, 'getById'),
            'HouseholdGoodsService must implement getById()'
        );
    }

}
