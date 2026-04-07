<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Adapters\Storage;

use Modules\Wallet\Domain\WalletRepositoryPort;
use Modules\Wallet\Domain\WalletAggregate;
use Modules\Wallet\Domain\ValueObjects\Money;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Modules\Wallet\Infrastructure\Models\WalletModel;
use RuntimeException;
use Throwable;

/**
 * Class EloquentWalletRepository
 *
 * A strict Data Mapper implementation of the WalletRepositoryPort using Laravel Eloquent.
 * It is responsible for bridging the divide between the strict Domain Aggregate Root
 * and the underlying relational database structure without exposing Eloquent to the Domain.
 */
final readonly class EloquentWalletRepository implements WalletRepositoryPort
{
    /**
     * Finds a Wallet by its unique identity and locks the row for an atomic update.
     * This method ensures pessimistic locking to prevent race conditions during concurrent transactions.
     *
     * @param string $walletId The exact UUID identity of the wallet.
     * @return WalletAggregate|null The reconstituted Domain Aggregate, or null if missing.
     * @throws RuntimeException If called outside an active transaction context.
     */
    public function findByIdForUpdate(string $walletId): ?WalletAggregate
    {
        try {
            /** @var WalletModel|null $model */
            $model = WalletModel::query()
                ->where('id', $walletId)
                ->lockForUpdate() // Crucial for concurrent balance correctness
                ->first();

            if ($model === null) {
                return null;
            }

            return $this->toDomainContext($model);

        } catch (Throwable $exception) {
            // Re-wrapping raw PDO/SQL exceptions to maintain abstract infrastructure boundaries
            throw new RuntimeException(
                "Failed to retrieve and lock Wallet with ID [{$walletId}]: " . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Persists the given WalletAggregate state back into the underlying database.
     * Identifies natively whether this is a new creation or an update to an existing record.
     *
     * @param WalletAggregate $wallet The Domain Aggregate housing the current uncommitted state.
     * @return void
     * @throws RuntimeException If an error occurs translating domain state into persistence.
     */
    public function save(WalletAggregate $wallet): void
    {
        try {
            $model = WalletModel::query()->find($wallet->getId());

            if ($model === null) {
                // Instantiating a new persistent representation
                $model = new WalletModel();
                $model->id = $wallet->getId();
                $model->tenant_id = $wallet->getTenantId();
            }

            // Syncing domain primitive values down to persistent scalar fields
            $model->current_balance = $wallet->getBalance()->getAmount();
            $model->correlation_id = $wallet->getCorrelationId();

            // Store structural modifications back to the exact storage table
            $saved = $model->save();

            if (!$saved) {
                throw new RuntimeException("Eloquent rejected the save mechanism for Wallet [{$wallet->getId()}].");
            }

        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Failed to persist Wallet Aggregate [{$wallet->getId()}]: " . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Internal factory translating persistent Eloquent states into Domain Aggregate entities.
     * This rigidly avoids exposing persistent properties outside the framework boundaries.
     *
     * @param WalletModel $model The raw generic data representation.
     * @return WalletAggregate The reconstituted clean Domain Aggregate.
     */
    private function toDomainContext(WalletModel $model): WalletAggregate
    {
        return new WalletAggregate(
            id: $model->id,
            tenantId: $model->tenant_id,
            balance: clone new Money($model->current_balance),
            correlationId: clone (string) $model->correlation_id
        );
    }
}
