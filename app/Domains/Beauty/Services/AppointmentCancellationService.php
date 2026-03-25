<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Enums\CancellationPolicy;
use App\Domains\Beauty\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Сервис управления правилами отмены бронирований.
 * Production 2026.
 */
final readonly class AppointmentCancellationService
{
    /**
     * Рассчитать штраф и сумму к возврату (Refund Calculation).
     * 
     * @param Appointment $appointment Запись
     * @param Carbon $cancelledAt Момент отмены
     * @return array {penalty_amount, refund_amount, penalty_percent, reason}
     */
    public function calculateRefund(Appointment $appointment, Carbon $cancelledAt): array
    {
        $policy = $appointment->cancellation_policy ?? CancellationPolicy::FLEXIBLE;
        $startAt = $appointment->datetime_start;
        $hoursBefore = $cancelledAt->diffInHours($startAt, false);
        $totalPrice = $appointment->price_cents;

        // 1. Базовый штраф по политике
        $penaltyPercent = (float)$policy->getPenaltyPercent($hoursBefore);

        // 2. Групповые правила (Увеличение штрафа при отмене группы)
        if ($appointment->is_group) {
            $groupService = new GroupBookingService();
            $groupFees = $groupService->calculateGroupFees($appointment, 'cancel');
            $penaltyMultiplier = $groupFees['penalty_multiplier'];
            $penaltyPercent = min(100, $penaltyPercent * $penaltyMultiplier);
        }

        // 2.1. Свадебные правила (Wedding Canon 2026)
        if ($appointment->is_wedding_group) {
            $weddingService = new WeddingGroupService();
            $weddingFees = $weddingService->calculateWeddingFees($appointment, 'cancel');
            $weddingPenalty = (float)$weddingFees['fee_percent'];
            $weddingMultiplier = (float)$weddingFees['multiplier'];
            
            // Если свадебный штраф выше базового, используем его, умножая на коэффициент
            $penaltyPercent = max($penaltyPercent, $weddingPenalty);
            $penaltyPercent = min(100, $penaltyPercent * $weddingMultiplier);
        }

        // 2.2. Правила детских праздников (Kids Party Canon 2026)
        if ($appointment->is_kids_party) {
            $kidsService = new KidsPartyService();
            $kidsFees = $kidsService->calculateKidsPartyFees($appointment, 'cancel');
            $kidsPenalty = (float)$kidsFees['fee_percent'];
            $kidsMultiplier = (float)$kidsFees['multiplier'];
            
            // Выбираем более строгий штраф
            $penaltyPercent = max($penaltyPercent, $kidsPenalty);
            $penaltyPercent = min(100, $penaltyPercent * $kidsMultiplier);
        }

        // 2.3. Корпоративные правила (Corporate Canon 2026)
        if ($appointment->is_corporate_event) {
            $corporateService = new CorporateEventService();
            $corporateFees = $corporateService->calculateCorporateFees($appointment, 'cancel');
            $corporatePenalty = (float)$corporateFees['fee_percent'];
            $corporateMultiplier = (float)$corporateFees['multiplier'];
            
            // Выбираем более строгий штраф (максимальный из всех политик)
            $penaltyPercent = max($penaltyPercent, $corporatePenalty);
            $penaltyPercent = min(100, $penaltyPercent * $corporateMultiplier);
        }

        // 3. Увеличение штрафа для сложных услуг (+10%)
        $isComplex = isset($appointment->metadata['is_complex']) && $appointment->metadata['is_complex'];
        if ($isComplex && $penaltyPercent > 0 && $penaltyPercent < 100) {
            $penaltyPercent += 10;
        }

        // 4. Проверка на повторные no-show (+20% штраф)
        $noShowCount = DB::table('appointments')
            ->where('client_id', $appointment->client_id)
            ->where('status', Appointment::STATUS_NO_SHOW)
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        if ($noShowCount > 1 && $penaltyPercent > 0 && $penaltyPercent < 100) {
            $penaltyPercent = min(100, $penaltyPercent + 20);
        }

        // 5. Специальные правила для B2B (через metadata или бизнес-группу)
        $isB2B = $appointment->metadata['is_b2b'] ?? false;
        if ($isB2B && $penaltyPercent === 0) {
            // В B2B всегда удерживаем 5% за банковский эквайринг при отмене
            $penaltyPercent = 5;
        }

        // Финализация суммы
        $penaltyAmount = (int)($totalPrice * ($penaltyPercent / 100));
        $refundAmount  = max(0, $totalPrice - $penaltyAmount);

        Log::channel('audit')->info('Penalty calculated for appointment cancellation', [
            'appointment_id' => $appointment->id,
            'hours_before' => $hoursBefore,
            'is_group' => $appointment->is_group,
            'policy' => $policy->value,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => $penaltyAmount,
            'refund_amount' => $refundAmount,
            'correlation_id' => $appointment->correlation_id,
        ]);

        return [
            'penalty_amount' => $penaltyAmount,
            'refund_amount'  => $refundAmount,
            'penalty_percent'=> (int)$penaltyPercent,
            'reason'         => "Policy: {$policy->value}, Complex: " . ($isComplex ? 'yes' : 'no') . ", Group: " . ($appointment->is_group ? 'yes' : 'no'),
        ];
    }
}
