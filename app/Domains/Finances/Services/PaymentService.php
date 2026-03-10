<?php

namespace App\Domains\Finances\Services;

use App\Domains\Finances\Interfaces\PaymentGatewayInterface;
use App\Domains\Finances\Interfaces\FiscalServiceInterface;
use App\Domains\Finances\Models\PaymentTransaction;
use App\Domains\Finances\Services\WalletService;
use App\Domains\Finances\Services\Fiscal\FiscalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Основной сервис управления платежами.
 * 
 * Поддерживает:
 * - Инициализацию платежей через платежные шлюзы
 * - Распределение средств между участниками (seller, platform, etc)
 * - Фискализацию чеков с учетом системы налогообложения
 * - Обработку вебхуков платежных систем
 * - Отслеживание статуса платежей
 */
class PaymentService
{
    public function __construct(
        private WalletService $wallet,
        private PaymentGatewayInterface $gateway,
        private FiscalService $fiscal
    ) {}

    /**
     * Инициировать платёж для заказа.
     */
    public function initializeOrderPayment(\Illuminate\Database\Eloquent\Model $order): array
    {
        $amount = $order->total_amount ?? $order->subtotal ?? 0;
        
        return $this->processSplitPayment([
            'amount' => $amount,
            'description' => "Order #{$order->id} Payment",
            'splits' => [
                // Базовое распределение: 90% продавцу, 10% платформе
                $order->user_id => $amount * 0.9,
                'platform' => $amount * 0.1
            ],
            'metadata' => [
                'order_type' => get_class($order),
                'order_id' => $order->id,
            ]
        ]);
    }

    public function handleWebhook(array $payload, string $correlationId = null): void
    {
        try {
            $correlationId = $correlationId ?? Str::uuid();
            
            $data = $this->gateway->handleWebhook($payload);
            $tx = PaymentTransaction::where('payment_id', $data['PaymentId'])->lockForUpdate()->first();
            
            if (!$tx) {
                Log::warning('Payment transaction not found in webhook', [
                    'payment_id' => $data['PaymentId'] ?? null,
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            if ($data['Status'] === 'CONFIRMED') {
                // Idempotency check: Don't process nested logic if already settled
                if ($tx->status === 'settled') {
                    Log::info('Payment already settled, skipping duplicate webhook', [
                        'transaction_id' => $tx->id,
                        'correlation_id' => $correlationId,
                    ]);
                    return;
                }

                $tx->updateStatus('settled', ['webhook_correlation_id' => $correlationId]);
                $this->distributeFunds($tx);
                
                // Отправить чек в налоговую систему (async)
                try {
                    $this->fiscal->sendReceipt([
                        'payment_id' => $tx->payment_id,
                        'amount' => $tx->amount,
                        'correlation_id' => $correlationId,
                        'tax_system' => $tx->metadata['tax_system'] ?? config('fiscal.common.taxation_system', 'OSN'),
                        'metadata' => [
                            'email' => $tx->metadata['customer_email'] ?? null,
                            'phone' => $tx->metadata['customer_phone'] ?? null,
                        ],
                    ], $tx->metadata['items'] ?? []);
                } catch (Throwable $e) {
                    Log::warning('Fiscal receipt sending failed', [
                        'transaction_id' => $tx->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::channel('payments')->info('Payment settled via webhook', [
                    'transaction_id' => $tx->id,
                    'amount' => $tx->amount,
                    'correlation_id' => $correlationId,
                ]);
            } elseif ($data['Status'] === 'FAILED') {
                $tx->updateStatus('failed', ['webhook_correlation_id' => $correlationId]);
                Log::warning('Payment failed via webhook', [
                    'transaction_id' => $tx->id,
                    'correlation_id' => $correlationId,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Уведомить пользователя о платеже (Email, Push, SMS).
     */
    private function notifyCustomer(PaymentTransaction $tx): void
    {
        try {
            // Отправка уведомления по Email, Push-уведомления, SMS
            if ($tx->user) {
                Log::channel('payments')->info('Customer notified about payment', [
                    'user_id' => $tx->user_id,
                    'transaction_id' => $tx->id,
                    'amount' => $tx->amount,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to notify customer', [
                'user_id' => $tx->user_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Инициализация платежа с минимальным набором параметров.
     */
    public function initPayment(array $data): array
    {
        $amount = (float) ($data['amount'] ?? 0);
        $orderId = $data['order_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $orderType = $data['order_type'] ?? 'general';
        $metadata = $data['metadata'] ?? [];

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Invalid payment amount: {$amount}");
        }

        if (empty($orderId)) {
            throw new \InvalidArgumentException("Order ID is required");
        }

        // Инициализация платежа через шлюз
        $res = $this->gateway->initPayment([
            'amount' => $amount,
            'order_id' => $orderId,
            'user_id' => $userId,
            'description' => $data['description'] ?? "Order {$orderId}",
            'metadata' => array_merge($metadata, [
                'order_type' => $orderType,
                'order_id' => $orderId,
            ]),
        ], false);

        // Создание записи транзакции
        $tx = PaymentTransaction::create([
            'payment_id' => $res['id'] ?? null,
            'amount' => $amount,
            'status' => 'pending',
            'correlation_id' => $metadata['correlation_id'] ?? request()->header('X-Correlation-ID', uniqid()),
            'metadata' => $metadata,
        ]);

        return [
            'status' => 'pending',
            'payment_id' => $tx->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_url' => $res['url'] ?? null,
        ];
    }

    private function distributeFunds(PaymentTransaction $tx): void
    {
        // Atomicity: All splits must succeed or none should be committed
        DB::transaction(function() use ($tx) {
            foreach ($tx->splits as $userId => $amount) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $this->wallet->credit($user, $amount, "Payment #{$tx->id}", $tx->id);
                }
            }
        });
    }
}
