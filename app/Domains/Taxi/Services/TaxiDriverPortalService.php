<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\Driver;
use App\Domains\Taxi\Models\TaxiDriverSchedule;
use App\Domains\Taxi\Models\TaxiDriverDocument;
use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriverWallet;
use App\Domains\Taxi\Models\TaxiDriverAnalytics;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * TaxiDriverPortalService - Production-ready driver portal for taxi operations
 * 
 * Features:
 * - Driver dashboard with key metrics
 * - Earnings tracking and breakdown
 * - Schedule management
 * - Document management and verification
 * - Ride history
 * - Performance analytics
 * - Wallet balance and transactions
 * - Notification center
 */
final readonly class TaxiDriverPortalService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly TaxiFinanceService $financeService,
        private readonly TaxiAnalyticsService $analyticsService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get driver dashboard
     */
    public function getDriverDashboard(int $driverId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $driver = Driver::findOrFail($driverId);
        $today = now()->startOfDay();
        
        // Today's rides
        $todayRides = TaxiRide::where('driver_id', $driverId)
            ->whereDate('created_at', $today)
            ->count();
        
        $todayCompletedRides = TaxiRide::where('driver_id', $driverId)
            ->where('status', TaxiRide::STATUS_COMPLETED)
            ->whereDate('created_at', $today)
            ->count();
        
        // Today's earnings
        $todayAnalytics = TaxiDriverAnalytics::where('driver_id', $driverId)
            ->where('date', $today)
            ->first();
        
        $todayEarnings = $todayAnalytics ? $todayAnalytics->getNetIncomeInRubles() : 0;
        
        // Wallet balance
        $wallet = TaxiDriverWallet::where('driver_id', $driverId)->first();
        $walletBalance = $wallet ? $wallet->getAvailableBalanceInRubles() : 0;
        
        // Current schedule
        $currentSchedule = TaxiDriverSchedule::where('driver_id', $driverId)
            ->where('date', $today)
            ->where('status', TaxiDriverSchedule::STATUS_ACTIVE)
            ->first();
        
        // Active ride
        $activeRide = TaxiRide::where('driver_id', $driverId)
            ->where('status', TaxiRide::STATUS_STARTED)
            ->first();
        
        return [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->first_name . ' ' . $driver->last_name,
                'rating' => $driver->rating,
                'is_available' => $driver->is_available,
                'is_active' => $driver->is_active,
            ],
            'today' => [
                'rides' => $todayRides,
                'completed_rides' => $todayCompletedRides,
                'earnings_rubles' => $todayEarnings,
                'completion_rate' => $todayRides > 0 ? ($todayCompletedRides / $todayRides) * 100 : 0,
            ],
            'wallet' => [
                'balance_rubles' => $walletBalance,
                'pending_withdrawals_rubles' => $wallet ? ($wallet->frozen_kopeki / 100) : 0,
            ],
            'schedule' => $currentSchedule ? [
                'id' => $currentSchedule->id,
                'start_time' => $currentSchedule->start_time->toIso8601String(),
                'end_time' => $currentSchedule->end_time->toIso8601String(),
                'target_rides' => $currentSchedule->target_rides,
                'target_earnings_rubles' => $currentSchedule->getTargetEarningsInRubles(),
            ] : null,
            'active_ride' => $activeRide ? [
                'id' => $activeRide->id,
                'uuid' => $activeRide->uuid,
                'pickup_address' => $activeRide->pickup_address,
                'dropoff_address' => $activeRide->dropoff_address,
                'status' => $activeRide->status,
            ] : null,
        ];
    }

    /**
     * Get driver earnings breakdown
     */
    public function getDriverEarnings(int $driverId, Carbon $startDate, Carbon $endDate, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $analytics = TaxiDriverAnalytics::where('driver_id', $driverId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
        
        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_rides' => $analytics->sum('total_rides'),
                'completed_rides' => $analytics->sum('completed_rides'),
                'total_revenue_rubles' => $analytics->sum(function ($item) {
                    return $item->getTotalRevenueInRubles();
                }),
                'total_net_income_rubles' => $analytics->sum(function ($item) {
                    return $item->getNetIncomeInRubles();
                }),
                'total_tips_rubles' => $analytics->sum(function ($item) {
                    return $item->getTotalTipsInRubles();
                }),
                'total_bonus_rubles' => $analytics->sum(function ($item) {
                    return $item->getBonusInRubles();
                }),
                'total_penalty_rubles' => $analytics->sum(function ($item) {
                    return $item->getPenaltyInRubles();
                }),
                'total_online_hours' => $analytics->sum('online_minutes') / 60,
                'average_hourly_earnings_rubles' => $analytics->avg(function ($item) {
                    return $item->getAverageHourlyEarningsRubles();
                }),
            ],
            'daily_breakdown' => $analytics->map(function ($item) {
                return [
                    'date' => $item->date->toDateString(),
                    'rides' => $item->total_rides,
                    'revenue_rubles' => $item->getTotalRevenueInRubles(),
                    'net_income_rubles' => $item->getNetIncomeInRubles(),
                    'tips_rubles' => $item->getTotalTipsInRubles(),
                    'online_minutes' => $item->online_minutes,
                ];
            })->values(),
        ];
    }

    /**
     * Create driver schedule
     */
    public function createSchedule(int $driverId, array $data, string $correlationId = null): TaxiDriverSchedule
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($driverId, $data, $correlationId) {
            $schedule = TaxiDriverSchedule::create([
                'tenant_id' => tenant()->id ?? 1,
                'driver_id' => $driverId,
                'date' => Carbon::parse($data['date']),
                'start_time' => Carbon::parse($data['start_time']),
                'end_time' => Carbon::parse($data['end_time']),
                'break_start_time' => isset($data['break_start_time']) ? Carbon::parse($data['break_start_time']) : null,
                'break_end_time' => isset($data['break_end_time']) ? Carbon::parse($data['break_end_time']) : null,
                'target_rides' => $data['target_rides'] ?? 10,
                'target_earnings_kopeki' => ($data['target_earnings_rubles'] ?? 5000) * 100,
                'notes' => $data['notes'] ?? null,
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'schedule'], $data['tags'] ?? []),
            ]);

            $this->audit->log(
                action: 'taxi_driver_schedule_created',
                subjectType: TaxiDriverSchedule::class,
                subjectId: $schedule->id,
                oldValues: [],
                newValues: $schedule->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi driver schedule created', [
                'correlation_id' => $correlationId,
                'schedule_uuid' => $schedule->uuid,
                'driver_id' => $driverId,
                'date' => $schedule->date->toDateString(),
            ]);

            return $schedule;
        });
    }

    /**
     * Upload driver document
     */
    public function uploadDocument(int $driverId, array $data, string $correlationId = null): TaxiDriverDocument
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->db->transaction(function () use ($driverId, $data, $correlationId) {
            $document = TaxiDriverDocument::create([
                'tenant_id' => tenant()->id ?? 1,
                'driver_id' => $driverId,
                'type' => $data['type'],
                'document_number' => $data['document_number'] ?? null,
                'issue_date' => isset($data['issue_date']) ? Carbon::parse($data['issue_date']) : null,
                'expiry_date' => isset($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null,
                'issuing_authority' => $data['issuing_authority'] ?? null,
                'file_path' => $data['file_path'],
                'file_name' => $data['file_name'],
                'file_size' => $data['file_size'],
                'file_mime_type' => $data['file_mime_type'],
                'status' => TaxiDriverDocument::STATUS_PENDING,
                'correlation_id' => $correlationId,
                'metadata' => $data['metadata'] ?? [],
                'tags' => array_merge(['taxi', 'document', $data['type']], $data['tags'] ?? []),
            ]);

            $this->audit->log(
                action: 'taxi_driver_document_uploaded',
                subjectType: TaxiDriverDocument::class,
                subjectId: $document->id,
                oldValues: [],
                newValues: $document->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Taxi driver document uploaded', [
                'correlation_id' => $correlationId,
                'document_uuid' => $document->uuid,
                'driver_id' => $driverId,
                'type' => $document->type,
            ]);

            return $document;
        });
    }

    /**
     * Get driver documents
     */
    public function getDriverDocuments(int $driverId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $documents = TaxiDriverDocument::where('driver_id', $driverId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return [
            'driver_id' => $driverId,
            'total_documents' => $documents->count(),
            'verified_count' => $documents->where('status', TaxiDriverDocument::STATUS_VERIFIED)->count(),
            'pending_count' => $documents->where('status', TaxiDriverDocument::STATUS_PENDING)->count(),
            'expired_count' => $documents->where('status', TaxiDriverDocument::STATUS_EXPIRED)->count(),
            'expiring_soon_count' => $documents->filter(function ($doc) {
                return $doc->isExpiringSoon();
            })->count(),
            'documents' => $documents->map(function ($doc) {
                return [
                    'uuid' => $doc->uuid,
                    'type' => $doc->type,
                    'document_number' => $doc->document_number,
                    'issue_date' => $doc->issue_date?->toDateString(),
                    'expiry_date' => $doc->expiry_date?->toDateString(),
                    'status' => $doc->status,
                    'is_valid' => $doc->isValid(),
                    'is_expired' => $doc->isExpired(),
                    'is_expiring_soon' => $doc->isExpiringSoon(),
                    'days_until_expiry' => $doc->getDaysUntilExpiry(),
                    'file_name' => $doc->file_name,
                    'verified_at' => $doc->verified_at?->toIso8601String(),
                ];
            })->values(),
        ];
    }

    /**
     * Get driver ride history
     */
    public function getDriverRideHistory(int $driverId, ?Carbon $startDate = null, ?Carbon $endDate = null, int $perPage = 20, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $query = TaxiRide::where('driver_id', $driverId);
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        $rides = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return [
            'driver_id' => $driverId,
            'total_rides' => $rides->total(),
            'rides' => $rides->map(function ($ride) {
                return [
                    'uuid' => $ride->uuid,
                    'status' => $ride->status,
                    'pickup_address' => $ride->pickup_address,
                    'dropoff_address' => $ride->dropoff_address,
                    'distance_km' => $ride->distance_km,
                    'price_rubles' => $ride->getFinalPriceInRubles(),
                    'created_at' => $ride->created_at->toIso8601String(),
                    'completed_at' => $ride->metadata['completed_at'] ?? null,
                    'passenger_rating' => $ride->metadata['passenger_rating'] ?? null,
                ];
            })->values(),
            'pagination' => [
                'current_page' => $rides->currentPage(),
                'per_page' => $rides->perPage(),
                'total' => $rides->total(),
                'last_page' => $rides->lastPage(),
            ],
        ];
    }

    /**
     * Toggle driver availability
     */
    public function toggleAvailability(int $driverId, bool $available, string $correlationId = null): Driver
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        $driver = Driver::findOrFail($driverId);
        
        $driver->update(['is_available' => $available]);
        
        $this->audit->log(
            action: 'taxi_driver_availability_toggled',
            subjectType: Driver::class,
            subjectId: $driver->id,
            oldValues: ['is_available' => !$available],
            newValues: ['is_available' => $available],
            correlationId: $correlationId,
        );

        $this->logger->info('Taxi driver availability toggled', [
            'correlation_id' => $correlationId,
            'driver_id' => $driverId,
            'is_available' => $available,
        ]);

        return $driver->fresh();
    }

    /**
     * Get driver performance report
     */
    public function getDriverPerformanceReport(int $driverId, Carbon $startDate, Carbon $endDate, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        
        return $this->analyticsService->getDriverPerformanceReport($driverId, $startDate, $endDate, $correlationId);
    }
}
