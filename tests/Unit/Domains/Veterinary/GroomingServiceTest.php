<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Veterinary;

use PHPUnit\Framework\TestCase;
use App\Domains\Veterinary\Domain\Services\GroomingService;

/**
 * Unit tests for GroomingService.
 *
 * @covers \App\Domains\Veterinary\Domain\Services\GroomingService
 */
final class GroomingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            GroomingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GroomingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            GroomingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GroomingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            GroomingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GroomingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_book_session_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GroomingService::class, 'bookSession'),
            'GroomingService must implement bookSession()'
        );
    }

    public function test_complete_and_tag_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GroomingService::class, 'completeAndTag'),
            'GroomingService must implement completeAndTag()'
        );
    }
}
