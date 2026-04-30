<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use App\Domains\Bonuses\Services\BonusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * ProcessBonusWithdrawalJob - Queue job for bonus withdrawal processing
 * 
 * Processes B2B bonus withdrawals asynchronously.
 * Integrates with payment system for actual payout.
 */
final readonly class ProcessBonusWithdrawalJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly WithdrawBonusDto $dto,
    ) {}

    public function handle(BonusService $bonusService, LoggerInterface $logger): void
    {
        try {
            $bonusService->withdraw($this->dto);

            $logger->info('Bonus withdrawal processed successfully', [
                'user_id' => $this->dto->userId,
                'amount' => $this->dto->amount,
                'correlation_id' => $this->dto->correlationId,
            ]);
        } catch (\Exception $e) {
            $logger->error('Bonus withdrawal processing failed', [
                'user_id' => $this->dto->userId,
                'amount' => $this->dto->amount,
                'error' => $e->getMessage(),
                'correlation_id' => $this->dto->correlationId,
            ]);

            throw $e;
        }
    }
}
