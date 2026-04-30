<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Marketing\NewsletterService;

/**
 * Unit tests for NewsletterService.
 *
 * @covers \App\Services\Marketing\NewsletterService
 */
final class NewsletterServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(NewsletterService::class);
        $this->assertTrue($reflection->isFinal(), 'NewsletterService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'NewsletterService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(NewsletterService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_and_send_method_exists(): void
    {
        $this->assertTrue(
            method_exists(NewsletterService::class, 'createAndSend'),
            'NewsletterService must implement createAndSend()'
        );
    }

    public function test_track_open_method_exists(): void
    {
        $this->assertTrue(
            method_exists(NewsletterService::class, 'trackOpen'),
            'NewsletterService must implement trackOpen()'
        );
    }

    public function test_track_click_method_exists(): void
    {
        $this->assertTrue(
            method_exists(NewsletterService::class, 'trackClick'),
            'NewsletterService must implement trackClick()'
        );
    }
}
