<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Services\FraudControl\FraudControlService;
use App\Services\ML\AnonymizationService;
use App\Services\ML\BigDataAggregatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AdExchangeService - Real-Time Bidding (RTB) Ad Exchange (OpenRTB 2.6)
 * 
 * Handles real-time auction for ad inventory across internal and external DSPs
 */
final readonly class AdExchangeService
{
    public function __construct(
        private readonly BigDataAggregatorService $bigData,
        private readonly FraudControlService $fraud,
        private readonly AnonymizationService $anonymizer,
        private readonly MarketingCampaignService $campaignService,
        private readonly Auctioneer $auctioneer,
        private readonly BidCollector $bidCollector,
        private readonly OpenRtbBidRequestBuilder $builder,
    ) {}

    /**
     * Handle RTB bid request
     * 
     * @param array $context Request context
     * @return array|null Winning ad or null if no bid
     */
    public function handleBidRequest(array $context): ?array
    {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $userId = $context['user_id'] ?? 0;
        $vertical = $context['vertical'] ?? 'general';

        // Check if RTB is enabled
        if (!config('monetization.rtb.enabled', false)) {
            Log::info('rtb.disabled', ['correlation_id' => $correlationId]);
            return null;
        }

        // Fraud check
        $this->fraud->check(
            userId: $userId,
            operationType: 'rtb_bid_request',
            amount: 0,
            ipAddress: $context['ip'] ?? null,
            deviceFingerprint: $context['device_fingerprint'] ?? null,
            correlationId: $correlationId,
        );

        try {
            // 1. Build OpenRTB BidRequest
            $bidRequest = $this->builder->build($context, $correlationId);

            // 2. Collect bids from all DSPs
            $timeoutMs = config('monetization.rtb.timeout_ms', 120);
            $bids = $this->bidCollector->collect($bidRequest, $timeoutMs);

            // 3. Run auction
            $floorPrice = $this->getFloorPrice($context);
            $winner = $this->auctioneer->run($bids, $floorPrice);

            if ($winner === null) {
                Log::info('rtb.auction.no_winner', [
                    'correlation_id' => $correlationId,
                    'total_bids' => count($bids),
                    'floor_price' => $floorPrice,
                ]);
                return null;
            }

            // 4. Track win and spend
            $this->trackWin($winner, $context, $correlationId);
            $this->campaignService->recordSpend(
                campaignId: $winner->campaignId,
                amountKopecks: (int)$winner->priceKopecks,
                correlationId: $correlationId,
            );

            // 5. Log analytics
            $anonymizedUserId = $this->anonymizer->anonymizeUserId($userId);
            $this->bigData->insertMarketingEvent([
                'anonymized_user_id' => $anonymizedUserId,
                'event_type' => 'rtb_auction_win',
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
                'price_kopecks' => $winner->priceKopecks,
                'dsp_id' => $winner->dspId,
                'campaign_id' => $winner->campaignId,
                'created_at' => now()->toDateTimeString(),
            ]);

            Log::info('rtb.auction.success', [
                'correlation_id' => $correlationId,
                'winner_bid_id' => $winner->id,
                'dsp_id' => $winner->dspId,
                'price' => $winner->priceKopecks,
            ]);

            return $winner->ad;
        } catch (\Throwable $e) {
            Log::error('rtb.bid_request.failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get floor price for the placement
     */
    private function getFloorPrice(array $context): int
    {
        return $context['floor_cpm_kopecks'] 
            ?? config('monetization.rtb.default_floor_cpm_kopecks', 500);
    }

    /**
     * Track auction win
     */
    private function trackWin(object $winner, array $context, string $correlationId): void
    {
        // Log win for analytics
        Log::channel('audit')->info('rtb.win.tracked', [
            'correlation_id' => $correlationId,
            'bid_id' => $winner->id,
            'dsp_id' => $winner->dspId,
            'campaign_id' => $winner->campaignId,
            'price' => $winner->priceKopecks,
        ]);
    }
}
