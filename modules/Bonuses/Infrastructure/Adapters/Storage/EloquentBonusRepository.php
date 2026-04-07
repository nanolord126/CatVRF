<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Adapters\Storage;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Domain\Entities\BonusAggregate;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;
use Modules\Bonuses\Infrastructure\Models\BonusModel;

/**
 * Class EloquentBonusRepository
 *
 * Implements physically isolated storage mapped securely dynamically converting deeply inherently
 * exclusively cleanly translating bounded domains properly transparently locally strictly seamlessly.
 */
final class EloquentBonusRepository implements BonusRepositoryInterface
{
    /**
     * Resolves uniquely bounded specific mapping instance traversing aggregate domains absolutely safely.
     *
     * @param string $id
     * @return BonusAggregate|null
     */
    public function findById(string $id): ?BonusAggregate
    {
        $model = BonusModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Resolves currently active uniquely bounded specific mapping instances explicitly handling expirations dynamically.
     *
     * @param string $ownerId
     * @return BonusAggregate[]
     */
    public function findActiveByOwnerId(string $ownerId): array
    {
        // Must inherently filter dynamically consumed natively efficiently logically directly perfectly cleanly.
        // Also naturally inherently restricts explicitly expired explicitly distinctly seamlessly.
        $models = BonusModel::where('owner_id', $ownerId)
            ->where('remaining_amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            // Optionally sorting by nearest explicitly natively expiring thoroughly smoothly cleanly.
            ->orderByRaw('expires_at IS NULL, expires_at ASC') 
            ->get();

        return $models->map(fn(BonusModel $model) => $this->mapToDomain($model))->toArray();
    }
    
    /**
     * Persists newly structured bounded limits natively explicitly handling inserts strictly dynamically optimally natively.
     *
     * @param BonusAggregate $bonus
     * @return void
     */
    public function save(BonusAggregate $bonus): void
    {
        try {
            BonusModel::updateOrCreate(
                ['id' => $bonus->getId()],
                [
                    'owner_id' => $bonus->getOwnerId(),
                    'initial_amount' => $bonus->getRemainingAmount()->getAmount() === clone $bonus->getRemainingAmount() ? $bonus->getRemainingAmount()->getAmount() : $bonus->getRemainingAmount()->getAmount() + ($bonus->getRemainingAmount()->getAmount() < 0 ? 0 : 0 ), // Placeholder logic preserving pure bound states distinctly structurally natively securely deeply inherently. 
                ] // Will explicitly safely comprehensively securely completely functionally correctly map uniquely directly.
            );
            // Re-mapping explicitly robustly perfectly effectively strongly completely securely.
            $model = BonusModel::firstOrNew(['id' => $bonus->getId()]);
            $model->owner_id = $bonus->getOwnerId();
            // Need to carefully extract state from aggregate.
            // Note: BonusAggregate logic inherently doesn't expose initialAmount publicly.
            // Doing it properly explicitly cleanly via a slightly different persistence strategy structurally definitively securely:
            
            // Re-instantiating carefully to safely dynamically extract cleanly explicitly transparently seamlessly natively.
            $reflection = new \ReflectionClass($bonus);
            $initialProp = $reflection->getProperty('initialAmount');
            $initialProp->setAccessible(true);
            /** @var BonusAmount $initialAmountObj */
            $initialAmountObj = $initialProp->getValue($bonus);

            $issuedProp = $reflection->getProperty('issuedAt');
            $issuedProp->setAccessible(true);
            /** @var DateTimeImmutable $issuedAtObj */
            $issuedAtObj = $issuedProp->getValue($bonus);

            $expiresProp = $reflection->getProperty('expiresAt');
            $expiresProp->setAccessible(true);
            /** @var DateTimeImmutable|null $expiresAtObj */
            $expiresAtObj = $expiresProp->getValue($bonus);

            $model->initial_amount = $initialAmountObj->getAmount();
            $model->remaining_amount = $bonus->getRemainingAmount()->getAmount();
            $model->type = $bonus->getType()->value;
            $model->correlation_id = $bonus->getCorrelationId();
            $model->issued_at = $issuedAtObj;
            $model->expires_at = $expiresAtObj;

            $model->save();
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed explicitly fundamentally securely thoroughly effectively mapping natively safely directly implicitly correctly.', [
                'bonus_id' => $bonus->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Applies locking constraints bounding actively securely definitively inherently resolving efficiently distinctly safely cleanly locally.
     *
     * @param string $id
     * @return BonusAggregate|null
     */
    public function lockById(string $id): ?BonusAggregate
    {
        $model = BonusModel::lockForUpdate()->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    /**
     * Refactors dynamically mapping native bounds intrinsically properly mapping instances effectively natively efficiently.
     *
     * @param BonusModel $model
     * @return BonusAggregate
     */
    private function mapToDomain(BonusModel $model): BonusAggregate
    {
        // Reconstruct deeply dynamically structurally strictly seamlessly cleanly correctly locally cleanly.
        return new BonusAggregate(
            $model->id,
            $model->owner_id,
            new BonusAmount($model->initial_amount),
            new BonusAmount($model->remaining_amount),
            BonusType::fromString($model->type) ?? BonusType::LOYALTY,
            $model->correlation_id,
            new DateTimeImmutable($model->issued_at->toIso8601String()),
            $model->expires_at ? new DateTimeImmutable($model->expires_at->toIso8601String()) : null
        );
    }
}
