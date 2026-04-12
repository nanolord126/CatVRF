<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CRM;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AutoCrmService.
 *
 * @covers \App\Domains\CRM\Domain\Services\AutoCrmService
 */
final class AutoCrmServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Domain\Services\AutoCrmService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoCrmService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Domain\Services\AutoCrmService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AutoCrmService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Domain\Services\AutoCrmService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AutoCrmService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createAutoProfile_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Domain\Services\AutoCrmService::class, 'createAutoProfile'),
            'AutoCrmService must implement createAutoProfile()'
        );
    }

    public function test_recordServiceVisit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Domain\Services\AutoCrmService::class, 'recordServiceVisit'),
            'AutoCrmService must implement recordServiceVisit()'
        );
    }

    public function test_updateMileage_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Domain\Services\AutoCrmService::class, 'updateMileage'),
            'AutoCrmService must implement updateMileage()'
        );
    }

    public function test_scheduleNextService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Domain\Services\AutoCrmService::class, 'scheduleNextService'),
            'AutoCrmService must implement scheduleNextService()'
        );
    }

    public function test_updateInsurance_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CRM\Domain\Services\AutoCrmService::class, 'updateInsurance'),
            'AutoCrmService must implement updateInsurance()'
        );
    }

}
