<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Http\Middleware\FraudCheckMiddleware;

/**
 * Unit tests for FraudCheckMiddleware.
 *
 * @covers \App\Http\Middleware\FraudCheckMiddleware
 */
final class FraudCheckMiddlewareTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(FraudCheckMiddleware::class));
    }

    public function test_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists(FraudCheckMiddleware::class, 'handle'),
            'FraudCheckMiddleware must have handle()'
        );
    }

    public function test_handle_signature(): void
    {
        $reflection = new \ReflectionMethod(FraudCheckMiddleware::class, 'handle');
        $this->assertGreaterThanOrEqual(2, $reflection->getNumberOfParameters());
    }
}
