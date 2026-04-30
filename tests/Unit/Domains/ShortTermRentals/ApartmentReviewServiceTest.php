<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ShortTermRentals;

use PHPUnit\Framework\TestCase;
use App\Domains\ShortTermRentals\Domain\Services\ApartmentReviewService;

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
            ApartmentReviewService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ApartmentReviewService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ApartmentReviewService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ApartmentReviewService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ApartmentReviewService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ApartmentReviewService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_review_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ApartmentReviewService::class, 'createReview'),
            'ApartmentReviewService must implement createReview()'
        );
    }

    public function test___to_string_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ApartmentReviewService::class, '__toString'),
            'ApartmentReviewService must implement __toString()'
        );
    }

    public function test_to_debug_array_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ApartmentReviewService::class, 'toDebugArray'),
            'ApartmentReviewService must implement toDebugArray()'
        );
    }
}
