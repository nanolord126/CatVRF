<?php declare(strict_types=1);

namespace Tests\Unit\Domains\WeddingPlanning;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WeddingService.
 *
 * @covers \App\Domains\WeddingPlanning\Domain\Services\WeddingService
 */
final class WeddingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\WeddingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'WeddingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\WeddingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'WeddingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\WeddingPlanning\Domain\Services\WeddingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'WeddingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createWedding_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\WeddingService::class, 'createWedding'),
            'WeddingService must implement createWedding()'
        );
    }

    public function test_bookService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\WeddingService::class, 'bookService'),
            'WeddingService must implement bookService()'
        );
    }

    public function test_updateStatus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\WeddingPlanning\Domain\Services\WeddingService::class, 'updateStatus'),
            'WeddingService must implement updateStatus()'
        );
    }

}
