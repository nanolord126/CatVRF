<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\Domain\Entities\WalletAggregate;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Infrastructure\Models\WalletModel;
use Modules\Wallet\Infrastructure\Models\WalletTransactionModel;

/**
 * Репозиторий кошелька, работающий через Eloquent и таблицы bavix/laravel-wallet.
 */
final class BavixWalletRepository implements WalletRepositoryInterface
{
    public function findByUser(int $userId, int $tenantId): ?WalletAggregate
    {
        $model = WalletModel::where([
            'holder_type' => 'App\\Models\\User',
            'holder_id'   => $userId,
            'slug'        => 'default',
        ])->first();

        return $model ? $this->toDomain($model, $userId, $tenantId) : null;
    }

    public function findOrCreateByUser(int $userId, int $tenantId): WalletAggregate
    {
        $model = WalletModel::firstOrCreate(
            [
                'holder_type' => 'App\\Models\\User',
                'holder_id'   => $userId,
                'slug'        => 'default',
            ],
            [
                'name'           => 'Default',
                'uuid'           => (string) Str::uuid(),
                'balance'        => 0,
                'decimal_places' => 2,
                'meta'           => ['tenant_id' => $tenantId],
            ]
        );

        return $this->toDomain($model, $userId, $tenantId);
    }

    public function lockForUpdate(int $userId, int $tenantId): ?WalletAggregate
    {
        $model = WalletModel::where([
            'holder_type' => 'App\\Models\\User',
            'holder_id'   => $userId,
            'slug'        => 'default',
        ])->lockForUpdate()->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model, $userId, $tenantId);
    }

    public function save(WalletAggregate $wallet): void
    {
        // Сохранить баланс и hold
        WalletModel::where([
            'holder_type' => 'App\\Models\\User',
            'holder_id'   => $wallet->getUserId(),
            'slug'        => 'default',
        ])->update([
            'balance' => $wallet->getBalance()->toKopeks(),
            'meta'    => [
                'tenant_id'   => $wallet->getTenantId(),
                'hold_amount' => $wallet->getHoldAmount()->toKopeks(),
            ],
        ]);

        // Записать все новые транзакции
        foreach ($wallet->popPendingTransactions() as $tx) {
            WalletTransactionModel::create([
                'payable_type' => 'App\\Models\\User',
                'payable_id'   => $wallet->getUserId(),
                'wallet_id'    => $this->resolveWalletId($wallet->getUserId()),
                'type'         => $tx['type'],
                'amount'       => $tx['amount'],
                'confirmed'    => true,
                'uuid'         => (string) Str::uuid(),
                'meta'         => [
                    'description'    => $tx['description'],
                    'correlation_id' => $tx['correlation_id'],
                ],
            ]);
        }
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function toDomain(WalletModel $model, int $userId, int $tenantId): WalletAggregate
    {
        $meta        = $model->meta ?? [];
        $holdKopeks  = (int) ($meta['hold_amount'] ?? 0);

        return WalletAggregate::reconstitute(
            id:         $model->id,
            userId:     $userId,
            tenantId:   $tenantId,
            balance:    Money::ofKopeks($model->balance),
            holdAmount: Money::ofKopeks($holdKopeks),
        );
    }

    private function resolveWalletId(int $userId): int
    {
        return (int) WalletModel::where([
            'holder_type' => 'App\\Models\\User',
            'holder_id'   => $userId,
            'slug'        => 'default',
        ])->value('id');
    }
}
