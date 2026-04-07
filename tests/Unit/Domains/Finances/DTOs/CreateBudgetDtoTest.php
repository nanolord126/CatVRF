<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\DTOs;

use App\Domains\Finances\DTOs\CreateBudgetDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для CreateBudgetDto.
 *
 * Покрытие: конструктор, toArray, toAuditContext, getAmountInRubles,
 * defaults, B2C vs B2B.
 */
final class CreateBudgetDtoTest extends TestCase
{
    private function makeDto(
        int $tenantId = 1,
        ?int $businessGroupId = null,
        int $userId = 10,
        string $correlationId = 'corr-budget',
        string $name = 'Q1 Marketing',
        int $amount = 10000000,
        string $currency = 'RUB',
        string $periodStart = '2026-01-01',
        string $periodEnd = '2026-03-31',
        array $metadata = [],
        ?string $idempotencyKey = null,
        bool $isB2B = false,
    ): CreateBudgetDto {
        return new CreateBudgetDto(
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            userId: $userId,
            correlationId: $correlationId,
            name: $name,
            amount: $amount,
            currency: $currency,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            metadata: $metadata,
            idempotencyKey: $idempotencyKey,
            isB2B: $isB2B,
        );
    }

    #[Test]
    public function it_stores_all_properties(): void
    {
        $dto = $this->makeDto(
            tenantId: 42,
            businessGroupId: 7,
            userId: 99,
            name: 'Advertising Budget',
            amount: 5000000,
            currency: 'USD',
            periodStart: '2026-04-01',
            periodEnd: '2026-06-30',
            metadata: ['vertical' => 'beauty'],
            idempotencyKey: 'idem-bgt-1',
            isB2B: true,
        );

        self::assertSame(42, $dto->tenantId);
        self::assertSame(7, $dto->businessGroupId);
        self::assertSame(99, $dto->userId);
        self::assertSame('Advertising Budget', $dto->name);
        self::assertSame(5000000, $dto->amount);
        self::assertSame('USD', $dto->currency);
        self::assertSame('2026-04-01', $dto->periodStart);
        self::assertSame('2026-06-30', $dto->periodEnd);
        self::assertSame(['vertical' => 'beauty'], $dto->metadata);
        self::assertSame('idem-bgt-1', $dto->idempotencyKey);
        self::assertTrue($dto->isB2B);
    }

    #[Test]
    public function defaults_are_applied(): void
    {
        $dto = new CreateBudgetDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 10,
            correlationId: 'corr',
            name: 'Test',
            amount: 100,
        );

        self::assertSame('RUB', $dto->currency);
        self::assertSame('', $dto->periodStart);
        self::assertSame('', $dto->periodEnd);
        self::assertSame([], $dto->metadata);
        self::assertNull($dto->idempotencyKey);
        self::assertFalse($dto->isB2B);
    }

    #[Test]
    public function toArray_returns_correct_structure(): void
    {
        $dto = $this->makeDto(
            tenantId: 100,
            businessGroupId: 20,
            userId: 55,
            correlationId: 'arr-test',
            name: 'Budget Alpha',
            amount: 7777700,
            currency: 'RUB',
            periodStart: '2026-01-01',
            periodEnd: '2026-12-31',
            metadata: ['source' => 'manual'],
        );

        $array = $dto->toArray();

        self::assertSame(100, $array['tenant_id']);
        self::assertSame(20, $array['business_group_id']);
        self::assertSame(55, $array['user_id']);
        self::assertSame('arr-test', $array['correlation_id']);
        self::assertSame('Budget Alpha', $array['name']);
        self::assertSame(7777700, $array['amount']);
        self::assertSame('RUB', $array['currency']);
        self::assertSame('2026-01-01', $array['period_start']);
        self::assertSame('2026-12-31', $array['period_end']);
        self::assertSame(['source' => 'manual'], $array['metadata']);
    }

    #[Test]
    public function toArray_does_not_leak_internal_flags(): void
    {
        $dto = $this->makeDto(idempotencyKey: 'key-123', isB2B: true);
        $array = $dto->toArray();

        self::assertArrayNotHasKey('idempotency_key', $array);
        self::assertArrayNotHasKey('is_b2b', $array);
    }

    #[Test]
    public function toAuditContext_contains_required_fields(): void
    {
        $dto = $this->makeDto(
            tenantId: 10,
            businessGroupId: 3,
            userId: 77,
            correlationId: 'audit-ctx',
            name: 'Test Budget',
            amount: 500000,
            isB2B: true,
        );

        $ctx = $dto->toAuditContext();

        self::assertSame(10, $ctx['tenant_id']);
        self::assertSame(3, $ctx['business_group_id']);
        self::assertSame(77, $ctx['user_id']);
        self::assertSame('audit-ctx', $ctx['correlation_id']);
        self::assertSame('Test Budget', $ctx['name']);
        self::assertSame(500000, $ctx['amount']);
        self::assertTrue($ctx['is_b2b']);
    }

    #[Test]
    public function getAmountInRubles_converts_kopecks(): void
    {
        self::assertSame(100000.0, $this->makeDto(amount: 10000000)->getAmountInRubles());
        self::assertSame(1.0, $this->makeDto(amount: 100)->getAmountInRubles());
        self::assertSame(0.01, $this->makeDto(amount: 1)->getAmountInRubles());
        self::assertSame(0.0, $this->makeDto(amount: 0)->getAmountInRubles());
    }

    #[Test]
    public function b2c_has_null_business_group(): void
    {
        $dto = $this->makeDto(businessGroupId: null, isB2B: false);
        self::assertNull($dto->businessGroupId);
        self::assertNull($dto->toArray()['business_group_id']);
    }

    #[Test]
    public function b2b_has_business_group_id(): void
    {
        $dto = $this->makeDto(businessGroupId: 15, isB2B: true);
        self::assertSame(15, $dto->businessGroupId);
        self::assertSame(15, $dto->toArray()['business_group_id']);
        self::assertTrue($dto->toAuditContext()['is_b2b']);
    }
}
