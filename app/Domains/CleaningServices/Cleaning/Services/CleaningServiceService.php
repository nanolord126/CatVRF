<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Cleaning\Services;

use App\Domains\CleaningServices\Cleaning\Models\CleaningOrder;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class CleaningServiceService
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
     * Создание заказа на клининг.
     */
    public function createOrder(
        int $serviceId,
        string $orderDate,
        int $durationHours,
        int $areaSqm,
        string $correlationId = '',
    ): CleaningOrder {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $userId = (int) ($this->guard->id() ?? 0);
        $rateLimitKey = 'cleaning:order:' . $userId;

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey, 20)) {
            throw new \RuntimeException('Too many attempts', 429);
        }

        $this->rateLimiter->hit($rateLimitKey, 3600);

        $this->fraud->check(
            userId: $userId,
            operationType: 'cleaning_order',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($serviceId, $orderDate, $durationHours, $areaSqm, $correlationId, $userId): CleaningOrder {
            $pricePerHour = 500;
            $total = $pricePerHour * $durationHours * 100;
            $payout = $total - (int) ($total * 0.14);

            $order = CleaningOrder::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'service_id' => $serviceId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'order_date' => $orderDate,
                'duration_hours' => $durationHours,
                'area_sqm' => $areaSqm,
                'tags' => ['cleaning' => true],
            ]);

            $this->logger->info('Cleaning order created', [
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
    public function completeOrder(int $orderId, string $correlationId = ''): CleaningOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): CleaningOrder {
            $order = CleaningOrder::findOrFail($orderId);

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
                reason: 'cleaning_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('Cleaning order completed', [
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
    public function cancelOrder(int $orderId, string $correlationId = ''): CleaningOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): CleaningOrder {
            $order = CleaningOrder::findOrFail($orderId);

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
                    reason: 'cleaning_refund',
                    correlationId: $correlationId,
                );
            }

            $this->logger->info('Cleaning order cancelled', [
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
    public function getOrder(int $orderId): CleaningOrder
    {
        return CleaningOrder::findOrFail($orderId);
    }

    /**
     * Получение заказов пользователя (последние 20).
     */
    public function getUserOrders(int $clientId): Collection
    {
        return CleaningOrder::query()
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();
    }
}
