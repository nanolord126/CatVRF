<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\DTOs\SerialNumberValidationDto;
use App\Domains\Electronics\DTOs\FraudDetectionResultDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Services\FraudControlService;
use App\Services\FraudMLService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class SerialNumberValidationService
{
    public function __construct(
        private FraudControlService $fraud,
        private FraudMLService $fraudML,
        private Cache $cache,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

    public function validateSerialNumber(SerialNumberValidationDto $dto): FraudDetectionResultDto
    {
        $correlationId = $dto->correlationId;

        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'electronics_serial_validation',
            amount: 0,
            correlationId: $correlationId
        );

        $cacheKey = sprintf(
            'serial_validation:%s:%s',
            $dto->serialNumber,
            $dto->productId
        );

        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            $this->logger->info('Serial number validation cache hit', [
                'serial_number' => $dto->serialNumber,
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
                model: 'serial_number_validation'
            );

            $riskFactors = $this->analyzeRiskFactors($dto, $product, $mlFeatures);

            $isFraudulent = $fraudScore > 0.7;
            $riskLevel = $this->calculateRiskLevel($fraudScore);

            $result = new FraudDetectionResultDto(
                isFraudulent: $isFraudulent,
                fraudProbability: $fraudScore,
                riskLevel: $riskLevel,
                riskFactors: $riskFactors,
                mlFeatures: $mlFeatures,
                correlationId: $correlationId,
                recommendedAction: $this->getRecommendedAction($riskLevel, $isFraudulent),
                holdDurationMinutes: $isFraudulent ? 1440 : null,
            );

            $this->logValidationResult($dto, $result);
            $this->saveValidationRecord($dto, $result);

            $this->cache->put($cacheKey, $result->toArray(), now()->addHours(24));

            return $result;
        });
    }

    private function extractMLFeatures(SerialNumberValidationDto $dto, ElectronicsProduct $product): array
    {
        $serialPattern = $this->analyzeSerialPattern($dto->serialNumber);

        $userOrderHistory = $this->getUserOrderHistory($dto->userId);

        $serialUsageCount = $this->getSerialUsageCount($dto->serialNumber);

        $timeSincePurchase = $dto->purchaseDate
            ? now()->diffInDays(\Carbon\Carbon::parse($dto->purchaseDate))
            : null;

        return [
            'serial_length' => strlen($dto->serialNumber),
            'serial_has_letters' => preg_match('/[a-zA-Z]/', $dto->serialNumber) ? 1 : 0,
            'serial_has_numbers' => preg_match('/[0-9]/', $dto->serialNumber) ? 1 : 0,
            'serial_has_special_chars' => preg_match('/[^a-zA-Z0-9]/', $dto->serialNumber) ? 1 : 0,
            'serial_pattern_score' => $serialPattern['score'],
            'serial_entropy' => $this->calculateEntropy($dto->serialNumber),
            'product_price_tier' => $this->getPriceTier($product->price_kopecks),
            'product_category_risk' => $this->getCategoryRisk($product->category),
            'user_total_orders' => $userOrderHistory['total_orders'],
            'user_total_returns' => $userOrderHistory['total_returns'],
            'user_return_rate' => $userOrderHistory['return_rate'],
            'user_account_age_days' => $userOrderHistory['account_age_days'],
            'serial_usage_count' => $serialUsageCount,
            'time_since_purchase_days' => $timeSincePurchase,
            'has_proof_of_purchase' => $dto->proofOfPurchaseUrl !== null ? 1 : 0,
            'has_order_reference' => $dto->orderId !== null ? 1 : 0,
            'is_business_user' => $this->isBusinessUser($dto->userId) ? 1 : 0,
        ];
    }

    private function analyzeSerialPattern(string $serialNumber): array
    {
        $patterns = [
            '/^[A-Z]{2}[0-9]{6}$/' => 0.95,
            '/^[0-9]{10}$/' => 0.90,
            '/^[A-Z0-9]{12}$/' => 0.85,
            '/^[A-Z]{3}-[0-9]{4}-[A-Z]{2}$/' => 0.80,
        ];

        foreach ($patterns as $pattern => $score) {
            if (preg_match($pattern, $serialNumber)) {
                return ['pattern' => $pattern, 'score' => $score];
            }
        }

        return ['pattern' => 'unknown', 'score' => 0.5];
    }

    private function calculateEntropy(string $string): float
    {
        $entropy = 0.0;
        $length = strlen($string);

        if ($length === 0) {
            return 0.0;
        }

        $frequency = array_count_values(str_split($string));

        foreach ($frequency as $count) {
            $probability = $count / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }

    private function getPriceTier(int $priceKopecks): string
    {
        $rubles = $priceKopecks / 100;

        return match (true) {
            $rubles < 5000 => 'budget',
            $rubles < 20000 => 'mid_range',
            $rubles < 50000 => 'premium',
            default => 'luxury',
        };
    }

    private function getCategoryRisk(string $category): float
    {
        $riskMap = [
            'smartphones' => 0.8,
            'laptops' => 0.75,
            'tablets' => 0.7,
            'smartwatches' => 0.65,
            'headphones' => 0.5,
            'accessories' => 0.3,
        ];

        return $riskMap[$category] ?? 0.5;
    }

    private function getUserOrderHistory(int $userId): array
    {
        $cacheKey = "user_order_history:{$userId}";

        return $this->cache->remember($cacheKey, now()->addHours(6), function () use ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            $accountAgeDays = $user ? now()->diffInDays($user->created_at) : 0;

            $totalOrders = (int) DB::table('orders')
                ->where('user_id', $userId)
                ->count();

            $totalReturns = (int) DB::table('electronics_returns')
                ->where('user_id', $userId)
                ->count();

            $returnRate = $totalOrders > 0 ? $totalReturns / $totalOrders : 0.0;

            return [
                'total_orders' => $totalOrders,
                'total_returns' => $totalReturns,
                'return_rate' => $returnRate,
                'account_age_days' => $accountAgeDays,
            ];
        });
    }

    private function getSerialUsageCount(string $serialNumber): int
    {
        return (int) DB::table('electronics_serial_validations')
            ->where('serial_number', $serialNumber)
            ->count();
    }

    private function isBusinessUser(int $userId): bool
    {
        return DB::table('business_groups')
            ->where('owner_id', $userId)
            ->exists();
    }

    private function analyzeRiskFactors(SerialNumberValidationDto $dto, ElectronicsProduct $product, array $mlFeatures): array
    {
        $factors = [];

        if ($mlFeatures['serial_usage_count'] > 3) {
            $factors[] = [
                'factor' => 'high_serial_usage',
                'severity' => 'high',
                'description' => 'Serial number used multiple times',
                'value' => $mlFeatures['serial_usage_count'],
            ];
        }

        if ($mlFeatures['user_return_rate'] > 0.3) {
            $factors[] = [
                'factor' => 'high_return_rate',
                'severity' => 'medium',
                'description' => 'User has high return rate',
                'value' => round($mlFeatures['user_return_rate'] * 100, 2) . '%',
            ];
        }

        if ($mlFeatures['serial_pattern_score'] < 0.7) {
            $factors[] = [
                'factor' => 'unusual_serial_pattern',
                'severity' => 'medium',
                'description' => 'Serial number pattern is unusual',
                'value' => $mlFeatures['serial_pattern_score'],
            ];
        }

        if ($mlFeatures['product_category_risk'] > 0.7) {
            $factors[] = [
                'factor' => 'high_risk_category',
                'severity' => 'low',
                'description' => 'Product category has high fraud risk',
                'value' => $product->category,
            ];
        }

        if ($mlFeatures['has_proof_of_purchase'] === 0) {
            $factors[] = [
                'factor' => 'no_proof_of_purchase',
                'severity' => 'medium',
                'description' => 'No proof of purchase provided',
                'value' => false,
            ];
        }

        if ($mlFeatures['time_since_purchase_days'] !== null && $mlFeatures['time_since_purchase_days'] > 365) {
            $factors[] = [
                'factor' => 'expired_warranty',
                'severity' => 'low',
                'description' => 'Purchase date exceeds warranty period',
                'value' => $mlFeatures['time_since_purchase_days'] . ' days',
            ];
        }

        return $factors;
    }

    private function calculateRiskLevel(float $fraudScore): string
    {
        return match (true) {
            $fraudScore >= 0.8 => 'critical',
            $fraudScore >= 0.6 => 'high',
            $fraudScore >= 0.4 => 'medium',
            $fraudScore >= 0.2 => 'low',
            default => 'minimal',
        };
    }

    private function getRecommendedAction(string $riskLevel, bool $isFraudulent): ?string
    {
        if ($isFraudulent) {
            return 'block_and_investigate';
        }

        return match ($riskLevel) {
            'critical' => 'manual_review_required',
            'high' => 'additional_verification',
            'medium' => 'flag_for_monitoring',
            'low' => 'proceed_with_caution',
            'minimal' => 'approve',
            default => 'approve',
        };
    }

    private function logValidationResult(SerialNumberValidationDto $dto, FraudDetectionResultDto $result): void
    {
        Log::channel('audit')->info('Serial number validation completed', [
            'serial_number' => $dto->serialNumber,
            'product_id' => $dto->productId,
            'user_id' => $dto->userId,
            'correlation_id' => $dto->correlationId,
            'is_fraudulent' => $result->isFraudulent,
            'fraud_probability' => $result->fraudProbability,
            'risk_level' => $result->riskLevel,
            'recommended_action' => $result->recommendedAction,
        ]);
    }

    private function saveValidationRecord(SerialNumberValidationDto $dto, FraudDetectionResultDto $result): void
    {
        DB::table('electronics_serial_validations')->insert([
            'product_id' => $dto->productId,
            'serial_number' => $dto->serialNumber,
            'user_id' => $dto->userId,
            'order_id' => $dto->orderId,
            'correlation_id' => $dto->correlationId,
            'is_fraudulent' => $result->isFraudulent,
            'fraud_probability' => $result->fraudProbability,
            'risk_level' => $result->riskLevel,
            'risk_factors' => json_encode($result->riskFactors),
            'ml_features' => json_encode($result->mlFeatures),
            'recommended_action' => $result->recommendedAction,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
