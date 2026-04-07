<?php

declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Auto\CarWashing\Models\CarWashStation;
use App\Domains\Auto\CarWashing\Models\WashingOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Сервис управления заказами на мойку автомобилей.
 *
 * Комиссия платформы: 14% от стоимости услуги.
 * Все операции в $this->db->transaction(). FraudControlService перед каждой мутацией.
 *
 * @package App\Domains\Auto\CarWashing\Services
 */
final readonly class CarWashingService
{
    private const COMMISSION_RATE   = 0.14;
    private const RATE_LIMIT_KEY    = 'carwash:order';
    private const RATE_LIMIT_MAX    = 30;
    private const RATE_LIMIT_DECAY  = 3600;

    public function __construct(private FraudControlService $fraud,
        private WalletService       $wallet,
        private RateLimiter         $rateLimiter,
        private LoggerInterface     $auditLogger,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Guard $guard) {

    }

    /**
     * Создать заказ на мойку.
     *
     * @throws \RuntimeException если превышен лимит запросов или заблокирован fraud
 */
    public function createOrder(
        int    $stationId,
        mixed  $bookingDate,
        string $serviceType,
        int    $clientId,
        string $correlationId = '',
    ): WashingOrder {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        $key = self::RATE_LIMIT_KEY . ':' . $clientId;
        if ($this->rateLimiter->tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            throw new \RuntimeException('Превышен лимит запросов.', 429);
        }
        $this->rateLimiter->hit($key, self::RATE_LIMIT_DECAY);

        return $this->db->transaction(function () use ($stationId, $bookingDate, $serviceType, $clientId, $correlationId): WashingOrder {
            $station = CarWashStation::findOrFail($stationId);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'car_washing', amount: 0, correlationId: $correlationId ?? '');

            if (($fraudResult['decision'] ?? 'allow') === 'block') {
                throw new \RuntimeException('Операция заблокирована системой безопасности.', 403);
            }

            $payout = (int) ($station->price_kopecks_per_service * (1 - self::COMMISSION_RATE));

            $order = WashingOrder::create([
                'uuid'           => Uuid::uuid4()->toString(),
                'tenant_id'      => tenant()->id,
                'station_id'     => $stationId,
                'client_id'      => $clientId,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $station->price_kopecks_per_service,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'booking_date'   => $bookingDate,
                'service_type'   => $serviceType,
                'tags'           => ['carwash' => true],
            ]);

            $this->auditLogger->info('Car wash order created', [
                'order_id'       => $order->id,
                'station_id'     => $stationId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и зачислить выплату на кошелёк станции.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): WashingOrder
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): WashingOrder {
            $order = WashingOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Заказ не оплачен.', 400);
            }

            $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->wallet->credit(
                tenantId: tenant()->id,
                amount: $order->payout_kopecks,
                type: 'wash_payout',
                meta: [
                    'order_id'       => $order->id,
                    'correlation_id' => $correlationId,
                ],
            );

            return $order;
        });
    }

    /**
     * Отменить заказ с возвратом средств (refund).
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): WashingOrder
    {
        $correlationId = $correlationId ?: Uuid::uuid4()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): WashingOrder {
            $order = WashingOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Нельзя отменить завершённый заказ.', 400);
            }

            $order->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(
                    tenantId: tenant()->id,
                    amount: $order->total_kopecks,
                    type: \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND,
                    meta: [
                        'order_id'       => $order->id,
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $order;
        });
    }

    /**
     * Получить заказ по id.
     */
    public function getOrder(int $orderId): WashingOrder
    {
        return WashingOrder::findOrFail($orderId);
    }

    /**
     * Получить заказы пользователя (10 последних).
     */
    public function getUserOrders(int $clientId): \Illuminate\Support\Collection
    {
        return WashingOrder::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
