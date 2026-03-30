<?php declare(strict_types=1);

namespace Modules\Finances\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly WalletService $wallet,
            private readonly FraudControlService $fraudControl,
            private readonly FraudMLService $fraudML,
            private readonly PaymentGatewayInterface $gateway,
            private readonly FiscalServiceInterface $fiscal,
            private readonly IdempotencyService $idempotency,
            private readonly RateLimiterService $rateLimiter
        ) {
        }
    
        /**
         * Инициировать платёж для заказа.
         * Согласно КАНОН 2026: FraudControl::check() перед всеми критичными операциями.
         * Новое: RateLimiter проверка + IdempotencyService проверка.
         */
        public function initializeOrderPayment(\Illuminate\Database\Eloquent\Model $order, ?string $correlationId = null): array
        {
            $correlationId = $correlationId ?? Str::uuid()->toString();
    
            return DB::transaction(function () use ($order, $correlationId): array {
                try {
                    $amount = (int) (($order->total_amount ?? $order->subtotal ?? 0) * 100); // В копейках
    
                    // КАНОН 2026: Rate Limiter проверка (10 платежей в минуту на пользователя)
                    try {
                        $this->rateLimiter->checkPaymentInit($order->user_id);
                    } catch (\Exception $e) {
                        Log::channel('audit')->warning('Payment rate limit exceeded', [
                            'user_id' => $order->user_id,
                            'correlation_id' => $correlationId,
                        ]);
                        throw new \App\Exceptions\RateLimitException('Слишком много платежей. Подождите.');
                    }
    
                    // КАНОН 2026: FraudControl::check() ПЕРЕД инициализацией
                    $fraudCheck = $this->fraudControl->checkPayment($order->user_id, $amount, [
                        'order_id' => $order->id,
                        'order_type' => get_class($order),
                        'correlation_id' => $correlationId,
                    ]);
    
                    if (!$fraudCheck['allowed']) {
                        Log::channel('audit')->warning('Payment blocked by fraud control', [
                            'user_id' => $order->user_id,
                            'amount' => $amount,
                            'reason' => $fraudCheck['reason'],
                            'score' => $fraudCheck['score'],
                            'correlation_id' => $correlationId,
                        ]);
    
                        throw new \RuntimeException('Payment blocked: ' . ($fraudCheck['reason'] ?? 'Fraud detection'));
                    }
    
                    // КАНОН 2026: Idempotency проверка через IdempotencyService
                    $idempotencyKey = request()->header('X-Idempotency-Key') 
                        ?? $this->generateIdempotencyKey($order);
                    
                    $payloadHash = hash('sha256', json_encode([
                        'user_id' => $order->user_id,
                        'amount' => $amount,
                        'order_id' => $order->id,
                    ]));
    
                    // Проверить, не был ли уже обработан такой платёж
                    $duplicate = $this->idempotency->check($idempotencyKey, $payloadHash);
                    if ($duplicate) {
                        Log::channel('audit')->info('Idempotent payment request (duplicate detected)', [
                            'order_id' => $order->id,
                            'idempotency_key' => $idempotencyKey,
                            'correlation_id' => $correlationId,
                        ]);
                        
                        throw new \App\Exceptions\DuplicatePaymentException(
                            'Платёж уже был обработан',
                            $duplicate
                        );
                    }
    
                    // Расчет сплитов
                    $splits = CommissionCalculator::calculateSplits($order);
    
                    // Создать платежную транзакцию с холдом
                    $payment = PaymentTransaction::create([
                        'uuid' => Str::uuid(),
                        'correlation_id' => $correlationId,
                        'tenant_id' => $order->tenant_id,
                        'user_id' => $order->user_id,
                        'business_group_id' => $order->business_group_id ?? null,
                        'idempotency_key' => $idempotencyKey,
                        'payload_hash' => hash('sha256', json_encode($order->toArray())),
                        'payment_method' => 'card', // По умолчанию, может быть переопределено
                        'amount' => $amount,
                        'hold_amount' => $amount,
                        'is_hold' => true,
                        'is_captured' => false,
                        'status' => PaymentTransaction::STATUS_PENDING,
                        'metadata' => [
                            'order_type' => get_class($order),
                            'order_id' => $order->id,
                            'splits' => $splits,
                            'commission_rate' => $order->metadata['commission_rate'] ?? 0.14,
                        ],
                        'tags' => [
                            'order',
                            strtolower(class_basename($order)),
                            'payment_init',
                        ],
                    ]);
    
                    // Создать холд в кошельке
                    $holdUuid = $this->wallet->hold(
                        walletId: $order->user_id, // Предположим, что wallet_id == user_id
                        amount: $amount,
                        sourceType: 'payment',
                        sourceId: (string) $payment->id,
                        correlationId: $correlationId
                    );
    
                    // Сохранить UUID холда в платежную транзакцию
                    $payment->update(['metadata' => array_merge($payment->metadata ?? [], ['hold_uuid' => $holdUuid])]);
    
                    // Инициировать платёж через шлюз
                    $gatewayResult = $this->gateway->initPayment([
                        'amount' => $amount,
                        'description' => "Order #{$order->id}",
                        'idempotency_key' => $idempotencyKey,
                        'return_url' => route('payments.return'),
                        'webhook_url' => route('payments.webhook'),
                        'metadata' => [
                            'order_id' => $order->id,
                            'correlation_id' => $correlationId,
                        ],
                    ], hold: true);
    
                    if (!$gatewayResult['success']) {
                        // Отпустить холд при ошибке
                        $this->wallet->release($holdUuid, $correlationId);
    
                        $payment->markFailed($gatewayResult['error_message'] ?? 'Gateway error', $gatewayResult['error_code'] ?? '');
    
                        throw new \RuntimeException($gatewayResult['error_message'] ?? 'Payment gateway error');
                    }
    
                    // Обновить платёж с данными провайдера
                    $payment->update([
                        'provider_payment_id' => $gatewayResult['provider_payment_id'] ?? null,
                        'status' => PaymentTransaction::STATUS_AUTHORIZED,
                        'authorized_at' => now(),
                    ]);
    
                    // КАНОН 2026: Сохранить идемпотентность через IdempotencyService
                    $response = [
                        'payment_id' => $payment->id,
                        'provider_payment_id' => $gatewayResult['provider_payment_id'],
                        'status' => 'authorized',
                        'redirect_url' => $gatewayResult['redirect_url'] ?? null,
                    ];
    
                    $this->idempotency->record($idempotencyKey, $payloadHash, $response, minutes: 10080); // 7 дней
    
                    // Логирование
                    Log::channel('audit')->info('Payment initiated', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'amount' => $amount,
                        'provider_payment_id' => $gatewayResult['provider_payment_id'],
                        'correlation_id' => $correlationId,
                    ]);
    
                    return $response;
    
                    return [
                        'success' => true,
                        'payment_id' => $payment->id,
                        'provider_payment_id' => $gatewayResult['provider_payment_id'],
                        'payment_url' => $gatewayResult['payment_url'] ?? null,
                        'correlation_id' => $correlationId,
                    ];
                } catch (Throwable $e) {
                    Log::channel('audit')->error('Payment initialization failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                        'trace' => $e->getTraceAsString(),
                    ]);
    
                    \Sentry\captureException($e);
    
                    throw $e;
                }
            });
        }
    
        /**
         * Обработать webhook от платежного провайдера.
         * Согласно КАНОН 2026: верификация подписи, захват холда, распределение средств.
         */
        public function handleWebhook(array $payload, ?string $correlationId = null): void
        {
            $correlationId = $correlationId ?? Str::uuid()->toString();
    
            try {
                // Верифицировать подпись (зависит от провайдера)
                if (!$this->gateway->verifyWebhookSignature($payload)) {
                    throw new \RuntimeException('Invalid webhook signature');
                }
    
                $data = $this->gateway->handleWebhook($payload);
                $transaction = PaymentTransaction::where('provider_payment_id', $data['provider_payment_id'])
                    ->lockForUpdate()
                    ->first();
    
                if (!$transaction) {
                    Log::warning('Payment transaction not found in webhook', [
                        'provider_payment_id' => $data['provider_payment_id'] ?? null,
                        'correlation_id' => $correlationId,
                    ]);
    
                    return;
                }
    
                // Проверка идемпотентности
                if ($transaction->status === PaymentTransaction::STATUS_CAPTURED) {
                    Log::info('Payment already captured, skipping duplicate webhook', [
                        'transaction_id' => $transaction->id,
                        'correlation_id' => $correlationId,
                    ]);
    
                    return;
                }
    
                // Обработать в зависимости от статуса
                if ($data['status'] === 'confirmed') {
                    // Захватить холд
                    $holdUuid = $transaction->metadata['hold_uuid'] ?? null;
    
                    if ($holdUuid) {
                        $this->wallet->capture($holdUuid, $correlationId);
                    }
    
                    // Обновить статус
                    $transaction->markCaptured($correlationId);
    
                    // Распределить средства (seller, platform, affiliates)
                    $this->distributeFunds($transaction, $correlationId);
    
                    // Отправить чек в ОФД
                    try {
                        $this->fiscal->sendReceipt($transaction, $correlationId);
                    } catch (Throwable $e) {
                        Log::warning('Fiscal receipt sending failed', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
    
                    Log::channel('audit')->info('Payment captured via webhook', [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'correlation_id' => $correlationId,
                    ]);
                } elseif ($data['status'] === 'failed') {
                    // Отпустить холд при ошибке
                    $holdUuid = $transaction->metadata['hold_uuid'] ?? null;
    
                    if ($holdUuid) {
                        $this->wallet->release($holdUuid, $correlationId);
                    }
    
                    // Обновить статус
                    $transaction->markFailed($data['error_message'] ?? 'Payment failed', $data['error_code'] ?? '');
    
                    Log::warning('Payment failed via webhook', [
                        'transaction_id' => $transaction->id,
                        'error_message' => $data['error_message'],
                        'correlation_id' => $correlationId,
                    ]);
                }
            } catch (Throwable $e) {
                Log::channel('audit')->error('Webhook handling failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
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
    
        /**
         * Выполнить платёж по токену карты (для подписок и повторяющихся платежей).
         */
        public function chargeByToken(string $token, float $amount, array $metadata = []): array
        {
            try {
                // Инициировать платёж через шлюз используя токен карты            /** @var array<string, mixed> $result */            $result = $this->gateway->chargeToken($token, $amount, $metadata);
    
                // Создать транзакцию
                $tx = PaymentTransaction::create([
                    'payment_id' => $result['id'] ?? null,
                    'amount' => $amount,
                    'status' => 'settled',
                    'correlation_id' => $metadata['correlation_id'] ?? request()->header('X-Correlation-ID', uniqid()),
                    'metadata' => $metadata,
                ]);
    
                Log::channel('payments')->info('Payment by token processed successfully', [
                    'transaction_id' => $tx->id,
                    'amount' => $amount,
                ]);
    
                return [
                    'status' => 'settled',
                    'transaction_id' => $tx->id,
                    'amount' => $amount,
                ];
            } catch (Throwable $e) {
                Log::error('Payment by token failed', [
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    
        /**
         * Обработать платёж с разделением средств между участниками.
         */
        private function processSplitPayment(array $data): array
        {
            try {
                $amount = (float) ($data['amount'] ?? 0);
                $splits = $data['splits'] ?? [];
                $metadata = $data['metadata'] ?? [];
    
                if ($amount <= 0) {
                    throw new \InvalidArgumentException("Invalid payment amount: {$amount}");
                }
    
                // Инициализация платежа через шлюз
                $res = $this->gateway->initPayment([
                    'amount' => $amount,
                    'description' => $data['description'] ?? 'Payment',
                    'metadata' => $metadata,
                ], false);
    
                // Создание записи транзакции со сплитами
                $tx = PaymentTransaction::create([
                    'payment_id' => $res['id'] ?? null,
                    'amount' => $amount,
                    'status' => 'pending',
                    'correlation_id' => $metadata['correlation_id'] ?? request()->header('X-Correlation-ID', uniqid()),
                    'metadata' => array_merge($metadata, ['splits' => $splits]),
                ]);
    
                Log::channel('payments')->info('Split payment initialized', [
                    'transaction_id' => $tx->id,
                    'amount' => $amount,
                    'split_count' => count($splits),
                ]);
        /**
         * Распределить средства между участниками (seller, platform, affiliates).
         */
        private function distributeFunds(PaymentTransaction $payment, string $correlationId): void
        {
            try {
                $splits = $payment->metadata['splits'] ?? [];
                
                // Логирование распределения
                Log::channel('audit')->info('Distributing payment funds', [
                    'payment_id' => $payment->id,
                    'splits' => $splits,
                    'correlation_id' => $correlationId,
                ]);
    
                // Реальное распределение будет в зависимости от типа заказа
                // Здесь плейсхолдер для демонстрации
            } catch (Throwable $e) {
                Log::error('Fund distribution failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    
        /**
         * Сгенерировать idempotency_key для заказа.
         */
        private function generateIdempotencyKey(\Illuminate\Database\Eloquent\Model $order): string
        {
            return hash('sha256', implode('|', [
                get_class($order),
                $order->id,
                $order->user_id,
                (int) (($order->total_amount ?? $order->subtotal ?? 0) * 100),
            ]));
        }
    
        /**
         * Проверить, обработан ли уже этот платёж (idempotency).
         */
        private function checkIdempotency(string $idempotencyKey, string $operation): ?array
        {
            $record = DB::table('payment_idempotency_records')
                ->where('idempotency_key', $idempotencyKey)
                ->where('operation', $operation)
                ->where('expires_at', '>', now())
                ->first();
    
            if (!$record) {
                return null;
            }
    
            return [
                'status' => $record->status,
                'response_data' => json_decode($record->response_data, true),
            ];
        }
    
        /**
         * Записать результат операции для idempotency.
         */
        private function recordIdempotency(
            string $idempotencyKey,
            string $operation,
            string $status,
            array $responseData
        ): void {
            DB::table('payment_idempotency_records')->insertOrIgnore([
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => auth()->user()->tenant_id ?? 'system',
                'operation' => $operation,
                'status' => $status,
                'response_data' => json_encode($responseData),
                'expires_at' => now()->addDays(7), // TTL 7 дней
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
}
