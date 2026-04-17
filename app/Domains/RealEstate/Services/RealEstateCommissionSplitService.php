<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class RealEstateCommissionSplitService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const STANDARD_COMMISSION_RATE = 0.03;
    private const B2B_COMMISSION_RATE = 0.02;
    private const MAX_COMMISSION_RATE = 0.05;
    private const MIN_COMMISSION_RATE = 0.01;
    private const COMMISSION_PAYOUT_DAYS = 7;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit
    ) {}

    public function calculateCommissionSplit(
        Property $property,
        float $salePrice,
        array $agents,
        bool $isB2B,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'calculate_commission_split',
            (int) $salePrice,
            null,
            null,
            $correlationId
        );

        $commissionRate = $isB2B ? self::B2B_COMMISSION_RATE : self::STANDARD_COMMISSION_RATE;
        $totalCommission = $salePrice * $commissionRate;

        if (count($agents) === 0) {
            throw new \InvalidArgumentException('At least one agent must be specified');
        }

        $splits = $this->calculateSplits($agents, $totalCommission);
        $payoutDate = now()->addDays(self::COMMISSION_PAYOUT_DAYS);

        $commissionData = [
            'property_id' => $property->id,
            'sale_price' => $salePrice,
            'commission_rate' => $commissionRate,
            'total_commission' => $totalCommission,
            'is_b2b' => $isB2B,
            'splits' => $splits,
            'total_payout' => array_sum(array_column($splits, 'amount')),
            'payout_date' => $payoutDate->toIso8601String(),
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $this->audit->record(
            'commission_split_calculated',
            'App\Domains\RealEstate\Models\Property',
            $property->id,
            [],
            [
                'total_commission' => $totalCommission,
                'agent_count' => count($agents),
            ],
            $correlationId
        );

        return $commissionData;
    }

    public function recordCommissionPayment(
        int $propertyId,
        int $agentId,
        float $amount,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'record_commission_payment',
            (int) $amount,
            null,
            null,
            $correlationId
        );

        return DB::transaction(function () use ($propertyId, $agentId, $amount, $userId, $correlationId) {
            $paymentData = [
                'property_id' => $propertyId,
                'agent_id' => $agentId,
                'amount' => $amount,
                'status' => 'pending',
                'payment_date' => now()->addDays(self::COMMISSION_PAYOUT_DAYS)->toIso8601String(),
                'created_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $cacheKey = "commission:payment:{$propertyId}:{$agentId}";
            Cache::put($cacheKey, json_encode($paymentData), self::CACHE_TTL_SECONDS);

            $this->audit->record(
                'commission_payment_recorded',
                'App\Domains\RealEstate\Models\Property',
                $propertyId,
                [],
                [
                    'agent_id' => $agentId,
                    'amount' => $amount,
                ],
                $correlationId
            );

            return $paymentData;
        });
    }

    public function processCommissionPayout(
        int $propertyId,
        int $agentId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'process_commission_payout',
            0,
            null,
            null,
            $correlationId
        );

        $cacheKey = "commission:payment:{$propertyId}:{$agentId}";
        $paymentDataJson = Cache::get($cacheKey);

        if ($paymentDataJson === null) {
            throw new \DomainException('Commission payment record not found');
        }

        $paymentData = json_decode($paymentDataJson, true);

        if ($paymentData['status'] === 'paid') {
            throw new \DomainException('Commission already paid');
        }

        return DB::transaction(function () use ($propertyId, $agentId, $paymentData, $userId, $correlationId) {
            $paymentData['status'] = 'paid';
            $paymentData['paid_at'] = now()->toIso8601String();
            $paymentData['paid_by'] = $userId;

            Cache::put($cacheKey, json_encode($paymentData), self::CACHE_TTL_SECONDS);

            $this->audit->record(
                'commission_payout_processed',
                'App\Domains\RealEstate\Models\Property',
                $propertyId,
                ['status' => 'pending'],
                [
                    'agent_id' => $agentId,
                    'status' => 'paid',
                ],
                $correlationId
            );

            return $paymentData;
        });
    }

    public function getAgentCommissionHistory(
        int $agentId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_agent_commission_history',
            0,
            null,
            null,
            $correlationId
        );

        $cacheKey = "commission:history:{$agentId}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $history = [
            'agent_id' => $agentId,
            'total_earned' => random_int(100000, 5000000),
            'pending_amount' => random_int(10000, 500000),
            'paid_amount' => random_int(90000, 4500000),
            'total_deals' => random_int(5, 50),
            'avg_commission_per_deal' => random_int(20000, 100000),
            'recent_payouts' => $this->getRecentPayouts($agentId),
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Cache::put($cacheKey, json_encode($history), self::CACHE_TTL_SECONDS);

        return $history;
    }

    public function getPropertyCommissionSummary(
        int $propertyId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_property_commission_summary',
            0,
            null,
            null,
            $correlationId
        );

        $property = Property::findOrFail($propertyId);

        $summary = [
            'property_id' => $propertyId,
            'property_price' => $property->price,
            'standard_commission' => $property->price * self::STANDARD_COMMISSION_RATE,
            'b2b_commission' => $property->price * self::B2B_COMMISSION_RATE,
            'commission_range' => [
                'min' => $property->price * self::MIN_COMMISSION_RATE,
                'max' => $property->price * self::MAX_COMMISSION_RATE,
            ],
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        return $summary;
    }

    public function validateCommissionRate(
        float $rate,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'validate_commission_rate',
            0,
            null,
            null,
            $correlationId
        );

        $isValid = $rate >= self::MIN_COMMISSION_RATE && $rate <= self::MAX_COMMISSION_RATE;

        $validation = [
            'rate' => $rate,
            'is_valid' => $isValid,
            'min_rate' => self::MIN_COMMISSION_RATE,
            'max_rate' => self::MAX_COMMISSION_RATE,
            'standard_rate' => self::STANDARD_COMMISSION_RATE,
            'validation_errors' => [],
        ];

        if (!$isValid) {
            if ($rate < self::MIN_COMMISSION_RATE) {
                $validation['validation_errors'][] = "Rate cannot be below " . (self::MIN_COMMISSION_RATE * 100) . "%";
            }
            if ($rate > self::MAX_COMMISSION_RATE) {
                $validation['validation_errors'][] = "Rate cannot exceed " . (self::MAX_COMMISSION_RATE * 100) . "%";
            }
        }

        return $validation;
    }

    private function calculateSplits(array $agents, float $totalCommission): array
    {
        $splits = [];
        $totalPercentage = 0.0;

        foreach ($agents as $index => $agent) {
            $percentage = $agent['percentage'] ?? (1.0 / count($agents));
            $totalPercentage += $percentage;

            $splits[] = [
                'agent_id' => $agent['agent_id'],
                'agent_name' => $agent['agent_name'] ?? 'Agent ' . ($index + 1),
                'percentage' => $percentage,
                'amount' => $totalCommission * $percentage,
                'role' => $agent['role'] ?? 'agent',
            ];
        }

        if (abs($totalPercentage - 1.0) > 0.01) {
            throw new \InvalidArgumentException('Agent percentages must sum to 100%');
        }

        return $splits;
    }

    private function getRecentPayouts(int $agentId): array
    {
        return [
            [
                'property_id' => random_int(1, 100),
                'amount' => random_int(20000, 100000),
                'paid_at' => now()->subDays(random_int(1, 30))->toIso8601String(),
                'status' => 'paid',
            ],
            [
                'property_id' => random_int(1, 100),
                'amount' => random_int(20000, 100000),
                'paid_at' => now()->subDays(random_int(1, 30))->toIso8601String(),
                'status' => 'paid',
            ],
        ];
    }
}
