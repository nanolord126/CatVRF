<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class LogisticsService
{
    private readonly string $correlationId;

    public function __construct(
        ?string $correlationId,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Расчет динамического коэффициента (Surge Pricing) для точки.
     *
     * @param  array  $point  [lat, lon]
     */
    public function calculateSurgeMultiplier(array $point, int $tenantId): float
    {
        $this->logger->$this->logger->info('Calculating surge multiplier', [
            'point' => $point,
            'tenant_id' => $tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        $activeSurge = SurgeZone::query()
            ->where('tenant_id', $tenantId)
            ->where('active_from', '<=', CarbonImmutable::now())
            ->where('active_until', '>=', CarbonImmutable::now())
            ->whereHas('geoZone', function ($q) {
                $q->where('is_active', true);
            })
            ->orderByDesc('multiplier')
            ->first();

        $multiplier = $activeSurge ? (float) $activeSurge->multiplier : 1.0;

        $onlineCouriers = Courier::where('tenant_id', $tenantId)->status('online')->count();
        $pendingOrders = DeliveryOrder::where('tenant_id', $tenantId)->where('status', 'pending')->count();

        if ($onlineCouriers > 0 && ($pendingOrders / $onlineCouriers) > 5) {
            $multiplier += 0.5;
        }

        return round($multiplier, 2);
    }

    public function findOptimalCourier(DeliveryOrder $order): ?Courier
    {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($order) {
            $couriers = Courier::query()
                ->where('tenant_id', $order->tenant_id)
                ->status('online')
                ->where('is_active', true)
                ->with('vehicle')
                ->get();

            if ($couriers->isEmpty()) {
                $this->logger->warning('No online couriers available', [
                    'order_uuid' => $order->uuid,
                    'correlation_id' => $this->correlationId,
                ]);
                throw new \RuntimeException('Unexpected null return');
            }

            $winner = $couriers->first();

            if ($winner) {
                $order->update([
                    'courier_id' => $winner->id,
                    'status' => 'assigned',
                    'correlation_id' => $this->correlationId,
                ]);

                $this->logger->$this->logger->info('Courier assigned', [
                    'order_uuid' => $order->uuid,
                    'courier_uuid' => $winner->uuid,
                    'correlation_id' => $this->correlationId,
                ]);
            }

            return $winner;
        });
    }

    public function createDeliveryOrder(array $data, int $tenantId): DeliveryOrder
    {
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $tenantId) {
            $multiplier = $this->calculateSurgeMultiplier($data['pickup'], $tenantId);
            $totalPrice = (int) ($data['base_price'] * $multiplier);

            $order = DeliveryOrder::create([
                'tenant_id' => $tenantId,
                'source_order_id' => $data['source_order_id'],
                'pickup_point' => $data['pickup'],
                'dropoff_point' => $data['dropoff'],
                'status' => 'pending',
                'base_price' => $data['base_price'],
                'surge_multiplier' => $multiplier,
                'total_price' => $totalPrice,
                'correlation_id' => $this->correlationId,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->logger->$this->logger->info('Delivery order created', [
                'order_uuid' => $order->uuid,
                'total_price' => $totalPrice,
                'correlation_id' => $this->correlationId,
            ]);

            return $order;
        });
    }
}
