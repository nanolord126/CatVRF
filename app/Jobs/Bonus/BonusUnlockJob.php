<?php

declare(strict_types=1);

namespace App\Jobs\Bonus;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

/**
 * Class BonusUnlockJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class BonusUnlockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('default');
    }

    public function handle(BonusService $bonusService): void
    {
        try {
            $unlockedCount = $bonusService->unlockExpiredHolds();

            if ($unlockedCount > 0) {
                $this->logger->channel('audit')->$this->logger->info('Bonus unlock job completed', [
                    'correlation_id' => $this->correlationId,
                    'unlocked_count' => $unlockedCount,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Bonus unlock job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['bonus', 'unlock', 'payout'];
    }
}
