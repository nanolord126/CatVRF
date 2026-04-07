<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * CorporateEventService — расчёт штрафов и предоплат для корпоративных мероприятий.
 *
 * Штрафная сетка учитывает количество участников, срок до события
 * и наличие AI-образа (дополнительный +10%).
 */
final readonly class CorporateEventService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитать параметры штрафов и предоплат для корпоративного мероприятия.
     *
     * @return array{multiplier: float, fee_percent: int, prepayment: int}
     */
    public function calculateCorporateFees(BeautyAppointment $appointment, string $action): array
    {
        if (!$appointment->is_corporate_event) {
            return [
                'multiplier' => 1.0,
                'fee_percent' => 0,
                'prepayment' => 0,
            ];
        }

        $now = Carbon::now();
        $start = Carbon::parse($appointment->start_at);
        $diffInHours = $now->diffInHours($start, false);
        $diffInDays = $now->diffInDays($start, false);

        $feePercent = match (true) {
            $diffInDays >= 10 => 0,
            $diffInDays >= 7 => 10,
            $diffInDays >= 3 => 25,
            $diffInHours >= 48 => 40,
            default => 70,
        };

        if (isset($appointment->metadata['ai_look_id']) || ($appointment->tags['ai_generated'] ?? false)) {
            $feePercent = min(100, $feePercent + 10);
        }

        $multiplier = match (true) {
            $appointment->participants_count >= 20 => 1.6,
            $appointment->participants_count >= 10 => 1.35,
            $appointment->participants_count >= 5 => 1.2,
            default => 1.1,
        };

        $prepayment = match (true) {
            $appointment->participants_count >= 30 => 70,
            $appointment->participants_count >= 15 => 50,
            $appointment->participants_count >= 5 => 30,
            default => 20,
        };

        $this->logger->info('Corporate event fees calculated', [
            'appointment_id' => $appointment->id,
            'action' => $action,
            'participants' => $appointment->participants_count,
            'fee_percent' => $feePercent,
            'multiplier' => $multiplier,
            'prepayment_needed' => $prepayment,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'multiplier' => $multiplier,
            'fee_percent' => $feePercent,
            'prepayment' => $prepayment,
        ];
    }
}
