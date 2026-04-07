<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * PhotoSessionService — расчёт штрафов и создание фотосессий.
 *
 * Штрафная сетка (14-дневная): >14 дней 0% → 7–14 25% → 3–7 50% → 48–72ч 75% → <48ч 100%.
 * AI Look добавляет +20%.
 */
final readonly class PhotoSessionService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
    ) {
    }

    /**
     * Рассчитать сборы и штрафы для фотосессий.
     *
     * @return array{appointment_id: int, type: string|null, hours_before: int, days_before: int, base_penalty_percent: int, has_ai_look: bool, fee_amount_kopeks: int, min_prepayment_percent: int, correlation_id: string, is_b2b: bool}
     */
    public function calculatePhotoSessionFees(BeautyAppointment $appointment, string $action, string $correlationId): array
    {
        $this->logger->info("Photo session fee calculation [{$action}]", [
            'appointment_id' => $appointment->id,
            'correlation_id' => $correlationId,
        ]);

        if (!$appointment->is_photo_session) {
            throw new \InvalidArgumentException('Appointment is not marked as photo_session.');
        }

        $now = Carbon::now();
        $eventDate = Carbon::parse($appointment->start_at);
        $hoursBefore = $now->diffInHours($eventDate, false);
        $daysBefore = $now->diffInDays($eventDate, false);

        $basePenaltyPercent = match (true) {
            $daysBefore >= 14 => 0,
            $daysBefore >= 7 => 25,
            $daysBefore >= 3 => 50,
            $hoursBefore >= 48 => 75,
            default => 100,
        };

        $hasAiLook = ($appointment->tags['ai_look'] ?? false) === true;

        if ($hasAiLook && $basePenaltyPercent > 0 && $basePenaltyPercent < 100) {
            $basePenaltyPercent = min(100, $basePenaltyPercent + 20);
        }

        $minPrepaymentPercent = match (true) {
            $daysBefore >= 14 => 30,
            $daysBefore >= 7 => 50,
            default => 100,
        };

        $feeAmount = (int) ($appointment->price_cents * ($basePenaltyPercent / 100));

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
    public function createPhotoSession(array $data, string $correlationId): BeautyAppointment
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_photo_session',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): BeautyAppointment {
            $appointment = BeautyAppointment::create(array_merge($data, [
                'is_photo_session' => true,
                'correlation_id' => $correlationId,
            ]));

            $this->logger->info('Photo session created', [
                'appointment_id' => $appointment->id,
                'type' => $appointment->photo_session_type,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }
}
