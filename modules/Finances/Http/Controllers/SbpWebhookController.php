<?php

namespace App\Domains\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Finances\Services\PaymentService;
use App\Jobs\Domains\Finances\Jobs\ProcessSbpWebhookJob;
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
     */
    public function handle(Request $request)
    {
        try {
            // Определение тенанта из запроса (обязательно для Multi-tenancy)
            // В реальной системе это может быть TerminalKey или sub-domain
            $tenantId = $request->header('X-Tenant-Id') ?? $request->input('TerminalKey');

            if (!$tenantId) {
                Log::error('Tenant ID not found in webhook', [
                    'correlation_id' => $this->correlationId,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Tenant identification failed'], 400);
            }

            // Валидация подписи вебхука (CRITICAL для безопасности)
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Invalid webhook signature - possible attack attempt', [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $tenantId,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();

            Log::channel('payments')->info('SBP Webhook received', [
                'tenant_id' => $tenantId,
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Валидация payload перед обработкой
            $validation = $this->validatePayload($payload);
            if (!$validation['valid']) {
                Log::warning('Invalid webhook payload', [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $tenantId,
                    'errors' => $validation['errors'],
                ]);
                return response()->json(['error' => 'Invalid payload', 'details' => $validation['errors']], 400);
            }

            // Асинхронная обработка платежа через Job (согласно архитектуре 2026)
            ProcessSbpWebhookJob::dispatch($payload, $this->correlationId, (string)$tenantId);

            return response()->json(['status' => 'OK'], 200);
        } catch (Throwable $e) {
            Log::error('SBP Webhook receiving failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
            return response()->json(['error' => 'Internal error'], 500);
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

        $payload = $request->all(); // Используем отфильтрованные данные
        $secret = (string) config('payments.webhook_secret');

        if (empty($secret)) {
            Log::error('Webhook secret not configured', [
                'correlation_id' => $this->correlationId,
            ]);
            return false;
        }

        // Для Tinkoff и многих СБП-шлюзов важен порядок полей для токена
        // Сортируем ключи и исключаем 'Token' / 'Signature' если они в теле
        unset($payload['Token'], $payload['Signature']);
        ksort($payload);
        
        $payload['Password'] = $secret; // Добавляем секрет согласно протоколу склеивания параметров
        $dataToString = implode('', array_values($payload));
        $expectedSignature = hash('sha256', $dataToString);

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
