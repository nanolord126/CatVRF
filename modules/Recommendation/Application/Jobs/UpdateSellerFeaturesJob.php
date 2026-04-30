<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;

final class UpdateSellerFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $sellerId,
    ) {}

    public function handle(RecommendationRepositoryInterface $repository): void
    {
        try {
            $features = $this->generateSellerFeatures($this->tenantId, $this->sellerId);

            $repository->saveSellerFeatures($this->tenantId, $this->sellerId, $features);

            Log::info('Seller features updated', [
                'tenant_id' => $this->tenantId,
                'seller_id' => $this->sellerId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update seller features', [
                'tenant_id' => $this->tenantId,
                'seller_id' => $this->sellerId,
                'error' => $e->getMessage(),
            ]);

            $this->release(60);
        }
    }

    private function generateSellerFeatures(int $tenantId, int $sellerId): FeatureVector
    {
        $performance = $this->getSellerPerformance($tenantId, $sellerId);
        $inventory = $this->getSellerInventory($tenantId, $sellerId);

        return FeatureVector::fromDense([
            $performance['total_revenue'] / 100000.0,
            $performance['order_count'] / 1000.0,
            $performance['avg_rating'] / 5.0,
            $performance['response_time_hours'] / 24.0,
            $performance['on_time_delivery_rate'],
            $inventory['total_items'] / 1000.0,
            $inventory['active_items'] / 500.0,
            $inventory['inventory_turnover'],
        ]);
    }

    private function getSellerPerformance(int $tenantId, int $sellerId): array
    {
        return [
            'total_revenue' => rand(10000, 100000),
            'order_count' => rand(100, 1000),
            'avg_rating' => rand(35, 50) / 10.0,
            'response_time_hours' => rand(1, 48) / 2.0,
            'on_time_delivery_rate' => rand(80, 100) / 100.0,
        ];
    }

    private function getSellerInventory(int $tenantId, int $sellerId): array
    {
        return [
            'total_items' => rand(100, 1000),
            'active_items' => rand(50, 500),
            'inventory_turnover' => rand(1, 10) / 10.0,
        ];
    }
}
