<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\Services\BonusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * ProcessBonusAwardJob - Queue job for bonus award processing
 * 
 * Processes bonus awards asynchronously to avoid blocking user requests.
 * Handles fraud checks, eligibility validation, and event dispatching.
 */
final readonly class ProcessBonusAwardJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly AwardBonusDto $dto,
    ) {}

    public function handle(BonusService $bonusService, LoggerInterface $logger): void
    {
        try {
            $bonusService->award($this->dto);

            $logger->info('Bonus award processed successfully', [
                'user_id' => $this->dto->userId,
                'amount' => $this->dto->amount,
                'correlation_id' => $this->dto->correlationId,
            ]);
        } catch (\Exception $e) {
            $logger->error('Bonus award processing failed', [
                'user_id' => $this->dto->userId,
                'amount' => $this->dto->amount,
                'error' => $e->getMessage(),
                'correlation_id' => $this->dto->correlationId,
            ]);

            throw $e;
        }
    }
}
