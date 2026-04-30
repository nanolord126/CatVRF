<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\EventPlanning;

use PHPUnit\Framework\TestCase;
use App\Domains\EventPlanning\Domain\Services\EventFinancialPolicyService;

/**
 * Unit tests for EventFinancialPolicyService.
 *
 * @covers \App\Domains\EventPlanning\Domain\Services\EventFinancialPolicyService
 */
final class EventFinancialPolicyServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            EventFinancialPolicyService::class
        );
        $this->assertTrue($reflection->isFinal(), 'EventFinancialPolicyService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            EventFinancialPolicyService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'EventFinancialPolicyService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            EventFinancialPolicyService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'EventFinancialPolicyService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_calculate_required_prepayment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(EventFinancialPolicyService::class, 'calculateRequiredPrepayment'),
            'EventFinancialPolicyService must implement calculateRequiredPrepayment()'
        );
    }

    public function test_calculate_cancellation_fee_method_exists(): void
    {
        $this->assertTrue(
            method_exists(EventFinancialPolicyService::class, 'calculateCancellationFee'),
            'EventFinancialPolicyService must implement calculateCancellationFee()'
        );
    }

    public function test_distribute_budget_method_exists(): void
    {
        $this->assertTrue(
            method_exists(EventFinancialPolicyService::class, 'distributeBudget'),
            'EventFinancialPolicyService must implement distributeBudget()'
        );
    }
}
