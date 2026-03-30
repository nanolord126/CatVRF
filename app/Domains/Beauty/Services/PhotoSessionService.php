<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PhotoSessionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Рассчитывает сборы и штрафы для фотосессий.
         * Штрафная сетка (за 14 дней):
         * > 14 дней: 0%
         * 7-14 дней: 25%
         * 3-7 дней: 50%
         * 48-72 часа (2-3 дня): 75%
         * < 48 часов: 100%
         * + AI Look (ИИ подбор образа): +20% к штрафу.
         *
         * Предоплата: от 30% до 100% (динамический расчет).
         */
        public function calculatePhotoSessionFees(Appointment $appointment, string $action, string $correlationId): array
        {
            Log::channel('audit')->info("Photo session fee calculation [{$action}]", [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
            ]);

            if (!$appointment->is_photo_session) {
                throw new \InvalidArgumentException("Appointment is not marked as photo_session.");
            }

            $now = Carbon::now();
            $eventDate = Carbon::parse($appointment->datetime_start);
            $hoursBefore = $now->diffInHours($eventDate, false);
            $daysBefore = $now->diffInDays($eventDate, false);

            // Расчет штрафа по специализированной сетке (14-дневная)
            $basePenaltyPercent = match (true) {
                $daysBefore >= 14 => 0,
                $daysBefore >= 7  => 25,
                $daysBefore >= 3  => 50,
                $hoursBefore >= 48 => 75,
                default           => 100,
            };

            // Дополнительный штраф за AI Look (+20%)
            $hasAiLook = ($appointment->tags['ai_look'] ?? false) === true;
            if ($hasAiLook && $basePenaltyPercent > 0 && $basePenaltyPercent < 100) {
                $basePenaltyPercent += 20;
                if ($basePenaltyPercent > 100) {
                    $basePenaltyPercent = 100;
                }
            }

            // Предоплата (логика: чем меньше дней до события, тем выше минимальная предоплата)
            $minPrepaymentPercent = match (true) {
                $daysBefore >= 14 => 30,
                $daysBefore >= 7  => 50,
                default           => 100,
            };

            $feeAmount = (int) ($appointment->price * ($basePenaltyPercent / 100));

            return [
                'appointment_id' => $appointment->id,
                'type' => $appointment->photo_session_type,
                'hours_before' => $hoursBefore,
                'days_before' => $daysBefore,
                'base_penalty_percent' => $basePenaltyPercent,
                'has_ai_look' => $hasAiLook,
                'fee_amount_kopeks' => $feeAmount,
                'min_prepayment_percent' => $minPrepaymentPercent,
                'correlation_id' => $correlationId,
                'is_b2b' => !empty($appointment->business_group_id),
            ];
        }

        /**
         * Создание фотосессии с транзакцией и аудитом.
         */
        public function createPhotoSession(array $data, string $correlationId): Appointment
        {
            FraudControlService::check($data);

            return DB::transaction(function () use ($data, $correlationId) {
                $appointment = Appointment::create(array_merge($data, [
                    'is_photo_session' => true,
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('Photo session created', [
                    'appointment_id' => $appointment->id,
                    'type' => $appointment->photo_session_type,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        }
}
