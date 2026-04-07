<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Domain\Entities;

use App\Domains\Finances\Domain\Entities\FinancialTransaction;
use App\Domains\Finances\Domain\Enums\TransactionType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для FinancialTransaction domain entity.
 *
 * Покрытие: конструктор, геттеры, isCredit/isDebit, toArray,
 * getAmountInRubles, immutability.
 */
final class FinancialTransactionTest extends TestCase
{
    private function makeTransaction(
        int $id = 1,
        int $tenantId = 100,
        ?int $businessGroupId = null,
        int $walletId = 50,
        TransactionType $type = TransactionType::DEPOSIT,
        int $amount = 150000,
        array $metadata = [],
        ?CarbonImmutable $createdAt = null,
        string $correlationId = 'test-corr-123',
    ): FinancialTransaction {
        return new FinancialTransaction(
            id: $id,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            walletId: $walletId,
            type: $type,
            amount: $amount,
            metadata: $metadata,
            createdAt: $createdAt ?? CarbonImmutable::parse('2026-04-05 12:00:00'),
            correlationId: $correlationId,
        );
    }

    #[Test]
    public function it_exposes_all_getters_correctly(): void
    {
        $createdAt = CarbonImmutable::parse('2026-04-01 10:30:00');
        $metadata = ['source' => 'payment_gateway', 'order_id' => 777];

        $tx = $this->makeTransaction(
            id: 42,
            tenantId: 200,
            businessGroupId: 15,
            walletId: 88,
            type: TransactionType::COMMISSION,
            amount: 99900,
            metadata: $metadata,
            createdAt: $createdAt,
            correlationId: 'corr-abc-def',
        );

        self::assertSame(42, $tx->getId());
        self::assertSame(200, $tx->getTenantId());
        self::assertSame(15, $tx->getBusinessGroupId());
        self::assertSame(88, $tx->getWalletId());
        self::assertSame(TransactionType::COMMISSION, $tx->getType());
        self::assertSame(99900, $tx->getAmount());
        self::assertSame($metadata, $tx->getMetadata());
        self::assertTrue($createdAt->equalTo($tx->getCreatedAt()));
        self::assertSame('corr-abc-def', $tx->getCorrelationId());
    }

    #[Test]
    public function business_group_id_can_be_null(): void
    {
        $tx = $this->makeTransaction(businessGroupId: null);

        self::assertNull($tx->getBusinessGroupId());
    }

    #[Test]
    public function getAmountInRubles_converts_kopecks_correctly(): void
    {
        $tx = $this->makeTransaction(amount: 150050);
        self::assertSame(1500.5, $tx->getAmountInRubles());

        $tx2 = $this->makeTransaction(amount: 100);
        self::assertSame(1.0, $tx2->getAmountInRubles());

        $tx3 = $this->makeTransaction(amount: 1);
        self::assertSame(0.01, $tx3->getAmountInRubles());

        $tx4 = $this->makeTransaction(amount: 0);
        self::assertSame(0.0, $tx4->getAmountInRubles());
    }

    #[Test]
    public function isCredit_delegates_to_transaction_type(): void
    {
        $deposit = $this->makeTransaction(type: TransactionType::DEPOSIT);
        self::assertTrue($deposit->isCredit());
        self::assertFalse($deposit->isDebit());

        $bonus = $this->makeTransaction(type: TransactionType::BONUS);
        self::assertTrue($bonus->isCredit());
    }

    #[Test]
    public function isDebit_delegates_to_transaction_type(): void
    {
        $payout = $this->makeTransaction(type: TransactionType::PAYOUT);
        self::assertTrue($payout->isDebit());
        self::assertFalse($payout->isCredit());

        $hold = $this->makeTransaction(type: TransactionType::HOLD);
        self::assertTrue($hold->isDebit());
    }

    #[Test]
    public function toArray_returns_complete_structure(): void
    {
        $createdAt = CarbonImmutable::parse('2026-03-15 08:00:00');
        $tx = $this->makeTransaction(
            id: 10,
            tenantId: 300,
            businessGroupId: 5,
            walletId: 70,
            type: TransactionType::REFUND,
            amount: 250000,
            metadata: ['reason' => 'duplicate_charge'],
            createdAt: $createdAt,
            correlationId: 'corr-xyz',
        );

        $array = $tx->toArray();

        self::assertSame(10, $array['id']);
        self::assertSame(300, $array['tenant_id']);
        self::assertSame(5, $array['business_group_id']);
        self::assertSame(70, $array['wallet_id']);
        self::assertSame('refund', $array['type']);
        self::assertSame(250000, $array['amount']);
        self::assertSame(2500.0, $array['amount_rubles']);
        self::assertSame(['reason' => 'duplicate_charge'], $array['metadata']);
        self::assertSame('corr-xyz', $array['correlation_id']);
        self::assertSame($createdAt->toIso8601String(), $array['created_at']);
    }

    #[Test]
    public function toArray_handles_null_business_group(): void
    {
        $tx = $this->makeTransaction(businessGroupId: null);
        $array = $tx->toArray();

        self::assertNull($array['business_group_id']);
    }

    #[Test]
    public function toArray_contains_all_required_keys(): void
    {
        $tx = $this->makeTransaction();
        $array = $tx->toArray();

        $requiredKeys = [
            'id', 'tenant_id', 'business_group_id', 'wallet_id',
            'type', 'amount', 'amount_rubles', 'metadata',
            'correlation_id', 'created_at',
        ];

        foreach ($requiredKeys as $key) {
            self::assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }
}
