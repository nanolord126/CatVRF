<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class PaymentController extends Controller
{

    public function __construct(
            private readonly PaymentService $paymentService,
            private readonly FraudControlService $fraud,
            private readonly RateLimiterService $rateLimiter,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly ResponseFactory $response,
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
                    $this->fraud->checkAndScore(
                        operationType: 'payment_init',
                        userId: $request->user()->id,
                        amount: $request->input('amount'),
                        ip: $request->ip()
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                        'exception' => $e::class,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'correlation_id' => request()->header('X-Correlation-ID'),
                    ]);

                    // Fraud blocks
                    $this->logger->channel('fraud_alert')->error('Payment blocked by Fraud Control', [
                        'user_id' => $request->user()->id,
                        'amount' => $request->input('amount'),
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json(['error' => 'Transaction blocked for security reasons.'], 403);
                }
                $validated = $request->all();
                $payment = $this->db->transaction(function () use ($validated, $correlationId) {
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
                $this->logger->channel('audit')->info('Payment initiated successfully', [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'user_id' => $request->user()->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'id' => $payment->uuid,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'hold' => $payment->is_hold,
                    'created_at' => $payment->created_at,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (DuplicatePaymentException $e) {
                $this->logger->channel('audit')->warning('Duplicate payment detected', [
                    'user_id' => $request->user()->id ?? null,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'message' => 'Duplicate payment detected',
                    'previous_payment' => $e->getPreviousPayment(),
                    'correlation_id' => $correlationId,
                ], 409);
            } catch (RateLimitException $e) {
                return $this->response->json([
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 429, [
                    'Retry-After' => $e->getRetryAfter(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Payment creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()->id ?? null,
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'message' => 'Payment creation failed',
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }
}
