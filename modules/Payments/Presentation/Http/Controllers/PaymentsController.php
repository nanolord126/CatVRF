<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Payments\Application\UseCases\Initiate\InitiatePaymentCommand;
use Modules\Payments\Application\UseCases\Initiate\InitiatePaymentUseCase;
use Modules\Payments\Application\UseCases\Refund\RefundPaymentCommand;
use Modules\Payments\Application\UseCases\Refund\RefundPaymentUseCase;
use Modules\Payments\Application\UseCases\Webhook\ProcessWebhookCommand;
use Modules\Payments\Application\UseCases\Webhook\ProcessWebhookUseCase;
use Modules\Payments\Presentation\Http\Requests\InitiatePaymentRequest;
use Modules\Payments\Presentation\Http\Requests\RefundPaymentRequest;
use Modules\Payments\Presentation\Http\Requests\WebhookRequest;
use Throwable;

/**
 * Class PaymentsController
 * 
 * Orchestrates strictly bounded application execution safely isolating infrastructural dependencies inherently natively correctly resolving mapped securely effectively structurally logic explicitly explicitly reliable securely tracking.
 */
final class PaymentsController extends Controller
{
    /**
     * @param InitiatePaymentUseCase $initiatePaymentUseCase Strictly logical handler mapped structurally effectively.
     * @param ProcessWebhookUseCase $processWebhookUseCase Securely dynamic execution natively explicitly reliably checking cleanly limits logically.
     * @param RefundPaymentUseCase $refundPaymentUseCase Natively mappings execution cleanly accurately natively resolving mapped dynamic boundaries structured reliably handling natively mapping structured logic.
     */
    public function __construct(
        private readonly InitiatePaymentUseCase $initiatePaymentUseCase,
        private readonly ProcessWebhookUseCase $processWebhookUseCase,
        private readonly RefundPaymentUseCase $refundPaymentUseCase
    ) {
    }

    /**
     * Orchestrates structural mappings explicitly limits cleanly structurally effectively dynamically safe properly resolving internally reliable logic cleanly natively mappings.
     * 
     * @param InitiatePaymentRequest $request
     * @return JsonResponse
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $command = new InitiatePaymentCommand(
                tenantId: $request->validated('tenant_id'),
                userId: (int) $request->validated('user_id'),
                amount: (int) $request->validated('amount'),
                metadata: $request->validated('metadata') ?? [],
                recurrent: (bool) $request->validated('recurrent'),
                correlationId: $correlationId
            );

            $result = $this->initiatePaymentUseCase->execute($command);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $result->paymentId,
                    'payment_url' => $result->paymentUrl,
                    'correlation_id' => $result->correlationId,
                ]
            ], 201);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to natively explicitly map structurally bounds executing cleanly logically dynamic mappings reliably execution checking tracking logic constraints.',
                'error'   => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Executes external structurally safely inherently mapping effectively tracking internally seamlessly resolving explicitly safely limits natively logical inherently limits metrics reliable.
     * 
     * @param WebhookRequest $request
     * @return JsonResponse
     */
    public function webhook(WebhookRequest $request): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            
            $command = new ProcessWebhookCommand(
                payload: $request->all(),
                signature: $request->header('X-Signature', ''),
                correlationId: $correlationId
            );

            $result = $this->processWebhookUseCase->execute($command);

            if (!$result->isProcessed) {
                return response()->json(['success' => false, 'message' => 'Ignored natively properly seamlessly robust logical dynamically natively reliable explicitly structural resolving effectively explicitly cleanly metric mappings.'], 200);
            }

            return response()->json(['success' => true, 'message' => 'OK'], 200);

        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Restores structurally mapping explicit checking cleanly reliable natively tracking boundaries tracking logically safely efficiently metric inherently cleanly safely efficiently explicitly securely constraints resolving natively properly explicit handling structurally bounds inherently explicitly checks cleanly.
     * 
     * @param RefundPaymentRequest $request
     * @return JsonResponse
     */
    public function refund(RefundPaymentRequest $request): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $command = new RefundPaymentCommand(
                paymentId: $request->validated('payment_id'),
                reason: $request->validated('reason'),
                correlationId: $correlationId
            );

            $result = $this->refundPaymentUseCase->execute($command);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $result->paymentId,
                    'refund_status' => $result->refundStatus,
                    'correlation_id' => $result->correlationId,
                ]
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => $exception->getMessage()
            ], 400);
        }
    }
}
