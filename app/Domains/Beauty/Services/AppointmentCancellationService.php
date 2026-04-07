<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * AppointmentCancellationService — расчёт штрафа и возврата при отмене записи.
 *
 * Учитывает групповые, свадебные, корпоративные и детские правила отмены,
 * no-show историю, B2B-специфику и сложность услуги.
 */
final readonly class AppointmentCancellationService
{
    public function __construct(
        private GroupBookingService $groupBookingService,
        private WeddingGroupService $weddingGroupService,
        private KidsPartyService $kidsPartyService,
        private CorporateEventService $corporateEventService,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитать штраф и сумму к возврату (Refund Calculation).
     *
     * @return array{penalty_amount: int, refund_amount: int, penalty_percent: int, reason: string}
     */
    public function calculateRefund(BeautyAppointment $appointment, Carbon $cancelledAt): array
    {
        $startAt = $appointment->start_at;
        $hoursBefore = $cancelledAt->diffInHours($startAt, false);
        $totalPrice = $appointment->price_cents;

        $penaltyPercent = $this->getBasePenaltyPercent($hoursBefore);

        if ($appointment->is_group) {
            $groupFees = $this->groupBookingService->calculateGroupFees($appointment, 'cancel');
            $penaltyMultiplier = (float) $groupFees['penalty_multiplier'];
            $penaltyPercent = min(100.0, $penaltyPercent * $penaltyMultiplier);
        }

        if ($appointment->is_wedding_group) {
            $weddingFees = $this->weddingGroupService->calculateWeddingFees($appointment, 'cancel');
            $weddingPenalty = (float) $weddingFees['fee_percent'];
            $weddingMultiplier = (float) $weddingFees['multiplier'];
            $penaltyPercent = max($penaltyPercent, $weddingPenalty);
            $penaltyPercent = min(100.0, $penaltyPercent * $weddingMultiplier);
        }

        if ($appointment->is_kids_party) {
            $kidsFees = $this->kidsPartyService->calculateKidsPartyFees($appointment, 'cancel');
            $kidsPenalty = (float) $kidsFees['fee_percent'];
            $kidsMultiplier = (float) $kidsFees['multiplier'];
            $penaltyPercent = max($penaltyPercent, $kidsPenalty);
            $penaltyPercent = min(100.0, $penaltyPercent * $kidsMultiplier);
        }

        if ($appointment->is_corporate_event) {
            $corporateFees = $this->corporateEventService->calculateCorporateFees($appointment, 'cancel');
            $corporatePenalty = (float) $corporateFees['fee_percent'];
            $corporateMultiplier = (float) $corporateFees['multiplier'];
            $penaltyPercent = max($penaltyPercent, $corporatePenalty);
            $penaltyPercent = min(100.0, $penaltyPercent * $corporateMultiplier);
        }

        $isComplex = isset($appointment->metadata['is_complex']) && $appointment->metadata['is_complex'];
        if ($isComplex && $penaltyPercent > 0 && $penaltyPercent < 100) {
            $penaltyPercent += 10;
        }

        $noShowCount = $this->db->table('beauty_appointments')
            ->where('client_id', $appointment->client_id)
            ->where('status', 'no_show')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->count();

        if ($noShowCount > 1 && $penaltyPercent > 0 && $penaltyPercent < 100) {
            $penaltyPercent = min(100.0, $penaltyPercent + 20);
        }

        $isB2B = $appointment->metadata['is_b2b'] ?? false;
        if ($isB2B && $penaltyPercent === 0.0) {
            $penaltyPercent = 5.0;
        }

        $penaltyAmount = (int) ($totalPrice * ($penaltyPercent / 100));
        $refundAmount = max(0, $totalPrice - $penaltyAmount);

        $this->logger->info('Penalty calculated for appointment cancellation', [
            'appointment_id' => $appointment->id,
            'hours_before' => $hoursBefore,
            'is_group' => $appointment->is_group,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => $penaltyAmount,
            'refund_amount' => $refundAmount,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'penalty_amount' => $penaltyAmount,
            'refund_amount' => $refundAmount,
            'penalty_percent' => (int) $penaltyPercent,
            'reason' => 'Complex: ' . ($isComplex ? 'yes' : 'no') . ', Group: ' . ($appointment->is_group ? 'yes' : 'no'),
        ];
    }

    /**
     * Базовый процент штрафа при отмене по количеству часов.
     */
    private function getBasePenaltyPercent(int $hoursBefore): float
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
