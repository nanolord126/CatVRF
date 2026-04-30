<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Http\Middleware\AgeVerificationMiddleware;

/**
 * Unit tests for AgeVerificationMiddleware.
 *
 * @covers \App\Http\Middleware\AgeVerificationMiddleware
 */
final class AgeVerificationMiddlewareTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(AgeVerificationMiddleware::class));
    }

    public function test_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists(AgeVerificationMiddleware::class, 'handle'),
            'AgeVerificationMiddleware must have handle()'
        );
    }

    public function test_handle_signature(): void
    {
        $reflection = new \ReflectionMethod(AgeVerificationMiddleware::class, 'handle');
        $this->assertGreaterThanOrEqual(2, $reflection->getNumberOfParameters());
    }
}
