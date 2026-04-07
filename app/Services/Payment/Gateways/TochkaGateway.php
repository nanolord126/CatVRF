<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * TochkaGateway
 *
 * Интеграция с платёжной системой Точка Банк (B2B платежи, эквайринг).
 * Точка — это платёжная система для бизнеса с поддержкой STP платежей.
 *
 * API: https://api.tochka.com/api/v1/
 * Документация: https://tochka.com/business/
 *
 * @final
 */
final class TochkaGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $apiKey,
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly LogManager $logger,
    ) {}

    /**
     * Инициировать платёж через Tochka API
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
            'gateway' => 'tochka',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('Tochka: Payment initialization started', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        $payload = [
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? '',
            'customer_email' => $data['customer_email'] ?? '',
            'return_url' => $data['return_url'] ?? '',
        ];

        $response = $this->http->withToken($this->apiKey)
            ->post('https://api.tochka.com/api/v1/payments', $payload);

        if (!$response->successful()) {
            $this->logger->channel('audit')->error('Tochka: Payment init failed', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException("Tochka init failed: {$response->status()}");
        }

        $this->logger->channel('audit')->info('Tochka: Payment init succeeded', [
            'correlation_id' => $correlationId,
            'payment_id' => $response->json()['payment_id'] ?? null,
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
            'gateway' => 'tochka',
            'amount' => $transaction->amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('Tochka: Payment capture started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'amount' => $transaction->amount,
        ]);

        try {
            $response = $this->http->withToken($this->apiKey)
                ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/capture", []);

            if (!$response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $success = $response->json()['status'] === 'captured';

            if ($success) {
                $this->logger->channel('audit')->info('Tochka: Payment capture succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                ]);
            } else {
                $this->logger->channel('audit')->warning('Tochka: Payment capture returned non-captured status', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'status' => $response->json()['status'] ?? 'unknown',
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Tochka: Payment capture exception', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
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
            'gateway' => 'tochka',
            'amount' => $amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('Tochka: Payment refund initiated', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'refund_amount' => $amount,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        try {
            $response = $this->http->withToken($this->apiKey)
                ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/refund", [
                    'amount' => $amount,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $success = ($response->json()['status'] ?? '') === 'refunded';

            if ($success) {
                $this->logger->channel('audit')->info('Tochka: Payment refund succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'refunded_amount' => $amount,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Tochka: Payment refund exception', [
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
        return $this->http->withToken($this->apiKey)
            ->get("https://api.tochka.com/api/v1/payments/{$providerPaymentId}")
            ->json();
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
            'gateway' => 'tochka',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->logger->channel('audit')->info('Tochka: Payout initiated', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        return $this->http->withToken($this->apiKey)
            ->post('https://api.tochka.com/api/v1/payouts', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'account_number' => $data['account_number'] ?? '',
                'description' => $data['description'] ?? '',
            ])->json();
    }

    /**
     * Обработать webhook от Tochka
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $correlationId = $payload['correlation_id'] ?? Str::uuid()->toString();

        $this->logger->channel('audit')->info('Tochka: Webhook received', [
            'correlation_id' => $correlationId,
            'order_id' => $payload['order_id'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);

        return [
            'provider' => 'tochka',
            'provider_payment_id' => (string) ($payload['payment_id'] ?? ''),
            'order_id' => (string) ($payload['order_id'] ?? ''),
            'status' => $payload['status'] ?? 'unknown',
            'amount' => (int) ($payload['amount'] ?? 0),
            'correlation_id' => $correlationId,
            'raw' => $payload,
        ];
    }

    /**
     * Fiscalize платёж через Tochka ОФД (54-ФЗ)
     *
     * Точка использует внешний ОФД (CloudPayments / АТОЛ)
     * Реализация зависит от подключённого ОФД-провайдера
     *
     * @param PaymentTransaction $transaction
     * @param string|null $correlationId
     * @return bool
     */
    public function fiscalize(PaymentTransaction $transaction, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        $this->logger->channel('audit')->info('Tochka: Fiscalization started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        try {
            $response = $this->http->withToken($this->apiKey)
                ->post("https://api.tochka.com/api/v1/payments/{$transaction->provider_payment_id}/receipt", [
                    'email' => $transaction->customer_email ?? '',
                    'items' => [[
                        'name' => $transaction->description ?? 'Услуга',
                        'price' => $transaction->amount,
                        'quantity' => 1,
                        'tax' => 'none',
                    ]],
                ]);

            $success = $response->successful();

            if ($success) {
                $this->logger->channel('audit')->info('Tochka: Fiscalization succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Tochka: Fiscalization failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
