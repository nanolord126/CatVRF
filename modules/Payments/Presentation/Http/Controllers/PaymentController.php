<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Payments\Application\UseCases\InitiatePayment\InitiatePaymentCommand;
use Modules\Payments\Application\UseCases\InitiatePayment\InitiatePaymentUseCase;
use Modules\Payments\Application\UseCases\RefundPayment\RefundPaymentCommand;
use Modules\Payments\Application\UseCases\RefundPayment\RefundPaymentUseCase;
use Modules\Payments\Presentation\Http\Requests\InitiatePaymentRequest;
use Modules\Payments\Presentation\Http\Requests\RefundPaymentRequest;

/**
 * HTTP-контроллер для платежей.
 * Presentation Layer — только маршрутизация + HTTP.
 * Вся логика в UseCases.
 */
final class PaymentController extends Controller
{
    public function __construct(
        private readonly InitiatePaymentUseCase $initiateUseCase,
        private readonly RefundPaymentUseCase   $refundUseCase,
    ) {}

    /**
     * POST /api/v1/payments
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        $tenantId      = (int) filament()->getTenant()?->id ?? 0;

        // Rate limiting — 10 req/min per tenant
        $key = "payment:initiate:{$tenantId}";
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'error'          => 'Too many requests',
                'correlation_id' => $correlationId,
                'retry_after'    => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $result = $this->initiateUseCase->execute(new InitiatePaymentCommand(
                tenantId:       $tenantId,
                userId:         (int) $request->user()?->id,
                amountKopeks:   (int) $request->validated('amount'),
                currency:       $request->validated('currency', 'RUB'),
                idempotencyKey: $request->header('X-Idempotency-Key') ?? Str::uuid()->toString(),
                correlationId:  $correlationId,
                description:    $request->validated('description', 'Payment'),
                successUrl:     $request->validated('success_url'),
                failUrl:        $request->validated('fail_url'),
                hold:           (bool) $request->validated('hold', false),
                recurring:      (bool) $request->validated('recurring', false),
                metadata:       $request->validated('metadata', []),
            ));

            return response()->json([
                'payment_id'     => $result->paymentId,
                'payment_url'    => $result->paymentUrl,
                'status'         => $result->status,
                'is_duplicate'   => $result->isDuplicate,
                'correlation_id' => $result->correlationId,
            ], $result->isDuplicate ? 200 : 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('payment.initiate.http.error', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'          => 'Payment initiation failed',
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/v1/payments/{id}/refund
     */
    public function refund(RefundPaymentRequest $request, string $paymentId): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        $tenantId      = (int) filament()->getTenant()?->id ?? 0;

        try {
            $refundId = $this->refundUseCase->execute(new RefundPaymentCommand(
                paymentId:     $paymentId,
                tenantId:      $tenantId,
                amountKopeks:  (int) $request->validated('amount'),
                reason:        $request->validated('reason', 'Refund'),
                correlationId: $correlationId,
            ));

            return response()->json([
                'refund_id'      => $refundId,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('payment.refund.http.error', [
                'correlation_id' => $correlationId,
                'payment_id'     => $paymentId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
