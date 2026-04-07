<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class PaymentController extends Controller
{


    public function __construct(
        private readonly ConfigRepository $config,
            // Payment-specific dependencies
            private readonly FraudControlService $fraudService,
            private readonly PaymentGatewayService $gatewayService,
            private readonly WalletService $walletService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {
            parent::__construct();
        }
        /**
         * POST /api/v1/payments/init
         * Инициализировать платёж (создать холд).
         *
         * @return JsonResponse
         */
        public function init(InitPaymentRequest $request): JsonResponse
        {
            $correlationId = $request->getCorrelationId();
            $tenantId = $request->getTenantId();
            $idempotencyKey = $request->input('idempotency_key') ?? Str::uuid()->toString();
            try {
                // 1. Проверка идемпотентности (предотвращение дублей)
                $existingPayment = PaymentTransaction::where([
                    'idempotency_key' => $idempotencyKey,
                    'tenant_id' => $tenantId,
                ])->first();
                if ($existingPayment) {
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Payment already initiated (idempotent)',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'payment_id' => $existingPayment->id,
                            'status' => $existingPayment->status,
                        ],
                    ], 200);
                }
                return $this->db->transaction(function () use ($request, $correlationId, $tenantId, $idempotencyKey) {
                    // 2. Fraud check перед платежом
                    $fraudResult = $this->fraudService->scoreOperation([
                        'type' => $request->input('operation_type'),
                        'amount' => $request->integer('amount'),
                        'user_id' => $this->guard->id(),
                        'ip_address' => $request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                    if ($fraudResult['decision'] === 'block') {
                        $this->logger->channel('fraud_alert')->warning('Payment blocked by fraud check', [
                            'correlation_id' => $correlationId,
                            'user_id' => $this->guard->id(),
                            'amount' => $request->integer('amount'),
                            'ml_score' => $fraudResult['score'],
                        ]);
                        return $this->response->json([
                            'success' => false,
                            'message' => 'Payment blocked due to fraud check',
                            'correlation_id' => $correlationId,
                        ], 403)->send();
                    }
                    // 3. Создать платёж
                    $payment = Payment::create([
                        'tenant_id' => $tenantId,
                        'user_id' => $this->guard->id(),
                        'operation_type' => $request->input('operation_type'),
                        'amount' => $request->integer('amount'),
                        'currency' => $request->input('currency', 'RUB'),
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid(),
                    ]);
                    // 4. Создать транзакцию платежа (hold)
                    $transaction = PaymentTransaction::create([
                        'payment_id' => $payment->id,
                        'tenant_id' => $tenantId,
                        'idempotency_key' => $idempotencyKey,
                        'operation_type' => $request->input('operation_type'),
                        'amount' => $request->integer('amount'),
                        'status' => 'authorized',
                        'ml_score' => $fraudResult['score'],
                        'ml_version' => $this->config->get('fraud.model_version', '2026-03-24-v1'),
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid(),
                    ]);
                    // 5. Hold сумм в кошельке пользователя (если требуется)
                    if ($request->boolean('hold', true)) {
                        $this->walletService->holdAmount(
                            wallet_id: $this->guard->user()->wallet_id ?? 1,
                            amount: $request->integer('amount'),
                            reason: 'Payment hold for ' . $request->input('operation_type'),
                            correlation_id: $correlationId,
                        );
                    }
                    // 6. Логирование
                    $this->logger->channel('audit')->info('Payment initiated', [
                        'correlation_id' => $correlationId,
                        'payment_id' => $payment->id,
                        'user_id' => $this->guard->id(),
                        'amount' => $request->integer('amount'),
                        'operation_type' => $request->input('operation_type'),
                        'fraud_score' => $fraudResult['score'],
                    ]);
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Payment initiated successfully',
                        'correlation_id' => $correlationId,
                        'data' => [
                            'payment_id' => $payment->id,
                            'transaction_id' => $transaction->id,
                            'status' => 'authorized',
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                        ],
                    ], 201);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Payment initiation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Payment initiation failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * POST /api/v1/payments/{id}/capture
         * Захватить платёж (списать деньги).
         *
         * @return JsonResponse
         */
        public function capture(
            Payment $payment,
            CapturePaymentRequest $request,
        ): JsonResponse {
            $correlationId = $request->getCorrelationId();
            $tenantId = $request->getTenantId();
            if ($payment->tenant_id !== $tenantId) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }
            try {
                return $this->db->transaction(function () use ($payment, $request, $correlationId) {
                    // Проверить статус платежа
                    if ($payment->status !== 'pending') {
                        return $this->response->json([
                            'success' => false,
                            'message' => 'Payment already processed',
                            'correlation_id' => $correlationId,
                        ], 400)->send();
                    }
                    // Получить commission rate
                    $commissionRate = $this->getCommissionRateByType($payment->operation_type);
                    $commission = intdiv((int) ($payment->amount * $commissionRate / 100), 1);
                    $netAmount = $payment->amount - $commission;
                    // Обновить статус платежа
                    $payment->update([
                        'status' => 'captured',
                        'captured_at' => now(),
                    ]);
                    // Обновить транзакцию
                    $transaction = $payment->transaction;
                    $transaction->update([
                        'status' => 'captured',
                        'captured_at' => now(),
                    ]);
                    // Кредитировать кошелёк провайдера (с комиссией)
                    $provider = $this->getProviderByOperationType($payment->operation_type, $request);
                    if ($provider) {
                        $providerWallet = $provider->wallet ?? Wallet::factory()->create([
                            'tenant_id' => $payment->tenant_id,
                            'user_id' => $provider->id,
                        ]);
                        $this->walletService->credit(
                            wallet_id: $providerWallet->id,
                            amount: $netAmount,
                            reason: 'Payment settlement for ' . $payment->operation_type,
                            correlation_id: $correlationId,
                        );
                    }
                    // Логирование
                    $this->logger->channel('audit')->info('Payment captured', [
                        'correlation_id' => $correlationId,
                        'payment_id' => $payment->id,
                        'net_amount' => $netAmount,
                    ]);

                    return $this->response->json([
                        'success' => true,
                        'message' => 'Payment captured',
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->channel('error')->error('Payment capture failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Capture failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получить комиссию по типу операции.
         */
        private function getCommissionRateByType(string $operationType): float
        {
            return match ($operationType) {
                'food_order' => 14.0,
                'hotel_booking' => 14.0,
                'taxi_ride' => 15.0,
                default => 14.0,
            };
        }
        /**
         * Получить провайдера (продавца) по типу операции.
         */
        private function getProviderByOperationType(string $operationType, mixed $request): mixed
        {
            return match ($operationType) {
                'food_order' => $request->user(),
                'hotel_booking' => $request->user(),
                'taxi_ride' => $request->user(),
                default => null,
            };
        }
}
