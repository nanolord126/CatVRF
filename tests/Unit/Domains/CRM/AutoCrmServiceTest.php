<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use PHPUnit\Framework\TestCase;
use App\Domains\CRM\Services\AutoCrmService;

/**
 * Unit tests for AutoCrmService.
 *
 * @covers \App\Domains\CRM\Services\AutoCrmService
 */
final class AutoCrmServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AutoCrmService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoCrmService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AutoCrmService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AutoCrmService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AutoCrmService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AutoCrmService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_auto_profile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoCrmService::class, 'createAutoProfile'),
            'AutoCrmService must implement createAutoProfile()'
        );
    }

    public function test_record_service_visit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoCrmService::class, 'recordServiceVisit'),
            'AutoCrmService must implement recordServiceVisit()'
        );
    }

    public function test_update_mileage_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoCrmService::class, 'updateMileage'),
            'AutoCrmService must implement updateMileage()'
        );
    }

    public function test_schedule_next_service_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoCrmService::class, 'scheduleNextService'),
            'AutoCrmService must implement scheduleNextService()'
        );
    }

    public function test_update_insurance_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AutoCrmService::class, 'updateInsurance'),
            'AutoCrmService must implement updateInsurance()'
        );
    }
}
