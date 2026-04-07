<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Queries;

use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;

/**
 * Query: Получить текущий баланс кошелька (копейки).
 */
final readonly class GetWalletBalanceQuery
{
    public function __construct(
        private WalletRepositoryInterface $wallets,
    ) {}

    public function execute(int $userId, int $tenantId): int
    {
        $wallet = $this->wallets->findOrCreateByUser($userId, $tenantId);

        return $wallet->getBalance()->toKopeks();
    }
}
