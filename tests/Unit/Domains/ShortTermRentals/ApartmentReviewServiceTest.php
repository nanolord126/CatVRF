<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ShortTermRentals;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApartmentReviewService.
 *
 * @covers \App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService
 */
final class ApartmentReviewServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ApartmentReviewService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ApartmentReviewService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ApartmentReviewService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createReview_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class, 'createReview'),
            'ApartmentReviewService must implement createReview()'
        );
    }

    public function test___toString_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class, '__toString'),
            'ApartmentReviewService must implement __toString()'
        );
    }

    public function test_toDebugArray_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService::class, 'toDebugArray'),
            'ApartmentReviewService must implement toDebugArray()'
        );
    }

}
