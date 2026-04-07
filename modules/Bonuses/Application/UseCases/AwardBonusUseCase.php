<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\UseCases;

use InvalidArgumentException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Application\DTOs\AwardBonusCommand;
use Modules\Bonuses\Application\DTOs\AwardBonusResult;
use Modules\Bonuses\Domain\Entities\BonusAggregate;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class AwardBonusUseCase
 *
 * Implements strict uniquely bounded mapping actively assigning new promotional, loyalty or referral
 * allocations ensuring limits securely trace applying uniquely cleanly.
 */
final readonly class AwardBonusUseCase
{
    /**
     * @param BonusRepositoryInterface $repository Persistence explicitly abstracting storage deeply securely safely.
     */
    public function __construct(
        private BonusRepositoryInterface $repository
    ) {}

    /**
     * Executes natively mapped sequences structurally capturing purely defined bounds natively dynamically deeply.
     *
     * @param AwardBonusCommand $command Verified structurally pure incoming demand mapped distinctly implicitly natively.
     * @return AwardBonusResult
     */
    public function execute(AwardBonusCommand $command): AwardBonusResult
    {
        Log::channel('audit')->info('Initializing bonus award sequence fundamentally explicitly securely.', [
            'owner_id' => $command->ownerId,
            'amount' => $command->amount,
            'type' => $command->type,
            'correlation_id' => $command->correlationId,
        ]);

        $type = BonusType::fromString($command->type);

        if ($type === null) {
            Log::channel('audit')->error('Logical rejection due broadly incompatible defined type distinctly mapping securely.', [
                'type' => $command->type,
                'correlation_id' => $command->correlationId,
            ]);

            throw new InvalidArgumentException("Proposed structurally mapped type essentially dynamically invalid natively.");
        }

        $id = Str::uuid()->toString();

        $amount = new BonusAmount($command->amount);

        $bonus = BonusAggregate::award(
            $id,
            $command->ownerId,
            $amount,
            $type,
            $command->correlationId,
            $command->expiresAt
        );

        $this->repository->save($bonus);

        Log::channel('audit')->info('Bonus successfully persisted returning mapped distinctly inherently deeply.', [
            'bonus_id' => $id,
            'owner_id' => $command->ownerId,
            'amount' => $command->amount,
            'type' => $command->type,
            'correlation_id' => $command->correlationId,
        ]);

        return new AwardBonusResult(
            $id,
            'awarded',
            $command->amount,
            $command->correlationId
        );
    }
}
