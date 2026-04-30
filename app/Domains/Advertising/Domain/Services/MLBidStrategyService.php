<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\BidRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * ML-Based Bid Strategy Recommendation Service
 *
 * Uses machine learning models to recommend optimal bidding strategies
 * based on historical performance, market conditions, and user behavior.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class MLBidStrategyService
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly BidRepositoryInterface $bidRepository,
        private readonly BidShadingService $bidShadingService,
        private readonly LoggerInterface $logger,
        private string $modelEndpoint = 'http://localhost:8000',
    ) {
        $this->modelEndpoint = env('ML_MODEL_ENDPOINT', 'http://localhost:8000');
    }

    /**
     * Get ML-based bid strategy recommendation
     *
     * @param int $auctionId The auction ID
     * @param int $userId User ID for personalization
     * @param int $maxBid Maximum bid amount
     * @return array{strategy: string, recommended_bid: int, confidence: float, reasoning: array}
     */
    public function getRecommendation(
        int $auctionId,
        int $userId,
        int $maxBid,
    ): array {
        $cacheKey = "ml_strategy:{$auctionId}:{$userId}:{$maxBid}";
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $auction = $this->auctionRepository->findById($auctionId);
        if ($auction === null) {
            throw new \RuntimeException('Auction not found');
        }

        $this->logger->info('Generating ML bid strategy recommendation', [
            'auction_id' => $auctionId,
            'user_id' => $userId,
            'max_bid' => $maxBid,
        ]);

        // Get historical data for ML model
        $features = $this->extractFeatures($auction, $userId, $maxBid);

        // Call ML model for prediction
        $prediction = $this->callMLModel($features);

        // Combine with bid shading for hybrid approach
        $bidShadingResult = $this->bidShadingService->calculateOptimalBid(
            auctionId: $auctionId,
            maxBid: $maxBid,
            targetWinRate: 0.7,
            userId: $userId,
        );

        // Hybrid recommendation
        $recommendedBid = (int) (($prediction['predicted_bid'] + $bidShadingResult['recommended_bid']) / 2);
        $confidence = min(0.95, ($prediction['confidence'] + $bidShadingResult['confidence']) / 2);

        $strategy = $this->determineStrategy($prediction, $bidShadingResult);

        $result = [
            'strategy' => $strategy,
            'recommended_bid' => $recommendedBid,
            'confidence' => $confidence,
            'ml_prediction' => $prediction,
            'bid_shading' => $bidShadingResult,
            'reasoning' => $this->generateReasoning($strategy, $prediction, $bidShadingResult),
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Extract features for ML model
     */
    private function extractFeatures(
        \App\Domains\Advertising\Domain\Entities\Auction $auction,
        int $userId,
        int $maxBid,
    ): array {
        $features = [
            'auction_type' => $auction->type,
            'starting_price' => $auction->starting_price,
            'current_price' => $auction->current_price,
            'reserve_price' => $auction->reserve_price,
            'bid_count' => count($auction->bid_history),
            'time_remaining' => $auction->end_at->diffInSeconds(now()),
            'max_bid' => $maxBid,
            'user_id' => $userId,
        ];

        // Add user historical performance
        $userStats = $this->getUserStats($userId);
        $features['user_win_rate'] = $userStats['win_rate'];
        $features['user_avg_bid'] = $userStats['avg_bid'];
        $features['user_total_bids'] = $userStats['total_bids'];

        // Add market conditions
        $marketStats = $this->getMarketStats($auction->tenant_id);
        $features['market_avg_win_rate'] = $marketStats['avg_win_rate'];
        $features['market_competition_level'] = $marketStats['competition_level'];

        return $features;
    }

    /**
     * Get user statistics for ML features
     */
    private function getUserStats(int $userId): array
    {
        $key = "ml:user_stats:{$userId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        // In production, query user's bid history
        // For now, return placeholder
        $stats = [
            'win_rate' => 0.65,
            'avg_bid' => 50000,
            'total_bids' => 150,
        ];

        Redis::setex($key, 3600, json_encode($stats));
        
        return $stats;
    }

    /**
     * Get market statistics for ML features
     */
    private function getMarketStats(int $tenantId): array
    {
        $key = "ml:market_stats:{$tenantId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        // In production, query market data
        // For now, return placeholder
        $stats = [
            'avg_win_rate' => 0.58,
            'competition_level' => 0.6,
        ];

        Redis::setex($key, 1800, json_encode($stats));
        
        return $stats;
    }

    /**
     * Call ML model endpoint
     */
    private function callMLModel(array $features): array
    {
        try {
            $response = Http::timeout(2)->post($this->modelEndpoint . '/predict', [
                'features' => $features,
                'model' => 'bid_strategy_v1',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            // Fallback to rule-based if ML unavailable
            return $this->fallbackPrediction($features);
        } catch (\Throwable $e) {
            $this->logger->warning('ML model unavailable, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackPrediction($features);
        }
    }

    /**
     * Fallback prediction when ML model is unavailable
     */
    private function fallbackPrediction(array $features): array
    {
        $predictedBid = (int) ($features['max_bid'] * 0.85);
        
        // Adjust based on competition
        if ($features['bid_count'] > 10) {
            $predictedBid = (int) ($predictedBid * 0.95);
        } elseif ($features['bid_count'] < 3) {
            $predictedBid = (int) ($predictedBid * 0.75);
        }

        return [
            'predicted_bid' => $predictedBid,
            'confidence' => 0.6,
            'model_used' => 'fallback_rule_based',
        ];
    }

    /**
     * Determine bidding strategy based on predictions
     */
    private function determineStrategy(array $mlPrediction, array $bidShading): string
    {
        $mlConfidence = $mlPrediction['confidence'];
        $shadingConfidence = $bidShading['confidence'];

        if ($mlConfidence > 0.8 && $shadingConfidence > 0.8) {
            return 'aggressive'; // High confidence in both models
        }

        if ($mlConfidence > 0.7 && $shadingConfidence > 0.7) {
            return 'moderate';
        }

        if ($mlConfidence < 0.5 || $shadingConfidence < 0.5) {
            return 'conservative'; // Low confidence, be conservative
        }

        return 'balanced';
    }

    /**
     * Generate reasoning for recommendation
     */
    private function generateReasoning(
        string $strategy,
        array $mlPrediction,
        array $bidShading,
    ): array {
        $reasoning = [];

        switch ($strategy) {
            case 'aggressive':
                $reasoning[] = 'High confidence from both ML and bid shading models';
                $reasoning[] = 'Market conditions favor higher bids';
                break;
            case 'moderate':
                $reasoning[] = 'Moderate confidence in predictions';
                $reasoning[] = 'Balanced approach recommended';
                break;
            case 'conservative':
                $reasoning[] = 'Low confidence or high uncertainty detected';
                $reasoning[] = 'Conservative bidding to minimize risk';
                break;
            case 'balanced':
                $reasoning[] = 'Mixed signals from prediction models';
                $reasoning[] = 'Balanced strategy to optimize ROI';
                break;
        }

        if ($mlPrediction['model_used'] === 'fallback_rule_based') {
            $reasoning[] = 'ML model unavailable, using rule-based fallback';
        }

        return $reasoning;
    }

    /**
     * Train model with new data
     *
     * @param array $trainingData Training data samples
     */
    public function trainModel(array $trainingData): bool
    {
        try {
            $response = Http::timeout(30)->post($this->modelEndpoint . '/train', [
                'data' => $trainingData,
                'model' => 'bid_strategy_v1',
            ]);

            if ($response->successful()) {
                $this->logger->info('ML model training completed', [
                    'samples' => count($trainingData),
                ]);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('ML model training failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get model performance metrics
     */
    public function getModelMetrics(): array
    {
        try {
            $response = Http::timeout(5)->get($this->modelEndpoint . '/metrics');

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'accuracy' => null,
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'status' => 'unavailable',
            ];
        } catch (\Throwable $e) {
            return [
                'accuracy' => null,
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
