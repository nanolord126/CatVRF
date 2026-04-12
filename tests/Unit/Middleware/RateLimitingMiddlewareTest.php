<?php declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RateLimitingMiddleware.
 *
 * @covers \App\Http\Middleware\RateLimitingMiddleware
 */
final class RateLimitingMiddlewareTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\RateLimitingMiddleware::class));
    }

    public function test_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Middleware\RateLimitingMiddleware::class, 'handle'),
            'RateLimitingMiddleware must have handle()'
        );
    }

    public function test_handle_signature(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Middleware\RateLimitingMiddleware::class, 'handle');
        $this->assertGreaterThanOrEqual(2, $reflection->getNumberOfParameters());
    }
}
