<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\DTOs;

use App\Domains\Finances\Domain\Enums\TransactionType;
use App\Domains\Finances\DTOs\CreateTransactionDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для CreateTransactionDto.
 *
 * Покрытие: конструктор, toArray, toAuditContext, getAmountInRubles, isB2B.
 */
final class CreateTransactionDtoTest extends TestCase
{
    private function makeDto(
        int $tenantId = 1,
        ?int $businessGroupId = null,
        int $userId = 10,
        string $correlationId = 'corr-tx-test',
        int $walletId = 50,
        TransactionType $type = TransactionType::DEPOSIT,
        int $amount = 100000,
        string $description = 'Test deposit',
        array $metadata = [],
        ?string $idempotencyKey = null,
        bool $isB2B = false,
    ): CreateTransactionDto {
        return new CreateTransactionDto(
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            userId: $userId,
            correlationId: $correlationId,
            walletId: $walletId,
            type: $type,
            amount: $amount,
            description: $description,
            metadata: $metadata,
            idempotencyKey: $idempotencyKey,
            isB2B: $isB2B,
        );
    }

    #[Test]
    public function it_stores_all_properties(): void
    {
        $dto = $this->makeDto(
            tenantId: 5,
            businessGroupId: 3,
            userId: 77,
            correlationId: 'corr-999',
            walletId: 88,
            type: TransactionType::COMMISSION,
            amount: 250000,
            description: 'Platform commission',
            metadata: ['order_id' => 42],
            idempotencyKey: 'idem-key-123',
            isB2B: true,
        );

        self::assertSame(5, $dto->tenantId);
        self::assertSame(3, $dto->businessGroupId);
        self::assertSame(77, $dto->userId);
        self::assertSame('corr-999', $dto->correlationId);
        self::assertSame(88, $dto->walletId);
        self::assertSame(TransactionType::COMMISSION, $dto->type);
        self::assertSame(250000, $dto->amount);
        self::assertSame('Platform commission', $dto->description);
        self::assertSame(['order_id' => 42], $dto->metadata);
        self::assertSame('idem-key-123', $dto->idempotencyKey);
        self::assertTrue($dto->isB2B);
    }

    #[Test]
    public function defaults_are_applied(): void
    {
        $dto = new CreateTransactionDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 10,
            correlationId: 'corr',
            walletId: 50,
            type: TransactionType::DEPOSIT,
            amount: 100,
        );

        self::assertSame('', $dto->description);
        self::assertSame([], $dto->metadata);
        self::assertNull($dto->idempotencyKey);
        self::assertFalse($dto->isB2B);
    }

    #[Test]
    public function toArray_returns_correct_structure(): void
    {
        $dto = $this->makeDto(
            tenantId: 10,
            businessGroupId: 5,
            walletId: 99,
            type: TransactionType::PAYOUT,
            amount: 500000,
            description: 'Monthly payout',
            metadata: ['period' => '2026-03'],
        );

        $array = $dto->toArray();

        self::assertSame(10, $array['tenant_id']);
        self::assertSame(5, $array['business_group_id']);
        self::assertSame(99, $array['wallet_id']);
        self::assertSame('payout', $array['type']);
        self::assertSame(500000, $array['amount']);
        self::assertSame('Monthly payout', $array['description']);
        self::assertSame(['period' => '2026-03'], $array['metadata']);
        self::assertArrayHasKey('correlation_id', $array);
        self::assertArrayHasKey('user_id', $array);
    }

    #[Test]
    public function toArray_does_not_leak_idempotency_or_isB2B(): void
    {
        $dto = $this->makeDto(idempotencyKey: 'secret-key', isB2B: true);
        $array = $dto->toArray();

        self::assertArrayNotHasKey('idempotency_key', $array);
        self::assertArrayNotHasKey('is_b2b', $array);
    }

    #[Test]
    public function toAuditContext_contains_required_fields(): void
    {
        $dto = $this->makeDto(
            tenantId: 7,
            businessGroupId: 2,
            userId: 55,
            correlationId: 'audit-corr',
            walletId: 33,
            type: TransactionType::HOLD,
            amount: 77700,
            isB2B: true,
        );

        $ctx = $dto->toAuditContext();

        self::assertSame(7, $ctx['tenant_id']);
        self::assertSame(2, $ctx['business_group_id']);
        self::assertSame(55, $ctx['user_id']);
        self::assertSame('audit-corr', $ctx['correlation_id']);
        self::assertSame(33, $ctx['wallet_id']);
        self::assertSame('hold', $ctx['type']);
        self::assertSame(77700, $ctx['amount']);
        self::assertTrue($ctx['is_b2b']);
    }

    #[Test]
    public function getAmountInRubles_converts_correctly(): void
    {
        self::assertSame(1500.0, $this->makeDto(amount: 150000)->getAmountInRubles());
        self::assertSame(0.01, $this->makeDto(amount: 1)->getAmountInRubles());
        self::assertSame(0.0, $this->makeDto(amount: 0)->getAmountInRubles());
    }

    #[Test]
    public function null_business_group_for_b2c(): void
    {
        $dto = $this->makeDto(businessGroupId: null, isB2B: false);

        self::assertNull($dto->businessGroupId);
        self::assertFalse($dto->isB2B);
        self::assertNull($dto->toArray()['business_group_id']);
    }
}
