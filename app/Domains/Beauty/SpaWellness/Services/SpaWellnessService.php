<?php

declare(strict_types=1);

namespace App\Domains\Beauty\SpaWellness\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Beauty\SpaWellness\Models\SpaBooking;
use App\Domains\Beauty\SpaWellness\Models\SpaCenter;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * SpaWellnessService — сервис управления бронированием СПА-процедур.
 *
 * Все суммы в копейках. Без статических фасадов.
 * clientId и tenantId передаются явно через параметры.
 */
final readonly class SpaWellnessService
{
    private const PLATFORM_COMMISSION = 0.14;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $walletService,
        private LoggerInterface $auditLogger,
        private \Illuminate\Database\DatabaseManager $db,
        private Guard $guard,
    ) {}

    /**
     * Создаёт бронирование СПА-процедуры.
     *
     * @throws \RuntimeException При блокировке фрода.
     */
    public function createBooking(
        int    $spaId,
        string $treatmentType,
        int    $durationMinutes,
        string $bookingDate,
        int    $clientId,
        string $tenantId,
        string $correlationId = '',
    ): SpaBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        $fraudCheck = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'spa_booking', amount: 0, correlationId: $correlationId ?? '');

        if (($fraudCheck['decision'] ?? '') === 'block') {
            throw new \RuntimeException('Операция заблокирована службой безопасности.', 403);
        }

        return $this->db->transaction(function () use (
            $spaId, $treatmentType, $durationMinutes,
            $bookingDate, $clientId, $tenantId, $correlationId,
        ): SpaBooking {
            $center = SpaCenter::withoutGlobalScopes()->findOrFail($spaId);

            $total  = $center->calculatePrice($durationMinutes);
            $payout = (int) ($total * (1 - self::PLATFORM_COMMISSION));

            $booking = SpaBooking::create([
                'uuid'             => Uuid::uuid4()->toString(),
                'tenant_id'        => $tenantId,
                'spa_center_id'    => $spaId,
                'client_id'        => $clientId,
                'correlation_id'   => $correlationId,
                'status'           => 'pending_payment',
                'total_kopecks'    => $total,
                'payout_kopecks'   => $payout,
                'payment_status'   => 'pending',
                'booking_date'     => $bookingDate,
                'duration_minutes' => $durationMinutes,
                'treatment_type'   => $treatmentType,
                'tags'             => ['spa' => true],
            ]);

            $this->auditLogger->info('SPA booking created.', [
                'booking_id'     => $booking->id,
                'correlation_id' => $correlationId,
                'total_kopecks'  => $total,
            ]);

            return $booking;
        });
    }

    /**
     * Завершает бронирование и зачисляет выплату.
     *
     * @throws \RuntimeException Если бронирование не оплачено.
     */
    public function completeBooking(
        int    $bookingId,
        string $tenantId,
        string $correlationId = '',
    ): SpaBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($bookingId, $tenantId, $correlationId): SpaBooking {
            $booking = SpaBooking::withoutGlobalScopes()->findOrFail($bookingId);

            if (! $booking->isPaid()) {
                throw new \RuntimeException('СПА-бронирование не оплачено — завершение невозможно.', 400);
            }

            $booking->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->walletService->credit(
                $tenantId,
                $booking->payout_kopecks,
                \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT,
                $correlationId,
                null,
                null,
                [
                    'booking_id'     => $booking->id,
                    'correlation_id' => $correlationId,
                ],
            );

            return $booking;
        });
    }

    /**
     * Отменяет бронирование с возвратом средств при необходимости.
     *
     * @throws \RuntimeException Если бронирование уже завершено.
     */
    public function cancelBooking(
        int    $bookingId,
        string $tenantId,
        string $correlationId = '',
    ): SpaBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($bookingId, $tenantId, $correlationId): SpaBooking {
            $booking = SpaBooking::withoutGlobalScopes()->findOrFail($bookingId);

            if (! $booking->isCancellable()) {
                throw new \RuntimeException('СПА-бронирование завершено — отмена невозможна.', 400);
            }

            $booking->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($booking->isPaid()) {
                $this->walletService->credit(
                    $tenantId,
                    $booking->total_kopecks,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                    $correlationId,
                    null,
                    null,
                    [
                        'booking_id'     => $booking->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $booking;
        });
    }

    /**
     * Возвращает историю бронирований клиента (последние 10).
     *
     * @return Collection<int, SpaBooking>
     */
    public function getClientBookings(int $clientId): Collection
    {
        return SpaBooking::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}

