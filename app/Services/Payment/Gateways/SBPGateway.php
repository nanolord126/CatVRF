<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;



use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SBPGateway
 *
 * Система Быстрых Платежей (СБП) — универсальная платёжная система Центрального Банка РФ.
 * Поддерживает QRIS-совместимые QR-коды и мгновенные платежи через мобильные приложения.
 *
 * Протокол: НСПК (Национальная система платёжных карт)
 * Документация: https://sbp.nspk.ru/
 *
 * Особенности:
 * - Комиссия: ~0.5% (самая низкая)
 * - Скорость: мгновенное зачисление
 * - Поддержка QR-кодов (Dynamic/Static)
 * - Универсальный QR для всех СБП-банков
 *
 * @final
 */
final class SBPGateway implements PaymentGatewayInterface
{
    private const BASE_URL = 'https://api.sbp.nspk.ru/v1';

    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly string $merchantId,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
        private string $fiscalApiKey = '',
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly LogManager $logger,
    ) {}

    /**
     * Инициировать платёж через СБП (создать QR-код)
     *
     * @param array $data
     * @return array
     *
     * @throws \App\Exceptions\FraudException
     * @throws RuntimeException
     */
    public function initPayment(array $data): array
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'payment_init_gateway',
            'gateway' => 'sbp',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('SBP: Payment initialization started', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
            'qr_type' => $data['qr_type'] ?? 'QRDynamic',
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

        try {
            $response = $this->http->withToken($this->apiKey)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post(self::BASE_URL . '/qr/register', $payload);

            if ($response->failed()) {
                throw new RuntimeException(
                    'SBP: не удалось создать QR-платёж. Код: ' . $response->status(),
                );
            }

            $result = $response->json();

            $this->logger->channel('audit')->info('SBP: QR created', [
                'correlation_id' => $correlationId,
                'qr_id' => $result['qrId'] ?? null,
                'order_id' => $data['order_id'] ?? null,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Payment init failed', [
                'correlation_id' => $correlationId,
                'amount' => $data['amount'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статус платежа СБП
     *
     * @param string $providerPaymentId
     * @return array
     */
    public function getStatus(string $providerPaymentId): array
    {
        $response = $this->http->withToken($this->apiKey)
            ->get(self::BASE_URL . '/qr/' . $providerPaymentId . '/payment-info');

        if ($response->failed()) {
            throw new RuntimeException('SBP: не удалось получить статус платежа: ' . $providerPaymentId);
        }

        return $response->json();
    }

    /**
     * Захватить (списать) платёж СБП
     *
     * ВАЖНО: СБП по умолчанию работает в режиме немедленного списания (одностадийный).
     * Capture нужен только при двухстадийной оплате (twoStagePayment=true при initPayment).
     *
     * @param PaymentTransaction $transaction
     * @param string|null $correlationId
     * @return bool
     *
     * @throws \App\Exceptions\FraudException
     */
    public function capture(PaymentTransaction $transaction, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'payment_capture_gateway',
            'gateway' => 'sbp',
            'amount' => $transaction->amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('SBP: Payment capture started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'amount' => $transaction->amount,
        ]);

        try {
            $response = $this->http->withToken($this->apiKey)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post(self::BASE_URL . '/payment/' . $transaction->provider_payment_id . '/confirm', [
                    'merchantId' => $this->merchantId,
                    'amount' => $transaction->amount,
                ]);

            if ($response->failed()) {
                throw new RuntimeException("SBP capture failed: {$response->status()}");
            }

            $result = $response->json();
            $success = in_array($result['status'] ?? '', ['CONFIRMED', 'SUCCESS'], true);

            if ($success) {
                $this->logger->channel('audit')->info('SBP: Payment capture succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'status' => $result['status'] ?? null,
                ]);
            } else {
                $this->logger->channel('audit')->warning('SBP: Payment capture returned non-success status', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'status' => $result['status'] ?? 'unknown',
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Payment capture exception', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Вернуть (возместить) платёж СБП
     *
     * @param PaymentTransaction $transaction
     * @param int $amount
     * @param string|null $correlationId
     * @return bool
     *
     * @throws \App\Exceptions\FraudException
     */
    public function refund(PaymentTransaction $transaction, int $amount, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'payment_refund_gateway',
            'gateway' => 'sbp',
            'amount' => $amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('SBP: Payment refund initiated', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'refund_amount' => $amount,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        try {
            $response = $this->http->withToken($this->apiKey)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post(self::BASE_URL . '/refund', [
                    'merchantId' => $this->merchantId,
                    'originalTransactionId' => $transaction->provider_payment_id,
                    'amount' => $amount,
                    'currency' => 'RUB',
                    'purpose' => 'Возврат по заказу ' . $transaction->id,
                ]);

            if ($response->failed()) {
                throw new RuntimeException("SBP refund failed: {$response->status()}");
            }

            $result = $response->json();
            $success = in_array($result['status'] ?? '', ['REFUNDED', 'SUCCESS'], true);

            if ($success) {
                $this->logger->channel('audit')->info('SBP: Payment refund succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'refunded_amount' => $amount,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Payment refund exception', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'refund_amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Создать выплату через СБП C2B (转移на счёт бизнеса)
     *
     * @param array $data
     * @return array
     *
     * @throws \App\Exceptions\FraudException
     */
    public function createPayout(array $data): array
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'payout_gateway',
            'gateway' => 'sbp',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('SBP: Payout initiated', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'account_number' => $data['account_number'] ?? null,
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

        try {
            $response = $this->http->withToken($this->apiKey)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post(self::BASE_URL . '/payout/register', $payload);

            if ($response->failed()) {
                throw new RuntimeException('SBP: не удалось создать выплату. Код: ' . $response->status());
            }

            $this->logger->channel('audit')->info('SBP: Payout succeeded', [
                'correlation_id' => $correlationId,
                'amount' => $data['amount'],
            ]);

            return $response->json();
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Payout failed', [
                'correlation_id' => $correlationId,
                'amount' => $data['amount'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Обработать вебхук от СБП (подтверждение платежа)
     *
     * Тип подписи: HMAC-SHA256
     * Формат подписи: X-Signature заголовок или _signature в payload
     * Гарантия: Вебхук только для completed платежей (ACSC статус)
     *
     * @param array $payload
     * @return array
     *
     * @throws \RuntimeException
     */
    public function handleWebhook(array $payload): array
    {
        // Получить correlation_id из payload или заголовка
        $correlationId = $payload['correlationId'] ?? $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            // 1. Проверка подписи HMAC-SHA256 (КАНОН SECURITY 2026)
            $signature = $payload['_signature'] ?? $this->request->header('X-Signature') ?? '';

            // Удалить подпись из payload перед вычислением хеша
            $payloadForHash = $payload;
            unset($payloadForHash['_signature']);

            $expectedSig = hash_hmac('sha256', json_encode($payloadForHash, JSON_UNESCAPED_UNICODE), $this->webhookSecret);

            if (!hash_equals($expectedSig, $signature)) {
                $this->logger->channel('audit')->warning('SBP: Webhook signature invalid', [
                    'correlation_id' => $correlationId,
                    'transaction_id' => $payload['transactionId'] ?? null,
                    'expected' => substr($expectedSig, 0, 8) . '...',
                    'received' => substr($signature, 0, 8) . '...',
                ]);

                throw new RuntimeException('SBP: недействительная подпись вебхука.');
            }

            $this->logger->channel('audit')->info('SBP: Webhook received', [
                'correlation_id' => $correlationId,
                'transaction_id' => $payload['transactionId'] ?? null,
                'status' => $payload['transactionStatus'] ?? null,
                'amount' => $payload['amount'] ?? null,
            ]);

            // 2. Преобразовать статус СБП в локальный формат
            $statusMap = [
                'ACSC' => 'captured',  // AcceptedSettlementCompleted
                'ACSP' => 'authorized', // AcceptedSettlementInProcess
                'RJCT' => 'failed',    // Rejected
                'CANC' => 'failed',    // Cancelled
                'RCNC' => 'refunded',  // Refunded
            ];

            $result = [
                'provider' => 'sbp',
                'provider_payment_id' => (string) ($payload['transactionId'] ?? ''),
                'order_id' => (string) ($payload['orderId'] ?? $payload['merchantOrderId'] ?? ''),
                'status' => $statusMap[$payload['transactionStatus'] ?? ''] ?? 'unknown',
                'amount' => (int) ($payload['amount'] ?? 0),
                'payer_bank' => $payload['payerBankId'] ?? null,
                'correlation_id' => $correlationId,
                'raw' => $payloadForHash,
            ];

            $this->logger->channel('audit')->info('SBP: Webhook processed', [
                'correlation_id' => $correlationId,
                'transaction_id' => $payload['transactionId'] ?? null,
                'status' => $result['status'],
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Webhook processing failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Отправить данные платежа в ОФД (54-ФЗ Фискализация)
     *
     * СБП с Яндекс.Касса использует встроенную fiscalization,
     * но для прямых СБП-платежей нужна отправка в отдельный ОФД-провайдер
     *
     * @param PaymentTransaction $transaction
     * @param ?string $correlationId
     * @return bool
     */
    public function fiscalize(PaymentTransaction $transaction, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        if (empty($this->fiscalApiKey)) {
            $this->logger->channel('audit')->warning('SBP: Fiscal API key not configured, skipping', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
            ]);

            return false;
        }

        $this->logger->channel('audit')->info('SBP: Fiscalizing', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'amount' => $transaction->amount,
        ]);

        try {
            // ОФД отправляется через отдельный провайдер (Атол/Первый ОФД/CloudKassir)
            // URL и параметры зависят от договора с ОФД-агентом
            $response = $this->http->withToken($this->fiscalApiKey)
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post($this->config->get('payments.fiscal_api_url', 'https://online.atol.ru/api/v4/') . 'sell', [
                    'external_id' => $transaction->id,
                    'correlation_id' => $correlationId,
                    'receipt' => [
                        'client' => [
                            'email' => $transaction->customer_email ?? '',
                        ],
                        'company' => [
                            'email' => $this->config->get('app.company_email', ''),
                            'inn' => $this->config->get('app.company_inn', ''),
                            'payment_address' => $this->config->get('app.company_payment_address', ''),
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
                $this->logger->channel('audit')->error('SBP: Fiscalization failed', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $this->logger->channel('audit')->info('SBP: Fiscalization succeeded', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'uuid' => $response->json()['uuid'] ?? null,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('SBP: Fiscalization error', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
