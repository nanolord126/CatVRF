<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Enums\ReschedulePolicy;
use App\Domains\Beauty\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис расчета комиссий за перенос бронирования (Rescheduling).
 * Production 2026.
 */
final readonly class AppointmentRescheduleService
{
    /**
     * Рассчитать комиссию за перенос бронирования.
     * 
     * @param Appointment $appointment Исходная запись
     * @param Carbon $newStartTime Новое время записи
     * @param Carbon $requestedAt Момент запроса на перенос
     * @return array {fee_amount, fee_percent, is_allowed, reason}
     */
    public function calculateRescheduleFee(Appointment $appointment, Carbon $newStartTime, Carbon $requestedAt): array
    {
        $policy = ReschedulePolicy::STANDARD; // В 2026 может расширяться из настроек салона
        $originalStart = $appointment->datetime_start;
        $hoursBefore = $requestedAt->diffInHours($originalStart, false);
        $totalPrice = $appointment->price_cents;

        // 1. Проверка минимально допустимого времени для переноса (<4ч — запрещено)
        if ($hoursBefore < 4) {
            return [
                'fee_amount' => $totalPrice, 
                'fee_percent' => 100, 
                'is_allowed' => false, 
                'reason' => 'Перенос невозмощен менее чем за 4 часа до начала услуги.',
            ];
        }

        // 2. Базовый процент по политике времени
        $feePercent = (float)$policy->getBaseFeePercent($hoursBefore);

        // 3. Групповые правила (Увеличение штрафа при переносе группы)
        if ($appointment->is_group) {
            $groupService = new GroupBookingService();
            $groupFees = $groupService->calculateGroupFees($appointment, 'reschedule');
            $penaltyMultiplier = $groupFees['penalty_multiplier'];
            $feePercent = min(100, $feePercent * $penaltyMultiplier);
        }

        // 3.1. Свадебные правила (Wedding Canon 2026)
        if ($appointment->is_wedding_group) {
            $weddingService = new WeddingGroupService();
            $weddingFees = $weddingService->calculateWeddingFees($appointment, 'reschedule');
            $weddingPenalty = (float)$weddingFees['fee_percent'];
            $weddingMultiplier = (float)$weddingFees['multiplier'];
            
            // Свадебные штрафы за перенос строже базовых
            $feePercent = max($feePercent, $weddingPenalty);
            $feePercent = min(100, $feePercent * $weddingMultiplier);
        }

        // 3.2. Правила детских праздников (Kids Party Canon 2026)
        if ($appointment->is_kids_party) {
            $kidsService = new KidsPartyService();
            $kidsFees = $kidsService->calculateKidsPartyFees($appointment, 'reschedule');
            $kidsPenalty = (float)$kidsFees['fee_percent'];
            $kidsMultiplier = (float)$kidsFees['multiplier'];
            
            // Выбираем более строгий штраф
            $feePercent = max($feePercent, $kidsPenalty);
            $feePercent = min(100, $feePercent * $kidsMultiplier);
        }

        // 4. Увеличение штрафа для сложных услуг (+10%)
        $isComplex = isset($appointment->metadata['is_complex']) && $appointment->metadata['is_complex'];
        if ($isComplex && $feePercent > 0 && $feePercent < 100) {
            $feePercent += 10;
        }

        // 5. Увеличение при переносе на более раннюю дату (×1.5)
        // Если новая дата раньше текущей запланированной даты (срочный перенос)
        if ($newStartTime->isBefore($originalStart->startOfDay())) {
            $feePercent = (int)($feePercent * 1.5);
        }

        $feePercent = min(100, $feePercent);
        $feeAmount = (int)($totalPrice * ($feePercent / 100));

        Log::channel('audit')->info('Reschedule fee calculated', [
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
            'fee_percent' => (int)$feePercent,
            'is_allowed' => true,
            'reason' => "Policy: {$policy->value}, Group: " . ($appointment->is_group ? 'yes' : 'no'),
        ];
    }
}
