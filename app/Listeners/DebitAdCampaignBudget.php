<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Advertising\Domain\Events\AdImpressionRegistered;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;


/**
 * Class DebitAdCampaignBudget
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Listeners
 */
final class DebitAdCampaignBudget implements ShouldQueue
{
    public function __construct(private readonly WalletService $walletService,
        private readonly LogManager $logger,
    )
    {
        // Implementation required by canon
    }

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(AdImpressionRegistered $event): void
    {
        try {
            // This assumes a tenant has a wallet.
            // The logic might be more complex, e.g., a dedicated ad wallet.
            $this->walletService->debit(
                tenantId: $event->tenantId,
                amount: $event->cost,
                description: "Ad impression for campaign {$event->campaignId}",
                correlationId: $event->correlationId
            );

            $this->logger->channel('audit')->info('Ad campaign budget debited', [
                'campaign_id' => $event->campaignId,
                'amount' => $event->cost,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Failed to debit ad campaign budget', [
                'campaign_id' => $event->campaignId,
                'amount' => $event->cost,
                'correlation_id' => $event->correlationId,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
