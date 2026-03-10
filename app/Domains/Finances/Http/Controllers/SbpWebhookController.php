<?php

namespace App\Domains\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Finances\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Контроллер обработки вебхуков платёжных систем.
 *
 * Поддерживает:
 * - Tinkoff (приоритет)
 * - Tochka Bank (Точка)
 * - Sber (Сбер)
 * - SBP (СБП - Система быстрых платежей)
 *
 * Все вебхуки проверяются по подписи (HMAC-SHA256) для безопасности.
 */
class SbpWebhookController extends Controller
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
    }

    /**
     * Обработка вебхука платёжной системы (Tinkoff/Tochka/Sber/SBP).
     *
     * @param Request $request Объект запроса с вебхуком
     * @param PaymentService $service Сервис платежей
     * @return \Illuminate\Http\JsonResponse Ответ: {'status': 'OK'} или {'error': '...'}
     */
    public function handle(Request $request, PaymentService $service)
    {
        try {
            // Валидация подписи вебхука (CRITICAL для безопасности)
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Invalid webhook signature - possible attack attempt', [
                    'correlation_id' => $this->correlationId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();

            Log::channel('payments')->info('SBP Webhook received', [
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Валидация payload перед обработкой
            $validation = $this->validatePayload($payload);
            if (!$validation['valid']) {
                Log::warning('Invalid webhook payload', [
                    'correlation_id' => $this->correlationId,
                    'errors' => $validation['errors'],
                ]);
                return response()->json(['error' => 'Invalid payload', 'details' => $validation['errors']], 400);
            }

            // Обработка платежа с обработкой ошибок
            $service->handleWebhook($payload, $this->correlationId);

            Log::channel('payments')->info('SBP Webhook processed successfully', [
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json(['status' => 'OK'], 200);
        } catch (Throwable $e) {
            Log::error('SBP Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Валидация подписи вебхука (HMAC-SHA256).
     *
     * @param Request $request Объект запроса
     * @return bool True если подпись верна, false в противном случае
     */
    private function validateWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $secret = config('payments.webhook_secret');

        if (empty($secret)) {
            Log::error('Webhook secret not configured', [
                'correlation_id' => $this->correlationId,
            ]);
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Валидация структуры payload вебхука.
     *
     * @param array $payload Данные вебхука
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    private function validatePayload(array $payload): array
    {
        $errors = [];

        // Обязательные поля
        if (empty($payload['PaymentId'])) {
            $errors[] = 'PaymentId is required';
        }

        if (empty($payload['Status'])) {
            $errors[] = 'Status is required';
        } elseif (!in_array($payload['Status'], ['CONFIRMED', 'PENDING', 'FAILED', 'CANCELLED'])) {
            $errors[] = "Invalid Status: {$payload['Status']}";
        }

        if (isset($payload['Amount']) && !is_numeric($payload['Amount'])) {
            $errors[] = 'Amount must be numeric';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
