<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Payment Webhook Controller — обработка webhook-ов от платёжных шлюзов.
 *
 * КРИТИЧНО: эти endpoints не имеют auth:sanctum.
 * Верификация через HMAC-подпись + IP-whitelist в middleware.
 */
final class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * POST /webhooks/tinkoff — обработка webhook от Тинькофф.
     */
    public function handleTinkoff(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $payload = $request->all();

            $this->logger->channel('audit')->info('Tinkoff webhook received', [
                'correlation_id' => $correlationId,
                'terminal_key' => $payload['TerminalKey'] ?? 'unknown',
                'order_id' => $payload['OrderId'] ?? 'unknown',
                'status' => $payload['Status'] ?? 'unknown',
                'amount' => $payload['Amount'] ?? 0,
            ]);

            $result = $this->paymentService->handleWebhook('tinkoff', $payload, $correlationId);

            if ($result) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ], 200);
            }

            $this->logger->channel('audit')->warning('Tinkoff webhook processing returned false', [
                'correlation_id' => $correlationId,
                'payload' => $payload,
            ]);

            return $this->response->json([
                'success' => false,
                'correlation_id' => $correlationId,
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Tinkoff webhook error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Internal webhook error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /webhooks/tochka — обработка webhook от Точки.
     */
    public function handleTochka(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $payload = $request->all();

            $this->logger->channel('audit')->info('Tochka webhook received', [
                'correlation_id' => $correlationId,
                'payment_id' => $payload['payment_id'] ?? 'unknown',
                'status' => $payload['status'] ?? 'unknown',
            ]);

            $result = $this->paymentService->handleWebhook('tochka', $payload, $correlationId);

            if ($result) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ], 200);
            }

            return $this->response->json([
                'success' => false,
                'correlation_id' => $correlationId,
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Tochka webhook error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Internal webhook error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /webhooks/sber — обработка webhook от Сбера.
     */
    public function handleSber(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $payload = $request->all();

            $this->logger->channel('audit')->info('Sber webhook received', [
                'correlation_id' => $correlationId,
                'order_number' => $payload['orderNumber'] ?? 'unknown',
                'operation' => $payload['operation'] ?? 'unknown',
                'status' => $payload['status'] ?? 'unknown',
            ]);

            $result = $this->paymentService->handleWebhook('sber', $payload, $correlationId);

            if ($result) {
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ], 200);
            }

            return $this->response->json([
                'success' => false,
                'correlation_id' => $correlationId,
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Sber webhook error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Internal webhook error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
