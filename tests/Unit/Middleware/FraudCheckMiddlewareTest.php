<?php declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FraudCheckMiddleware.
 *
 * @covers \App\Http\Middleware\FraudCheckMiddleware
 */
final class FraudCheckMiddlewareTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\FraudCheckMiddleware::class));
    }

    public function test_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Middleware\FraudCheckMiddleware::class, 'handle'),
            'FraudCheckMiddleware must have handle()'
        );
    }

    public function test_handle_signature(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Middleware\FraudCheckMiddleware::class, 'handle');
        $this->assertGreaterThanOrEqual(2, $reflection->getNumberOfParameters());
    }
}
