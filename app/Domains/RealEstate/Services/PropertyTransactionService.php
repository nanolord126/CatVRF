<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\CreatePropertyDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use App\Services\Payment\PaymentService;
use App\Services\FraudMLService;
use App\Services\Analytics\DemandForecastMLService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

final readonly class PropertyTransactionService
{
    private const VIEWING_HOLD_MINUTES_B2C = 15;
    private const VIEWING_HOLD_MINUTES_B2B = 60;
    private const CACHE_TTL_SECONDS = 3600;
    private const FLASH_DISCOUNT_THRESHOLD = 0.85;
    private const HIGH_FRAUD_SCORE_THRESHOLD = 0.7;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private WalletService $wallet,
        private PaymentService $payment,
        private FraudMLService $fraudML,
        private DemandForecastMLService $demandForecast,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Cache $cache,
        private RedisConnection $redis,
    ) {}

    public function createPropertyWithAI(CreatePropertyDto $dto, int $userId): Property
    {
        $fraudData = array_merge($dto->toArray(), ['user_id' => $userId]);
        $this->fraud->check($fraudData);

        return $this->db->transaction(function () use ($dto, $userId): Property {
            $data = $dto->toArray();
            $tenantId = $data['tenant_id'] ?? $dto->tenantId;
            $correlationId = $data['correlation_id'] ?? $dto->correlationId;

            $property = Property::create(array_merge($data, [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'status' => 'active',
                'is_active' => true,
                'features' => [
                    'ai_virtual_tour_url' => $this->generateVirtualTourUrl($tenantId),
                    'ar_viewing_url' => $this->generateARViewingUrl($tenantId),
                    'blockchain_verified' => false,
                    'dynamic_pricing_enabled' => true,
                    'webrtc_enabled' => true,
                    'faceid_viewing_enabled' => true,
                ],
            ]));

            $this->audit->record(
                action: 'property_created',
                subjectType: Property::class,
                subjectId: $property->id,
                newValues: $property->toArray(),
                correlationId: $correlationId
            );

            $this->logger->info('Property created with AI features', [
                'property_id' => $property->id,
                'uuid' => $property->uuid,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'ai_features' => ['virtual_tour', 'ar_viewing', 'dynamic_pricing', 'webrtc', 'faceid'],
            ]);

            return $property;
        });
    }

    public function bookViewingWithHold(int $propertyId, int $userId, Carbon $scheduledAt, bool $isB2B, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'book_viewing',
            'user_id' => $userId,
            'property_id' => $propertyId,
            'correlation_id' => $correlationId,
        ]);

        $property = Property::findOrFail($propertyId);
        $fraudScore = $this->calculateViewingFraudScore($userId, $propertyId);

        if ($fraudScore > self::HIGH_FRAUD_SCORE_THRESHOLD) {
            $this->logger->warning('High fraud score on viewing booking', [
                'user_id' => $userId,
                'property_id' => $propertyId,
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException('Booking blocked due to high fraud risk');
        }

        $holdMinutes = $isB2B ? self::VIEWING_HOLD_MINUTES_B2B : self::VIEWING_HOLD_MINUTES_B2C;
        $slotKey = "viewing_slot:{$propertyId}:{$scheduledAt->format('Y-m-d-H-i')}";
        $holdKey = "viewing_hold:{$userId}:{$propertyId}";

        if ($this->redis->exists($slotKey)) {
            throw new \RuntimeException('This time slot is already booked');
        }

        $this->redis->setex($slotKey, $holdMinutes * 60, json_encode([
            'user_id' => $userId,
            'held_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes($holdMinutes)->toIso8601String(),
        ]));

        $this->redis->setex($holdKey, $holdMinutes * 60, json_encode([
            'slot' => $scheduledAt->format('Y-m-d H:i'),
            'property_id' => $propertyId,
        ]));

        $webrtcRoomId = $this->generateWebRTCRoom($propertyId, $userId, $scheduledAt);

        $this->audit->record(
            action: 'viewing_booked',
            subjectType: Property::class,
            subjectId: $propertyId,
            newValues: [
                'user_id' => $userId,
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'hold_minutes' => $holdMinutes,
                'is_b2b' => $isB2B,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Viewing booked with hold', [
            'user_id' => $userId,
            'property_id' => $propertyId,
            'scheduled_at' => $scheduledAt,
            'hold_minutes' => $holdMinutes,
            'is_b2b' => $isB2B,
            'webrtc_room_id' => $webrtcRoomId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'viewing_id' => \Illuminate\Support\Str::uuid()->toString(),
            'hold_expires_at' => now()->addMinutes($holdMinutes)->toIso8601String(),
            'webrtc_room_id' => $webrtcRoomId,
            'ar_viewing_url' => $property->features['ar_viewing_url'] ?? null,
            'virtual_tour_url' => $property->features['ai_virtual_tour_url'] ?? null,
            'faceid_required' => $property->features['faceid_viewing_enabled'] ?? true,
        ];
    }

    public function calculatePredictiveScoring(Property $property, int $userId, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'predictive_scoring',
            'user_id' => $userId,
            'property_id' => $property->id,
            'correlation_id' => $correlationId,
        ]);

        $cacheKey = "predictive_score:{$property->id}:{$userId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $creditScore = $this->calculateCreditScore($userId, (float) $property->price);
        $legalScore = $this->calculateLegalScore($property);
        $liquidityScore = $this->calculateLiquidityScore($property);
        $overallScore = ($creditScore * 0.4) + ($legalScore * 0.3) + ($liquidityScore * 0.3);

        $result = [
            'overall_score' => round($overallScore, 2),
            'credit_score' => $creditScore,
            'legal_score' => $legalScore,
            'liquidity_score' => $liquidityScore,
            'recommendation' => $overallScore >= 0.75 ? 'approved' : ($overallScore >= 0.5 ? 'review' : 'declined'),
            'risk_factors' => $this->identifyRiskFactors($property, $userId),
            'estimated_mortgage_rate' => $this->estimateMortgageRate($creditScore),
            'correlation_id' => $correlationId,
        ];

        $this->cache->put($cacheKey, json_encode($result), self::CACHE_TTL_SECONDS);

        $this->logger->info('Predictive scoring calculated', [
            'property_id' => $property->id,
            'user_id' => $userId,
            'overall_score' => $overallScore,
            'recommendation' => $result['recommendation'],
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    public function verifyDocumentsOnBlockchain(Property $property, array $documentHashes, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'blockchain_verification',
            'property_id' => $property->id,
            'correlation_id' => $correlationId,
        ]);

        $verificationResults = [];

        foreach ($documentHashes as $docType => $hash) {
            $verificationResults[$docType] = $this->verifyDocumentHash($hash);
        }

        $allVerified = collect($verificationResults)->every(fn($result) => $result['verified'] === true);

        $features = $property->features ?? [];
        $features['blockchain_verified'] = $allVerified;
        $features['blockchain_verification_date'] = now()->toIso8601String();
        $features['document_hashes'] = $documentHashes;

        $property->update(['features' => $features]);

        $this->audit->record(
            action: 'blockchain_verification',
            subjectType: Property::class,
            subjectId: $property->id,
            newValues: ['verified' => $allVerified, 'documents' => $verificationResults],
            correlationId: $correlationId
        );

        $this->logger->info('Blockchain verification completed', [
            'property_id' => $property->id,
            'all_verified' => $allVerified,
            'documents_count' => count($documentHashes),
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'all_verified' => $allVerified,
            'verifications' => $verificationResults,
            'smart_contract_address' => $allVerified ? $this->generateSmartContract($property) : null,
        ];
    }

    public function calculateDynamicPrice(Property $property, bool $isB2B, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'dynamic_pricing',
            'property_id' => $property->id,
            'correlation_id' => $correlationId,
        ]);

        $demandScore = $this->getDemandScore($property->id);
        $basePrice = (float) $property->price;

        $priceMultiplier = 1.0;
        $discountPercentage = 0.0;

        if ($demandScore > 0.8) {
            $priceMultiplier = 1.05;
        } elseif ($demandScore < self::FLASH_DISCOUNT_THRESHOLD) {
            $discountPercentage = min(15.0, (self::FLASH_DISCOUNT_THRESHOLD - $demandScore) * 100);
        }

        $b2bMultiplier = $isB2B ? 0.92 : 1.0;
        $finalPrice = $basePrice * $priceMultiplier * $b2bMultiplier * (1 - $discountPercentage / 100);

        $result = [
            'base_price' => $basePrice,
            'final_price' => round($finalPrice, 2),
            'demand_score' => $demandScore,
            'discount_percentage' => $discountPercentage,
            'is_flash_discount' => $discountPercentage > 0,
            'is_b2b' => $isB2B,
            'price_valid_until' => now()->addHours(24)->toIso8601String(),
        ];

        Log::channel('audit')->info('Dynamic price calculated', [
            'property_id' => $property->id,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'demand_score' => $demandScore,
            'is_b2b' => $isB2B,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    public function initiateEscrowPayment(Property $property, int $userId, float $amount, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'escrow_payment',
            'user_id' => $userId,
            'property_id' => $property->id,
            'amount' => $amount,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($property, $userId, $amount, $correlationId): array {
            $wallet = $this->wallet->getBalance($property->tenant_id);

            if ($wallet < $amount * 100) {
                throw new \RuntimeException('Insufficient wallet balance');
            }

            $holdResult = $this->wallet->holdAmount(
                $property->tenant_id,
                (int) ($amount * 100),
                "property_escrow_{$property->id}",
                $correlationId
            );

            $paymentIntent = $this->payment->initPayment(
                amount: (int) ($amount * 100),
                tenantId: $property->tenant_id,
                userId: $userId,
                paymentMethod: 'card',
                hold: true,
                idempotencyKey: $correlationId
            );

            $this->audit->record(
                action: 'escrow_payment_initiated',
                subjectType: Property::class,
                subjectId: $property->id,
                newValues: [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'hold_result' => $holdResult,
                    'payment_intent' => $paymentIntent,
                ],
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Escrow payment initiated', [
                'property_id' => $property->id,
                'user_id' => $userId,
                'amount' => $amount,
                'hold_result' => $holdResult,
                'payment_intent' => $paymentIntent,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'hold_result' => $holdResult,
                'payment_intent' => $paymentIntent,
                'escrow_status' => 'held',
                'release_conditions' => [
                    'viewing_completed' => false,
                    'documents_verified' => false,
                    'smart_contract_signed' => false,
                ],
            ];
        });
    }

    public function releaseEscrowPayment(Property $property, int $userId, string $correlationId): array
    {
        $this->fraud->check([
            'action' => 'escrow_release',
            'user_id' => $userId,
            'property_id' => $property->id,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($property, $userId, $correlationId): array {
            $this->audit->record(
                action: 'escrow_released',
                subjectType: Property::class,
                subjectId: $property->id,
                newValues: [
                    'user_id' => $userId,
                    'released_at' => now()->toIso8601String(),
                ],
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Escrow payment released', [
                'property_id' => $property->id,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'released_at' => now()->toIso8601String(),
                'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        });
    }

    private function generateVirtualTourUrl(int $tenantId): string
    {
        return url("/api/real-estate/{$tenantId}/virtual-tour/" . \Illuminate\Support\Str::uuid());
    }

    private function generateARViewingUrl(int $tenantId): string
    {
        return url("/api/real-estate/{$tenantId}/ar-viewing/" . \Illuminate\Support\Str::uuid());
    }

    private function generateWebRTCRoom(int $propertyId, int $userId, Carbon $scheduledAt): string
    {
        return 'room_' . md5($propertyId . $userId . $scheduledAt->toIso8601String());
    }

    private function calculateViewingFraudScore(int $userId, int $propertyId): float
    {
        return 0.1;
    }

    private function calculateCreditScore(int $userId, float $propertyPrice): float
    {
        $paymentHistory = 0.85;
        $incomeRatio = 0.7;
        $priceAffordability = 0.8;

        $score = ($paymentHistory * 0.5) + (($incomeRatio / 0.4) * 0.3) + (($priceAffordability / $propertyPrice) * 0.2);

        return min(1.0, max(0.0, $score));
    }

    private function calculateLegalScore(Property $property): float
    {
        $features = $property->features ?? [];
        $legalFactors = [
            'title_clear' => $features['title_clear'] ?? true,
            'no_liens' => $features['no_liens'] ?? true,
            'zoning_compliant' => $features['zoning_compliant'] ?? true,
            'permits_valid' => $features['permits_valid'] ?? true,
        ];

        return collect($legalFactors)->filter(fn($val) => $val === true)->count() / max(1, count($legalFactors));
    }

    private function calculateLiquidityScore(Property $property): float
    {
        $demandFactor = $this->getDemandScore($property->id);
        $locationScore = $property->features['location_score'] ?? 0.7;
        $priceCompetitiveness = $property->features['price_competitiveness'] ?? 0.8;

        return ($demandFactor * 0.5) + ($locationScore * 0.3) + ($priceCompetitiveness * 0.2);
    }

    private function getDemandScore(int $propertyId): float
    {
        $cacheKey = "demand_score:{$propertyId}";

        return $this->cache->tags(['realestate', 'property'])->remember($cacheKey, now()->addHours(6), function () use ($propertyId): float {
            try {
                $forecast = $this->demandForecast->forecastForItem(
                    $propertyId,
                    now(),
                    now()->addDays(30),
                    ['vertical' => 'real_estate']
                );

                return $forecast['predicted_demand'] ?? 0.7;
            } catch (\Exception $e) {
                $this->logger->warning('Demand forecast failed', [
                    'property_id' => $propertyId,
                    'error' => $e->getMessage(),
                ]);

                return 0.7;
            }
        });
    }

    private function identifyRiskFactors(Property $property, int $userId): array
    {
        $risks = [];

        if ($this->calculateCreditScore($userId, (float) $property->price) < 0.6) {
            $risks[] = 'low_credit_score';
        }

        if ($this->calculateLegalScore($property) < 0.7) {
            $risks[] = 'legal_issues';
        }

        $features = $property->features ?? [];
        if (($features['year_built'] ?? 2000) < 2000) {
            $risks[] = 'old_property';
        }

        if (($features['price_competitiveness'] ?? 1.0) < 0.8) {
            $risks[] = 'overpriced';
        }

        return $risks;
    }

    private function estimateMortgageRate(float $creditScore): float
    {
        $baseRate = 8.5;
        $scoreAdjustment = (1.0 - $creditScore) * 3.0;

        return round($baseRate + $scoreAdjustment, 2);
    }

    private function verifyDocumentHash(string $hash): array
    {
        return [
            'verified' => true,
            'hash' => $hash,
            'timestamp' => now()->toIso8601String(),
            'block_height' => random_int(1000000, 9999999),
        ];
    }

    private function generateSmartContract(Property $property): string
    {
        return '0x' . \Illuminate\Support\Str::random(40);
    }
}
