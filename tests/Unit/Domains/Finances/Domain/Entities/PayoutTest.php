<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Domain\Entities;

use App\Domains\Finances\Domain\Entities\Payout;
use App\Domains\Finances\Domain\Enums\PayoutStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для Payout domain entity.
 *
 * Покрытие: конструктор, геттеры, state machine (transitionTo),
 * isTerminal, toArray, processedAt проставляется при COMPLETED.
 */
final class PayoutTest extends TestCase
{
    private function makePayout(
        int $id = 1,
        int $tenantId = 100,
        ?int $businessGroupId = null,
        int $amount = 500000,
        PayoutStatus $status = PayoutStatus::DRAFT,
        ?CarbonImmutable $periodStart = null,
        ?CarbonImmutable $periodEnd = null,
        ?CarbonImmutable $processedAt = null,
        string $correlationId = 'payout-corr-123',
    ): Payout {
        return new Payout(
            id: $id,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            amount: $amount,
            status: $status,
            periodStart: $periodStart ?? CarbonImmutable::parse('2026-03-01'),
            periodEnd: $periodEnd ?? CarbonImmutable::parse('2026-03-31'),
            processedAt: $processedAt,
            correlationId: $correlationId,
        );
    }

    #[Test]
    public function it_exposes_all_getters_correctly(): void
    {
        $start = CarbonImmutable::parse('2026-01-01');
        $end = CarbonImmutable::parse('2026-01-31');
        $processed = CarbonImmutable::parse('2026-02-05 14:00:00');

        $payout = $this->makePayout(
            id: 42,
            tenantId: 200,
            businessGroupId: 10,
            amount: 999900,
            status: PayoutStatus::COMPLETED,
            periodStart: $start,
            periodEnd: $end,
            processedAt: $processed,
            correlationId: 'corr-pay-456',
        );

        self::assertSame(42, $payout->getId());
        self::assertSame(200, $payout->getTenantId());
        self::assertSame(10, $payout->getBusinessGroupId());
        self::assertSame(999900, $payout->getAmount());
        self::assertSame(9999.0, $payout->getAmountInRubles());
        self::assertSame(PayoutStatus::COMPLETED, $payout->getStatus());
        self::assertTrue($start->equalTo($payout->getPeriodStart()));
        self::assertTrue($end->equalTo($payout->getPeriodEnd()));
        self::assertTrue($processed->equalTo($payout->getProcessedAt()));
        self::assertSame('corr-pay-456', $payout->getCorrelationId());
    }

    #[Test]
    public function business_group_id_can_be_null(): void
    {
        $payout = $this->makePayout(businessGroupId: null);
        self::assertNull($payout->getBusinessGroupId());
    }

