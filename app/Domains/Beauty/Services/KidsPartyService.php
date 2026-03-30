<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsPartyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Рассчитывает параметры штрафов и предоплат для детского праздника.
         *
         * @param Appointment $appointment
         * @param string $action ("cancel", "reschedule", "prepayment")
         * @return array ["multiplier" => float, "fee_percent" => int, "prepayment" => int]
         */
        public function calculateKidsPartyFees(Appointment $appointment, string $action): array
        {
            if (!$appointment->is_kids_party) {
                return [
                    "multiplier" => 1.0,
                    "fee_percent" => 0,
                    "prepayment" => 0
                ];
            }

            $now = Carbon::now();
            $start = Carbon::parse($appointment->datetime_start);
            $diffInDays = $now->diffInDays($start, false);
            $diffInHours = $now->diffInHours($start, false);

            // Таблица штрафов для детских праздников (Kids Party Canon 2026)
            $feePercent = match (true) {
                $diffInDays > 7 => 0,
                $diffInDays >= 3 => 15,
                $diffInHours >= 48 => 30, // 48–72 часа
                $diffInHours >= 24 => 50, // 24–48 часов
                default => 80, // < 24 часов
            };

            // Увеличение штрафа при использовании AI Look (+10%)
            if (isset($appointment->metadata["ai_look_id"]) || ($appointment->tags["ai_generated"] ?? false)) {
                $feePercent = min(100, $feePercent + 10);
            }

            // Групповой множитель (влияет на итоговый расчет штрафа)
            $multiplier = match (true) {
                $appointment->kids_count >= 10 => 1.4,
                $appointment->kids_count >= 6  => 1.25,
                $appointment->kids_count >= 4  => 1.15,
                default => 1.0,
            };

            // Логика обязательной предоплаты
            $prepayment = match (true) {
                $appointment->kids_count >= 8 => 75,
                $appointment->kids_count >= 4 => 50,
                default => 0,
            };

            Log::channel("audit")->info("Kids party fees calculated", [
                "appointment_id" => $appointment->id,
                "action"         => $action,
                "fee_percent"    => $feePercent,
                "multiplier"     => $multiplier,
                "prepayment"     => $prepayment,
                "correlation_id" => $appointment->correlation_id,
            ]);

            return [
                "multiplier"  => $multiplier,
                "fee_percent" => $feePercent,
                "prepayment"  => $prepayment,
            ];
        }
}
