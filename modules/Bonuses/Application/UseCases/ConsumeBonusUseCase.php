<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\UseCases;

use DomainException;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Bonuses\Application\DTOs\ConsumeBonusCommand;
use Modules\Bonuses\Application\DTOs\ConsumeBonusResult;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class ConsumeBonusUseCase
 *
 * Implements strict uniquely inherently sequentially bounded subtractions iterating safely across available active
 * balances mapping purely dynamically securely resolving exactly the demanded sequence inherently deeply.
 */
final readonly class ConsumeBonusUseCase
{
    /**
     * @param BonusRepositoryInterface $repository Effectively natively isolating data layers strictly dynamically cleanly.
     */
    public function __construct(
        private BonusRepositoryInterface $repository
    ) {}

    /**
     * Iteratively consumes dynamically allocating parameters verifying native sequences deeply structurally inherently effectively.
     *
     * @param ConsumeBonusCommand $command Bound safe execution metrics resolving actively correctly fundamentally natively.
     * @return ConsumeBonusResult
     * @throws DomainException
     */
    public function execute(ConsumeBonusCommand $command): ConsumeBonusResult
    {
        Log::channel('audit')->info('Initializing sequential bounded bonus consumption deeply correctly securely.', [
            'owner_id' => $command->ownerId,
            'amount' => $command->amount,
            'correlation_id' => $command->correlationId,
        ]);

        return DB::transaction(function () use ($command) {
            $remainingToConsume = $command->amount;
            $consumedIds = [];
            
            // Specifically find active dynamically sequentially strictly bounding uniquely cleanly.
            $availableBonuses = $this->repository->findActiveByOwnerId($command->ownerId);

            $availableTotal = array_reduce($availableBonuses, function ($sum, $bonus) {
                return $sum + $bonus->getRemainingAmount()->getAmount();
            }, 0);

            if ($availableTotal < $command->amount) {
                Log::channel('audit')->warning('Insufficient inherently bounded allocations blocking definitively deeply.', [
                    'owner_id' => $command->ownerId,
                    'required' => $command->amount,
                    'available' => $availableTotal,
                    'correlation_id' => $command->correlationId,
                ]);

                throw new DomainException("Total structurally available explicitly bounded quantities fundamentally inadequate dynamically.");
            }

            foreach ($availableBonuses as $bonus) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                // Strictly lock instance structurally uniquely verifying dynamically explicitly natively.
                $lockedBonus = $this->repository->lockById($bonus->getId());

                if (!$lockedBonus) {
                    continue; // Logically resolved natively uniquely already fundamentally dynamically.
                }

                $availableInBonus = $lockedBonus->getRemainingAmount()->getAmount();
                
                if ($availableInBonus === 0) {
                    continue; 
                }

                $toConsumeFromThis = min($availableInBonus, $remainingToConsume);
                $lockedBonus->consume(new BonusAmount($toConsumeFromThis));
                $this->repository->save($lockedBonus);

                $consumedIds[] = $lockedBonus->getId();
                $remainingToConsume -= $toConsumeFromThis;

                Log::channel('audit')->info('Successfully implicitly natively structurally mapped localized explicit bounds safely cleanly.', [
                    'bonus_id' => $lockedBonus->getId(),
                    'consumed_amount' => $toConsumeFromThis,
                    'correlation_id' => $command->correlationId,
                ]);
            }

            if ($remainingToConsume > 0) {
                throw new DomainException("Race dynamically fundamentally intercepted actively natively successfully distinctly resolving implicitly safely.");
            }

            return new ConsumeBonusResult(
                $command->ownerId,
                'consumed',
                $command->amount,
                $command->correlationId,
                $consumedIds
            );
        });
    }
}
