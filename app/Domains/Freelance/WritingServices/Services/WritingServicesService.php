<?php declare(strict_types=1);

namespace App\Domains\Freelance\WritingServices\Services;

use App\Domains\Freelance\WritingServices\Models\WritingOrder;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * WritingServicesService — управление заказами на написание текстов.
 *
 * Полный цикл: создание, завершение, отмена заказов
 * с fraud-check, wallet-интеграцией и audit-логированием.
 *
 * @package App\Domains\Freelance\WritingServices\Services
 */
final readonly class WritingServicesService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать заказ на написание текста.
     */
    public function createOrder(
        int $writerId,
        string $projectType,
        int $wordCount,
        string $dueDate,
        string $correlationId = '',
    ): WritingOrder {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'writing_order_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($writerId, $projectType, $wordCount, $dueDate, $correlationId, $userId) {
            $ratePerWord = 200; // копейки за слово
            $total = $wordCount * $ratePerWord;

            $order = WritingOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'writer_id' => $writerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'word_count' => $wordCount,
                'due_date' => $dueDate,
                'tags' => ['writing' => true],
            ]);

            $this->audit->log(
                action: 'writing_order_created',
                subjectType: WritingOrder::class,
                subjectId: $order->id,
                old: [],
                new: $order->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Writing order created', [
                'order_id' => $order->id,
                'writer_id' => $writerId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и выплатить автору.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): WritingOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = WritingOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this order', 400);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $order->writer_id,
                amount: $order->payout_kopecks,
                type: 'freelance_payout',
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id, 'vertical' => 'writing'],
            );

            $this->audit->log(
                action: 'writing_order_completed',
                subjectType: WritingOrder::class,
                subjectId: $order->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Writing order completed', [
                'order_id' => $order->id,
                'payout' => $order->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить заказ и вернуть средства.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): WritingOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = WritingOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel a completed order', 400);
            }

            $oldStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: $order->client_id,
                    amount: $order->total_kopecks,
                    type: 'freelance_refund',
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id, 'reason' => 'order_cancelled'],
                );
            }

            $this->audit->log(
                action: 'writing_order_cancelled',
                subjectType: WritingOrder::class,
                subjectId: $order->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Writing order cancelled', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Получить заказ по ID.
     */
    public function getOrder(int $orderId): WritingOrder
    {
        return WritingOrder::findOrFail($orderId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserOrders(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return WritingOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
