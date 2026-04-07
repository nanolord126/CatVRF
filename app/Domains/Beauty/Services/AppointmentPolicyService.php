<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Enums\ForceMajeureParty;
use App\Enums\ForceMajeureType;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

final readonly class AppointmentPolicyService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
    ) { }
    /**
     * Рассчитывает штраф при отмене бронирования.
     * Возвращает 0%, если подтвержден форс-мажор.
     */
    public function calculateCancellationFee(Appointment $appointment, string $correlationId): array
    {
        $this->logger->info('Policy 13.0 cancellation calculation', [
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
                $aiModifier = in_array($appointment->appointment_type, ['wedding', 'corporate', 'photo_session'], true) ? 20 : 15;
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
     * @param Appointment $appointment
     * @param ForceMajeureType $type
     * @param ForceMajeureParty $party
     * @param array $proof
     * @param string $correlationId
     * @return Appointment
     */
    public function handleForceMajeure(
        Appointment $appointment,
        ForceMajeureType $type,
        ForceMajeureParty $party,
        array $proof,
        string $correlationId
    ): Appointment {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'force_majeure',
            amount: 0,
            correlationId: $correlationId,
        );
        return $this->db->transaction(function () use ($appointment, $type, $party, $proof, $correlationId): Appointment {
            $this->logger->info('Handling force majeure', [
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
                'force_majeure_at' => Carbon::now(),
                'status' => 'cancelled',
                'cancelled_by' => $party->value,
                'compensation_amount' => $compensationAmount,
                'compensation_type' => $compensationType,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Force majeure handled with compensation', [
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
    private function getCompensationRule(ForceMajeureType $type, ForceMajeureParty $party): array
    {
        return match ($type) {
            // Стихийные бедствия / Война / ЧС — Полный возврат всем, без штрафов
            ForceMajeureType::NATURAL_DISASTER,
            ForceMajeureType::MILITARY_EMERGENCY => [
                'compensation_percent' => 100,
                'compensation_type' => 'full_refund',
            ],

            // Отключение коммуникаций в салоне — Салон виноват, клиенту 100% + 15% бонусами за неудобства
            ForceMajeureType::UTILITY_FAILURE => [
                'compensation_percent' => 115,
                'compensation_type' => 'refund_with_bonus',
            ],

            // Болезнь мастера — Салон виноват, клиенту 100% + 25% бонусом
            ForceMajeureType::STAFF_ILLNESS => [
                'compensation_percent' => 125,
                'compensation_type' => 'refund_with_high_bonus',
            ],

            // Болезнь/Госпитализация клиента или смерть родственника — Клиенту 100% возврат (лояльность)
            ForceMajeureType::CLIENT_ILLNESS,
            ForceMajeureType::BEREAVEMENT => [
                'compensation_percent' => 100,
                'compensation_type' => 'full_refund_loyalty',
            ],

            // Закрытие госорганами — Платформа берет на себя 50% компенсации мастеру
            ForceMajeureType::GOVERNMENT_ACTION => [
                'compensation_percent' => 100,
                'compensation_type' => 'government_shutdown_refund',
            ],

            // Сбой платформы — Платформа платит 100% клиенту и 50% мастеру за простой
            ForceMajeureType::PLATFORM_FAILURE => [
                'compensation_percent' => 150, // 100 клиенту + 50 мастеру (условно в общей сумме компенсации)
                'compensation_type' => 'platform_failure_refund',
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
        if (in_array($appointment->appointment_type, $specialCategories, true) && $penaltyPercent > 0) {
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
            ->where('business_cancelled_at', '>=', Carbon::now()->subDays(30))
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
        return $this->db->transaction(function () use ($appointment, $reason, $correlationId): Appointment {
            // 1. Скорринг и расчет
            $penaltyData = $this->calculateBusinessPenalty($appointment, $correlationId);

            // 2. Обновление записи
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_by' => 'provider',
                'is_unjustified_cancellation' => true,
                'business_cancelled_at' => Carbon::now(),
                'business_penalty_amount' => $penaltyData['penalty_amount_kopeks'],
                'client_compensation_bonus' => $penaltyData['client_bonus_amount_kopeks'],
                'business_penalty_status' => 'pending',
                'tags' => collect($appointment->tags)->merge([
                    'cancellation_reason' => $reason,
                    'is_repeat_offender' => $penaltyData['recurrence_modifier_percent'] > 0
                ]),
                'correlation_id' => $correlationId,
            ]);

            // 4. Логируем санкции
            $this->logger->warning('Business cancellation penalty applied', [
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
