<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Ritual\Services;

use App\Domains\HomeServices\Ritual\Models\FuneralOrder;
use App\Domains\HomeServices\Ritual\Models\RitualAgency;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * RitualCoreService — управление ритуальными услугами.
 *
 * Создание заказов на ритуальные услуги, оформление мемориальных
 * сертификатов, координация с ритуальными агентствами.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class RitualCoreService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать заказ на ритуальную услугу.
     */
    public function createOrder(
        int    $agencyId,
        string $serviceType,
        string $scheduledDate,
        string $correlationId = '',
    ): FuneralOrder {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($agencyId, $serviceType, $scheduledDate, $correlationId): FuneralOrder {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'ritual_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $order = FuneralOrder::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'agency_id'      => $agencyId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending',
                'service_type'   => $serviceType,
                'scheduled_date' => $scheduledDate,
                'tags'           => ['ritual' => true],
            ]);

            $this->logger->info('Ritual order created', [
                'order_id'       => $order->id,
                'agency_id'      => $agencyId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить ритуальный заказ и выплатить агентству.
     */
    public function completeOrder(int $orderId, int $totalKopecks, string $correlationId = ''): FuneralOrder
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $totalKopecks, $correlationId): FuneralOrder {
            $order = FuneralOrder::findOrFail($orderId);

            $payoutKopecks = $totalKopecks - (int) ($totalKopecks * 0.14);

            $order->update([
                'status'         => 'completed',
                'total_kopecks'  => $totalKopecks,
                'payout_kopecks' => $payoutKopecks,
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $payoutKopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id],
            );

            $this->logger->info('Ritual order completed', [
                'order_id'       => $order->id,
                'total_kopecks'  => $totalKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить ритуальный заказ.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): FuneralOrder
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): FuneralOrder {
            $order = FuneralOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed order', 400);
            }

            $order->update([
                'status'         => 'cancelled',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Ritual order cancelled', [
                'order_id'       => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Найти ритуальное агентство по ID.
     */
    public function getAgency(int $agencyId): RitualAgency
    {
        return RitualAgency::findOrFail($agencyId);
    }

    /**
     * Получить заказ по ID.
     */
    public function getOrder(int $orderId): FuneralOrder
    {
        return FuneralOrder::findOrFail($orderId);
    }

    public function __toString(): string
    {
        return static::class;
    }

    /** @return array<string, mixed> */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
