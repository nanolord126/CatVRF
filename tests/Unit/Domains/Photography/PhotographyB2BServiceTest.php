<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Photography;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PhotographyB2BService.
 *
 * @covers \App\Domains\Photography\Domain\Services\PhotographyB2BService
 */
final class PhotographyB2BServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\PhotographyB2BService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PhotographyB2BService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\PhotographyB2BService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PhotographyB2BService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\PhotographyB2BService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PhotographyB2BService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBatchCorporateBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Photography\Domain\Services\PhotographyB2BService::class, 'createBatchCorporateBooking'),
            'PhotographyB2BService must implement createBatchCorporateBooking()'
        );
    }

}
