<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Domain\Events\BonusAwarded;
use Modules\Bonuses\Infrastructure\Services\WalletIntegrationService;

/**
 * Listener HandleBonusAwarded
 *
 * Handles the BonusAwarded event by integrating with the wallet system.
 * Credits the bonus amount to the user's wallet for immediate usability.
 * Processes asynchronously to avoid blocking the award flow.
 */
final class HandleBonusAwarded implements ShouldQueue
{
    public string $queue = 'bonuses';
    public int $tries = 3;

    public function __construct(
        private readonly WalletIntegrationService $walletIntegration
    ) {}

    /**
     * Handles the bonus awarded event.
     */
    public function handle(BonusAwarded $event): void
    {
        try {
            // Credit bonus to wallet
            $this->walletIntegration->creditBonus(
                userId: $event->ownerId,
                amount: $event->amount->getAmount(),
                correlationId: $event->correlationId
            );

            Log::channel('bonuses')->info('Bonus credited to wallet', [
                'bonus_id' => $event->bonusId,
                'owner_id' => $event->ownerId,
                'amount' => $event->amount->getAmount(),
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Failed to credit bonus to wallet', [
                'bonus_id' => $event->bonusId,
                'owner_id' => $event->ownerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-queue for retry
            $this->release(60);
        }
    }

    /**
     * Handles listener failure.
     */
    public function failed(BonusAwarded $event, \Throwable $exception): void
    {
        Log::channel('bonuses')->error('Bonus awarded listener failed permanently', [
            'bonus_id' => $event->bonusId,
            'owner_id' => $event->ownerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
