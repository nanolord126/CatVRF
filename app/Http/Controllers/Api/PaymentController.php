<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PaymentInitRequest;
use App\Services\Payment\PaymentService;
use App\Exceptions\DuplicatePaymentException;
use App\Exceptions\RateLimitException;
use App\Services\FraudControlService;
use App\Services\Security\RateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FraudControlService $fraudControl,
        private readonly RateLimiterService $rateLimiter
    ) {}

    public function init(PaymentInitRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            // Apply Rate Limiting
            $this->rateLimiter->ensureLimit(
                key: 'payment_init_' . $request->user()->id,
                maxAttempts: 10,
                decayMinutes: 1
            );

            // Apply Fraud Check before payment processing
            try {
                $this->fraudControl->checkAndScore(
                    operationType: 'payment_init',
                    userId: $request->user()->id,
                    amount: $request->input('amount'),
                    ip: $request->ip()
                );
            } catch (\Exception $e) {
                // Fraud blocks
                Log::channel('fraud_alert')->error('Payment blocked by Fraud Control', [
                    'user_id' => $request->user()->id,
                    'amount' => $request->input('amount'),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json(['error' => 'Transaction blocked for security reasons.'], 403);
            }

            $validated = $request->all();
            $payment = DB::transaction(function () use ($validated, $correlationId) {
                return $this->paymentService->initPayment(
                    tenantId: ($validated['tenant_id'] ?? null),
                    userId: $request->user()->id,
                    amount: ($validated['amount'] ?? null),
                    currency: ($validated['currency'] ?? 'RUB'),
                    isHold: ($validated['hold'] ?? false),
                    idempotencyKey: ($validated['idempotency_key'] ?? null),
                    correlationId: $correlationId
                );
            });

            Log::channel('audit')->info('Payment initiated successfully', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'user_id' => $request->user()->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'id' => $payment->uuid,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'hold' => $payment->is_hold,
                'created_at' => $payment->created_at,
                'correlation_id' => $correlationId,
            ], 201);

        } catch (DuplicatePaymentException $e) {
            Log::channel('audit')->warning('Duplicate payment detected', [
                'user_id' => $request->user()->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'message' => 'Duplicate payment detected',
                'previous_payment' => $e->getPreviousPayment(),
                'correlation_id' => $correlationId,
            ], 409);

        } catch (RateLimitException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 429, [
                'Retry-After' => $e->getRetryAfter(),
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Payment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Payment creation failed',
                'correlation_id' => $correlationId,
            ], 400);
        }
    }
}