    #[Test]
    public function processedAt_is_null_for_non_completed(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::DRAFT);
        self::assertNull($payout->getProcessedAt());
    }

    // --- State Machine Tests ---

    #[Test]
    public function transition_draft_to_pending_succeeds(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::DRAFT);
        $payout->transitionTo(PayoutStatus::PENDING);

        self::assertSame(PayoutStatus::PENDING, $payout->getStatus());
    }

    #[Test]
    public function transition_pending_to_processing_succeeds(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::PENDING);
        $payout->transitionTo(PayoutStatus::PROCESSING);

        self::assertSame(PayoutStatus::PROCESSING, $payout->getStatus());
    }

    #[Test]
    public function transition_processing_to_completed_sets_processedAt(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::PROCESSING);

        self::assertNull($payout->getProcessedAt());

        $payout->transitionTo(PayoutStatus::COMPLETED);

        self::assertSame(PayoutStatus::COMPLETED, $payout->getStatus());
        self::assertNotNull($payout->getProcessedAt());
        self::assertInstanceOf(CarbonImmutable::class, $payout->getProcessedAt());
    }

    #[Test]
    public function transition_to_failed_from_pending_succeeds(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::PENDING);
        $payout->transitionTo(PayoutStatus::FAILED);

        self::assertSame(PayoutStatus::FAILED, $payout->getStatus());
    }

    #[Test]
    public function transition_to_cancelled_from_draft_succeeds(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::DRAFT);
        $payout->transitionTo(PayoutStatus::CANCELLED);

        self::assertSame(PayoutStatus::CANCELLED, $payout->getStatus());
    }

    #[Test]
    public function invalid_transition_throws_domain_exception(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::DRAFT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/draft.*completed/i');

        $payout->transitionTo(PayoutStatus::COMPLETED);
    }

    #[Test]
    public function transition_from_terminal_status_throws(): void
    {
        $completed = $this->makePayout(status: PayoutStatus::COMPLETED);
        $this->expectException(\DomainException::class);
        $completed->transitionTo(PayoutStatus::PENDING);
    }

    #[Test]
    public function transition_from_failed_throws(): void
    {
        $failed = $this->makePayout(status: PayoutStatus::FAILED);
        $this->expectException(\DomainException::class);
        $failed->transitionTo(PayoutStatus::DRAFT);
    }

    #[Test]
    public function transition_from_cancelled_throws(): void
    {
        $cancelled = $this->makePayout(status: PayoutStatus::CANCELLED);
        $this->expectException(\DomainException::class);
        $cancelled->transitionTo(PayoutStatus::DRAFT);
    }

    // --- isTerminal ---

    #[Test]
    #[DataProvider('terminalPayoutProvider')]
    public function isTerminal_reflects_current_status(PayoutStatus $status, bool $expected): void
    {
        $payout = $this->makePayout(status: $status);
        self::assertSame($expected, $payout->isTerminal());
    }

    public static function terminalPayoutProvider(): iterable
    {
        yield 'draft'      => [PayoutStatus::DRAFT, false];
        yield 'pending'    => [PayoutStatus::PENDING, false];
        yield 'processing' => [PayoutStatus::PROCESSING, false];
        yield 'completed'  => [PayoutStatus::COMPLETED, true];
        yield 'failed'     => [PayoutStatus::FAILED, true];
        yield 'cancelled'  => [PayoutStatus::CANCELLED, true];
    }

    // --- toArray ---

    #[Test]
    public function toArray_returns_complete_structure(): void
    {
        $start = CarbonImmutable::parse('2026-03-01');
        $end = CarbonImmutable::parse('2026-03-31');

        $payout = $this->makePayout(
            id: 7,
            tenantId: 300,
            businessGroupId: 20,
            amount: 1234500,
            status: PayoutStatus::PENDING,
            periodStart: $start,
            periodEnd: $end,
            correlationId: 'corr-arr-test',
        );

        $array = $payout->toArray();

        self::assertSame(7, $array['id']);
        self::assertSame(300, $array['tenant_id']);
        self::assertSame(20, $array['business_group_id']);
        self::assertSame(1234500, $array['amount']);
        self::assertSame(12345.0, $array['amount_rubles']);
        self::assertSame('pending', $array['status']);
        self::assertSame('2026-03-01', $array['period_start']);
        self::assertSame('2026-03-31', $array['period_end']);
        self::assertNull($array['processed_at']);
        self::assertSame('corr-arr-test', $array['correlation_id']);
    }

    #[Test]
    public function toArray_includes_processedAt_when_completed(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::PROCESSING);
        $payout->transitionTo(PayoutStatus::COMPLETED);

        $array = $payout->toArray();

        self::assertNotNull($array['processed_at']);
        self::assertIsString($array['processed_at']);
    }

    #[Test]
    public function full_lifecycle_draft_to_completed(): void
    {
        $payout = $this->makePayout(status: PayoutStatus::DRAFT);

        self::assertFalse($payout->isTerminal());

        $payout->transitionTo(PayoutStatus::PENDING);
        self::assertSame(PayoutStatus::PENDING, $payout->getStatus());

        $payout->transitionTo(PayoutStatus::PROCESSING);
        self::assertSame(PayoutStatus::PROCESSING, $payout->getStatus());

        $payout->transitionTo(PayoutStatus::COMPLETED);
        self::assertSame(PayoutStatus::COMPLETED, $payout->getStatus());
        self::assertTrue($payout->isTerminal());
        self::assertNotNull($payout->getProcessedAt());
    }

    #[Test]
    public function getAmountInRubles_converts_correctly(): void
    {
        self::assertSame(50.0, $this->makePayout(amount: 5000)->getAmountInRubles());
        self::assertSame(0.01, $this->makePayout(amount: 1)->getAmountInRubles());
        self::assertSame(0.0, $this->makePayout(amount: 0)->getAmountInRubles());
        self::assertSame(123456.78, $this->makePayout(amount: 12345678)->getAmountInRubles());
    }
}
