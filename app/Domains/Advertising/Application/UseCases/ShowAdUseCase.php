<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Application\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Advertising\Domain\Entities\AdImpression;
use App\Domains\Advertising\Domain\Events\AdImpressionRegistered;
use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AdImpressionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AdPlacementStrategyInterface;
use App\Domains\Advertising\Domain\Services\AdTargetingService;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Str;

final readonly class ShowAdUseCase
{
    public function __construct(private AdCampaignRepositoryInterface $campaignRepository,
        private AdImpressionRepositoryInterface $impressionRepository,
        private AdTargetingService $targetingService,
        private AdPlacementStrategyInterface $placementStrategy,
        private FraudControlService $fraud,
        private EventDispatcher $events,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    public function execute(User $user, string $placementZone, string $ipAddress, string $deviceFingerprint, ?string $correlationId = null): ?array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'show_ad', amount: 0, correlationId: $correlationId ?? '');

        $activeCampaigns = $this->campaignRepository->getActiveCampaignsForTenant($user->tenant_id);
        $targetedCampaigns = $this->targetingService->filterCampaignsForUser($activeCampaigns, $user);

        if ($targetedCampaigns->isEmpty()) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $selectedCampaignId = $this->placementStrategy->selectBestPlacement($targetedCampaigns, $user, $placementZone);

        if (!$selectedCampaignId) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $campaign = $this->campaignRepository->findById($selectedCampaignId);
        if (!$campaign) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }
        
        // For now, cost is fixed. In the future, it can be dynamic.
        $cost = $campaign->pricing_model === 'cpc' ? 50 : 5; // 50 cents for CPC, 5 for CPM

        return $this->db->transaction(function () use ($campaign, $user, $ipAddress, $deviceFingerprint, $cost, $correlationId) {
            
            $this->campaignRepository->updateSpent($campaign->id, $cost);

            $impression = new AdImpression(
                id: null,
                campaign_id: $campaign->id,
                placement_id: 0, // Placeholder, as we don't have AdPlacement entity yet
                user_id: $user->id,
                ip_address: $ipAddress,
                device_fingerprint: $deviceFingerprint,
                cost: $cost,
                created_at: Carbon::now(),
                correlation_id: $correlationId
            );

            $this->impressionRepository->save($impression);

            $this->events->dispatch(new AdImpressionRegistered($campaign->id, $cost, $correlationId));

            $this->logger->info('Ad impression registered', [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'cost' => $cost,
                'correlation_id' => $correlationId,
            ]);

            // In a real app, you would get the actual ad content.
            return [
                'campaign_uuid' => $campaign->uuid,
                'content' => 'This is an advertisement.',
            ];
        });
    }
}
