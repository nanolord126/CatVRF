<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auto;

use PHPUnit\Framework\TestCase;
use App\Domains\Auto\Domain\Services\AutoAIService;

/**
 * Unit tests for AutoAIService.
 *
 * @covers \App\Domains\Auto\Domain\Services\AutoAIService
 */
final class AutoAIServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AutoAIService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoAIService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AutoAIService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AutoAIService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AutoAIService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AutoAIService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_estimate_repair_from_photo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoAIService::class, 'estimateRepairFromPhoto'),
            'AutoAIService must implement estimateRepairFromPhoto()'
        );
    }

    public function test_recommend_vehicle_for_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoAIService::class, 'recommendVehicleForUser'),
            'AutoAIService must implement recommendVehicleForUser()'
        );
    }
}
