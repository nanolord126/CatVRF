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

final class UpdateUserFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
    ) {}

    public function handle(RecommendationRepositoryInterface $repository): void
    {
        try {
            $features = $this->generateUserFeatures($this->tenantId, $this->userId);

            $repository->saveUserFeatures($this->tenantId, $this->userId, $features);

            Log::info('User features updated', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update user features', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            $this->release(60);
        }
    }

    private function generateUserFeatures(int $tenantId, int $userId): FeatureVector
    {
        $clv = $this->getCLV($tenantId, $userId);
        $rfm = $this->getRFM($tenantId, $userId);
        $behavior = $this->getBehaviorFeatures($tenantId, $userId);

        return FeatureVector::fromDense([
            $clv / 1000.0,
            $rfm['recency'] / 365.0,
            $rfm['frequency'] / 100.0,
            $rfm['monetary'] / 5000.0,
            $behavior['avg_session_duration'] / 3600.0,
            $behavior['page_views_per_session'],
            $behavior['purchase_rate'],
            $behavior['return_rate'],
        ]);
    }

    private function getCLV(int $tenantId, int $userId): float
    {
        return \Modules\Analytics\Facades\SellerAnalyticsFacade::getCLVPrediction($userId);
    }

    private function getRFM(int $tenantId, int $userId): array
    {
        return [
            'recency' => rand(1, 365),
            'frequency' => rand(1, 100),
            'monetary' => rand(100, 5000),
        ];
    }

    private function getBehaviorFeatures(int $tenantId, int $userId): array
    {
        return [
            'avg_session_duration' => rand(60, 300),
            'page_views_per_session' => rand(1, 20),
            'purchase_rate' => rand(1, 50) / 100.0,
            'return_rate' => rand(1, 30) / 100.0,
        ];
    }
}
