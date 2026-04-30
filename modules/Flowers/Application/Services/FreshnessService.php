<?php

declare(strict_types=1);

namespace Modules\Flowers\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\Flowers\Domain\Repositories\FlowerRepositoryInterface;
use Modules\Flowers\Domain\Entities\Flower;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Modules\Flowers\Domain\Events\FreshnessStatusChanged;
use Modules\Flowers\Domain\Events\LowStockAlert;
use Modules\Flowers\Domain\Events\ExpiryWarning;
use Illuminate\Support\Facades\Event;
use Carbon\CarbonImmutable;

final class FreshnessService
{
    use WithAuditLogging;

    public function __construct(
        private FlowerRepositoryInterface $flowerRepository,
        private readonly AuditService $auditService,
    ) {}

    public function updateFreshnessForVenue(int $venueId): array
    {
        $flowers = $this->flowerRepository->getByVenue($venueId, []);
        $updated = [];

        foreach ($flowers as $flowerModel) {
            $flower = $flowerModel->toDomain();
            $newStatus = $this->calculateFreshnessStatus($flower);

            if ($newStatus !== $flower->freshnessStatus) {
                $flower = $flower->updateFreshnessStatus($newStatus);
                $flower = $this->flowerRepository->save($flower);
                
                $updated[] = $flower;
                Event::dispatch(new FreshnessStatusChanged($flower, $flower->freshnessStatus, $newStatus));
            }

            // Check for low stock
            if ($flower->isLowStock()) {
                Event::dispatch(new LowStockAlert($flower));
            }

            // Check for expiry warning
            if ($flower->isExpiringWithin(3) && $flower->freshnessStatus !== FreshnessStatus::EXPIRED) {
                Event::dispatch(new ExpiryWarning($flower));
            }
        }

        return $updated;
    }

    public function getExpiringSoon(int $venueId, int $days = 3): array
    {
        return $this->flowerRepository->getExpiringSoon($venueId, $days);
    }

    public function getExpired(int $venueId): array
    {
        return $this->flowerRepository->getExpired($venueId);
    }

    public function getLowStock(int $venueId): array
    {
        return $this->flowerRepository->getLowStock($venueId);
    }

    public function getFreshnessReport(int $venueId): array
    {
        $allFlowers = $this->flowerRepository->getByVenue($venueId, []);

        $report = [
            'total' => count($allFlowers),
            'fresh' => 0,
            'good' => 0,
            'aging' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'low_stock' => 0,
            'total_value' => 0,
            'at_risk_value' => 0,
        ];

        foreach ($allFlowers as $flowerModel) {
            $flower = $flowerModel->toDomain();
            
            $report[$flower->freshnessStatus->value]++;
            $report['total_value'] += $flower->stockQuantity * $flower->costPrice;

            if ($flower->isLowStock()) {
                $report['low_stock']++;
            }

            if (in_array($flower->freshnessStatus, [FreshnessStatus::AGING, FreshnessStatus::EXPIRING_SOON, FreshnessStatus::EXPIRED])) {
                $report['at_risk_value'] += $flower->stockQuantity * $flower->costPrice;
            }
        }

        return $report;
    }

    public function markAsExpired(int $flowerId): Flower
    {
        $flower = $this->flowerRepository->findById($flowerId);
        
        if (!$flower) {
            throw new \RuntimeException('Flower not found');
        }

        $flower = $flower->updateFreshnessStatus(FreshnessStatus::EXPIRED);
        $flower = $this->flowerRepository->save($flower);

        Event::dispatch(new FreshnessStatusChanged($flower, $flower->freshnessStatus, FreshnessStatus::EXPIRED));

        return $flower;
    }

    public function bulkUpdateFreshness(): array
    {
        $venues = []; // Would get all venues from venue repository
        $results = [];

        foreach ($venues as $venue) {
            $results[$venue->id] = $this->updateFreshnessForVenue($venue->id);
        }

        return $results;
    }

    private function calculateFreshnessStatus(Flower $flower): FreshnessStatus
    {
        if ($flower->isExpired()) {
            return FreshnessStatus::EXPIRED;
        }

        $daysUntilExpiry = $flower->expiryDate->diffInDays(CarbonImmutable::now());

        if ($daysUntilExpiry <= 1) {
            return FreshnessStatus::EXPIRING_SOON;
        }

        if ($daysUntilExpiry <= 3) {
            return FreshnessStatus::AGING;
        }

        if ($daysUntilExpiry <= 7) {
            return FreshnessStatus::GOOD;
        }

        return FreshnessStatus::FRESH;
    }

    public function getRecommendedActions(int $venueId): array
    {
        $expiringSoon = $this->getExpiringSoon($venueId, 2);
        $lowStock = $this->getLowStock($venueId);
        $expired = $this->getExpired($venueId);

        $actions = [];

        if (!empty($expiringSoon)) {
            $actions[] = [
                'type' => 'discount',
                'priority' => 'high',
                'message' => count($expiringSoon) . ' flower types expiring soon. Consider discounting.',
                'flowers' => $expiringSoon,
            ];
        }

        if (!empty($lowStock)) {
            $actions[] = [
                'type' => 'reorder',
                'priority' => 'medium',
                'message' => count($lowStock) . ' flower types are low on stock.',
                'flowers' => $lowStock,
            ];
        }

        if (!empty($expired)) {
            $actions[] = [
                'type' => 'dispose',
                'priority' => 'urgent',
                'message' => count($expired) . ' flower types have expired. Remove from inventory.',
                'flowers' => $expired,
            ];
        }

        return $actions;
    }
}
