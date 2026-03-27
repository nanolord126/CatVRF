declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Payment;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Payment\InitPaymentRequest;
use App\Http\Requests\Payment\CapturePaymentRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentTransaction;
use App\Models\Wallet\Wallet;
use App\Services\FraudControlService;
use App\Services\PaymentGatewayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * Payment API Controller.
 * Workflow: init → authorize (hold) → capture (settle).
 * Integration: Fraud check, Wallet service, Payment gateway.
 *
 * Features:
 * - Idempotency via idempotency_key
 * - 2-stage payment (hold + capture)
 * - Fraud scoring on high amounts
 * - Commission calculation per vertical
 * - Audit logging with correlation_id
 */
final class PaymentController extends BaseApiController
{
    public function __construct(
        // Payment-specific dependencies
        private readonly FraudControlService $fraudService,
        private readonly PaymentGatewayService $gatewayService,
        private readonly WalletService $walletService,
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
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already initiated (idempotent)',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'payment_id' => $existingPayment->id,
                        'status' => $existingPayment->status,
                    ],
                ], 200);
            }
            return DB::transaction(function () use ($request, $correlationId, $tenantId, $idempotencyKey) {
                // 2. Fraud check перед платежом
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => $request->input('operation_type'),
                    'amount' => $request->integer('amount'),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                if ($fraudResult['decision'] === 'block') {
                    Log::channel('fraud_alert')->warning('Payment blocked by fraud check', [
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                        'amount' => $request->integer('amount'),
                        'ml_score' => $fraudResult['score'],
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment blocked due to fraud check',
                        'correlation_id' => $correlationId,
                    ], 403)->send();
                }
                // 3. Создать платёж
                $payment = Payment::create([
                    'tenant_id' => $tenantId,
                    'user_id' => auth()->id(),
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
                    'ml_version' => config('fraud.model_version', '2026-03-24-v1'),
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
                // 5. Hold сумм в кошельке пользователя (если требуется)
                if ($request->boolean('hold', true)) {
                    $this->walletService->holdAmount(
                        wallet_id: auth()->user()->wallet_id ?? 1,
                        amount: $request->integer('amount'),
                        reason: 'Payment hold for ' . $request->input('operation_type'),
                        correlation_id: $correlationId,
                    );
                }
                // 6. Логирование
                Log::channel('audit')->info('Payment initiated', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id(),
                    'amount' => $request->integer('amount'),
                    'operation_type' => $request->input('operation_type'),
                    'fraud_score' => $fraudResult['score'],
                ]);
                return response()->json([
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
            Log::channel('audit')->error('Payment initiation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }
        try {
            return DB::transaction(function () use ($payment, $request, $correlationId) {
                // Проверить статус платежа
                if ($payment->status !== 'pending') {
                    return response()->json([
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
                Log::channel('audit')->info('Payment captured', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'commission' => $commission,
                    'net_amount' => $netAmount,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Payment captured successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => 'captured',
                        'amount' => $payment->amount,
                        'commission' => $commission,
                        'net_amount' => $netAmount,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Payment capture failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment capture failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * POST /api/v1/payments/{id}/refund
     * Вернуть платёж.
     *
     * @return JsonResponse
     */
    public function refund(
        Payment $payment,
        RefundPaymentRequest $request,
    ): JsonResponse {
        $correlationId = $request->getCorrelationId();
        try {
            return DB::transaction(function () use ($payment, $request, $correlationId) {
                $refundAmount = $request->integer('amount', $payment->amount);
                if ($refundAmount > $payment->amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Refund amount exceeds payment',
                        'correlation_id' => $correlationId,
                    ], 400)->send();
                }
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
                // Вернуть средства в кошелёк пользователя
                $this->walletService->credit(
                    wallet_id: auth()->user()->wallet_id ?? 1,
                    amount: $refundAmount,
                    reason: 'Refund for ' . $payment->operation_type,
                    correlation_id: $correlationId,
                );
                Log::channel('audit')->info('Payment refunded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                    'refund_amount' => $refundAmount,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Payment refunded successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'payment_id' => $payment->id,
                        'refund_amount' => $refundAmount,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Payment refund failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Refund failed',
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
            'beauty_appointment' => 14.0,
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
            'beauty_appointment' => $request->user(),
            'food_order' => $request->user(),
            'hotel_booking' => $request->user(),
            'taxi_ride' => $request->user(),
            default => null,
        };
    }
}
