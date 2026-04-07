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
 * WeddingEventService — управление свадебными бронированиями.
 *
 * Рассчитывает штрафы при отмене и создаёт свадебные записи.
 * Штрафная сетка: >30 дней 0% → 14–30 дней 20% → 7–14 дней 45% → 3–7 дней 70% → <3 дней 100%.
 * AI Look добавляет +15% к штрафу.
 */
final readonly class WeddingEventService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private FraudControlService $fraud,
    ) {
    }

    /**
     * Рассчитать штрафы при отмене свадебного бронирования.
     *
     * @return array{appointment_id: int, days_before: int, base_penalty_percent: int, has_ai_look: bool, fee_amount: int}
     */
    public function calculateCancellationFee(BeautyAppointment $appointment, string $correlationId): array
    {
        $this->logger->info('Wedding cancellation fee calculation started', [
            'appointment_id' => $appointment->id,
            'correlation_id' => $correlationId,
        ]);

        if (!$appointment->is_wedding_event) {
            throw new \InvalidArgumentException('Appointment is not marked as wedding_event.');
        }

        $now = Carbon::now();
        $eventDate = Carbon::parse($appointment->start_at);
        $daysBefore = $now->diffInDays($eventDate, false);

        $basePenaltyPercent = match (true) {
            $daysBefore >= 30 => 0,
            $daysBefore >= 14 => 20,
            $daysBefore >= 7 => 45,
            $daysBefore >= 3 => 70,
            default => 100,
        };

        $hasAiLook = ($appointment->tags['ai_look'] ?? false) === true;

        if ($hasAiLook && $basePenaltyPercent < 100) {
            $basePenaltyPercent = min(100, $basePenaltyPercent + 15);
        }

        $feeAmount = (int) ($appointment->price_cents * ($basePenaltyPercent / 100));

        return [
            'appointment_id' => $appointment->id,
            'days_before' => $daysBefore,
            'base_penalty_percent' => $basePenaltyPercent,
            'has_ai_look' => $hasAiLook,
            'fee_amount' => $feeAmount,
        ];
    }

    /**
     * Создать свадебное бронирование с проверкой фрода.
     */
    public function createWeddingAppointment(array $data, string $correlationId): BeautyAppointment
    {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_wedding_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): BeautyAppointment {
            $appointment = BeautyAppointment::create(array_merge($data, [
                'is_wedding_event' => true,
                'correlation_id' => $correlationId,
            ]));

            $this->logger->info('Wedding appointment created', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }
}
