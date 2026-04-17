<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services;

use App\Domains\Sports\Models\Membership;
use App\Domains\Sports\Models\Gym;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class SportsDynamicPricingService
{
    private const CACHE_TTL = 1800;
    private const FLASH_DISCOUNT_START_HOUR = 14;
    private const FLASH_DISCOUNT_END_HOUR = 17;
    private const FLASH_DISCOUNT_PERCENTAGE = 30;
    private const PEAK_HOUR_MULTIPLIER = 1.5;
    private const LOW_LOAD_DISCOUNT = 0.85;
    private const B2B_DISCOUNT = 0.85;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
    ) {}

    public function calculateDynamicPrice(int $venueId, string $serviceType, bool $isB2B = false, int $userId = 0, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'dynamic_pricing_calculation',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = "sports:dynamic_price:{$venueId}:{$serviceType}:" . ($isB2B ? 'b2b' : 'b2c');
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $venue = Gym::with(['memberships'])->findOrFail($venueId);
        $loadFactor = $this->calculateLoadFactor($venueId);
        $timeMultiplier = $this->calculateTimeMultiplier();
        $isFlashDiscount = $this->isFlashDiscountAvailable($loadFactor);

        $basePrice = $this->getBasePrice($venue, $serviceType);
        
        $dynamicMultiplier = $loadFactor * $timeMultiplier;
        
        if ($isFlashDiscount) {
            $dynamicMultiplier *= (1 - self::FLASH_DISCOUNT_PERCENTAGE / 100);
        }

        if ($isB2B) {
            $dynamicMultiplier *= self::B2B_DISCOUNT;
        }

        if ($loadFactor < 0.3) {
            $dynamicMultiplier *= self::LOW_LOAD_DISCOUNT;
        }

        $finalPrice = $basePrice * $dynamicMultiplier;

        $result = [
            'base_price' => round($basePrice, 2),
            'final_price' => round($finalPrice, 2),
            'load_factor' => round($loadFactor, 2),
            'time_multiplier' => round($timeMultiplier, 2),
            'is_flash_discount' => $isFlashDiscount,
            'flash_discount_percentage' => $isFlashDiscount ? self::FLASH_DISCOUNT_PERCENTAGE : 0,
            'is_b2b' => $isB2B,
            'b2b_discount' => $isB2B ? (1 - self::B2B_DISCOUNT) * 100 : 0,
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $this->cache->put($cacheKey, json_encode($result), self::CACHE_TTL);

        $this->logger->info('Dynamic price calculated', [
            'venue_id' => $venueId,
            'service_type' => $serviceType,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'load_factor' => $loadFactor,
            'is_flash_discount' => $isFlashDiscount,
            'is_b2b' => $isB2B,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    public function createFlashMembership(int $venueId, int $userId, array $membershipData, string $correlationId): Membership
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'flash_membership_creation',
            amount: intval($membershipData['amount'] ?? 0),
            correlationId: $correlationId,
        );

        $loadFactor = $this->calculateLoadFactor($venueId);
        
        if (!$this->isFlashDiscountAvailable($loadFactor)) {
            throw new \RuntimeException('Flash membership discount is not available at this time.');
        }

        $basePrice = floatval($membershipData['base_price'] ?? 0);
        $discountedPrice = $basePrice * (1 - self::FLASH_DISCOUNT_PERCENTAGE / 100);

        return $this->db->transaction(function () use ($venueId, $userId, $membershipData, $discountedPrice, $correlationId) {
            $membership = Membership::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $membershipData['tenant_id'] ?? $this->getTenantId(),
                'business_group_id' => $membershipData['business_group_id'] ?? null,
                'user_id' => $userId,
                'gym_id' => $venueId,
                'membership_type' => $membershipData['membership_type'],
                'duration_days' => $membershipData['duration_days'] ?? 30,
                'base_price' => $basePrice,
                'discounted_price' => $discountedPrice,
                'discount_percentage' => self::FLASH_DISCOUNT_PERCENTAGE,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addDays($membershipData['duration_days'] ?? 30),
                'is_flash' => true,
                'correlation_id' => $correlationId,
                'tags' => json_encode(['flash_membership', 'dynamic_pricing']),
            ]);

            $this->audit->log(
                action: 'flash_membership_created',
                entityType: 'sports_membership',
                entityId: $membership->id,
                metadata: [
                    'venue_id' => $venueId,
                    'base_price' => $basePrice,
                    'discounted_price' => $discountedPrice,
                    'discount_percentage' => self::FLASH_DISCOUNT_PERCENTAGE,
                    'correlation_id' => $correlationId,
                ]
            );

            $this->logger->info('Flash membership created', [
                'membership_id' => $membership->id,
                'user_id' => $userId,
                'venue_id' => $venueId,
                'base_price' => $basePrice,
                'discounted_price' => $discountedPrice,
                'correlation_id' => $correlationId,
            ]);

            return $membership;
        });
    }

    public function getBulkMembershipPricing(int $venueId, int $employeeCount, int $userId = 0, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'bulk_pricing_calculation',
            amount: 0,
            correlationId: $correlationId,
        );

        $venue = Gym::findOrFail($venueId);
        $basePrice = $this->getBasePrice($venue, 'monthly_membership');
        
        $bulkDiscount = $this->calculateBulkDiscount($employeeCount);
        
        $individualPrice = $basePrice * self::B2B_DISCOUNT;
        $bulkPricePerEmployee = $individualPrice * (1 - $bulkDiscount);
        $totalPrice = $bulkPricePerEmployee * $employeeCount;

        $result = [
            'employee_count' => $employeeCount,
            'individual_price' => round($individualPrice, 2),
            'bulk_discount_percentage' => round($bulkDiscount * 100, 2),
            'bulk_price_per_employee' => round($bulkPricePerEmployee, 2),
            'total_price' => round($totalPrice, 2),
            'savings' => round(($individualPrice * $employeeCount) - $totalPrice, 2),
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $this->logger->info('Bulk membership pricing calculated', [
            'venue_id' => $venueId,
            'employee_count' => $employeeCount,
            'total_price' => $totalPrice,
            'savings' => $result['savings'],
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    public function updatePricingBasedOnLoad(int $venueId, string $correlationId): void
    {
        $loadFactor = $this->calculateLoadFactor($venueId);
        
        $pricingKey = "sports:pricing:{$venueId}";
        $this->redis->setex($pricingKey, 3600, json_encode([
            'load_factor' => $loadFactor,
            'updated_at' => now()->toIso8601String(),
        ]));

        $this->audit->log(
            action: 'pricing_updated_based_on_load',
            entityType: 'sports_venue',
            entityId: $venueId,
            metadata: [
                'load_factor' => $loadFactor,
                'correlation_id' => $correlationId,
            ]
        );

        $this->logger->info('Pricing updated based on load', [
            'venue_id' => $venueId,
            'load_factor' => $loadFactor,
            'correlation_id' => $correlationId,
        ]);
    }

    private function calculateLoadFactor(int $venueId): float
    {
        $todayBookings = $this->db->table('sports_bookings')
            ->where('venue_id', $venueId)
            ->whereDate('slot_start', today())
            ->where('status', '!=', 'cancelled')
            ->count();

        $maxDailyCapacity = $this->getVenueCapacity($venueId);
        
        return min(floatval($todayBookings / $maxDailyCapacity), 1.0);
    }

    private function calculateTimeMultiplier(): float
    {
        $hour = now()->hour;

        if ($hour >= 6 && $hour < 9) {
            return 1.2;
        }

        if ($hour >= 17 && $hour < 21) {
            return self::PEAK_HOUR_MULTIPLIER;
        }

        if ($hour >= 21 || $hour < 6) {
            return 0.8;
        }

        return 1.0;
    }

    private function isFlashDiscountAvailable(float $loadFactor): bool
    {
        $hour = now()->hour;
        
        return $loadFactor < 0.3 
            && $hour >= self::FLASH_DISCOUNT_START_HOUR 
            && $hour <= self::FLASH_DISCOUNT_END_HOUR;
    }

    private function getBasePrice(Gym $venue, string $serviceType): float
    {
        $prices = [
            'single_visit' => floatval($venue->single_visit_price ?? 500),
            'monthly_membership' => floatval($venue->monthly_membership_price ?? 3000),
            'personal_training' => floatval($venue->personal_training_price ?? 1500),
            'group_class' => floatval($venue->group_class_price ?? 500),
        ];

        return $prices[$serviceType] ?? 500;
    }

    private function calculateBulkDiscount(int $employeeCount): float
    {
        return match (true) {
            $employeeCount >= 100 => 0.25,
            $employeeCount >= 50 => 0.20,
            $employeeCount >= 20 => 0.15,
            $employeeCount >= 10 => 0.10,
            $employeeCount >= 5 => 0.05,
            default => 0.0,
        };
    }

    private function getVenueCapacity(int $venueId): int
    {
        $venue = Gym::find($venueId);
        return intval($venue->max_daily_capacity ?? 200);
    }

    private function getTenantId(): int
    {
        return tenant()->id ?? 0;
    }
}
