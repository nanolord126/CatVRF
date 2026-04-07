<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use Carbon\Carbon;
final readonly class WeddingGroupService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Рассчитывает параметры штрафов и предоплат для свадебной группы.
     *
     * @param BeautyAppointment $appointment
     * @param string      $action ('cancel', 'reschedule', 'prepayment')
     * @return array ['multiplier' => float, 'fee_percent' => int, 'prepayment' => int]
     */
    public function calculateWeddingFees(BeautyAppointment $appointment, string $action): array
    {
        if (!$appointment->is_wedding_group) {
            return [
                'multiplier'  => 1.0,
                'fee_percent' => 0,
                'prepayment'  => 0,
            ];
        }

        $now             = Carbon::now();
        $appointmentDate = Carbon::parse($appointment->start_at);
        $daysLeft        = $appointmentDate->diffInDays($now, false);
        $hoursLeft       = $appointmentDate->diffInHours($now, false);

        // Базовые правила штрафов для свадеб (Wedding Canon 2026)
        $feePercent = match (true) {
            $daysLeft > 14  => 0,
            $daysLeft >= 7  => 25,
            $daysLeft >= 3  => 50,
            $hoursLeft >= 0 => 75,
            default         => 100,
        };

        // Увеличение штрафа при использовании AI Look (+15%)
        if (
            isset($appointment->metadata['ai_look_id'])
            || ($appointment->tags['ai_generated'] ?? false)
        ) {
            $feePercent = min(100, $feePercent + 15);
        }

        // Групповой коэффициент
        $multiplier = match (true) {
            $appointment->group_size >= 10 => 1.8,
            $appointment->group_size >= 5  => 1.5,
            default                        => 1.2,
        };

        // Логика предоплат для свадебных групп
        $prepayment = match (true) {
            ($appointment->group_size ?? 0) >= 10 => 100,
            ($appointment->group_size ?? 0) >= 5  => 75,
            default                               => 50,
        };

        $this->logger->info('Wedding fees calculated', [
            'appointment_id' => $appointment->id,
            'action'         => $action,
            'fee_percent'    => $feePercent,
            'multiplier'     => $multiplier,
            'prepayment'     => $prepayment,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'multiplier'  => $multiplier,
            'fee_percent' => $feePercent,
            'prepayment'  => $prepayment,
        ];
    }
}