<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * TinkoffGateway
 *
 * Интеграция с платёжной системой Tinkoff (захват, возврат, fiscalization).
 *
 * API: https://securepay.tinkoff.ru/v2/
 * Документация: https://www.tinkoff.ru/business/kasssa/
 *
 * @final
 */
final class TinkoffGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $terminalKey,
        private readonly string $secretKey,
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Инициировать платёж через Tinkoff API
     *
     * @param array $data
     * @return array
     *
     * @throws \App\Exceptions\FraudException
     */
    public function initPayment(array $data): array
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'payment_init_gateway',
            'gateway' => 'tinkoff',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Tinkoff: Payment initialization started', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $data['amount'],
            'OrderId' => $data['order_id'],
            'Description' => $data['description'] ?? '',
            'CustomerKey' => $data['customer_key'] ?? '',
            'Recurrent' => $data['recurrent'] ?? false,
            'Token' => $this->generateToken($data),
        ];

        $response = $this->http->post('https://securepay.tinkoff.ru/v2/Init', $payload);

        if (!$response->successful()) {
            $this->log->channel('audit')->error('Tinkoff: Payment init failed', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception("Tinkoff init failed: {$response->status()}");
        }

        $this->log->channel('audit')->info('Tinkoff: Payment init succeeded', [
            'correlation_id' => $correlationId,
            'payment_id' => $response->json()['PaymentId'] ?? null,
        ]);

        return $response->json();
    }

    /**
     * Захватить (списать) платёж
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
            'gateway' => 'tinkoff',
            'amount' => $transaction->amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Tinkoff: Payment capture started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'amount' => $transaction->amount,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        try {
            $response = $this->http->post('https://securepay.tinkoff.ru/v2/Confirm', $payload);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: {$response->body()}");
            }

            $success = $response->json()['Success'] ?? false;

            if ($success) {
                $this->log->channel('audit')->info('Tinkoff: Payment capture succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'response_id' => $response->json()['TerminalKey'] ?? null,
                ]);
            } else {
                $this->log->channel('audit')->warning('Tinkoff: Payment capture returned false', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'response' => $response->json(),
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tinkoff: Payment capture exception', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Вернуть (возместить) платёж
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
            'gateway' => 'tinkoff',
            'amount' => $amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Tinkoff: Payment refund initiated', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'refund_amount' => $amount,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Amount' => $amount,
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        try {
            $response = $this->http->post('https://securepay.tinkoff.ru/v2/Refund', $payload);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: {$response->body()}");
            }

            $success = $response->json()['Success'] ?? false;

            if ($success) {
                $this->log->channel('audit')->info('Tinkoff: Payment refund succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'refunded_amount' => $amount,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tinkoff: Payment refund exception', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'refund_amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статус платежа
     *
     * @param string $providerPaymentId
     * @return array
     */
    public function getStatus(string $providerPaymentId): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $providerPaymentId,
            'Token' => $this->generateTokenForPayment($providerPaymentId),
        ];

        $response = $this->http->post('https://securepay.tinkoff.ru/v2/GetState', $payload);

        return $response->json();
    }

    /**
     * Создать выплату (массовая выплата)
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
            'gateway' => 'tinkoff',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Tinkoff: Payout initiated', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $data['amount'],
            'OrderId' => $data['order_id'],
            'Description' => $data['description'] ?? '',
            'AccountNumber' => $data['account_number'] ?? '',
            'Token' => $this->generateToken($data),
        ];

        $response = $this->http->post('https://securepay.tinkoff.ru/v2/Payout', $payload);

        return $response->json();
    }

    /**
     * Обработать webhook от Tinkoff
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $correlationId = $payload['correlation_id'] ?? Str::uuid()->toString();

        $this->log->channel('audit')->info('Tinkoff: Webhook received', [
            'correlation_id' => $correlationId,
            'order_id' => $payload['OrderId'] ?? null,
            'payment_id' => $payload['PaymentId'] ?? null,
            'status' => $payload['Status'] ?? null,
        ]);

        $status = match ($payload['Status'] ?? '') {
            'AUTHORIZED' => 'authorized',
            'CONFIRMED' => 'captured',
            'REFUNDED', 'PARTIAL_REFUNDED' => 'refunded',
            'REJECTED', 'REVERSED', 'CANCELLED' => 'failed',
            default => 'unknown',
        };

        return [
            'provider' => 'tinkoff',
            'provider_payment_id' => (string) ($payload['PaymentId'] ?? ''),
            'order_id' => (string) ($payload['OrderId'] ?? ''),
            'status' => $status,
            'amount' => (int) ($payload['Amount'] ?? 0),
            'correlation_id' => $correlationId,
            'raw' => $payload,
        ];
    }

    /**
     * Fiscalize платёж через Tinkoff ОФД (54-ФЗ)
     *
     * @param PaymentTransaction $transaction
     * @param string|null $correlationId
     * @return bool
     */
    public function fiscalize(PaymentTransaction $transaction, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        $this->log->channel('audit')->info('Tinkoff: Fiscalization started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $transaction->provider_payment_id,
            'Receipt' => [
                'Email' => $transaction->customer_email ?? '',
                'Taxation' => 'osn',
                'Items' => [
                    [
                        'Name' => $transaction->description ?? 'Услуга',
                        'Quantity' => 1,
                        'Amount' => $transaction->amount,
                        'Price' => $transaction->amount,
                        'Tax' => 'none',
                    ],
                ],
            ],
            'Token' => $this->generateTokenForPayment($transaction->provider_payment_id),
        ];

        try {
            $response = $this->http->post('https://securepay.tinkoff.ru/v2/SendClosingReceipt', $payload);

            $success = $response->json()['Success'] ?? false;

            if ($success) {
                $this->log->channel('audit')->info('Tinkoff: Fiscalization succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Tinkoff: Fiscalization failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Генерировать токен для платежа
     *
     * @param array $data
     * @return string
     */
    private function generateToken(array $data): string
    {
        $tokenString = $data['order_id'] . $data['amount'] . $this->secretKey;

        return md5($tokenString);
    }

    /**
     * Генерировать токен для существующего платежа
     *
     * @param string $paymentId
     * @return string
     */
    private function generateTokenForPayment(string $paymentId): string
    {
        $tokenString = $this->terminalKey . $paymentId . $this->secretKey;

        return md5($tokenString);
    }
}
