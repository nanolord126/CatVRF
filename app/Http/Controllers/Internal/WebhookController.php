<?php
declare(strict_types=1);
namespace App\Http\Controllers\Internal;
use App\Services\Security\WebhookSignatureService;
use App\Exceptions\InvalidPayloadException;
use App\Domains\Consulting\Finances\Services\PaymentService;
use App\Services\FraudControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
/**
 * WebhookController — обработка вебхуков платёжных систем.
 * Middleware: IpWhitelistMiddleware:webhook
 * Все операции защищены: signature verification, fraud checks.
 */
final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureService $signatureService,
        private readonly PaymentService $paymentService,
        private readonly FraudControlService $fraudControl,
    ) {}
    /**
     * Обработать вебхук от платёжной системы.
     * 
     * Маршрут: POST /api/internal/webhooks/{provider}
     * Провайдеры: tinkoff, sber, sbp
     * Middleware: IpWhitelistMiddleware:webhook (IP whitelisted)
     * 
     * Процесс:
     * 1. Проверка подписи (HMAC или сертификат)
     * 2. Проверка IP (CIDR whitelist)
     * 3. Обновление статуса платежа
     * 4. Логирование с correlation_id
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            // Получить тело запроса (raw JSON)
            $payload = $request->getContent();
            $data = json_decode($payload, true) ?? (array) $request->input();
            Log::channel('audit')->info('Webhook received', [
                'provider' => $provider,
                'correlation_id' => $correlationId,
                'ip' => $request->ip(),
                'signature' => $request->header('X-Signature') ? 'present' : 'missing',
            ]);
            // КАНОН 2026: Проверить подпись вебхука
            if (!$this->signatureService->verify($provider, $payload, $request->headers->all())) {
                Log::channel('fraud_alert')->warning('Invalid webhook signature', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                throw new InvalidPayloadException(
                    'Invalid webhook signature',
                    400
                );
            }
            // Проверить IP (дополнительная защита)
            if (!$this->signatureService->isIpWhitelisted($provider, $request->ip())) {
                Log::channel('fraud_alert')->warning('Webhook from non-whitelisted IP', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                throw new InvalidPayloadException(
                    'IP address not whitelisted',
                    403
                );
            }
            // Извлечь информацию о платеже из разных провайдеров
            $paymentInfo = $this->extractPaymentInfo($provider, $data);
            // Найти платёж в БД
            $payment = \App\Domains\Consulting\Finances\Models\PaymentTransaction::where(
                'provider_payment_id',
                $paymentInfo['provider_payment_id']
            )->first();
            if (!$payment) {
                Log::channel('audit')->warning('Webhook payment not found', [
                    'provider' => $provider,
                    'provider_payment_id' => $paymentInfo['provider_payment_id'],
                    'correlation_id' => $correlationId,
                ]);
                // Даже если платёж не найден, возвращаем 200 (idempotent)
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Webhook processed',
                    'correlation_id' => $correlationId,
                ]);
            }
            // КАНОН 2026: DB::transaction() для всех мутаций
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $paymentInfo, $provider, $correlationId) {
                // Обновить статус платежа
                $payment->update([
                    'status' => $paymentInfo['status'],
                    'authorized_at' => $paymentInfo['authorized_at'] ?? $payment->authorized_at,
                    'captured_at' => $paymentInfo['captured_at'] ?? $payment->captured_at,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'webhook_received_at' => now()->toIso8601String(),
                        'provider' => $provider,
                    ]),
                ]);
                // Если платёж успешно захвачен — зачислить на кошелёк
                if ($paymentInfo['status'] === 'captured' && !$payment->is_captured) {
                    $this->paymentService->capturePayment($payment, $correlationId);
                }
            });
            Log::channel('audit')->info('Webhook processed successfully', [
                'provider' => $provider,
                'payment_id' => $payment->id,
                'new_status' => $paymentInfo['status'],
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'status' => 'ok',
                'message' => 'Webhook processed',
                'correlation_id' => $correlationId,
            ]);
        } catch (InvalidPayloadException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::channel('audit')->error('Webhook processing error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * Извлечь информацию о платеже из вебхука.
     */
    private function extractPaymentInfo(string $provider, array $data): array
    {
        return match ($provider) {
            'tinkoff' => $this->extractTinkoffInfo($data),
            'sber' => $this->extractSberInfo($data),
            'sbp' => $this->extractSbpInfo($data),
            default => throw new InvalidPayloadException("Unknown payment provider: {$provider}", 400),
        };
    }
    /**
     * Парсинг Tinkoff вебхука.
     */
    private function extractTinkoffInfo(array $data): array
    {
        if (!isset($data['OrderId'])) {
            throw new InvalidPayloadException('Invalid Tinkoff webhook payload: missing OrderId', 400);
        }
        $statusMap = [
            'AUTHORIZED' => 'authorized',
            'CONFIRMED' => 'captured',
            'REJECTED' => 'failed',
            'CANCELED' => 'cancelled',
        ];
        return [
            'provider_payment_id' => $data['PaymentId'] ?? null,
            'status' => $statusMap[$data['Status']] ?? 'pending',
            'authorized_at' => isset($data['AuthDateTime']) ? now() : null,
            'captured_at' => $data['Status'] === 'CONFIRMED' ? now() : null,
        ];
    }
    /**
     * Парсинг Sber вебхука.
     */
    private function extractSberInfo(array $data): array
    {
        if (!isset($data['ordernumber'])) {
            throw new InvalidPayloadException('Invalid Sber webhook payload: missing ordernumber', 400);
        }
        $statusMap = [
            '1' => 'authorized',
            '2' => 'captured',
            '0' => 'failed',
        ];
        return [
            'provider_payment_id' => $data['mdOrder'] ?? null,
            'status' => $statusMap[$data['orderStatus']] ?? 'pending',
            'authorized_at' => in_array($data['orderStatus'], ['1', '2']) ? now() : null,
            'captured_at' => $data['orderStatus'] === '2' ? now() : null,
        ];
    }
    /**
     * Парсинг СБП вебхука.
     */
    private function extractSbpInfo(array $data): array
    {
        if (!isset($data['order_id'])) {
            throw new InvalidPayloadException('Invalid SBP webhook payload: missing order_id', 400);
        }
        $statusMap = [
            'ACCEPTED' => 'authorized',
            'COMPLETED' => 'captured',
            'REJECTED' => 'failed',
            'CANCELLED' => 'cancelled',
        ];
        return [
            'provider_payment_id' => $data['transaction_id'] ?? null,
            'status' => $statusMap[$data['status']] ?? 'pending',
            'authorized_at' => isset($data['created_at']) ? now() : null,
            'captured_at' => $data['status'] === 'COMPLETED' ? now() : null,
        ];
    }
}
