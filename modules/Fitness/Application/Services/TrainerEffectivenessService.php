<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Modules\Fitness\Domain\Entities\TrainerEffectiveness;
use Modules\Fitness\Domain\Entities\TrainerMetricHistory;
use Modules\Fitness\Infrastructure\Models\AttendanceModel;
use Modules\Fitness\Infrastructure\Models\BookingModel;
use Modules\Fitness\Infrastructure\Models\ClientModel;
use Modules\Fitness\Infrastructure\Models\TrainerEffectivenessModel;
use Modules\Fitness\Infrastructure\Models\TrainerMetricHistoryModel;
use Modules\Fitness\Infrastructure\Models\TrainerModel;
use Modules\Fitness\Infrastructure\Models\TrainerReviewModel;

final readonly class TrainerEffectivenessService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}
    public function calculateScore(
        int $trainerId,
        ?CarbonImmutable $periodStart = null,
        ?CarbonImmutable $periodEnd = null,
    ): TrainerEffectiveness {
        $trainer = TrainerModel::findOrFail($trainerId);
        $periodStart ??= CarbonImmutable::now()->subDays(30);
        $periodEnd ??= CarbonImmutable::now();

        // Calculate client metrics (60% weight)
        $retentionRate = $this->calculateRetentionRate($trainerId, $periodStart, $periodEnd);
        $npsScore = $this->calculateNPSScore($trainerId, $periodStart, $periodEnd);
        $avgCheckPerClient = $this->calculateAvgCheckPerClient($trainerId, $periodStart, $periodEnd);
        $repeatBookingsCount = $this->calculateRepeatBookings($trainerId, $periodStart, $periodEnd);
        $churnRate = $this->calculateChurnRate($trainerId, $periodStart, $periodEnd);

        // Calculate operational metrics (25% weight)
        $occupancyRate = $this->calculateOccupancyRate($trainerId, $periodStart, $periodEnd);
        $avgGroupAttendance = $this->calculateAvgGroupAttendance($trainerId, $periodStart, $periodEnd);
        $individualSessionsCount = $this->calculateIndividualSessions($trainerId, $periodStart, $periodEnd);
        $scheduleCompliance = $this->calculateScheduleCompliance($trainerId, $periodStart, $periodEnd);

        // Calculate qualitative metrics (15% weight)
        $managerScore = $this->getManagerScore($trainerId);
        $methodologyCompliance = $this->checkMethodologyCompliance($trainerId);
        $progressPhotosCount = $this->countProgressPhotos($trainerId, $periodStart, $periodEnd);

        // Create effectiveness entity
        $effectiveness = TrainerEffectiveness::create(
            tenantId: $trainer->tenant_id,
            trainerId: $trainerId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            businessGroupId: $trainer->business_group_id,
        );

        // Calculate total score
        $effectiveness = $effectiveness->calculateTotalScore(
            retentionRate: $retentionRate ?? 0,
            npsScore: $npsScore ?? 0,
            avgCheckPerClient: $avgCheckPerClient ?? 0,
            occupancyRate: $occupancyRate ?? 0,
            managerScore: $managerScore ?? 5,
        );

        // Update with all metrics
        $effectiveness = new TrainerEffectiveness(
            ...get_object_vars($effectiveness),
            retentionRate: $retentionRate,
            npsScore: $npsScore,
            avgCheckPerClient: $avgCheckPerClient,
            repeatBookingsCount: $repeatBookingsCount,
            churnRate: $churnRate,
            occupancyRate: $occupancyRate,
            avgGroupAttendance: $avgGroupAttendance,
            individualSessionsCount: $individualSessionsCount,
            scheduleCompliance: $scheduleCompliance,
            managerScore: $managerScore,
            methodologyCompliance: $methodologyCompliance,
            progressPhotosCount: $progressPhotosCount,
        );

        // Generate recommendations
        $recommendations = $this->generateRecommendations($effectiveness);
        $effectiveness = new TrainerEffectiveness(
            ...get_object_vars($effectiveness),
            recommendations: $recommendations,
        );

        return $effectiveness;
    }

    public function saveEffectiveness(TrainerEffectiveness $effectiveness): TrainerEffectiveness
    {
        return $this->db->transaction(function () use ($effectiveness) {
            // Save effectiveness snapshot
            $model = TrainerEffectivenessModel::fromDomain($effectiveness);
            $model->save();

            // Track metric changes from previous snapshot
            $previousSnapshot = TrainerEffectivenessModel::where('trainer_id', $effectiveness->trainerId)
                ->where('period_end', '<', $effectiveness->periodEnd)
                ->orderBy('period_end', 'desc')
                ->first();

            if ($previousSnapshot) {
                $this->trackMetricChanges($previousSnapshot->toDomain(), $effectiveness);
            }

            return $model->toDomain();
        });
    }

    public function generateDevelopmentPlan(int $trainerId): array
    {
        $effectiveness = $this->getLatestEffectiveness($trainerId);

        if (!$effectiveness) {
            return [];
        }

        $plan = [];

        if ($effectiveness->retentionRate < 70) {
            $plan[] = [
                'metric' => 'retention_rate',
                'current' => $effectiveness->retentionRate,
                'target' => 80,
                'actions' => [
                    'Increase personal training sessions',
                    'Implement client follow-up system',
                    'Create personalized workout plans',
                    'Schedule regular check-ins',
                ],
            ];
        }

        if ($effectiveness->npsScore < 50) {
            $plan[] = [
                'metric' => 'nps_score',
                'current' => $effectiveness->npsScore,
                'target' => 70,
                'actions' => [
                    'Improve communication with clients',
                    'Request feedback after each session',
                    'Address client concerns promptly',
                    'Enhance session quality',
                ],
            ];
        }

        if ($effectiveness->occupancyRate < 75) {
            $plan[] = [
                'metric' => 'occupancy_rate',
                'current' => $effectiveness->occupancyRate,
                'target' => 85,
                'actions' => [
                    'Optimize schedule availability',
                    'Promote available slots',
                    'Offer flexible booking options',
                    'Improve online presence',
                ],
            ];
        }

        if ($effectiveness->managerScore < 7) {
            $plan[] = [
                'metric' => 'manager_score',
                'current' => $effectiveness->managerScore,
                'target' => 8,
                'actions' => [
                    'Schedule meeting with supervisor',
                    'Review methodology compliance',
                    'Attend additional training',
                    'Improve administrative tasks',
                ],
            ];
        }

        return $plan;
    }

    public function getTopTrainers(int $limit = 5, ?int $tenantId = null): array
    {
        $query = TrainerEffectivenessModel::with('trainer')
            ->orderBy('total_score', 'desc')
            ->limit($limit);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()->map(fn ($model) => $model->toDomain())->toArray();
    }

    private function calculateRetentionRate(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $totalClients = BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct('client_id')
            ->count();

        if ($totalClients === 0) {
            return null;
        }

        $returningClients = BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('client', function ($query) use ($trainerId, $start) {
                $query->whereHas('bookings', function ($q) use ($trainerId, $start) {
                    $q->where('trainer_id', $trainerId)
                        ->where('created_at', '<', $start);
                });
            })
            ->distinct('client_id')
            ->count();

        return ($returningClients / $totalClients) * 100;
    }

    private function calculateNPSScore(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $reviews = TrainerReviewModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->where('is_verified', true)
            ->get();

        if ($reviews->isEmpty()) {
            return null;
        }

        $promoters = $reviews->filter(fn ($r) => $r->rating >= 9)->count();
        $detractors = $reviews->filter(fn ($r) => $r->rating <= 6)->count();
        $total = $reviews->count();

        return (($promoters - $detractors) / $total) * 100;
    }

    private function calculateAvgCheckPerClient(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $totalRevenue = BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('price');

        $totalClients = BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct('client_id')
            ->count();

        if ($totalClients === 0) {
            return null;
        }

        return $totalRevenue / $totalClients;
    }

    private function calculateRepeatBookings(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('client', function ($query) use ($trainerId, $start) {
                $query->whereHas('bookings', function ($q) use ($trainerId, $start) {
                    $q->where('trainer_id', $trainerId)
                        ->where('created_at', '<', $start);
                });
            })
            ->count();
    }

    private function calculateChurnRate(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $previousClients = BookingModel::where('trainer_id', $trainerId)
            ->where('created_at', '<', $start)
            ->distinct('client_id')
            ->pluck('client_id')
            ->toArray();

        if (empty($previousClients)) {
            return null;
        }

        $activeClients = BookingModel::where('trainer_id', $trainerId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct('client_id')
            ->pluck('client_id')
            ->toArray();

        $churned = array_diff($previousClients, $activeClients);

        return (count($churned) / count($previousClients)) * 100;
    }

    private function calculateOccupancyRate(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $totalSlots = AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$start, $end])
            ->count();

        if ($totalSlots === 0) {
            return null;
        }

        $attendedSlots = AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$start, $end])
            ->where('status', 'completed')
            ->count();

        return ($attendedSlots / $totalSlots) * 100;
    }

    private function calculateAvgGroupAttendance(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        // This would need group session data - simplified for now
        return $this->calculateOccupancyRate($trainerId, $start, $end);
    }

    private function calculateIndividualSessions(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$start, $end])
            ->where('status', 'completed')
            ->count();
    }

    private function calculateScheduleCompliance(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): float
    {
        $totalScheduled = AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$start, $end])
            ->count();

        if ($totalScheduled === 0) {
            return 100.0;
        }

        $onTime = AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$start, $end])
            ->whereNotNull('check_in_time')
            ->count();

        return ($onTime / $totalScheduled) * 100;
    }

    private function getManagerScore(int $trainerId): ?float
    {
        // This would come from a manager evaluation system
        // For now, return a default or fetch from a separate table
        $trainer = TrainerModel::find($trainerId);
        return $trainer?->rating ?? null;
    }

    private function checkMethodologyCompliance(int $trainerId): bool
    {
        // This would check if trainer follows proper methodology
        // For now, return true
        return true;
    }

    private function countProgressPhotos(int $trainerId, CarbonImmutable $start, CarbonImmutable $end): int
    {
        // This would count progress photos uploaded for clients
        // For now, return 0
        return 0;
    }

    private function generateRecommendations(TrainerEffectiveness $effectiveness): string
    {
        $recommendations = [];

        if ($effectiveness->retentionRate < 70) {
            $recommendations[] = 'Focus on improving client retention through personalized attention';
        }

        if ($effectiveness->npsScore < 50) {
            $recommendations[] = 'Work on improving client satisfaction and NPS score';
        }

        if ($effectiveness->occupancyRate < 75) {
            $recommendations[] = 'Increase schedule occupancy through marketing and availability optimization';
        }

        if ($effectiveness->managerScore < 7) {
            $recommendations[] = 'Discuss performance with supervisor and address feedback';
        }

        return implode('. ', $recommendations) ?: 'Keep up the good work!';
    }

    private function trackMetricChanges(TrainerEffectiveness $previous, TrainerEffectiveness $current): void
    {
        $metrics = [
            'retention_rate' => ['old' => $previous->retentionRate, 'new' => $current->retentionRate],
            'nps_score' => ['old' => $previous->npsScore, 'new' => $current->npsScore],
            'occupancy_rate' => ['old' => $previous->occupancyRate, 'new' => $current->occupancyRate],
            'manager_score' => ['old' => $previous->managerScore, 'new' => $current->managerScore],
        ];

        foreach ($metrics as $metricName => $values) {
            if ($values['old'] !== null && $values['new'] !== null) {
                $history = TrainerMetricHistory::create(
                    tenantId: $current->tenantId,
                    trainerId: $current->trainerId,
                    effectivenessId: $current->id,
                    metricName: $metricName,
                    oldValue: $values['old'],
                    newValue: $values['new'],
                    businessGroupId: $current->businessGroupId,
                );

                $historyModel = TrainerMetricHistoryModel::fromDomain($history);
                $historyModel->save();
            }
        }
    }

    private function getLatestEffectiveness(int $trainerId): ?TrainerEffectiveness
    {
        $model = TrainerEffectivenessModel::where('trainer_id', $trainerId)
            ->orderBy('period_end', 'desc')
            ->first();

        return $model?->toDomain();
    }
}
