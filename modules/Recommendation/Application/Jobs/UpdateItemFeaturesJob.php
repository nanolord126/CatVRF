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

final class UpdateItemFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $itemId,
    ) {}

    public function handle(RecommendationRepositoryInterface $repository): void
    {
        try {
            $features = $this->generateItemFeatures($this->tenantId, $this->itemId);

            $repository->saveItemFeatures($this->tenantId, $this->itemId, $features);

            Log::info('Item features updated', [
                'tenant_id' => $this->tenantId,
                'item_id' => $this->itemId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update item features', [
                'tenant_id' => $this->tenantId,
                'item_id' => $this->itemId,
                'error' => $e->getMessage(),
            ]);

            $this->release(60);
        }
    }

    private function generateItemFeatures(int $tenantId, int $itemId): FeatureVector
    {
        $itemData = $this->getItemData($tenantId, $itemId);
        $salesData = $this->getSalesData($tenantId, $itemId);

        return FeatureVector::fromDense([
            $itemData['price'] / 10000.0,
            $itemData['rating'] / 5.0,
            $itemData['reviews_count'] / 1000.0,
            $salesData['sales_7d'] / 100.0,
            $salesData['sales_30d'] / 500.0,
            $salesData['conversion_rate'],
            $salesData['return_rate'],
            $itemData['availability_score'],
        ]);
    }

    private function getItemData(int $tenantId, int $itemId): array
    {
        return [
            'price' => rand(100, 10000),
            'rating' => rand(30, 50) / 10.0,
            'reviews_count' => rand(0, 1000),
            'availability_score' => rand(0, 100) / 100.0,
        ];
    }

    private function getSalesData(int $tenantId, int $itemId): array
    {
        return [
            'sales_7d' => rand(0, 100),
            'sales_30d' => rand(0, 500),
            'conversion_rate' => rand(1, 20) / 100.0,
            'return_rate' => rand(1, 15) / 100.0,
        ];
    }
}
