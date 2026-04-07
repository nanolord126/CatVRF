<?php

declare(strict_types=1);

namespace Modules\Finances\Interfaces;

use Illuminate\Support\Collection;
use Modules\Finances\Data\BalanceTransactionData;
use Modules\Finances\Data\WalletData;
use Modules\Finances\Enums\BalanceTransactionType;
use Modules\Finances\Models\Wallet;

/**
 * Interface WalletServiceInterface
 *
 * @method WalletData createWallet(int $tenantId, ?int $businessGroupId)
 * @method WalletData|null getWalletByTenant(int $tenantId)
 * @method WalletData|null getWalletByBusinessGroup(int $businessGroupId)
 * @method BalanceTransactionData credit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null)
 * @method BalanceTransactionData debit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null)
 * @method void hold(int $walletId, int $amount, string $correlationId, ?array $meta = null)
 * @method void releaseHold(int $walletId, int $amount, string $correlationId, ?array $meta = null)
 * @method int getBalance(int $walletId)
 * @method Collection<BalanceTransactionData> getTransactions(int $walletId)
 */
interface WalletServiceInterface
{
    public function createWallet(int $tenantId, ?int $businessGroupId, string $correlationId): Wallet;

    public function getWalletByTenant(int $tenantId): ?Wallet;

    public function getWalletByBusinessGroup(int $businessGroupId): ?Wallet;

    public function credit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null): BalanceTransactionData;

    public function debit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null): BalanceTransactionData;

    public function hold(int $walletId, int $amount, string $correlationId, ?array $meta = null): bool;

    public function releaseHold(int $walletId, int $amount, string $correlationId, ?array $meta = null): bool;

    public function getBalance(int $walletId): int;

    public function getTransactions(int $walletId): Collection;
}
