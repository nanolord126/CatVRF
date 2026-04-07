<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BathsSaunas\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Beauty\BathsSaunas\Models\BathBooking;
use App\Domains\Beauty\BathsSaunas\Models\Bathhouse;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * BathsSaunasService — сервис управления бронированием бань и саун.
 *
 * Все суммы в копейках. Без статических фасадов.
 * Идентификатор клиента и tenant передаются явно через параметры.
 */
final readonly class BathsSaunasService
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
     * Создаёт бронирование бани.
     *
     * @throws \RuntimeException При блокировке фрода.
     */
    public function createBooking(
        int    $bathId,
        string $bookingDate,
        int    $durationHours,
        string $bathType,
        int    $clientId,
        string $tenantId,
        string $correlationId = '',
    ): BathBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        $fraudCheck = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'bath_booking', amount: 0, correlationId: $correlationId ?? '');

        if (($fraudCheck['decision'] ?? '') === 'block') {
            throw new \RuntimeException('Операция заблокирована службой безопасности.', 403);
        }

        return $this->db->transaction(function () use (
            $bathId, $bookingDate, $durationHours,
            $bathType, $clientId, $tenantId, $correlationId,
        ): BathBooking {
            $bathhouse = Bathhouse::withoutGlobalScopes()->findOrFail($bathId);

            $total  = $bathhouse->calculatePrice($durationHours);
            $payout = (int) ($total * (1 - self::PLATFORM_COMMISSION));

            $booking = BathBooking::create([
                'uuid'           => Uuid::uuid4()->toString(),
                'tenant_id'      => $tenantId,
                'bath_id'        => $bathId,
                'client_id'      => $clientId,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'booking_date'   => $bookingDate,
                'duration_hours' => $durationHours,
                'bath_type'      => $bathType,
                'tags'           => ['bath' => true],
            ]);

            $this->auditLogger->info('Bath booking created.', [
                'booking_id'    => $booking->id,
                'correlation_id'=> $correlationId,
                'total_kopecks' => $total,
            ]);

            return $booking;
        });
    }

    /**
     * Завершает бронирование и начисляет выплату.
     *
     * @throws \RuntimeException Если бронирование не оплачено.
     */
    public function completeBooking(
        int    $bookingId,
        string $tenantId,
        string $correlationId = '',
    ): BathBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($bookingId, $tenantId, $correlationId): BathBooking {
            $booking = BathBooking::withoutGlobalScopes()->findOrFail($bookingId);

            if (! $booking->isPaid()) {
                throw new \RuntimeException('Бронирование не оплачено — завершение невозможно.', 400);
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
                    'booking_id'    => $booking->id,
                    'correlation_id'=> $correlationId,
                ],
            );

            return $booking;
        });
    }

    /**
     * Отменяет бронирование с возвратом при необходимости.
     *
     * @throws \RuntimeException Если бронирование уже завершено.
     */
    public function cancelBooking(
        int    $bookingId,
        string $tenantId,
        string $correlationId = '',
    ): BathBooking {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($bookingId, $tenantId, $correlationId): BathBooking {
            $booking = BathBooking::withoutGlobalScopes()->findOrFail($bookingId);

            if (! $booking->isCancellable()) {
                throw new \RuntimeException('Бронирование уже завершено — отмена невозможна.', 400);
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
                        'booking_id'    => $booking->id,
                        'correlation_id'=> $correlationId,
                    ],
                );
            }

            return $booking;
        });
    }

    /**
     * Возвращает историю бронирований клиента (последние 10).
     *
     * @return Collection<int, BathBooking>
     */
    public function getClientBookings(int $clientId): Collection
    {
        return BathBooking::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
