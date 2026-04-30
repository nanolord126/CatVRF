<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Fraud Detection ML Service
 *
 * Integrates machine learning models for advanced fraud detection
 * in advertising operations including bid fraud, click fraud, and inventory fraud.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class FraudDetectionMLService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const RISK_THRESHOLD = 0.7;

    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LoggerInterface $logger,
        private string $modelEndpoint = 'http://localhost:8001',
    ) {
        $this->modelEndpoint = env('ML_FRAUD_MODEL_ENDPOINT', 'http://localhost:8001');
    }

    /**
     * Analyze bid for fraud using ML model
     *
     * @param int $auctionId Auction ID
     * @param int $bidderId Bidder ID
     * @param int $amount Bid amount
     * @param array $context Additional context
     * @return array{is_fraudulent: bool, risk_score: float, risk_factors: array, action: string}
     */
    public function analyzeBid(
        int $auctionId,
        int $bidderId,
        int $amount,
        array $context = [],
    ): array {
        $cacheKey = "fraud:bid:{$auctionId}:{$bidderId}:{$amount}";
        
        $features = $this->extractBidFeatures($auctionId, $bidderId, $amount, $context);
        
        // Call ML model
        $prediction = $this->callFraudModel($features, 'bid_fraud');
        
        $isFraudulent = $prediction['risk_score'] >= self::RISK_THRESHOLD;
        $action = $this->determineAction($prediction['risk_score'], $isFraudulent);

        $result = [
            'is_fraudulent' => $isFraudulent,
            'risk_score' => $prediction['risk_score'],
            'confidence' => $prediction['confidence'],
            'risk_factors' => $prediction['risk_factors'],
            'action' => $action,
        ];

        // Log high-risk bids
        if ($prediction['risk_score'] > 0.5) {
            $this->logger->warning('High-risk bid detected', [
                'auction_id' => $auctionId,
                'bidder_id' => $bidderId,
                'amount' => $amount,
                'risk_score' => $prediction['risk_score'],
            ]);
        }

        return $result;
    }

    /**
     * Analyze click for fraud using ML model
     *
     * @param int $bidId Bid ID
     * @param int $userId User ID
     * @param array $context Additional context
     * @return array{is_fraudulent: bool, risk_score: float, risk_factors: array, action: string}
     */
    public function analyzeClick(
        int $bidId,
        int $userId,
        array $context = [],
    ): array {
        $features = $this->extractClickFeatures($bidId, $userId, $context);
        
        $prediction = $this->callFraudModel($features, 'click_fraud');
        
        $isFraudulent = $prediction['risk_score'] >= self::RISK_THRESHOLD;
        $action = $this->determineAction($prediction['risk_score'], $isFraudulent);

        return [
            'is_fraudulent' => $isFraudulent,
            'risk_score' => $prediction['risk_score'],
            'confidence' => $prediction['confidence'],
            'risk_factors' => $prediction['risk_factors'],
            'action' => $action,
        ];
    }

    /**
     * Analyze inventory reservation for fraud
     *
     * @param int $publisherId Publisher ID
     * @param int $inventoryId Inventory ID
     * @param int $impressions Impressions to reserve
     * @return array{is_fraudulent: bool, risk_score: float, risk_factors: array, action: string}
     */
    public function analyzeInventoryReservation(
        int $publisherId,
        int $inventoryId,
        int $impressions,
    ): array {
        $features = $this->extractInventoryFeatures($publisherId, $inventoryId, $impressions);
        
        $prediction = $this->callFraudModel($features, 'inventory_fraud');
        
        $isFraudulent = $prediction['risk_score'] >= self::RISK_THRESHOLD;
        $action = $this->determineAction($prediction['risk_score'], $isFraudulent);

        return [
            'is_fraudulent' => $isFraudulent,
            'risk_score' => $prediction['risk_score'],
            'confidence' => $prediction['confidence'],
            'risk_factors' => $prediction['risk_factors'],
            'action' => $action,
        ];
    }

    /**
     * Extract features for bid fraud detection
     */
    private function extractBidFeatures(
        int $auctionId,
        int $bidderId,
        int $amount,
        array $context,
    ): array {
        $features = [
            'auction_id' => $auctionId,
            'bidder_id' => $bidderId,
            'amount' => $amount,
            'timestamp' => now()->toIso8601String(),
        ];

        // Get bidder history
        $bidderStats = $this->getBidderStats($bidderId);
        $features['bidder_total_bids'] = $bidderStats['total_bids'];
        $features['bidder_win_rate'] = $bidderStats['win_rate'];
        $features['bidder_avg_bid'] = $bidderStats['avg_bid'];
        $features['bidder_bid_velocity'] = $bidderStats['bid_velocity'];

        // Get auction context
        $auctionStats = $this->getAuctionStats($auctionId);
        $features['auction_current_price'] = $auctionStats['current_price'];
        $features['auction_bid_count'] = $auctionStats['bid_count'];
        $features['auction_time_remaining'] = $auctionStats['time_remaining'];

        // Add contextual features
        $features['ip_address'] = $context['ip_address'] ?? null;
        $features['user_agent'] = $context['user_agent'] ?? null;
        $features['device_type'] = $context['device_type'] ?? null;

        return $features;
    }

    /**
     * Extract features for click fraud detection
     */
    private function extractClickFeatures(
        int $bidId,
        int $userId,
        array $context,
    ): array {
        $features = [
            'bid_id' => $bidId,
            'user_id' => $userId,
            'timestamp' => now()->toIso8601String(),
        ];

        // Get user click history
        $userStats = $this->getUserClickStats($userId);
        $features['user_total_clicks'] = $userStats['total_clicks'];
        $features['user_click_rate'] = $userStats['click_rate'];
        $features['user_avg_time_between_clicks'] = $userStats['avg_time_between_clicks'];

        // Add contextual features
        $features['ip_address'] = $context['ip_address'] ?? null;
        $features['user_agent'] = $context['user_agent'] ?? null;
        $features['referrer'] = $context['referrer'] ?? null;
        $features['click_position'] = $context['click_position'] ?? null;

        return $features;
    }

    /**
     * Extract features for inventory fraud detection
     */
    private function extractInventoryFeatures(
        int $publisherId,
        int $inventoryId,
        int $impressions,
    ): array {
        $features = [
            'publisher_id' => $publisherId,
            'inventory_id' => $inventoryId,
            'impressions' => $impressions,
            'timestamp' => now()->toIso8601String(),
        ];

        // Get publisher stats
        $publisherStats = $this->getPublisherStats($publisherId);
        $features['publisher_total_reservations'] = $publisherStats['total_reservations'];
        $features['publisher_fill_rate'] = $publisherStats['fill_rate'];
        $features['publisher_avg_impressions'] = $publisherStats['avg_impressions'];

        // Get inventory stats
        $inventoryStats = $this->getInventoryStats($inventoryId);
        $features['inventory_available'] = $inventoryStats['available'];
        $features['inventory_utilization'] = $inventoryStats['utilization'];

        return $features;
    }

    /**
     * Get bidder statistics for fraud detection
     */
    private function getBidderStats(int $bidderId): array
    {
        $key = "fraud:bidder_stats:{$bidderId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        // In production, query bidder's bid history
        // For now, return placeholder
        $stats = [
            'total_bids' => 50,
            'win_rate' => 0.6,
            'avg_bid' => 75000,
            'bid_velocity' => 1.2, // bids per minute
        ];

        Redis::setex($key, 1800, json_encode($stats));
        
        return $stats;
    }

    /**
     * Get auction statistics
     */
    private function getAuctionStats(int $auctionId): array
    {
        $key = "fraud:auction_stats:{$auctionId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        // In production, query auction data
        $stats = [
            'current_price' => 100000,
            'bid_count' => 15,
            'time_remaining' => 3600, // seconds
        ];

        Redis::setex($key, 300, json_encode($stats));
        
        return $stats;
    }

    /**
     * Get user click statistics
     */
    private function getUserClickStats(int $userId): array
    {
        $key = "fraud:user_click_stats:{$userId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $stats = [
            'total_clicks' => 25,
            'click_rate' => 0.05,
            'avg_time_between_clicks' => 30, // seconds
        ];

        Redis::setex($key, 1800, json_encode($stats));
        
        return $stats;
    }

    /**
     * Get publisher statistics
     */
    private function getPublisherStats(int $publisherId): array
    {
        $key = "fraud:publisher_stats:{$publisherId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $stats = [
            'total_reservations' => 100,
            'fill_rate' => 0.85,
            'avg_impressions' => 50000,
        ];

        Redis::setex($key, 3600, json_encode($stats));
        
        return $stats;
    }

    /**
     * Get inventory statistics
     */
    private function getInventoryStats(int $inventoryId): array
    {
        $key = "fraud:inventory_stats:{$inventoryId}";
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $stats = [
            'available' => 100000,
            'utilization' => 0.6,
        ];

        Redis::setex($key, 300, json_encode($stats));
        
        return $stats;
    }

    /**
     * Call fraud detection ML model
     */
    private function callFraudModel(array $features, string $modelType): array
    {
        try {
            $response = Http::timeout(2)->post($this->modelEndpoint . '/predict', [
                'features' => $features,
                'model' => $modelType,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return $this->fallbackFraudDetection($features);
        } catch (\Throwable $e) {
            $this->logger->warning('Fraud ML model unavailable, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackFraudDetection($features);
        }
    }

    /**
     * Fallback fraud detection when ML model is unavailable
     */
    private function fallbackFraudDetection(array $features): array
    {
        $riskScore = 0.0;
        $riskFactors = [];

        // Rule-based checks
        if (isset($features['bidder_bid_velocity']) && $features['bidder_bid_velocity'] > 10) {
            $riskScore += 0.3;
            $riskFactors[] = 'High bid velocity';
        }

        if (isset($features['bidder_win_rate']) && $features['bidder_win_rate'] > 0.95) {
            $riskScore += 0.2;
            $riskFactors[] = 'Suspiciously high win rate';
        }

        if (isset($features['user_avg_time_between_clicks']) && $features['user_avg_time_between_clicks'] < 1) {
            $riskScore += 0.4;
            $riskFactors[] = 'Bot-like clicking pattern';
        }

        return [
            'risk_score' => min(1.0, $riskScore),
            'confidence' => 0.5,
            'risk_factors' => $riskFactors,
            'model_used' => 'fallback_rule_based',
        ];
    }

    /**
     * Determine action based on risk score
     */
    private function determineAction(float $riskScore, bool $isFraudulent): string
    {
        if ($riskScore >= 0.9) {
            return 'block'; // Block immediately
        }

        if ($riskScore >= 0.7) {
            return 'review'; // Flag for manual review
        }

        if ($riskScore >= 0.5) {
            return 'monitor'; // Monitor for patterns
        }

        return 'allow'; // Allow with normal processing
    }

    /**
     * Report confirmed fraud for model training
     *
     * @param string $fraudType Type of fraud (bid_fraud, click_fraud, inventory_fraud)
     * @param array $features Features used for detection
     * @param bool $actualFraud Whether it was actually fraud
     */
    public function reportFraud(
        string $fraudType,
        array $features,
        bool $actualFraud,
    ): void {
        $trainingData = [
            'features' => $features,
            'label' => $actualFraud ? 1 : 0,
            'timestamp' => now()->toIso8601String(),
        ];

        Redis::lpush('fraud:training_data:' . $fraudType, json_encode($trainingData));
        
        // Keep only last 5000 records
        Redis::ltrim('fraud:training_data:' . $fraudType, 0, 4999);

        $this->logger->info('Fraud reported for training', [
            'type' => $fraudType,
            'actual_fraud' => $actualFraud,
        ]);
    }

    /**
     * Get fraud detection metrics
     */
    public function getMetrics(): array
    {
        try {
            $response = Http::timeout(5)->get($this->modelEndpoint . '/metrics');

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'false_positive_rate' => null,
                'status' => 'unavailable',
            ];
        } catch (\Throwable $e) {
            return [
                'precision' => null,
                'recall' => null,
                'f1_score' => null,
                'false_positive_rate' => null,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
