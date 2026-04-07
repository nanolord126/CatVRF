<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * KidsPartyService — расчёт штрафов и предоплат для детских праздников.
 *
 * Штрафная сетка (7-дневная) + AI Look +10% + групповой множитель.
 */
final readonly class KidsPartyService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитать параметры штрафов и предоплат для детского праздника.
     *
     * @return array{multiplier: float, fee_percent: int, prepayment: int}
     */
    public function calculateKidsPartyFees(BeautyAppointment $appointment, string $action): array
    {
        if (!$appointment->is_kids_party) {
            return [
                'multiplier' => 1.0,
                'fee_percent' => 0,
                'prepayment' => 0,
            ];
        }

        $now = Carbon::now();
        $start = Carbon::parse($appointment->start_at);
        $diffInDays = $now->diffInDays($start, false);
        $diffInHours = $now->diffInHours($start, false);

        $feePercent = match (true) {
            $diffInDays > 7 => 0,
            $diffInDays >= 3 => 15,
            $diffInHours >= 48 => 30,
            $diffInHours >= 24 => 50,
            default => 80,
        };

        if (isset($appointment->metadata['ai_look_id']) || ($appointment->tags['ai_generated'] ?? false)) {
            $feePercent = min(100, $feePercent + 10);
        }

        $multiplier = match (true) {
            $appointment->kids_count >= 10 => 1.4,
            $appointment->kids_count >= 6 => 1.25,
            $appointment->kids_count >= 4 => 1.15,
            default => 1.0,
        };

        $prepayment = match (true) {
            $appointment->kids_count >= 8 => 75,
            $appointment->kids_count >= 4 => 50,
            default => 0,
        };

        $this->logger->info('Kids party fees calculated', [
            'appointment_id' => $appointment->id,
            'action' => $action,
            'fee_percent' => $feePercent,
            'multiplier' => $multiplier,
            'prepayment' => $prepayment,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'multiplier' => $multiplier,
            'fee_percent' => $feePercent,
            'prepayment' => $prepayment,
        ];
    }
}
