<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления корпоративными мероприятиями (Beauty 2026).
 */
final readonly class CorporateEventService
{
    /**
     * Рассчитывает параметры штрафов и предоплат для корпоративного мероприятия.
     * 
     * @param Appointment $appointment
     * @param string $action ("cancel", "reschedule", "prepayment")
     * @return array ["multiplier" => float, "fee_percent" => int, "prepayment" => int]
     */
    public function calculateCorporateFees(Appointment $appointment, string $action): array
    {
        if (!$appointment->is_corporate_event) {
            return [
                "multiplier" => 1.0,
                "fee_percent" => 0,
                "prepayment" => 0
            ];
        }

        $now = Carbon::now();
        $start = Carbon::parse($appointment->datetime_start);
        $diffInHours = $now->diffInHours($start, false);
        $diffInDays = $now->diffInDays($start, false);

        // Таблица штрафов для корпоратов (Corporate Canon 2026)
        $feePercent = match (true) {
            $diffInDays >= 10 => 0,
            $diffInDays >= 7  => 10,
            $diffInDays >= 3  => 25,
            $diffInHours >= 48 => 40, // 48–72 часа
            default => 70, // < 48 часов
        };

        // Увеличение штрафа при использовании AI Look (+10%)
        if (isset($appointment->metadata["ai_look_id"]) || ($appointment->tags["ai_generated"] ?? false)) {
            $feePercent = min(100, $feePercent + 10);
        }

        // Групповой множитель сложности для корпоратов
        $multiplier = match (true) {
            $appointment->participants_count >= 20 => 1.6,
            $appointment->participants_count >= 10 => 1.35,
            $appointment->participants_count >= 5  => 1.2,
            default => 1.1,
        };

        // Логика предоплаты корпоратов (от 30% до 70%)
        $prepayment = match (true) {
            $appointment->participants_count >= 30 => 70,
            $appointment->participants_count >= 15 => 50,
            $appointment->participants_count >= 5  => 30,
            default => 20,
        };

        Log::channel("audit")->info("Corporate event fees calculated", [
            "appointment_id"    => $appointment->id,
            "action"            => $action,
            "participants"      => $appointment->participants_count,
            "fee_percent"       => $feePercent,
            "multiplier"        => $multiplier,
            "prepayment_needed" => $prepayment,
            "correlation_id"    => $appointment->correlation_id,
        ]);

        return [
            "multiplier"  => $multiplier,
            "fee_percent" => $feePercent,
            "prepayment"  => $prepayment,
        ];
    }
}
