<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\B2BDeal;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class RealEstateFleetRentalService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const FLEET_DISCOUNT_RATE = 0.15;
    private const BULK_DISCOUNT_THRESHOLD = 5;
    private const BULK_DISCOUNT_RATE = 0.10;
    private const MAX_LEASE_TERM_MONTHS = 60;
    private const MIN_LEASE_TERM_MONTHS = 3;
    private const MIN_UNIT_COUNT = 2;
    private const APPROVAL_TIMEOUT_HOURS = 48;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit
    ) {}

    public function createFleetRentalDeal(
        int $propertyId,
        int $businessGroupId,
        int $unitCount,
        int $leaseTermMonths,
        float $basePricePerUnit,
        int $tenantId,
        int $userId,
        string $correlationId,
        ?string $idempotencyKey = null
    ): B2BDeal {
        $this->fraudControl->check(
            $userId,
            'create_fleet_rental_deal',
            (int) ($basePricePerUnit * $unitCount),
            null,
            null,
            $correlationId
        );

        if ($idempotencyKey !== null) {
            $cached = Cache::get("fleet:{$idempotencyKey}");
            if ($cached !== null) {
                return B2BDeal::findOrFail(json_decode($cached, true)['deal_id']);
            }
        }

        $property = Property::findOrFail($propertyId);

        $this->validateFleetRentalParameters($unitCount, $leaseTermMonths, $basePricePerUnit);

        $result = DB::transaction(function () use ($property, $propertyId, $businessGroupId, $unitCount, $leaseTermMonths, $basePricePerUnit, $tenantId, $correlationId) {
            $discountRate = $this->calculateDiscountRate($unitCount);
            $discountedPricePerUnit = $basePricePerUnit * (1 - $discountRate);
            $totalMonthlyPrice = $discountedPricePerUnit * $unitCount;
            $totalContractValue = $totalMonthlyPrice * $leaseTermMonths;

            $deal = B2BDeal::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'uuid' => (string) Str::uuid(),
                'correlation_id' => $correlationId,
                'property_id' => $propertyId,
                'deal_type' => 'fleet_rental',
                'unit_count' => $unitCount,
                'lease_term_months' => $leaseTermMonths,
                'base_price_per_unit' => $basePricePerUnit,
                'discount_rate' => $discountRate,
                'discounted_price_per_unit' => $discountedPricePerUnit,
                'total_monthly_price' => $totalMonthlyPrice,
                'total_contract_value' => $totalContractValue,
                'status' => 'pending_approval',
                'approval_deadline' => now()->addHours(self::APPROVAL_TIMEOUT_HOURS)->toIso8601String(),
                'tags' => json_encode(['fleet_rental', 'b2b', 'bulk_discount']),
            ]);

            $property->update(['status' => 'reserved']);

            $this->audit->record(
                'fleet_rental_deal_created',
                'App\Domains\RealEstate\Models\B2BDeal',
                $deal->id,
                [],
                [
                    'property_id' => $propertyId,
                    'business_group_id' => $businessGroupId,
                    'total_contract_value' => $totalContractValue,
                    'discount_rate' => $discountRate,
                ],
                $correlationId
            );

            return $deal;
        });

        if ($idempotencyKey !== null) {
            Cache::put("fleet:{$idempotencyKey}", json_encode(['deal_id' => $result->id]), self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    public function approveFleetDeal(
        int $dealId,
        int $approvedBy,
        string $correlationId
    ): B2BDeal {
        $this->fraudControl->check(
            $approvedBy,
            'approve_fleet_deal',
            0,
            null,
            null,
            $correlationId
        );

        $deal = B2BDeal::findOrFail($dealId);

        if ($deal->status !== 'pending_approval') {
            throw new \DomainException('Deal is not in pending approval status');
        }

        if (now()->isAfter(Carbon::parse($deal->approval_deadline))) {
            throw new \DomainException('Approval deadline has passed');
        }

        return DB::transaction(function () use ($deal, $approvedBy, $correlationId) {
            $deal->update([
                'status' => 'approved',
                'approved_at' => now()->toIso8601String(),
                'approved_by' => $approvedBy,
                'contract_start_date' => now()->addDays(7)->toIso8601String(),
                'contract_end_date' => now()->addDays(7)->addMonths($deal->lease_term_months)->toIso8601String(),
            ]);

            $property = Property::findOrFail($deal->property_id);
            $property->update(['status' => 'rented']);

            $this->audit->record(
                'fleet_rental_deal_approved',
                'App\Domains\RealEstate\Models\B2BDeal',
                $deal->id,
                ['status' => 'pending_approval'],
                [
                    'approved_by' => $approvedBy,
                    'status' => 'approved',
                ],
                $correlationId
            );

            return $deal->refresh();
        });
    }

    public function rejectFleetDeal(
        int $dealId,
        int $rejectedBy,
        string $rejectionReason,
        string $correlationId
    ): B2BDeal {
        $this->fraudControl->check(
            $rejectedBy,
            'reject_fleet_deal',
            0,
            null,
            null,
            $correlationId
        );

        $deal = B2BDeal::findOrFail($dealId);

        if ($deal->status !== 'pending_approval') {
            throw new \DomainException('Deal is not in pending approval status');
        }

        return DB::transaction(function () use ($deal, $rejectedBy, $rejectionReason, $correlationId) {
            $deal->update([
                'status' => 'rejected',
                'rejected_at' => now()->toIso8601String(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $rejectionReason,
            ]);

            $property = Property::findOrFail($deal->property_id);
            $property->update(['status' => 'available']);

            $this->audit->record(
                'fleet_rental_deal_rejected',
                'App\Domains\RealEstate\Models\B2BDeal',
                $deal->id,
                ['status' => 'pending_approval'],
                [
                    'rejected_by' => $rejectedBy,
                    'rejection_reason' => $rejectionReason,
                    'status' => 'rejected',
                ],
                $correlationId
            );

            return $deal->refresh();
        });
    }

    public function calculateFleetPricing(
        int $unitCount,
        int $leaseTermMonths,
        float $basePricePerUnit,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'calculate_fleet_pricing',
            (int) ($basePricePerUnit * $unitCount),
            null,
            null,
            $correlationId
        );

        $discountRate = $this->calculateDiscountRate($unitCount);
        $discountedPricePerUnit = $basePricePerUnit * (1 - $discountRate);
        $totalMonthlyPrice = $discountedPricePerUnit * $unitCount;
        $totalContractValue = $totalMonthlyPrice * $leaseTermMonths;

        return [
            'unit_count' => $unitCount,
            'lease_term_months' => $leaseTermMonths,
            'base_price_per_unit' => $basePricePerUnit,
            'discount_rate' => $discountRate,
            'discount_percentage' => round($discountRate * 100, 2),
            'discounted_price_per_unit' => $discountedPricePerUnit,
            'total_monthly_price' => $totalMonthlyPrice,
            'total_contract_value' => $totalContractValue,
            'total_savings' => ($basePricePerUnit * $unitCount * $leaseTermMonths) - $totalContractValue,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    public function getActiveFleetDeals(
        int $businessGroupId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_active_fleet_deals',
            0,
            null,
            null,
            $correlationId
        );

        $deals = B2BDeal::where('business_group_id', $businessGroupId)
            ->where('deal_type', 'fleet_rental')
            ->whereIn('status', ['approved', 'active'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'business_group_id' => $businessGroupId,
            'deals' => $deals->map(fn($deal) => [
                'deal_id' => $deal->id,
                'uuid' => $deal->uuid,
                'property_id' => $deal->property_id,
                'unit_count' => $deal->unit_count,
                'lease_term_months' => $deal->lease_term_months,
                'total_contract_value' => $deal->total_contract_value,
                'status' => $deal->status,
                'contract_start_date' => $deal->contract_start_date,
                'contract_end_date' => $deal->contract_end_date,
            ]),
            'total_deals' => $deals->count(),
            'total_contract_value' => $deals->sum('total_contract_value'),
        ];
    }

    public function extendFleetDeal(
        int $dealId,
        int $additionalMonths,
        int $requestedBy,
        string $correlationId
    ): B2BDeal {
        $this->fraudControl->check(
            $requestedBy,
            'extend_fleet_deal',
            0,
            null,
            null,
            $correlationId
        );

        $deal = B2BDeal::findOrFail($dealId);

        if ($deal->status !== 'approved' && $deal->status !== 'active') {
            throw new \DomainException('Can only extend approved or active deals');
        }

        if ($additionalMonths < 1 || $additionalMonths > 24) {
            throw new \InvalidArgumentException('Extension must be between 1 and 24 months');
        }

        return DB::transaction(function () use ($deal, $additionalMonths, $requestedBy, $correlationId) {
            $newLeaseTerm = $deal->lease_term_months + $additionalMonths;

            if ($newLeaseTerm > self::MAX_LEASE_TERM_MONTHS) {
                throw new \DomainException('Maximum lease term exceeded');
            }

            $additionalValue = $deal->total_monthly_price * $additionalMonths;

            $deal->update([
                'lease_term_months' => $newLeaseTerm,
                'total_contract_value' => $deal->total_contract_value + $additionalValue,
                'contract_end_date' => Carbon::parse($deal->contract_end_date)->addMonths($additionalMonths)->toIso8601String(),
            ]);

            $this->audit->record(
                'fleet_rental_deal_extended',
                'App\Domains\RealEstate\Models\B2BDeal',
                $deal->id,
                [
                    'lease_term_months' => $deal->lease_term_months - $additionalMonths,
                    'total_contract_value' => $deal->total_contract_value - $additionalValue,
                ],
                [
                    'additional_months' => $additionalMonths,
                    'additional_value' => $additionalValue,
                    'requested_by' => $requestedBy,
                ],
                $correlationId
            );

            return $deal->refresh();
        });
    }

    private function calculateDiscountRate(int $unitCount): float
    {
        if ($unitCount >= self::BULK_DISCOUNT_THRESHOLD) {
            return max(self::FLEET_DISCOUNT_RATE, self::BULK_DISCOUNT_RATE);
        }

        return self::FLEET_DISCOUNT_RATE;
    }

    private function validateFleetRentalParameters(
        int $unitCount,
        int $leaseTermMonths,
        float $basePricePerUnit
    ): void {
        if ($unitCount < self::MIN_UNIT_COUNT) {
            throw new \InvalidArgumentException('Minimum unit count is ' . self::MIN_UNIT_COUNT);
        }

        if ($leaseTermMonths < self::MIN_LEASE_TERM_MONTHS) {
            throw new \InvalidArgumentException('Minimum lease term is ' . self::MIN_LEASE_TERM_MONTHS . ' months');
        }

        if ($leaseTermMonths > self::MAX_LEASE_TERM_MONTHS) {
            throw new \InvalidArgumentException('Maximum lease term is ' . self::MAX_LEASE_TERM_MONTHS . ' months');
        }

        if ($basePricePerUnit <= 0) {
            throw new \InvalidArgumentException('Base price must be positive');
        }
    }
}
