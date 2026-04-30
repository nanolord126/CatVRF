<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Carbon\CarbonImmutable;

use App\Domains\Logistics\Models\CourierTypeConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Time Window Validator for SLA compliance.
 *
 * Production Strategy:
 * - Validate customer time windows against courier type capabilities
 * - Calculate ETA based on type-specific speeds and parking times
 * - Support flexible time windows with buffers
 * - Alert when SLA is at risk
 */
final readonly class TimeWindowValidator
{
    public function __construct(
        private readonly CourierTypeConfigurationService $configService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Validate if courier can meet delivery time window.
     *
     * @param  string  $timeWindowStart  "18:00"
     * @param  string  $timeWindowEnd  "20:00"
     */
    public function canMeetTimeWindow(
        int $tenantId,
        string $type,
        float $distanceKm,
        string $timeWindowStart,
        string $timeWindowEnd,
        ?string $city = null,
        int $bufferMin = 15,
    ): bool {
        $config = $this->configService->getConfiguration($tenantId, $type, $city);

        if ($config === null) {
            return false;
        }

        $etaMin = $this->calculateETA($config, $distanceKm);
        $now = CarbonImmutable::now();
        $windowStart = Carbon::parse($timeWindowStart);
        $windowEnd = Carbon::parse($timeWindowEnd);

        // Adjust for current time if window is today
        if ($windowStart->isPast()) {
            $windowStart->addDay();
            $windowEnd->addDay();
        }

        $availableTime = $now->diffInMinutes($windowStart);
        $requiredTime = $etaMin + $bufferMin;

        $canMeet = $availableTime >= $requiredTime;

        $this->logger->$this->logger->info('Time window validation', [
            'tenant_id' => $tenantId,
            'type' => $type,
            'distance_km' => $distanceKm,
            'eta_min' => $etaMin,
            'buffer_min' => $bufferMin,
            'required_time' => $requiredTime,
            'available_time' => $availableTime,
            'can_meet' => $canMeet,
            'window_start' => $timeWindowStart,
            'window_end' => $timeWindowEnd,
        ]);

        return $canMeet;
    }

    /**
     * Calculate ETA in minutes based on type configuration.
     */
    public function calculateETA(CourierTypeConfiguration $config, float $distanceKm): int
    {
        $speedKmh = $config->avg_speed_kmh;
        $travelTimeMin = ($distanceKm / $speedKmh) * 60;
        $parkingTimeMin = $config->parking_time_min;

        return (int) ceil($travelTimeMin + $parkingTimeMin);
    }

    /**
     * Get best courier type for time window.
     *
     * @return array{type: string, eta_min: int, can_meet: bool}|null
     */
    public function getBestTypeForTimeWindow(
        int $tenantId,
        float $distanceKm,
        string $timeWindowStart,
        string $timeWindowEnd,
        ?string $city = null,
        int $bufferMin = 15,
    ): ?array {
        $configs = $this->configService->getAllConfigurations($tenantId, $city);

        $results = $configs->map(function ($config) use (
            $distanceKm,
            $timeWindowStart,
            $timeWindowEnd,
            $bufferMin,
            $tenantId,
            $city,
        ) {
            $canMeet = $this->canMeetTimeWindow(
                $tenantId,
                $config->type,
                $distanceKm,
                $timeWindowStart,
                $timeWindowEnd,
                $city,
                $bufferMin,
            );

            return [
                'type' => $config->type,
                'eta_min' => $this->calculateETA($config, $distanceKm),
                'can_meet' => $canMeet,
                'priority' => $config->priority,
                'cost_multiplier' => $config->cost_multiplier,
            ];
        });

        // Filter to only types that can meet the window
        $validTypes = $results->filter(fn ($r) => $r['can_meet']);

        if ($validTypes->isEmpty()) {
            return null;
        }

        // Return cheapest valid type
        return $validTypes->sortBy('cost_multiplier')->first();
    }

    /**
     * Check if SLA is at risk for delivery.
     */
    public function isSlaAtRisk(
        int $tenantId,
        string $type,
        float $distanceKm,
        string $deadline,
        ?string $city = null,
        int $warningThresholdMin = 30,
    ): bool {
        $config = $this->configService->getConfiguration($tenantId, $type, $city);

        if ($config === null) {
            return true;
        }

        $etaMin = $this->calculateETA($config, $distanceKm);
        $now = CarbonImmutable::now();
        $deadlineTime = Carbon::parse($deadline);

        $timeRemaining = $now->diffInMinutes($deadlineTime, false); // negative if past

        return $timeRemaining < ($etaMin + $warningThresholdMin);
    }

    /**
     * Get recommended delivery time slots.
     *
     * @return Collection<int, array{start: string, end: string, eta_min: int}>
     */
    public function getRecommendedTimeSlots(
        int $tenantId,
        string $type,
        float $distanceKm,
        ?string $city = null,
        int $slotDurationMin = 60,
        int $slotsAhead = 5,
    ): Collection {
        $config = $this->configService->getConfiguration($tenantId, $type, $city);

        if ($config === null) {
            return new Collection();
        }

        $etaMin = $this->calculateETA($config, $distanceKm);
        $slots = new Collection();
        $currentTime = CarbonImmutable::now()->addMinutes($etaMin + 15); // Add buffer

        for ($i = 0; $i < $slotsAhead; $i++) {
            $slotStart = $currentTime->copy()->addMinutes($i * $slotDurationMin);
            $slotEnd = $slotStart->copy()->addMinutes($slotDurationMin);

            $slots->push([
                'start' => $slotStart->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'eta_min' => $etaMin,
                'available_at' => $slotStart->toIso8601String(),
            ]);
        }

        return $slots;
    }
}
