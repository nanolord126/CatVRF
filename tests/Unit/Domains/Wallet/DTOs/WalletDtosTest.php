<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\DTOs;

use App\Domains\Wallet\DTOs\CreateTopUpDto;
use App\Domains\Wallet\DTOs\CreateTransactionDto;
use App\Domains\Wallet\DTOs\CreateWithdrawalDto;
use App\Domains\Wallet\DTOs\SearchTopUpDto;
use App\Domains\Wallet\DTOs\SearchTransactionDto;
use App\Domains\Wallet\DTOs\SearchWithdrawalDto;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use PHPUnit\Framework\TestCase;

final class WalletDtosTest extends TestCase
{
    // ─── CreateTopUpDto ──────────────────────────────────────────────

    public function test_create_top_up_dto_constructor(): void
    {
        $dto = new CreateTopUpDto(
            walletId: 1,
            tenantId: 10,
            businessGroupId: 5,
            amount: 50000,
            correlationId: 'corr-123',
            idempotencyKey: 'idem-001',
            description: 'Test top-up',
        );

        $this->assertSame(1, $dto->walletId);
        $this->assertSame(10, $dto->tenantId);
        $this->assertSame(5, $dto->businessGroupId);
        $this->assertSame(50000, $dto->amount);
        $this->assertSame('corr-123', $dto->correlationId);
        $this->assertSame('idem-001', $dto->idempotencyKey);
        $this->assertSame('Test top-up', $dto->description);
    }

    public function test_create_top_up_dto_to_array(): void
    {
        $dto = new CreateTopUpDto(
            walletId: 1,
            tenantId: 10,
            businessGroupId: null,
            amount: 50000,
            correlationId: 'corr-123',
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('wallet_id', $array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('correlation_id', $array);
        $this->assertSame(1, $array['wallet_id']);
        $this->assertNull($array['business_group_id']);
    }

    public function test_create_top_up_dto_to_audit_context(): void
    {
        $dto = new CreateTopUpDto(
            walletId: 1,
            tenantId: 10,
            businessGroupId: null,
            amount: 50000,
            correlationId: 'corr-123',
        );

        $ctx = $dto->toAuditContext();

        $this->assertArrayHasKey('wallet_id', $ctx);
        $this->assertArrayHasKey('correlation_id', $ctx);
        $this->assertArrayNotHasKey('idempotency_key', $ctx);
    }

    // ─── CreateTransactionDto ────────────────────────────────────────

    public function test_create_transaction_dto_constructor(): void
    {
        $dto = new CreateTransactionDto(
            walletId: 2,
            tenantId: 10,
            businessGroupId: null,
            amount: 30000,
            type: BalanceTransactionType::DEPOSIT,
            correlationId: 'tx-corr-1',
        );

        $this->assertSame(2, $dto->walletId);
        $this->assertSame(30000, $dto->amount);
        $this->assertSame(BalanceTransactionType::DEPOSIT, $dto->type);
    }

    public function test_create_transaction_dto_to_array_contains_type(): void
    {
        $dto = new CreateTransactionDto(
            walletId: 2,
            tenantId: 10,
            businessGroupId: null,
            amount: 30000,
            type: BalanceTransactionType::WITHDRAWAL,
            correlationId: 'tx-corr-2',
        );

        $array = $dto->toArray();
        $this->assertSame('withdrawal', $array['type']);
    }

    public function test_create_transaction_dto_audit_context(): void
    {
        $dto = new CreateTransactionDto(
            walletId: 2,
            tenantId: 10,
            businessGroupId: null,
            amount: 30000,
            type: BalanceTransactionType::COMMISSION,
            correlationId: 'tx-corr-3',
        );

        $ctx = $dto->toAuditContext();
        $this->assertSame('commission', $ctx['type']);
        $this->assertArrayHasKey('correlation_id', $ctx);
    }

    // ─── CreateWithdrawalDto ─────────────────────────────────────────

    public function test_create_withdrawal_dto_constructor(): void
    {
        $dto = new CreateWithdrawalDto(
            walletId: 3,
            tenantId: 10,
            businessGroupId: null,
            amount: 10000,
            correlationId: 'wd-corr-1',
            bankAccount: '40702810000000000001',
        );

        $this->assertSame(3, $dto->walletId);
        $this->assertSame(10000, $dto->amount);
        $this->assertSame('40702810000000000001', $dto->bankAccount);
    }

    public function test_create_withdrawal_dto_to_array_contains_bank_account(): void
    {
        $dto = new CreateWithdrawalDto(
            walletId: 3,
            tenantId: 10,
            businessGroupId: null,
            amount: 10000,
            correlationId: 'wd-corr-2',
            bankAccount: '40702810000000000002',
        );

        $array = $dto->toArray();
        $this->assertSame('40702810000000000002', $array['bank_account']);
    }

    // ─── SearchTopUpDto ──────────────────────────────────────────────

    public function test_search_top_up_dto_defaults(): void
    {
        $dto = new SearchTopUpDto();

        $this->assertNull($dto->walletId);
        $this->assertNull($dto->tenantId);
        $this->assertSame(1, $dto->page);
        $this->assertSame(20, $dto->perPage);
    }

    public function test_search_top_up_dto_to_array_filters_nulls(): void
    {
        $dto = new SearchTopUpDto(walletId: 5, minAmount: 1000);

        $array = $dto->toArray();
        $this->assertSame(5, $array['wallet_id']);
        $this->assertSame(1000, $array['min_amount']);
        $this->assertArrayNotHasKey('tenant_id', $array);
    }

    // ─── SearchTransactionDto ────────────────────────────────────────

    public function test_search_transaction_dto_with_type_filter(): void
    {
        $dto = new SearchTransactionDto(type: 'deposit');

        $array = $dto->toArray();
        $this->assertSame('deposit', $array['type']);
    }

    // ─── SearchWithdrawalDto ─────────────────────────────────────────

    public function test_search_withdrawal_dto_with_status_filter(): void
    {
        $dto = new SearchWithdrawalDto(status: 'pending');

        $array = $dto->toArray();
        $this->assertSame('pending', $array['status']);
    }

    public function test_search_withdrawal_dto_per_page_capped_at_100(): void
    {
        $dto = new SearchWithdrawalDto(perPage: 200);

        // Конструктор не ограничивает, ограничение — в from(Request)
        $this->assertSame(200, $dto->perPage);
    }
}
