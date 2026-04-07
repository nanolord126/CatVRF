<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Repositories;

use Modules\Wallet\Domain\Entities\WalletAggregate;

/**
 * Outgoing Port: Хранилище кошельков.
 */
interface WalletRepositoryInterface
{
    public function findByUser(int $userId, int $tenantId): ?WalletAggregate;

    public function findOrCreateByUser(int $userId, int $tenantId): WalletAggregate;

    public function save(WalletAggregate $wallet): void;

    public function lockForUpdate(int $userId, int $tenantId): ?WalletAggregate;
}
