<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Application\Services\BonusesFacadeService;
use Modules\Bonuses\Domain\Enums\BonusType;

/**
 * Job ProcessBonusAwardJob
 *
 * Asynchronous job for processing bonus awards.
 * Used for high-volume bonus distributions or when immediate processing is not required.
 * Decouples bonus awarding from the request-response cycle for better performance.
 */
final class ProcessBonusAwardJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'bonuses';
    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param  string  $ownerId  The entity receiving the bonus.
     * @param  string  $type  The bonus type as string.
     * @param  int  $amount  The bonus amount.
     * @param  array  $context  Additional context.
     */
    public function __construct(
        public readonly string $ownerId,
        public readonly string $type,
        public readonly int $amount,
        public readonly array $context = []
    ) {}

    /**
     * Executes the bonus award process.
     */
    public function handle(BonusesFacadeService $bonusesFacade): void
    {
        try {
            $bonusType = BonusType::fromString($this->type);

            $bonus = $bonusesFacade->awardBonus(
                ownerId: $this->ownerId,
                type: $bonusType,
                amount: $this->amount,
                context: $this->context
            );

            Log::channel('bonuses')->info('Bonus award job completed', [
                'bonus_id' => $bonus->getId(),
                'owner_id' => $this->ownerId,
                'amount' => $this->amount,
                'type' => $this->type,
                'job_id' => $this->job?->getJobId(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Bonus award job failed', [
                'owner_id' => $this->ownerId,
                'amount' => $this->amount,
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handles job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('bonuses')->error('Bonus award job failed permanently', [
            'owner_id' => $this->ownerId,
            'amount' => $this->amount,
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
