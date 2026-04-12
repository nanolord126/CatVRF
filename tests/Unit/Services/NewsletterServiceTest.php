<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NewsletterService.
 *
 * @covers \App\Services\Marketing\NewsletterService
 */
final class NewsletterServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Marketing\NewsletterService::class);
        $this->assertTrue($reflection->isFinal(), 'NewsletterService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'NewsletterService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Marketing\NewsletterService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createAndSend_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\NewsletterService::class, 'createAndSend'),
            'NewsletterService must implement createAndSend()'
        );
    }

    public function test_trackOpen_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\NewsletterService::class, 'trackOpen'),
            'NewsletterService must implement trackOpen()'
        );
    }

    public function test_trackClick_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\NewsletterService::class, 'trackClick'),
            'NewsletterService must implement trackClick()'
        );
    }

}
