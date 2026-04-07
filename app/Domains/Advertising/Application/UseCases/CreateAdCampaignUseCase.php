<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Application\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Advertising\Domain\Entities\AdCampaign;
use App\Domains\Advertising\Domain\Events\AdCampaignCreated;
use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Carbon\Carbon;

final readonly class CreateAdCampaignUseCase
{
    public function __construct(private AdCampaignRepositoryInterface $repository,
        private FraudControlService $fraud,
        private EventDispatcher $events,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    public function execute(
        int $tenantId,
        string $name,
        Carbon $startAt,
        Carbon $endAt,
        int $budget,
        string $pricingModel,
        array $targetingCriteria,
        ?string $correlationId
    ): AdCampaign {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_ad_campaign', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($tenantId, $name, $startAt, $endAt, $budget, $pricingModel, $targetingCriteria, $correlationId) {
            $campaign = AdCampaign::create(
                $tenantId,
                $name,
                $startAt,
                $endAt,
                $budget,
                $pricingModel,
                $targetingCriteria,
                $correlationId
            );

            $savedCampaign = $this->repository->save($campaign);

            $this->events->dispatch(new AdCampaignCreated($savedCampaign->id, $savedCampaign->correlation_id));

            $this->logger->info('Ad campaign created', [
                'campaign_id' => $savedCampaign->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $savedCampaign->correlation_id,
            ]);

            return $savedCampaign;
        });
    }
}
