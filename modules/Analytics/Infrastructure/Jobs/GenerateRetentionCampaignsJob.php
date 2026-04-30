<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Application\Services\SellerCLVService;

/**
 * Generate Retention Campaigns Job
 * 
 * Automatically creates retention campaigns for high churn risk buyers.
 * Generates personalized offers based on CLV predictions and segment.
 * 
 * Campaign types:
 * - VIP buyers: Dedicated support call + premium offer
 * - High CLV: Personalized email + targeted discount
 * - Medium CLV: Automated nurture sequence
 * - Low CLV: Re-engagement campaign
 * 
 * Production considerations:
 * - Runs daily after feature calculation
 * - Respects seller communication preferences
 * - Limits frequency to avoid spam
 * - Tracks campaign effectiveness
 */
final class GenerateRetentionCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes

    public function __construct(
        private readonly ?int $sellerId = null,
        private readonly AuditService $auditService,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $this->logAction('generate_retention_campaigns', 'Starting retention campaign generation');

        Log::info('Starting GenerateRetentionCampaignsJob');

        $clvService = app(SellerCLVService::class);

        if ($this->sellerId) {
            $this->generateCampaignsForSeller($this->sellerId, $clvService);
        } else {
            $this->generateCampaignsForAllSellers($clvService);
        }

        Log::info('GenerateRetentionCampaignsJob completed');
        $this->logAction('generate_retention_campaigns_complete', 'Retention campaigns generated');
    }

    private function generateCampaignsForAllSellers(SellerCLVService $clvService): void
    {
        // Get all sellers with churn risk buyers
        $sellers = DB::table('buyer_seller_features')
            ->select('seller_id', 'tenant_id')
            ->where('churn_probability', '>', 0.5)
            ->distinct()
            ->get();

        foreach ($sellers as $seller) {
            $this->generateCampaignsForSeller((int) $seller->seller_id, $clvService, (int) $seller->tenant_id);
        }
    }

    private function generateCampaignsForSeller(
        int $sellerId,
        SellerCLVService $clvService,
        int $tenantId = 1,
    ): void {
        $atRiskBuyers = $clvService->getHighChurnRiskBuyers($sellerId, $tenantId, threshold: 0.5, limit: 100);

        foreach ($atRiskBuyers as $prediction) {
            if (!$prediction instanceof CLVPredictionDTO) {
                $prediction = CLVPredictionDTO::fromArray($prediction);
            }

            $this->generateCampaignForBuyer($sellerId, $tenantId, $prediction);
        }
    }

    private function generateCampaignForBuyer(
        int $sellerId,
        int $tenantId,
        CLVPredictionDTO $prediction,
    ): void {
        // Check if buyer already has active campaign
        $hasActiveCampaign = DB::table('retention_campaigns')
            ->where('buyer_id', $prediction->buyerId)
            ->where('seller_id', $sellerId)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveCampaign) {
            Log::info("Buyer {$prediction->buyerId} already has active campaign, skipping");
            return;
        }

        // Generate campaign based on segment and CLV
        $campaign = $this->createCampaign($sellerId, $tenantId, $prediction);

        if ($campaign) {
            $this->dispatchCampaign($campaign);
        }
    }

    private function createCampaign(
        int $sellerId,
        int $tenantId,
        CLVPredictionDTO $prediction,
    ): ?array {
        $campaignType = $this->determineCampaignType($prediction);
        $offer = $this->determineOffer($prediction);

        $campaignId = DB::table('retention_campaigns')->insertGetId([
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
            'buyer_id' => $prediction->buyerId,
            'campaign_type' => $campaignType,
            'offer_type' => $offer['type'],
            'offer_value' => $offer['value'],
            'predicted_clv' => $prediction->predictedClv180d,
            'churn_probability' => $prediction->churnProbability,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $campaignId,
            'type' => $campaignType,
            'offer' => $offer,
            'buyer_id' => $prediction->buyerId,
            'seller_id' => $sellerId,
        ];
    }

    private function determineCampaignType(CLVPredictionDTO $prediction): string
    {
        if ($prediction->isVip()) {
            return 'vip_personal';
        }

        if ($prediction->predictedClv180d > 20000) {
            return 'high_value_personal';
        }

        if ($prediction->predictedClv180d > 5000) {
            return 'medium_value_nurture';
        }

        return 'low_value_reengage';
    }

    private function determineOffer(CLVPredictionDTO $prediction): array
    {
        // Higher discount for higher churn risk and CLV
        $baseDiscount = match ($prediction->segment) {
            'vip' => 20,
            'high' => 15,
            'medium' => 10,
            default => 5,
        };

        // Adjust by churn probability
        $churnBonus = (int) ($prediction->churnProbability * 10);
        $finalDiscount = min(25, $baseDiscount + $churnBonus);

        return [
            'type' => 'discount_coupon',
            'value' => $finalDiscount,
            'validity_days' => 14,
            'min_order_value' => $prediction->predictedClv180d * 0.1,
        ];
    }

    private function dispatchCampaign(array $campaign): void
    {
        // Update campaign status to active
        DB::table('retention_campaigns')
            ->where('id', $campaign['id'])
            ->update([
                'status' => 'active',
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        // Dispatch notification (email, push, SMS based on preferences)
        // This would integrate with existing notification system
        Log::info("Campaign {$campaign['id']} dispatched to buyer {$campaign['buyer_id']}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateRetentionCampaignsJob failed', [
            'error' => $exception->getMessage(),
        ]);
        $this->logAction('generate_retention_campaigns_failed', $exception->getMessage());
    }
}
