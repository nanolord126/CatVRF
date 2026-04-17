<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Bus\Dispatcher;

use App\Services\Wallet\WalletService;
use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Services\FraudControlService;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BalanceTransaction;

final class ProcessB2BOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $orderId,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {}

    public function handle(
        WalletService $wallet,
        CommissionService $commission,
        NotificationService $notification,
        FraudControlService $fraud,
        LogManager $logger,
        DatabaseManager $db,
        Guard $guard,
        Dispatcher $bus,
    ): void {
        try {
            $logger->channel('audit')->info('B2B Order processing started', [
                'order_id' => $this->orderId,
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            $order = $db->transaction(function () use ($db, $wallet, $commission, $notification, $fraud, $logger) {
                $order = Order::where('id', $this->orderId)
                    ->where('tenant_id', $this->tenantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$order->is_b2b) {
                    $logger->channel('audit')->warning('Attempted to process non-B2B order as B2B', [
                        'order_id' => $this->orderId,
                        'correlation_id' => $this->correlationId,
                    ]);
                    return $order;
                }

                $fraudResult = $fraud->check(
                    userId: $this->tenantId,
                    operationType: 'b2b_order_process',
                    amount: $order->total,
                    ipAddress: request()->ip() ?? '127.0.0.1',
                    deviceFingerprint: request()->header('X-Device-Fingerprint'),
                    correlationId: $this->correlationId,
                );

                $order->update([
                    'status' => 'processing',
                    'metadata' => array_merge($order->metadata ?? [], [
                        'fraud_score' => $fraudResult['score'],
                        'fraud_decision' => $fraudResult['decision'],
                        'b2b_processed_at' => now()->toIso8601String(),
                    ]),
                ]);

                $commissionAmount = $commission->calculateCommission(
                    tenantId: $this->tenantId,
                    vertical: $order->vertical,
                    amount: $order->total,
                    context: ['b2b_tier' => 'standard'],
                );

                $wallet->debit(
                    tenantId: $this->tenantId,
                    amount: $commissionAmount,
                    type: 'commission',
                    sourceId: $order->id,
                    sourceType: Order::class,
                    correlationId: $this->correlationId,
                    reason: "B2B commission for order {$order->uuid}",
                );

                $sellerWalletId = $this->getSellerWalletId($order, $db);
                if ($sellerWalletId > 0) {
                    $wallet->credit(
                        walletId: $sellerWalletId,
                        amount: $order->seller_earnings,
                        type: 'order_earning',
                        sourceId: $order->id,
                        sourceType: Order::class,
                        correlationId: $this->correlationId,
                        reason: "B2B order earnings for {$order->uuid}",
                    );
                }

                $order->update([
                    'status' => 'confirmed',
                    'platform_commission' => $commissionAmount,
                ]);

                $notification->sendB2BOrderConfirmation($order, $this->correlationId);
                $notification->sendB2BOrderToSeller($order, $this->correlationId);

                $logger->channel('audit')->info('B2B Order processed successfully', [
                    'order_id' => $this->orderId,
                    'order_uuid' => $order->uuid,
                    'commission' => $commissionAmount,
                    'seller_earnings' => $order->seller_earnings,
                    'correlation_id' => $this->correlationId,
                ]);

                return $order;
            });

        } catch (\Throwable $e) {
            $logger->channel('audit')->error('B2B Order processing failed', [
                'order_id' => $this->orderId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $db->table('orders')
                ->where('id', $this->orderId)
                ->update([
                    'status' => 'failed',
                    'metadata->b2b_error' => $e->getMessage(),
                ]);

            throw $e;
        }
    }

    private function getSellerWalletId(Order $order, DatabaseManager $db): int
    {
        $sellerId = $db->table('order_items')
            ->where('order_id', $order->id)
            ->value('seller_id');

        if (!$sellerId) {
            return 0;
        }

        $wallet = $db->table('wallets')
            ->where('tenant_id', $sellerId)
            ->first();

        return $wallet ? (int) $wallet->id : 0;
    }

    public function failed(\Throwable $exception): void
    {
        $logger = app(LogManager::class);
        $logger->channel('audit')->error('ProcessB2BOrderJob failed permanently', [
            'order_id' => $this->orderId,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
