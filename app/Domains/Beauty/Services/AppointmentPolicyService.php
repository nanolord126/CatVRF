<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Enums\AppointmentType;
use App\Enums\AppointmentCancellationPolicy;
use App\Enums\AppointmentReschedulePolicy;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AppointmentPolicyService (Canon 2026 ЛЮТЫЙ РЕЖИМ 13.0)
 * Единая централизованная система правил отмен, переносов, штрафов и ФОРС-МАЖОРА.
 */
final readonly class AppointmentPolicyService
{
    /**
     * Рассчитывает штраф при отмене бронирования.
     * Возвращает 0%, если подтвержден форс-мажор.
     */
    public function calculateCancellationFee(Appointment $appointment, string $correlationId): array
    {
        Log::channel('audit')->info('Policy 13.0 cancellation calculation', [
            'appointment_id' => $appointment->id,
            'correlation_id' => $correlationId,
        ]);

        // Если форс-мажор УЖЕ подтвержден (админом или системой)
        if ($appointment->is_force_majeure) {
            return $this->buildForceMajeureResult($appointment, $correlationId);
        }

        $now = Carbon::now();
        $eventDate = Carbon::parse($appointment->datetime_start);
        $hoursBefore = $now->diffInHours($eventDate, false);
        $daysBefore = $now->diffInDays($eventDate, false);

        // 1. Базовая политика
        $basePenaltyPercent = $this->getBasePenaltyByPolicy($appointment, $daysBefore, $hoursBefore);

        // 2. AI Look Modifier (13.0: +15-20%)
        $aiModifier = 0;
        if ($basePenaltyPercent > 0 && $basePenaltyPercent < 100) {
            $hasAiLook = ($appointment->tags['ai_look'] ?? false) === true || $appointment->is_ai_constructed;
            if ($hasAiLook) {
                // +20% для сложных типов, +15% для стандартных
                $aiModifier = in_array($appointment->appointment_type, ['wedding', 'corporate', 'photo_session']) ? 20 : 15;
            }
        }

        $finalPenaltyPercent = min(100, $basePenaltyPercent + $aiModifier);

        // 3. Модификатор группы (B2B / Большие группы)
        // Удержание комиссии платформы для B2B всегда минимум 10%
        if (!empty($appointment->corporate_client_id) && $finalPenaltyPercent < 10) {
            $finalPenaltyPercent = 10;
        }

        $feeAmount = (int) ($appointment->price * ($finalPenaltyPercent / 100));

        return [
            'appointment_id' => $appointment->id,
            'event_type' => $appointment->appointment_type,
            'cancellation_policy' => $appointment->cancellation_policy,
            'hours_before' => $hoursBefore,
            'days_before' => $daysBefore,
            'base_penalty_percent' => $basePenaltyPercent,
            'ai_modifier' => $aiModifier,
            'final_penalty_percent' => $finalPenaltyPercent,
            'fee_amount_kopeks' => $feeAmount,
            'is_force_majeure' => false,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Обработка подтвержденного форс-мажора (13.0 - Детальная компенсация).
     *
     * @param \App\Domains\Beauty\Models\Appointment $appointment
     * @param \App\Enums\ForceMajeureType $type
     * @param \App\Enums\ForceMajeureParty $party
     * @param array $proof
     * @param string $correlationId
     * @return \App\Domains\Beauty\Models\Appointment
     */
    public function handleForceMajeure(
        Appointment $appointment,
        \App\Enums\ForceMajeureType $type,
        \App\Enums\ForceMajeureParty $party,
        array $proof,
        string $correlationId
    ): Appointment {
        return DB::transaction(function () use ($appointment, $type, $party, $proof, $correlationId) {
            Log::channel('audit')->info('Handling force majeure', [
                'appointment_id' => $appointment->id,
                'type' => $type->value,
                'party' => $party->value,
                'correlation_id' => $correlationId,
            ]);

            // 1. Рассчитываем компенсации по таблице правил 2026
            $rule = $this->getCompensationRule($type, $party);

            $compensationAmount = (int)($appointment->price * ($rule['compensation_percent'] / 100));
            $compensationType = $rule['compensation_type'];

            // 2. Обновляем статус записи
            $appointment->update([
                'is_force_majeure' => true,
                'force_majeure_type' => $type,
                'force_majeure_party' => $party,
                'force_majeure_proof' => $proof,
                'force_majeure_at' => now(),
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_by' => $party->value,
                'compensation_amount' => $compensationAmount,
                'compensation_type' => $compensationType,
                'correlation_id' => $correlationId,
            ]);

            // 3. Вызываем WalletService для проведения выплат (в реальности здесь будет вызов WalletService)
            // if ($compensationAmount > 0) { ... }

            Log::channel('audit')->info('Force majeure handled with compensation', [
                'appointment_id' => $appointment->id,
                'compensation_amount' => $compensationAmount,
                'compensation_type' => $compensationType,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Таблица правил компенсаций при форс-мажоре (2026).
     */
    private function getCompensationRule(\App\Enums\ForceMajeureType $type, \App\Enums\ForceMajeureParty $party): array
    {
        return match ($type) {
            // Стихийные бедствия / Война / ЧС — Полный возврат всем, без штрафов
            \App\Enums\ForceMajeureType::NATURAL_DISASTER,
            \App\Enums\ForceMajeureType::MILITARY_EMERGENCY => [
                'compensation_percent' => 100,
                'compensation_type' => 'full_refund',
            ],

            // Отключение коммуникаций в салоне — Салон виноват, клиенту 100% + 15% бонусами за неудобства
            \App\Enums\ForceMajeureType::UTILITY_FAILURE => [
                'compensation_percent' => 115,
                'compensation_type' => 'refund_with_bonus',
            ],

            // Болезнь мастера — Салон виноват, клиенту 100% + 25% бонусом
            \App\Enums\ForceMajeureType::STAFF_ILLNESS => [
                'compensation_percent' => 125,
                'compensation_type' => 'refund_with_high_bonus',
            ],

            // Болезнь/Госпитализация клиента или смерть родственника — Клиенту 100% возврат (лояльность)
            \App\Enums\ForceMajeureType::CLIENT_ILLNESS,
            \App\Enums\ForceMajeureType::BEREAVEMENT => [
                'compensation_percent' => 100,
                'compensation_type' => 'full_refund_loyalty',
            ],

            // Закрытие госорганами — Платформа берет на себя 50% компенсации мастеру
            \App\Enums\ForceMajeureType::GOVERNMENT_ACTION => [
                'compensation_percent' => 100,
                'compensation_type' => 'government_shutdown_refund',
            ],

            // Сбой платформы — Платформа платит 100% клиенту и 50% мастеру за простой
            \App\Enums\ForceMajeureType::PLATFORM_FAILURE => [
                'compensation_percent' => 150, // 100 клиенту + 50 мастеру (условно в общей сумме компенсации)
                'compensation_type' => 'platform_error_compensation',
            ],

            default => [
                'compensation_percent' => 100,
                'compensation_type' => 'standard_refund',
            ],
        };
    }

    /**
     * Рассчитывает штраф для бизнеса при отмене БЕЗ уважительной причины (Canon 2026).
     *
     * @param Appointment $appointment
     * @param string $correlationId
     * @return array Результат расчета штрафа
     */
    public function calculateBusinessPenalty(Appointment $appointment, string $correlationId): array
    {
        $now = Carbon::now();
        $startTime = Carbon::parse($appointment->datetime_start);
        $hoursBefore = (float) $now->diffInHours($startTime, false);

        // 1. Базовая ставка штрафа (от стоимости услуги)
        $penaltyPercent = match (true) {
            $hoursBefore >= 48 => 0,
            $hoursBefore >= 24 => 20,
            $hoursBefore >= 12 => 40,
            $hoursBefore >= 4  => 70,
            default           => 100, // Менее 4 часов или No-Show
        };

        // 2. Модификаторы типов (Свадьбы, Фотосессии, Детские праздники) +30% к штрафу
        $typeModifier = 0;
        $specialCategories = ['wedding', 'photo_session', 'kids_party'];
        if (in_array($appointment->appointment_type, $specialCategories) && $penaltyPercent > 0) {
            $typeModifier = 30;
        }

        // 3. Дополнительные компенсации клиенту (от стоимости)
        $clientBonusPercent = 0;
        if ($hoursBefore < 12) {
            $clientBonusPercent = 15; // Правило: менее чем за 12 часов — клиент получает +15%
        }
        if ($hoursBefore < 4) {
            $clientBonusPercent = 10; // Исходя из таблицы: "Менее 4 часов — 100% + 10% компенсация"
        }

        // 4. Проверка рецидива (Повторная отмена без причины за 30 дней) +50% штрафа
        $recurrenceModifier = 0;
        $recentCancellations = Appointment::where('salon_id', $appointment->salon_id)
            ->where('is_unjustified_cancellation', true)
            ->where('business_cancelled_at', '>=', now()->subDays(30))
            ->count();

        if ($recentCancellations > 0 && $penaltyPercent > 0) {
            $recurrenceModifier = 50;
        }

        // Итоговый процент штрафа
        $finalPenaltyPercent = $penaltyPercent + $typeModifier + $recurrenceModifier;
        $penaltyAmount = (int) ($appointment->price * ($finalPenaltyPercent / 100));
        $clientBonusAmount = (int) ($appointment->price * ($clientBonusPercent / 100));

        return [
            'appointment_id' => $appointment->id,
            'hours_before' => $hoursBefore,
            'base_penalty_percent' => $penaltyPercent,
            'type_modifier_percent' => $typeModifier,
            'recurrence_modifier_percent' => $recurrenceModifier,
            'final_penalty_percent' => $finalPenaltyPercent,
            'penalty_amount_kopeks' => $penaltyAmount,
            'client_bonus_percent' => $clientBonusPercent,
            'client_bonus_amount_kopeks' => $clientBonusAmount,
            'requires_ban_check' => ($recentCancellations >= 3), // Авто-бан при рецидиве более 3-х раз
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Проводит отмену со стороны бизнеса (unjustified) с применением штрафных санкций.
     */
    public function handleBusinessCancellation(Appointment $appointment, string $reason, string $correlationId): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $correlationId) {
            // 1. Скорринг и расчет
            $penaltyData = $this->calculateBusinessPenalty($appointment, $correlationId);

            // 2. Обновление записи
            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_by' => 'provider',
                'is_unjustified_cancellation' => true,
                'business_cancelled_at' => now(),
                'business_penalty_amount' => $penaltyData['penalty_amount_kopeks'],
                'client_compensation_bonus' => $penaltyData['client_bonus_amount_kopeks'],
                'business_penalty_status' => 'pending',
                'tags' => collect($appointment->tags)->merge([
                    'cancellation_reason' => $reason,
                    'is_repeat_offender' => $penaltyData['recurrence_modifier_percent'] > 0
                ]),
                'correlation_id' => $correlationId,
            ]);

            // 3. Вызов WalletService для дебетования баланса салона (штраф)
            // WalletService::debit($appointment->salon_id, $penaltyData['penalty_amount_kopeks'] + $penaltyData['client_bonus_amount_kopeks']);

            // 4. Логируем санкции
            Log::channel('audit')->warning('Business cancellation penalty applied', [
                'salon_id' => $appointment->salon_id,
                'penalty' => $penaltyData,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Рассчитывает сбор за перенос (reschedule) бронирования.
     * При форс-мажоре перенос всегда БЕСПЛАТНЫЙ (0%)!
     */
    public function calculateRescheduleFee(Appointment $appointment, string $correlationId): array
    {
        if ($appointment->is_force_majeure) {
            return [
                'appointment_id' => $appointment->id,
                'can_reschedule' => true,
                'fee_percent' => 0,
                'fee_amount_kopeks' => 0,
                'is_force_majeure' => true,
                'correlation_id' => $correlationId,
            ];
        }

        $policy = $appointment->reschedule_policy;
        $now = Carbon::now();
        $eventDate = Carbon::parse($appointment->datetime_start);
        $hoursBefore = $now->diffInHours($eventDate, false);

        $rescheduleFeePercent = 0;
        $canReschedule = true;

        switch ($policy) {
            case 'unlimited_free':
                $rescheduleFeePercent = 0;
                break;
            case 'once_free_24h':
                $rescheduleFeePercent = ($hoursBefore < 24) ? 20 : 0;
                break;
            case 'once_free_72h':
                $rescheduleFeePercent = ($hoursBefore < 72) ? 30 : 0;
                break;
            case 'once_fixed_fee':
                $rescheduleFeePercent = 15; // Фиксированный сбор 15% за любой перенос
                break;
            case 'no_reschedule':
                $canReschedule = false;
                break;
        }

        $feeAmount = (int) ($appointment->price * ($rescheduleFeePercent / 100));

        return [
            'appointment_id' => $appointment->id,
            'reschedule_policy' => $policy,
            'can_reschedule' => $canReschedule,
            'fee_percent' => $rescheduleFeePercent,
            'fee_amount_kopeks' => $feeAmount,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Определяет базовый процент штрафа на основе политики отмены.
     */
    private function getBasePenaltyByPolicy(Appointment $appointment, int $daysBefore, int $hoursBefore): int
    {
        $policy = $appointment->cancellation_policy;

        return match ($policy) {
            'free' => 0,
            
            'standard' => match (true) {
                $hoursBefore >= 72 => 0,
                $hoursBefore >= 48 => 50,
                default           => 100,
            },

            'strict_30d' => match (true) {
                $daysBefore >= 30 => 0,
                $daysBefore >= 14 => 25,
                $daysBefore >= 7  => 50,
                $hoursBefore >= 72 => 75,
                default           => 100,
            },

            'strict_14d' => match (true) {
                $daysBefore >= 14 => 0,
                $daysBefore >= 7  => 25,
                $daysBefore >= 3  => 50,
                $hoursBefore >= 48 => 75,
                default           => 100,
            },

            'non_refundable' => 100,

            default => 100,
        };
    }

    /**
     * Вспомогательный метод для результата при форс-мажоре.
     */
    private function buildForceMajeureResult(Appointment $appointment, string $correlationId): array
    {
        return [
            'appointment_id' => $appointment->id,
            'final_penalty_percent' => 0, // Форс-мажор: полный возврат клиенту
            'fee_amount_kopeks' => 0,
            'compensation_amount' => $appointment->compensation_amount,
            'is_force_majeure' => true,
            'force_majeure_type' => $appointment->force_majeure_type,
            'cancelled_by' => $appointment->cancelled_by,
            'is_provider_fault' => $appointment->cancelled_by === 'provider',
            'correlation_id' => $correlationId,
        ];
    }
}

            'non_refundable' => 100,

            default => 100,
        };
    }
}
