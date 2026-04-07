<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * AppointmentRescheduleService — расчёт комиссии за перенос записи.
 *
 * Учитывает групповые, свадебные и детские правила при переносе,
 * а также сложность услуги и срочность запроса.
 */
final readonly class AppointmentRescheduleService
{
    public function __construct(
        private GroupBookingService $groupBookingService,
        private WeddingGroupService $weddingGroupService,
        private KidsPartyService $kidsPartyService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитать комиссию за перенос бронирования.
     *
     * @return array{fee_amount: int, fee_percent: int, is_allowed: bool, reason: string}
     */
    public function calculateRescheduleFee(
        BeautyAppointment $appointment,
        Carbon $newStartTime,
        Carbon $requestedAt,
    ): array {
        $originalStart = $appointment->start_at;
        $hoursBefore = $requestedAt->diffInHours($originalStart, false);
        $totalPrice = $appointment->price_cents;

        if ($hoursBefore < 4) {
            return [
                'fee_amount' => $totalPrice,
                'fee_percent' => 100,
                'is_allowed' => false,
                'reason' => 'Перенос невозможен менее чем за 4 часа до начала услуги.',
            ];
        }

        $feePercent = $this->getBaseFeePercent($hoursBefore);

        if ($appointment->is_group) {
            $groupFees = $this->groupBookingService->calculateGroupFees($appointment, 'reschedule');
            $penaltyMultiplier = (float) $groupFees['penalty_multiplier'];
            $feePercent = min(100.0, $feePercent * $penaltyMultiplier);
        }

        if ($appointment->is_wedding_group) {
            $weddingFees = $this->weddingGroupService->calculateWeddingFees($appointment, 'reschedule');
            $weddingPenalty = (float) $weddingFees['fee_percent'];
            $weddingMultiplier = (float) $weddingFees['multiplier'];
            $feePercent = max($feePercent, $weddingPenalty);
            $feePercent = min(100.0, $feePercent * $weddingMultiplier);
        }

        if ($appointment->is_kids_party) {
            $kidsFees = $this->kidsPartyService->calculateKidsPartyFees($appointment, 'reschedule');
            $kidsPenalty = (float) $kidsFees['fee_percent'];
            $kidsMultiplier = (float) $kidsFees['multiplier'];
            $feePercent = max($feePercent, $kidsPenalty);
            $feePercent = min(100.0, $feePercent * $kidsMultiplier);
        }

        $isComplex = isset($appointment->metadata['is_complex']) && $appointment->metadata['is_complex'];
        if ($isComplex && $feePercent > 0 && $feePercent < 100) {
            $feePercent += 10;
        }

        if ($newStartTime->isBefore($originalStart->startOfDay())) {
            $feePercent = $feePercent * 1.5;
        }

        $feePercent = min(100.0, $feePercent);
        $feeAmount = (int) ($totalPrice * ($feePercent / 100));

        $this->logger->info('Reschedule fee calculated', [
            'appointment_id' => $appointment->id,
            'hours_before' => $hoursBefore,
            'is_group' => $appointment->is_group,
            'is_early_reschedule' => $newStartTime->isBefore($originalStart),
            'fee_percent' => $feePercent,
            'fee_amount' => $feeAmount,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'fee_amount' => $feeAmount,
            'fee_percent' => (int) $feePercent,
            'is_allowed' => true,
            'reason' => 'Group: ' . ($appointment->is_group ? 'yes' : 'no'),
        ];
    }

    /**
     * Базовый процент штрафа за перенос по количеству часов.
     */
    private function getBaseFeePercent(int $hoursBefore): float
    {
        return match (true) {
            $hoursBefore >= 48 => 0.0,
            $hoursBefore >= 24 => 10.0,
            $hoursBefore >= 12 => 25.0,
            $hoursBefore >= 4 => 50.0,
            default => 100.0,
        };
    }
}
