<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Security\IdempotencyService;

/**
 * Unit tests for IdempotencyService.
 *
 * @covers \App\Services\Security\IdempotencyService
 */
final class IdempotencyServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(IdempotencyService::class);
        $this->assertTrue($reflection->isFinal(), 'IdempotencyService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'IdempotencyService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(IdempotencyService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_check_method_exists(): void
    {
        $this->assertTrue(
            method_exists(IdempotencyService::class, 'check'),
            'IdempotencyService must implement check()'
        );
    }

    public function test_record_method_exists(): void
    {
        $this->assertTrue(
            method_exists(IdempotencyService::class, 'record'),
            'IdempotencyService must implement record()'
        );
    }

    public function test_cleanup_method_exists(): void
    {
        $this->assertTrue(
            method_exists(IdempotencyService::class, 'cleanup'),
            'IdempotencyService must implement cleanup()'
        );
    }

    public function test_get_record_method_exists(): void
    {
        $this->assertTrue(
            method_exists(IdempotencyService::class, 'getRecord'),
            'IdempotencyService must implement getRecord()'
        );
    }
}
