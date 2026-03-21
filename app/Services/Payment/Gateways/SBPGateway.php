<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * СБП (Система Быстрых Платежей) — шлюз ЦБ РФ.
 * Реализует PaymentGatewayInterface (КАНОН 2026).
 *
 * Протокол: НСПК / QRIS-совместимый universal QR.
 * Документация: https://sbp.nspk.ru/
 */
final class SBPGateway implements PaymentGatewayInterface
{
    private const BASE_URL = 'https://api.sbp.nspk.ru/v1';

    public function __construct(
        private readonly string $merchantId,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
        private readonly string $fiscalApiKey = '',
    ) {}

    // -------------------------------------------------------------------------
    // initPayment
    // -------------------------------------------------------------------------

    public function initPayment(array $data): array
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        Log::channel('audit')->info('SBP: Initializing payment', [
            'amount' => $data['amount'],
            'order_id' => $data['order_id'],
            'correlation_id' => $correlationId,
        ]);

        $payload = [
            'merchantId' => $this->merchantId,
            'orderId' => (string) $data['order_id'],
            'amount' => (int) $data['amount'],           // копейки
            'currency' => 'RUB',
            'purpose' => $data['description'] ?? 'Оплата заказа',
            'qrType' => $data['qr_type'] ?? 'QRDynamic',  // QRStatic или QRDynamic
            'redirectUrl' => $data['return_url'] ?? '',
            'expirationDate' => now()->addMinutes(15)->toIso8601String(),
            'customerId' => (string) ($data['customer_id'] ?? ''),
        ];

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['X-Correlation-ID' => $correlationId])
            ->post(self::BASE_URL . '/qr/register', $payload);

        if ($response->failed()) {
            Log::channel('audit')->error('SBP: initPayment failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'correlation_id' => $correlationId,
            ]);

            throw new RuntimeException(
                'SBP: не удалось создать QR-платёж. Код: ' . $response->status(),
            );
        }

        $result = $response->json();

        Log::channel('audit')->info('SBP: QR created', [
            'qr_id' => $result['qrId'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    // -------------------------------------------------------------------------
    // getStatus
    // -------------------------------------------------------------------------

    public function getStatus(string $providerPaymentId): array
    {
        $response = Http::withToken($this->apiKey)
            ->get(self::BASE_URL . '/qr/' . $providerPaymentId . '/payment-info');

        if ($response->failed()) {
            throw new RuntimeException('SBP: не удалось получить статус платежа: ' . $providerPaymentId);
        }

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // capture
    // -------------------------------------------------------------------------

    public function capture(PaymentTransaction $transaction): bool
    {
        // СБП по умолчанию работает в режиме немедленного списания (не hold).
        // Capture нужен только при двухстадийной оплате (twoStagePayment=true).
        Log::channel('audit')->info('SBP: Capturing payment', [
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        $response = Http::withToken($this->apiKey)
            ->post(self::BASE_URL . '/payment/' . $transaction->provider_payment_id . '/confirm', [
                'merchantId' => $this->merchantId,
                'amount' => $transaction->amount,
            ]);

        $result = $response->json();

        Log::channel('audit')->info('SBP: Capture result', [
            'payment_id' => $transaction->id,
            'status' => $result['status'] ?? null,
        ]);

        return in_array($result['status'] ?? '', ['CONFIRMED', 'SUCCESS'], true);
    }

    // -------------------------------------------------------------------------
    // refund
    // -------------------------------------------------------------------------

    public function refund(PaymentTransaction $transaction, int $amount): bool
    {
        $correlationId = Str::uuid()->toString();

        Log::channel('audit')->info('SBP: Processing refund', [
            'payment_id' => $transaction->id,
            'amount' => $amount,
            'correlation_id' => $correlationId,
        ]);

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['X-Correlation-ID' => $correlationId])
            ->post(self::BASE_URL . '/refund', [
                'merchantId' => $this->merchantId,
                'originalTransactionId' => $transaction->provider_payment_id,
                'amount' => $amount,
                'currency' => 'RUB',
                'purpose' => 'Возврат по заказу ' . $transaction->id,
            ]);

        if ($response->failed()) {
            Log::channel('audit')->error('SBP: Refund failed', [
                'payment_id' => $transaction->id,
                'body' => $response->body(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }

        $result = $response->json();

        Log::channel('audit')->info('SBP: Refund result', [
            'payment_id' => $transaction->id,
            'refund_status' => $result['status'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return in_array($result['status'] ?? '', ['REFUNDED', 'SUCCESS'], true);
    }

    // -------------------------------------------------------------------------
    // createPayout (СБП C2B — перевод на счёт бизнеса)
    // -------------------------------------------------------------------------

    public function createPayout(array $data): array
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        Log::channel('audit')->info('SBP: Creating C2B payout', [
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $payload = [
            'merchantId' => $this->merchantId,
            'paymentId' => Str::uuid()->toString(),
            'amount' => (int) $data['amount'],
            'currency' => 'RUB',
            'bankId' => $data['bank_id'] ?? '',           // БИК банка получателя
            'accountNumber' => $data['account_number'] ?? '',
            'purpose' => $data['description'] ?? 'Выплата',
        ];

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['X-Correlation-ID' => $correlationId])
            ->post(self::BASE_URL . '/payout/register', $payload);

        if ($response->failed()) {
            Log::channel('audit')->error('SBP: Payout failed', [
                'body' => $response->body(),
                'correlation_id' => $correlationId,
            ]);

            throw new RuntimeException('SBP: не удалось создать выплату. Код: ' . $response->status());
        }

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // handleWebhook
    // -------------------------------------------------------------------------

    public function handleWebhook(array $payload): array
    {
        // Проверка подписи HMAC-SHA256 (КАНОН SECURITY 2026)
        $signature = $payload['_signature'] ?? '';
        unset($payload['_signature']);

        $expectedSig = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $this->webhookSecret);

        if (!hash_equals($expectedSig, $signature)) {
            Log::channel('audit')->warning('SBP: Invalid webhook signature', [
                'expected' => substr($expectedSig, 0, 8) . '...',
                'received' => substr($signature, 0, 8) . '...',
            ]);

            throw new RuntimeException('SBP: недействительная подпись вебхука.');
        }

        Log::channel('audit')->info('SBP: Webhook received', [
            'transaction_id' => $payload['transactionId'] ?? null,
            'status' => $payload['transactionStatus'] ?? null,
        ]);

        $statusMap = [
            'ACSC' => 'captured',  // AcceptedSettlementCompleted
            'ACSP' => 'authorized', // AcceptedSettlementInProcess
            'RJCT' => 'failed',    // Rejected
            'CANC' => 'failed',    // Cancelled
            'RCNC' => 'refunded',  // Refunded
        ];

        return [
            'provider' => 'sbp',
            'provider_payment_id' => (string) ($payload['transactionId'] ?? ''),
            'order_id' => (string) ($payload['orderId'] ?? $payload['merchantOrderId'] ?? ''),
            'status' => $statusMap[$payload['transactionStatus'] ?? ''] ?? 'unknown',
            'amount' => (int) ($payload['amount'] ?? 0),
            'payer_bank' => $payload['payerBankId'] ?? null,
            'raw' => $payload,
        ];
    }

    // -------------------------------------------------------------------------
    // fiscalize (54-ФЗ через ОФД-провайдера)
    // -------------------------------------------------------------------------

    public function fiscalize(PaymentTransaction $transaction): bool
    {
        if (empty($this->fiscalApiKey)) {
            Log::channel('audit')->warning('SBP: Fiscal API key not set, skipping fiscalization', [
                'payment_id' => $transaction->id,
            ]);

            return false;
        }

        Log::channel('audit')->info('SBP: Fiscalizing', ['payment_id' => $transaction->id]);

        // ОФД отправляется через отдельный провайдер (Атол/Первый ОФД/CloudKassir)
        // URL и параметры зависят от договора с ОФД-агентом
        $response = Http::withToken($this->fiscalApiKey)
            ->post(config('payments.fiscal_api_url', 'https://online.atol.ru/api/v4/') . 'sell', [
                'external_id' => $transaction->id,
                'receipt' => [
                    'client' => [
                        'email' => $transaction->customer_email ?? '',
                    ],
                    'company' => [
                        'email' => config('app.company_email', ''),
                        'inn' => config('app.company_inn', ''),
                        'payment_address' => config('app.company_payment_address', ''),
                    ],
                    'items' => [
                        [
                            'name' => $transaction->description ?? 'Услуга',
                            'price' => $transaction->amount / 100,   // рубли
                            'sum' => $transaction->amount / 100,
                            'quantity' => 1,
                            'payment_method' => 'full_payment',
                            'payment_object' => 'service',
                            'vat' => ['type' => 'none'],
                        ],
                    ],
                    'payments' => [
                        [
                            'type' => 1,
                            'sum' => $transaction->amount / 100,
                        ],
                    ],
                    'total' => $transaction->amount / 100,
                ],
            ]);

        if ($response->failed()) {
            Log::channel('audit')->error('SBP: Fiscalization failed', [
                'payment_id' => $transaction->id,
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::channel('audit')->info('SBP: Fiscalized successfully', [
            'payment_id' => $transaction->id,
            'uuid' => $response->json()['uuid'] ?? null,
        ]);

        return true;
    }
}
