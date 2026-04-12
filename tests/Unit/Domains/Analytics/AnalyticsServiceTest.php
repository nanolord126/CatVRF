<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Analytics;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnalyticsService.
 *
 * @covers \App\Domains\Analytics\Domain\Services\AnalyticsService
 */
final class AnalyticsServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Analytics\Domain\Services\AnalyticsService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AnalyticsService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Analytics\Domain\Services\AnalyticsService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AnalyticsService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Analytics\Domain\Services\AnalyticsService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AnalyticsService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Analytics\Domain\Services\AnalyticsService::class, 'create'),
            'AnalyticsService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Analytics\Domain\Services\AnalyticsService::class, 'update'),
            'AnalyticsService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Analytics\Domain\Services\AnalyticsService::class, 'delete'),
            'AnalyticsService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Analytics\Domain\Services\AnalyticsService::class, 'list'),
            'AnalyticsService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Analytics\Domain\Services\AnalyticsService::class, 'getById'),
            'AnalyticsService must implement getById()'
        );
    }

}
