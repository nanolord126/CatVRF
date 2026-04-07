<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Laundry\Services;

use App\Domains\CleaningServices\Laundry\Models\LaundryOrder;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class LaundryService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Создание заказа на стирку.
     */
    public function createOrder(
        int $shopId,
        float $weightKg,
        string $pickupDate,
        string $deliveryDate,
        string $correlationId = '',
    ): LaundryOrder {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $userId = (int) ($this->guard->id() ?? 0);
        $rateLimitKey = 'laundry:order:' . $userId;

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey, 18)) {
            throw new \RuntimeException('Too many attempts', 429);
        }

        $this->rateLimiter->hit($rateLimitKey, 3600);

        $this->fraud->check(
            userId: $userId,
            operationType: 'laundry_order',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($shopId, $weightKg, $pickupDate, $deliveryDate, $correlationId, $userId): LaundryOrder {
            $pricePerKg = 300;
            $total = (int) ($pricePerKg * $weightKg * 100);
            $payout = $total - (int) ($total * 0.14);

            $order = LaundryOrder::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'shop_id' => $shopId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'weight_kg' => $weightKg,
                'pickup_date' => $pickupDate,
                'delivery_date' => $deliveryDate,
                'tags' => ['laundry' => true],
            ]);

            $this->logger->info('Laundry order created', [
                'order_id' => $order->id,
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершение заказа — выплата исполнителю.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): LaundryOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): LaundryOrder {
            $order = LaundryOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed', 400);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $order->payout_kopecks,
                reason: 'laundry_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('Laundry order completed', [
                'order_id' => $order->id,
                'payout_kopecks' => $order->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отмена заказа — возврат средств клиенту.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): LaundryOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): LaundryOrder {
            $order = LaundryOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed order', 400);
            }

            $previousPaymentStatus = $order->payment_status;

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousPaymentStatus === 'completed') {
                $this->wallet->credit(
                    walletId: (int) tenant()->id,
                    amount: $order->total_kopecks,
                    reason: 'laundry_refund',
                    correlationId: $correlationId,
                );
            }

            $this->logger->info('Laundry order cancelled', [
                'order_id' => $order->id,
                'refunded' => $previousPaymentStatus === 'completed',
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Получение заказа по ID.
     */
    public function getOrder(int $orderId): LaundryOrder
    {
        return LaundryOrder::findOrFail($orderId);
    }

    /**
     * Получение заказов пользователя (последние 20).
     */
    public function getUserOrders(int $clientId): Collection
    {
        return LaundryOrder::query()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();
    }
}
