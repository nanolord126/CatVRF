<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\DTOs\CalculatePriceDto;
use App\Domains\Education\DTOs\PriceAdjustmentDto;
use App\Domains\Education\Models\Course;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\AnonymizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class EducationDynamicPricingService
{
    private const CACHE_TTL = 1800;
    private const FLASH_SALE_THRESHOLD = 0.7;
    private const MAX_DISCOUNT_PERCENT = 40;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private AnonymizationService $anonymizer,
    ) {}

    public function calculateDynamicPrice(CalculatePriceDto $dto): PriceAdjustmentDto
    {
        $this->fraud->check($dto);

        if ($dto->idempotencyKey !== null) {
            $cached = $this->idempotency->check('education_pricing_calculation', $dto->idempotencyKey, $dto->toArray(), $dto->tenantId);
            if (!empty($cached)) {
                return PriceAdjustmentDto::fromArray($cached);
            }
        }

        $cacheKey = $this->getCacheKey($dto);
        $cachedResult = Redis::get($cacheKey);

        if ($cachedResult !== null) {
            Log::channel('audit')->info('Dynamic price served from cache', [
                'correlation_id' => $dto->correlationId,
                'cache_key' => $cacheKey,
                'course_id' => $dto->courseId,
            ]);

            return PriceAdjustmentDto::fromArray(json_decode($cachedResult, true));
        }

        return DB::transaction(function () use ($dto, $cacheKey) {
            $course = Course::findOrFail($dto->courseId);

            $basePrice = $dto->isCorporate 
                ? ($course->corporate_price_kopecks ?? $course->price_kopecks)
                : $course->price_kopecks;

            $demandFactor = $this->calculateDemandFactor($course, $dto);
            $seasonalityFactor = $this->calculateSeasonalityFactor($dto->timeSlot);
            $competitionFactor = $this->calculateCompetitionFactor($course);
            $userSegmentFactor = $this->calculateUserSegmentFactor($dto->userSegment, $dto->userId);
            $enrollmentVelocityFactor = $this->calculateEnrollmentVelocityFactor($dto->enrollmentCount, $course);

            $factors = [
                'demand' => $demandFactor,
                'seasonality' => $seasonalityFactor,
                'competition' => $competitionFactor,
                'user_segment' => $userSegmentFactor,
                'enrollment_velocity' => $enrollmentVelocityFactor,
            ];

            $adjustmentPercent = $this->calculateAdjustmentPercent($factors);
            $adjustedPrice = $this->applyAdjustment($basePrice, $adjustmentPercent);

            $isFlashSale = $adjustmentPercent > 20 && $demandFactor > self::FLASH_SALE_THRESHOLD;

            $priceAdjustment = new PriceAdjustmentDto(
                priceId: (string) Str::uuid(),
                originalPriceKopecks: $basePrice,
                adjustedPriceKopecks: $adjustedPrice,
                discountPercent: abs($adjustmentPercent),
                adjustmentReason: $this->getAdjustmentReason($factors, $adjustmentPercent),
                factors: $factors,
                validUntil: now()->addHours(24)->toIso8601String(),
                isFlashSale: $isFlashSale,
                generatedAt: now()->toIso8601String(),
            );

            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($priceAdjustment->toArray()));

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->record('education_pricing_calculation', $dto->idempotencyKey, $dto->toArray(), $priceAdjustment->toArray(), $dto->tenantId, 1440);
            }

            $this->audit->record('education_dynamic_pricing_calculated', 'PriceAdjustmentDto', null, [], [
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
                'course_id' => $dto->courseId,
                'price_id' => $priceAdjustment->priceId,
                'original_price' => $priceAdjustment->originalPriceKopecks,
                'adjusted_price' => $priceAdjustment->adjustedPriceKopecks,
                'discount_percent' => $priceAdjustment->discountPercent,
                'is_flash_sale' => $priceAdjustment->isFlashSale,
                'is_corporate' => $dto->isCorporate,
            ], $dto->correlationId);

            $this->savePriceHistory($dto, $priceAdjustment, $factors);

            return $priceAdjustment;
        });
    }

    private function getCacheKey(CalculatePriceDto $dto): string
    {
        return sprintf(
            'education:pricing:tenant:%d:course:%d:corporate:%d:user:%d:segment:%s',
            $dto->tenantId,
            $dto->courseId,
            $dto->isCorporate ? 1 : 0,
            $dto->userId ?? 0,
            $dto->userSegment ?? 'default',
        );
    }

    private function calculateDemandFactor(Course $course, CalculatePriceDto $dto): float
    {
        $enrollmentCount = $course->enrollments()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $baselineEnrollments = 10;
        $demandRatio = $enrollmentCount / max($baselineEnrollments, 1);

        return min(max($demandRatio, 0.3), 2.0);
    }

    private function calculateSeasonalityFactor(?string $timeSlot): float
    {
        if ($timeSlot === null) {
            return 1.0;
        }

        $hour = (int) explode(':', $timeSlot)[0];

        return match (true) {
            $hour >= 9 && $hour <= 12 => 1.2,
            $hour >= 18 && $hour <= 21 => 1.3,
            $hour >= 13 && $hour <= 17 => 1.0,
            default => 0.8,
        };
    }

    private function calculateCompetitionFactor(Course $course): float
    {
        $similarCoursesCount = DB::table('courses')
            ->where('level', $course->level)
            ->where('is_active', true)
            ->where('id', '!=', $course->id)
            ->count();

        return match (true) {
            $similarCoursesCount === 0 => 1.0,
            $similarCoursesCount <= 3 => 0.95,
            $similarCoursesCount <= 7 => 0.9,
            default => 0.85,
        };
    }

    private function calculateUserSegmentFactor(?string $userSegment, ?int $userId): float
    {
        if ($userSegment !== null) {
            return match ($userSegment) {
                'vip' => 0.85,
                'premium' => 0.9,
                'standard' => 1.0,
                'new' => 0.8,
                default => 1.0,
            };
        }

        if ($userId !== null) {
            $totalEnrollments = DB::table('enrollments')
                ->where('user_id', $userId)
                ->count();

            return match (true) {
                $totalEnrollments === 0 => 0.8,
                $totalEnrollments <= 2 => 0.9,
                $totalEnrollments <= 5 => 0.95,
                $totalEnrollments > 10 => 0.85,
                default => 1.0,
            };
        }

        return 1.0;
    }

    private function calculateEnrollmentVelocityFactor(?int $enrollmentCount, Course $course): float
    {
        if ($enrollmentCount === null) {
            $enrollmentCount = $course->enrollments()
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
        }

        $velocityThreshold = 5;

        return match (true) {
            $enrollmentCount >= $velocityThreshold * 2 => 1.2,
            $enrollmentCount >= $velocityThreshold => 1.1,
            $enrollmentCount >= $velocityThreshold / 2 => 1.0,
            $enrollmentCount > 0 => 0.9,
            default => 0.85,
        };
    }

    private function calculateAdjustmentPercent(array $factors): float
    {
        $avgFactor = array_sum($factors) / count($factors);

        return match (true) {
            $avgFactor >= 1.3 => 15,
            $avgFactor >= 1.15 => 10,
            $avgFactor >= 1.05 => 5,
            $avgFactor <= 0.7 => -30,
            $avgFactor <= 0.8 => -20,
            $avgFactor <= 0.9 => -10,
            default => 0,
        };
    }

    private function applyAdjustment(int $basePrice, float $adjustmentPercent): int
    {
        $adjustmentPercent = max(min($adjustmentPercent, self::MAX_DISCOUNT_PERCENT), -self::MAX_DISCOUNT_PERCENT);
        $adjustedPrice = $basePrice * (1 + $adjustmentPercent / 100);
        
        return max((int) round($adjustedPrice), (int) ($basePrice * 0.6));
    }

    private function getAdjustmentReason(array $factors, float $adjustmentPercent): string
    {
        $reasons = [];

        if ($factors['demand'] > 1.2) {
            $reasons[] = 'high_demand';
        } elseif ($factors['demand'] < 0.8) {
            $reasons[] = 'low_demand';
        }

        if ($factors['seasonality'] > 1.1) {
            $reasons[] = 'peak_hours';
        }

        if ($factors['competition'] < 0.9) {
            $reasons[] = 'competitive_pricing';
        }

        if ($factors['user_segment'] < 0.9) {
            $reasons[] = 'user_segment_discount';
        }

        if ($factors['enrollment_velocity'] > 1.1) {
            $reasons[] = 'high_velocity';
        } elseif ($factors['enrollment_velocity'] < 0.9) {
            $reasons[] = 'low_velocity_discount';
        }

        return empty($reasons) ? 'standard_pricing' : implode(', ', $reasons);
    }

    private function savePriceHistory(CalculatePriceDto $dto, PriceAdjustmentDto $priceAdjustment, array $factors): void
    {
        DB::table('education_price_history')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $dto->tenantId,
            'business_group_id' => $dto->businessGroupId,
            'course_id' => $dto->courseId,
            'user_id' => $dto->userId,
            'original_price_kopecks' => $priceAdjustment->originalPriceKopecks,
            'adjusted_price_kopecks' => $priceAdjustment->adjustedPriceKopecks,
            'discount_percent' => $priceAdjustment->discountPercent,
            'adjustment_reason' => $priceAdjustment->adjustmentReason,
            'factors' => json_encode($factors),
            'is_flash_sale' => $priceAdjustment->isFlashSale,
            'is_corporate' => $dto->isCorporate ?? false,
            'valid_until' => $priceAdjustment->validUntil,
            'correlation_id' => $dto->correlationId,
            'created_at' => now(),
        ]);
    }

    public function triggerFlashSale(int $courseId, int $discountPercent, string $correlationId): PriceAdjustmentDto
    {
        $dto = new CalculatePriceDto(
            tenantId: (int) tenant()->id,
            businessGroupId: null,
            courseId: $courseId,
            correlationId: $correlationId,
            isCorporate: false,
            userSegment: 'flash_sale',
        );

        $course = Course::findOrFail($courseId);
        $basePrice = $course->price_kopecks;

        $discountPercent = min($discountPercent, self::MAX_DISCOUNT_PERCENT);
        $adjustedPrice = $this->applyAdjustment($basePrice, -$discountPercent);

        $priceAdjustment = new PriceAdjustmentDto(
            priceId: (string) Str::uuid(),
            originalPriceKopecks: $basePrice,
            adjustedPriceKopecks: $adjustedPrice,
            discountPercent: $discountPercent,
            adjustmentReason: 'manual_flash_sale',
            factors: ['flash_sale' => true],
            validUntil: now()->addHours(6)->toIso8601String(),
            isFlashSale: true,
            generatedAt: now()->toIso8601String(),
        );

        $this->audit->record('education_flash_sale_triggered', 'PriceAdjustmentDto', null, [], [
            'correlation_id' => $correlationId,
            'tenant_id' => $dto->tenantId,
            'course_id' => $courseId,
            'discount_percent' => $discountPercent,
        ], $correlationId);

        return $priceAdjustment;
    }
}
