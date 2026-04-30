<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Tickets;

use PHPUnit\Framework\TestCase;
use App\Domains\Tickets\Domain\Services\TicketFraudService;

/**
 * Unit tests for TicketFraudService.
 *
 * @covers \App\Domains\Tickets\Domain\Services\TicketFraudService
 */
final class TicketFraudServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            TicketFraudService::class
        );
        $this->assertTrue($reflection->isFinal(), 'TicketFraudService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            TicketFraudService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'TicketFraudService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            TicketFraudService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'TicketFraudService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_check_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TicketFraudService::class, 'check'),
            'TicketFraudService must implement check()'
        );
    }

    public function test_validate_check_in_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TicketFraudService::class, 'validateCheckIn'),
            'TicketFraudService must implement validateCheckIn()'
        );
    }
}
