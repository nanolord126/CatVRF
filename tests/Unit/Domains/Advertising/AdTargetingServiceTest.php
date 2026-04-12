<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Advertising;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AdTargetingService.
 *
 * @covers \App\Domains\Advertising\Domain\Services\AdTargetingService
 */
final class AdTargetingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Advertising\Domain\Services\AdTargetingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AdTargetingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Advertising\Domain\Services\AdTargetingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AdTargetingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Advertising\Domain\Services\AdTargetingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AdTargetingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_filterCampaignsForUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Advertising\Domain\Services\AdTargetingService::class, 'filterCampaignsForUser'),
            'AdTargetingService must implement filterCampaignsForUser()'
        );
    }

}
