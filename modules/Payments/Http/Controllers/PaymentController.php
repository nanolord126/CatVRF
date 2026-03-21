<?php declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\PaymentTransaction;
use App\Modules\Payments\Http\Requests\StorePaymentRequest;
use App\Modules\Payments\Services\PaymentService;
use App\Domains\Finances\Services\Security\FraudControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Контроллер платежей.
 * Production 2026.
 */
final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Получить все платежи пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            Log::channel('audit')->info('payment.transactions.index.start', [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant('id'),
                'user_id' => auth()->id(),
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $transactions = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            Log::channel('audit')->info('payment.transactions.index.success', [
                'correlation_id' => $correlationId,
                'count' => $transactions->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.transactions.index.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении платежей',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Инициировать платёж.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            // Fraud check
            $fraudScore = $this->fraudControl->assessRisk(auth()->user(), [
                'amount' => $request->amount,
                'type' => 'payment_init',
                'correlation_id' => $correlationId,
            ]);

            if ($fraudScore > 80) {
                Log::channel('audit')->warning('payment.init.fraud.blocked', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudScore,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Операция заблокирована системой безопасности',
                    'correlation_id' => (string) $correlationId,
                ], 403);
            }

            Log::channel('audit')->info('payment.init.start', [
                'correlation_id' => $correlationId,
                'amount' => $request->amount,
                'fraud_score' => $fraudScore,
            ]);

            // Инициирование платежа через сервис
            $transaction = DB::transaction(function () use ($request, $correlationId) {
                return $this->paymentService->initPayment(
                    userId: auth()->id(),
                    tenantId: tenant('id'),
                    amount: (int) ($request->amount * 100), // копейки
                    currency: $request->currency ?? 'RUB',
                    paymentMethod: $request->payment_method,
                    idempotencyKey: $request->idempotency_key ?? (string) Str::uuid(),
                    correlationId: $correlationId,
                    metadata: $request->metadata ?? [],
                );
            });

            Log::channel('audit')->info('payment.init.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'correlation_id' => (string) $correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.init.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при инициировании платежа',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Захватить платёж (hold → capture).
     */
    public function capture(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $transaction = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->findOrFail($request->transaction_id);

            Log::channel('audit')->info('payment.capture.start', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
            ]);

            $captured = DB::transaction(function () use ($transaction, $correlationId) {
                return $this->paymentService->capturePayment(
                    transaction: $transaction,
                    correlationId: $correlationId,
                );
            });

            Log::channel('audit')->info('payment.capture.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $captured->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $captured,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.capture.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при захвате платежа',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Отменить платёж (refund).
     */
    public function refund(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $transaction = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->findOrFail($request->transaction_id);

            Log::channel('audit')->info('payment.refund.start', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
            ]);

            $refunded = DB::transaction(function () use ($transaction, $correlationId) {
                return $this->paymentService->refundPayment(
                    transaction: $transaction,
                    reason: $request->reason ?? 'User requested',
                    correlationId: $correlationId,
                );
            });

            Log::channel('audit')->info('payment.refund.success', [
                'correlation_id' => $correlationId,
                'transaction_id' => $refunded->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $refunded,
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.refund.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при возврате платежа',
                'correlation_id' => (string) $correlationId,
            ], 500);
        }
    }

    /**
     * Получить статус платежа.
     */
    public function status(Request $request): JsonResponse
    {
        $correlationId = Str::uuid();
        
        try {
            $transaction = PaymentTransaction::where('tenant_id', tenant('id'))
                ->where('user_id', auth()->id())
                ->findOrFail($request->transaction_id);

            Log::channel('audit')->info('payment.status.check', [
                'correlation_id' => $correlationId,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'provider_code' => $transaction->provider_code,
                ],
                'correlation_id' => (string) $correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('payment.status.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Платёж не найден',
                'correlation_id' => (string) $correlationId,
            ], 404);
        }
    }
}
