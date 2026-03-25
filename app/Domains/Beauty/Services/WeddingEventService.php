<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * WeddingEventService (Canon 2026)
 * Управляет логикой специализированных свадебных мероприятий в вертикали Beauty.
 */
final readonly class WeddingEventService
{
    /**
     * Рассчитывает штрафы при отмене свадебного бронирования.
     * Штрафная сетка (за 30 дней):
     * > 30 дней: 0%
     * 14-30 дней: 20%
     * 7-14 дней: 45%
     * 3-7 дней: 70%
     * < 3 дней: 100%
     * + AI Look (ИИ подбор образа): +15% к штрафу.
     */
    public function calculateCancellationFee(Appointment $appointment, string $correlationId): array
    {
        Log::channel('audit')->info('Wedding cancellation fee calculation started', [
            'appointment_id' => $appointment->id,
            'correlation_id' => $correlationId,
        ]);

        if (!$appointment->is_wedding_event) {
             throw new \InvalidArgumentException("Appointment is not marked as wedding_event.");
        }

        $now = Carbon::now();
        $eventDate = Carbon::parse($appointment->datetime_start);
        $daysBefore = $now->diffInDays($eventDate, false);

        // Расчёт базового штрафа по прогрессивной шкале (30-дневная сетка)
        $basePenaltyPercent = match (true) {
            $daysBefore >= 30 => 0,
            $daysBefore >= 14 => 20,
            $daysBefore >= 7  => 45,
            $daysBefore >= 3  => 70,
            default           => 100,
        };

        // Дополнительный штраф за AI Look (+15%)
        // Поле tags (jsonb) используется для хранения флага ai_look по канону 2026
        $hasAiLook = ($appointment->tags['ai_look'] ?? false) === true;
        if ($hasAiLook && $basePenaltyPercent < 100) {
            $basePenaltyPercent += 15;
            // Кап на 100%
            if ($basePenaltyPercent > 100) {
                $basePenaltyPercent = 100;
            }
        }

        $feeAmount = (int) ($appointment->price * ($basePenaltyPercent / 100));

        return [
            'appointment_id' => $appointment->id,
            'days_before' => $daysBefore,
            'base_penalty_percent' => $basePenaltyPercent,
            'has_ai_look' => $hasAiLook,
            'fee_amount' => $feeAmount, // в копейках (int)
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создание свадебного бронирования с проверкой фрода.
     */
    public function createWeddingAppointment(array $data, string $correlationId): Appointment
    {
        FraudControlService::check($data); // Canon 2026

        return DB::transaction(function () use ($data, $correlationId) {
            $appointment = Appointment::create(array_merge($data, [
                'is_wedding_event' => true,
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Wedding appointment created', [
                'appointment_id' => $appointment->id,
                'bride_name' => $appointment->bride_name,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }
}
