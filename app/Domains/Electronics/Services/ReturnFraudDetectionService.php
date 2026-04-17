<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\DTOs\ReturnFraudDetectionDto;
use App\Domains\Electronics\DTOs\FraudDetectionResultDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Events\ReturnFraudDetectedEvent;
use App\Services\FraudControlService;
use App\Services\FraudMLService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class ReturnFraudDetectionService
{
    public function __construct(
        private FraudControlService $fraud,
        private FraudMLService $fraudML,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private Cache $cache,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

    public function detectReturnFraud(ReturnFraudDetectionDto $dto): FraudDetectionResultDto
    {
        $correlationId = $dto->correlationId;

        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'electronics_return_fraud_detection',
            amount: 0,
            correlationId: $correlationId
        );

        $cacheKey = sprintf(
            'return_fraud_detection:%d:%d:%s',
            $dto->orderId,
            $dto->productId,
            $dto->serialNumber
        );

        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->logger->info('Return fraud detection cache hit', [
                'order_id' => $dto->orderId,
                'correlation_id' => $correlationId,
            ]);

            return FraudDetectionResultDto::fromArray($cachedResult);
        }

        return $this->db->transaction(function () use ($dto, $correlationId, $cacheKey) {
            $product = ElectronicsProduct::findOrFail($dto->productId);

            $mlFeatures = $this->extractMLFeatures($dto, $product);

            $fraudScore = $this->fraudML->predict(
                vertical: 'electronics',
                features: $mlFeatures,
                model: 'return_fraud_detection'
            );

            $riskFactors = $this->analyzeRiskFactors($dto, $product, $mlFeatures);

            $isFraudulent = $fraudScore > 0.65;
            $riskLevel = $this->calculateRiskLevel($fraudScore);

            $result = new FraudDetectionResultDto(
                isFraudulent: $isFraudulent,
                fraudProbability: $fraudScore,
                riskLevel: $riskLevel,
                riskFactors: $riskFactors,
                mlFeatures: $mlFeatures,
                correlationId: $correlationId,
                recommendedAction: $this->getRecommendedAction($riskLevel, $isFraudulent),
                holdDurationMinutes: $this->calculateHoldDuration($riskLevel),
            );

            $this->logDetectionResult($dto, $result);
            $this->saveDetectionRecord($dto, $result);

            if ($isFraudulent) {
                event(new ReturnFraudDetectedEvent($dto, $result, $correlationId));
            }

            $this->cache->put($cacheKey, $result->toArray(), now()->addHours(12));

            return $result;
        });
    }

    private function extractMLFeatures(ReturnFraudDetectionDto $dto, ElectronicsProduct $product): array
    {
        $orderData = $this->getOrderData($dto->orderId);

        $userReturnHistory = $this->getUserReturnHistory($dto->userId);

        $serialValidation = $this->getSerialValidation($dto->serialNumber);

        $behaviorPattern = $this->behaviorAnalyzer->classifyUser($dto->userId);

        $deviceConditionScore = $this->analyzeDeviceCondition($dto->condition);

        $returnReasonRisk = $this->analyzeReturnReason($dto->returnReason);

        return [
            'product_price_kopecks' => $product->price_kopecks,
            'product_category' => $product->category,
            'product_category_risk' => $this->getCategoryRisk($product->category),
            'order_total_kopecks' => $orderData['total_kopecks'],
            'order_item_count' => $orderData['item_count'],
            'days_since_purchase' => $orderData['days_since_purchase'],
            'payment_method' => $orderData['payment_method'],
            'user_total_orders' => $userReturnHistory['total_orders'],
            'user_total_returns' => $userReturnHistory['total_returns'],
            'user_return_rate' => $userReturnHistory['return_rate'],
            'user_avg_return_days' => $userReturnHistory['avg_return_days'],
            'user_account_age_days' => $userReturnHistory['account_age_days'],
            'serial_validation_score' => $serialValidation['score'],
            'serial_is_validated' => $serialValidation['is_validated'] ? 1 : 0,
            'behavior_pattern' => $behaviorPattern,
            'device_condition_score' => $deviceConditionScore,
            'return_reason_risk' => $returnReasonRisk,
            'has_device_metadata' => !empty($dto->deviceMetadata) ? 1 : 0,
            'device_metadata_completeness' => $this->calculateMetadataCompleteness($dto->deviceMetadata),
            'same_serial_multiple_returns' => $this->checkSerialMultipleReturns($dto->serialNumber, $dto->userId),
            'return_frequency_7d' => $this->getReturnFrequency($dto->userId, 7),
            'return_frequency_30d' => $this->getReturnFrequency($dto->userId, 30),
            'shipping_speed_days' => $orderData['shipping_speed_days'],
            'is_business_user' => $this->isBusinessUser($dto->userId) ? 1 : 0,
            'time_on_site_minutes' => $dto->userBehavior['time_on_site_minutes'] ?? 0,
            'page_views_before_purchase' => $dto->userBehavior['page_views_before_purchase'] ?? 0,
            'cart_abandonment_rate' => $dto->userBehavior['cart_abandonment_rate'] ?? 0.0,
        ];
    }

    private function getOrderData(int $orderId): array
    {
        $cacheKey = "order_data:{$orderId}";

        return $this->cache->remember($cacheKey, now()->addHours(2), function () use ($orderId) {
            $order = DB::table('orders')->where('id', $orderId)->first();

            if (!$order) {
                return [
                    'total_kopecks' => 0,
                    'item_count' => 0,
                    'days_since_purchase' => 0,
                    'payment_method' => 'unknown',
                    'shipping_speed_days' => 0,
                ];
            }

            $items = DB::table('order_items')
                ->where('order_id', $orderId)
                ->count();

            $shipping = DB::table('electronics_shipments')
                ->where('order_id', $orderId)
                ->first();

            $shippingSpeed = 0;
            if ($shipping && isset($shipping->delivered_at) && isset($shipping->shipped_at)) {
                $shippingSpeed = now()->diffInDays(
                    \Carbon\Carbon::parse($shipping->delivered_at),
                    \Carbon\Carbon::parse($shipping->shipped_at)
                );
            }

            return [
                'total_kopecks' => $order->total_kopecks ?? 0,
                'item_count' => $items,
                'days_since_purchase' => $order->created_at ? now()->diffInDays($order->created_at) : 0,
                'payment_method' => $order->payment_method ?? 'unknown',
                'shipping_speed_days' => $shippingSpeed,
            ];
        });
    }

    private function getUserReturnHistory(int $userId): array
    {
        $cacheKey = "user_return_history:{$userId}";

        return $this->cache->remember($cacheKey, now()->addHours(4), function () use ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            $accountAgeDays = $user ? now()->diffInDays($user->created_at) : 0;

            $totalOrders = (int) DB::table('orders')
                ->where('user_id', $userId)
                ->count();

            $totalReturns = (int) DB::table('electronics_returns')
                ->where('user_id', $userId)
                ->count();

            $returnRate = $totalOrders > 0 ? $totalReturns / $totalOrders : 0.0;

            $avgReturnDays = DB::table('electronics_returns')
                ->where('user_id', $userId)
                ->selectRaw('AVG(DATEDIFF(created_at, purchase_date)) as avg_days')
                ->value('avg_days') ?? 0;

            return [
                'total_orders' => $totalOrders,
                'total_returns' => $totalReturns,
                'return_rate' => $returnRate,
                'avg_return_days' => (int) $avgReturnDays,
                'account_age_days' => $accountAgeDays,
            ];
        });
    }

    private function getSerialValidation(string $serialNumber): array
    {
        $validation = DB::table('electronics_serial_validations')
            ->where('serial_number', $serialNumber)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$validation) {
            return ['score' => 0.5, 'is_validated' => false];
        }

        return [
            'score' => $validation->fraud_probability < 0.3 ? 1.0 : (1 - $validation->fraud_probability),
            'is_validated' => !$validation->is_fraudulent,
        ];
    }

    private function analyzeDeviceCondition(string $condition): float
    {
        $conditionScores = [
            'new' => 1.0,
            'like_new' => 0.9,
            'good' => 0.7,
            'fair' => 0.5,
            'poor' => 0.3,
            'damaged' => 0.1,
        ];

        return $conditionScores[strtolower($condition)] ?? 0.5;
    }

    private function analyzeReturnReason(string $reason): float
    {
        $highRiskReasons = [
            'defective',
            'not_as_described',
            'wrong_item',
            'damaged',
        ];

        $mediumRiskReasons = [
            'changed_mind',
            'found_better_price',
            'no_longer_needed',
        ];

        $lowerReason = strtolower($reason);

        foreach ($highRiskReasons as $risky) {
            if (str_contains($lowerReason, $risky)) {
                return 0.8;
            }
        }

        foreach ($mediumRiskReasons as $risky) {
            if (str_contains($lowerReason, $risky)) {
                return 0.5;
            }
        }

        return 0.3;
    }

    private function getCategoryRisk(string $category): float
    {
        $riskMap = [
            'smartphones' => 0.85,
            'laptops' => 0.80,
            'tablets' => 0.75,
            'smartwatches' => 0.70,
            'headphones' => 0.55,
            'accessories' => 0.35,
        ];

        return $riskMap[$category] ?? 0.5;
    }

    private function calculateMetadataCompleteness(array $metadata): float
    {
        if (empty($metadata)) {
            return 0.0;
        }

        $requiredFields = ['imei', 'battery_health', 'screen_condition', 'activation_date'];
        $presentFields = 0;

        foreach ($requiredFields as $field) {
            if (isset($metadata[$field]) && !empty($metadata[$field])) {
                $presentFields++;
            }
        }

        return $presentFields / count($requiredFields);
    }

    private function checkSerialMultipleReturns(string $serialNumber, int $userId): int
    {
        return (int) DB::table('electronics_returns')
            ->where('serial_number', $serialNumber)
            ->where('user_id', '!=', $userId)
            ->count();
    }

    private function getReturnFrequency(int $userId, int $days): int
    {
        return (int) DB::table('electronics_returns')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    private function isBusinessUser(int $userId): bool
    {
        return DB::table('business_groups')
            ->where('owner_id', $userId)
            ->exists();
    }

    private function analyzeRiskFactors(ReturnFraudDetectionDto $dto, ElectronicsProduct $product, array $mlFeatures): array
    {
        $factors = [];

        if ($mlFeatures['user_return_rate'] > 0.4) {
            $factors[] = [
                'factor' => 'excessive_return_rate',
                'severity' => 'high',
                'description' => 'User return rate exceeds threshold',
                'value' => round($mlFeatures['user_return_rate'] * 100, 2) . '%',
            ];
        }

        if ($mlFeatures['return_frequency_30d'] > 3) {
            $factors[] = [
                'factor' => 'high_return_frequency',
                'severity' => 'high',
                'description' => 'Multiple returns in 30 days',
                'value' => $mlFeatures['return_frequency_30d'],
            ];
        }

        if ($mlFeatures['same_serial_multiple_returns'] > 0) {
            $factors[] = [
                'factor' => 'serial_reuse_detected',
                'severity' => 'critical',
                'description' => 'Same serial number returned by different users',
                'value' => $mlFeatures['same_serial_multiple_returns'],
            ];
        }

        if ($mlFeatures['days_since_purchase'] < 7) {
            $factors[] = [
                'factor' => 'quick_return',
                'severity' => 'medium',
                'description' => 'Return initiated within 7 days of purchase',
                'value' => $mlFeatures['days_since_purchase'] . ' days',
            ];
        }

        if ($mlFeatures['device_condition_score'] > 0.8 && $mlFeatures['days_since_purchase'] > 90) {
            $factors[] = [
                'factor' => 'condition_mismatch',
                'severity' => 'high',
                'description' => 'Device condition too good for purchase age',
                'value' => $mlFeatures['device_condition_score'],
            ];
        }

        if ($mlFeatures['return_reason_risk'] > 0.7 && $mlFeatures['device_condition_score'] < 0.5) {
            $factors[] = [
                'factor' => 'reason_condition_mismatch',
                'severity' => 'medium',
                'description' => 'Return reason conflicts with device condition',
                'value' => 'reason: ' . $mlFeatures['return_reason_risk'] . ', condition: ' . $mlFeatures['device_condition_score'],
            ];
        }

        if ($mlFeatures['device_metadata_completeness'] < 0.5) {
            $factors[] = [
                'factor' => 'insufficient_metadata',
                'severity' => 'low',
                'description' => 'Device metadata is incomplete',
                'value' => round($mlFeatures['device_metadata_completeness'] * 100, 2) . '%',
            ];
        }

        if ($mlFeatures['cart_abandonment_rate'] > 0.8) {
            $factors[] = [
                'factor' => 'high_cart_abandonment',
                'severity' => 'low',
                'description' => 'User has high cart abandonment rate',
                'value' => round($mlFeatures['cart_abandonment_rate'] * 100, 2) . '%',
            ];
        }

        return $factors;
    }

    private function calculateRiskLevel(float $fraudScore): string
    {
        return match (true) {
            $fraudScore >= 0.8 => 'critical',
            $fraudScore >= 0.65 => 'high',
            $fraudScore >= 0.45 => 'medium',
            $fraudScore >= 0.25 => 'low',
            default => 'minimal',
        };
    }

    private function getRecommendedAction(string $riskLevel, bool $isFraudulent): ?string
    {
        if ($isFraudulent) {
            return 'block_return_and_investigate';
        }

        return match ($riskLevel) {
            'critical' => 'manual_review_with_evidence',
            'high' => 'additional_verification_required',
            'medium' => 'flag_for_monitoring',
            'low' => 'process_with_delay',
            'minimal' => 'approve_immediately',
            default => 'approve_immediately',
        };
    }

    private function calculateHoldDuration(string $riskLevel): ?int
    {
        return match ($riskLevel) {
            'critical' => 4320,
            'high' => 2880,
            'medium' => 1440,
            'low' => 720,
            'minimal' => null,
            default => null,
        };
    }

    private function logDetectionResult(ReturnFraudDetectionDto $dto, FraudDetectionResultDto $result): void
    {
        Log::channel('audit')->info('Return fraud detection completed', [
            'order_id' => $dto->orderId,
            'product_id' => $dto->productId,
            'serial_number' => $dto->serialNumber,
            'user_id' => $dto->userId,
            'correlation_id' => $dto->correlationId,
            'is_fraudulent' => $result->isFraudulent,
            'fraud_probability' => $result->fraudProbability,
            'risk_level' => $result->riskLevel,
            'recommended_action' => $result->recommendedAction,
        ]);
    }

    private function saveDetectionRecord(ReturnFraudDetectionDto $dto, FraudDetectionResultDto $result): void
    {
        DB::table('electronics_return_fraud_detections')->insert([
            'order_id' => $dto->orderId,
            'product_id' => $dto->productId,
            'serial_number' => $dto->serialNumber,
            'user_id' => $dto->userId,
            'correlation_id' => $dto->correlationId,
            'return_reason' => $dto->returnReason,
            'condition' => $dto->condition,
            'is_fraudulent' => $result->isFraudulent,
            'fraud_probability' => $result->fraudProbability,
            'risk_level' => $result->riskLevel,
            'risk_factors' => json_encode($result->riskFactors),
            'ml_features' => json_encode($result->mlFeatures),
            'recommended_action' => $result->recommendedAction,
            'hold_duration_minutes' => $result->holdDurationMinutes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
