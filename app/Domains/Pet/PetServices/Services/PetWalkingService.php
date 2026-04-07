<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PetWalkingService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание заказа на выгул питомца.
     */
    public function createWalkOrder(
        int $walkerId,
        int $petId,
        string $scheduledDate,
        int $durationMinutes,
        int $priceKopecks,
        string $correlationId,
    ): PetWalkingOrder {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pet_walking_book',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($walkerId, $petId, $scheduledDate, $durationMinutes, $priceKopecks, $correlationId): PetWalkingOrder {
            $platformFee = (int) ($priceKopecks * 0.14);
            $payoutAmount = $priceKopecks - $platformFee;

            $walkOrder = PetWalkingOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'walker_id' => $walkerId,
                'client_id' => $this->guard->id(),
                'pet_id' => $petId,
                'scheduled_date' => $scheduledDate,
                'duration_minutes' => $durationMinutes,
                'total_kopecks' => $priceKopecks,
                'platform_fee_kopecks' => $platformFee,
                'payout_kopecks' => $payoutAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'correlation_id' => $correlationId,
                'tags' => ['service' => 'walking'],
            ]);

            $this->logger->info('Pet walking order created', [
                'order_id' => $walkOrder->id,
                'order_uuid' => $walkOrder->uuid,
                'walker_id' => $walkerId,
                'pet_id' => $petId,
                'duration_minutes' => $durationMinutes,
                'total_kopecks' => $priceKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $walkOrder;
        });
    }

    /**
     * Начало выгула (вокер подтверждает забор питомца).
     */
    public function startWalk(int $orderId, float $lat, float $lon, string $correlationId): PetWalkingOrder
    {
        return $this->db->transaction(function () use ($orderId, $lat, $lon, $correlationId): PetWalkingOrder {
            $order = PetWalkingOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                throw new \RuntimeException("Walk order {$orderId} cannot be started from status: {$order->status}.");
            }

            $order->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'start_location' => ['lat' => $lat, 'lon' => $lon],
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pet walk started', [
                'order_id' => $order->id,
                'walker_id' => $order->walker_id,
                'start_lat' => $lat,
                'start_lon' => $lon,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Завершение выгула и выплата вокеру.
     */
    public function completeWalk(int $orderId, float $lat, float $lon, string $correlationId): PetWalkingOrder
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'pet_walking_complete',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($orderId, $lat, $lon, $correlationId): PetWalkingOrder {
            $order = PetWalkingOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'in_progress') {
                throw new \RuntimeException("Walk order {$orderId} is not in progress.");
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'end_location' => ['lat' => $lat, 'lon' => $lon],
                'actual_duration_minutes' => (int) now()->diffInMinutes($order->started_at),
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                userId: $order->walker_id,
                amount: $order->payout_kopecks,
                type: 'walking_payout',
                reason: "Pet walk #{$order->id} completed",
                correlationId: $correlationId,
            );

            $this->logger->info('Pet walk completed with payout', [
                'order_id' => $order->id,
                'payout_kopecks' => $order->payout_kopecks,
                'walker_id' => $order->walker_id,
                'actual_duration' => $order->actual_duration_minutes,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Отмена заказа на выгул.
     */
    public function cancelWalk(int $orderId, string $reason, string $correlationId): PetWalkingOrder
    {
        return $this->db->transaction(function () use ($orderId, $reason, $correlationId): PetWalkingOrder {
            $order = PetWalkingOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException("Cannot cancel a completed walk order.");
            }

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(
                    userId: $order->client_id,
                    amount: $order->total_kopecks,
                    type: 'walking_refund',
                    reason: "Pet walk #{$order->id} cancelled: {$reason}",
                    correlationId: $correlationId,
                );
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => $order->payment_status === 'completed' ? 'refunded' : $order->payment_status,
                'cancellation_reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Pet walk cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }
}
