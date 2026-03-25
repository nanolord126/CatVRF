<?php declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * SberGateway
 *
 * Интеграция с платёжной системой Сбербанка (захват, возврат, fiscalization).
 *
 * API: https://3dsec.sberbank.ru/payment/rest/
 * Документация: https://securepayments.sberbank.ru/wiki/
 *
 * @final
 */
final class SberGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $merchantId,
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Инициировать платёж через Sber API
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
            'gateway' => 'sber',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Sber: Payment initialization started', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/registerOrder.do';

        $response = $this->http->asForm()->post($url, [
            'userName' => $this->username,
            'password' => $this->password,
            'orderNumber' => $data['order_id'],
            'amount' => $data['amount'],
            'currency' => '810', // RUB
            'returnUrl' => $data['return_url'] ?? '',
            'description' => $data['description'] ?? '',
            'clientId' => $data['customer_id'] ?? '',
            'email' => $data['customer_email'] ?? '',
        ]);

        if (!$response->successful()) {
            $this->log->channel('audit')->error('Sber: Payment init failed', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
            ]);

            throw new \Exception("Sber init failed: {$response->status()}");
        }

        $this->log->channel('audit')->info('Sber: Payment init succeeded', [
            'correlation_id' => $correlationId,
            'order_id' => $response->json()['orderId'] ?? null,
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
            'gateway' => 'sber',
            'amount' => $transaction->amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Sber: Payment capture started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'amount' => $transaction->amount,
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/deposit.do';

        try {
            $response = $this->http->asForm()->post($url, [
                'userName' => $this->username,
                'password' => $this->password,
                'orderId' => $transaction->provider_payment_id,
                'amount' => $transaction->amount,
            ]);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}");
            }

            $success = ($response->json()['errorCode'] ?? '') === '0';

            if ($success) {
                $this->log->channel('audit')->info('Sber: Payment capture succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                ]);
            } else {
                $this->log->channel('audit')->warning('Sber: Payment capture returned error', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'error_code' => $response->json()['errorCode'] ?? 'unknown',
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Sber: Payment capture exception', [
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
            'gateway' => 'sber',
            'amount' => $amount,
            'payment_id' => $transaction->id,
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Sber: Payment refund initiated', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'refund_amount' => $amount,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        $url = 'https://3dsec.sberbank.ru/payment/rest/refund.do';

        try {
            $response = $this->http->asForm()->post($url, [
                'userName' => $this->username,
                'password' => $this->password,
                'orderId' => $transaction->provider_payment_id,
                'amount' => $amount,
            ]);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}");
            }

            $success = ($response->json()['errorCode'] ?? '') === '0';

            if ($success) {
                $this->log->channel('audit')->info('Sber: Payment refund succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                    'refunded_amount' => $amount,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Sber: Payment refund exception', [
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
        $response = $this->http->asForm()->post('https://3dsec.sberbank.ru/payment/rest/getOrderStatusExtended.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $providerPaymentId,
        ]);

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
            'gateway' => 'sber',
            'amount' => $data['amount'],
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->info('Sber: Payout initiated', [
            'correlation_id' => $correlationId,
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
        ]);

        $response = $this->http->asForm()->post('https://3dsec.sberbank.ru/payment/rest/payout.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'merchantId' => $this->merchantId,
            'orderNumber' => $data['order_id'],
            'amount' => $data['amount'],
            'clientId' => $data['customer_id'] ?? '',
        ]);

        return $response->json();
    }

    /**
     * Обработать webhook от Sber
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $correlationId = $payload['correlation_id'] ?? Str::uuid()->toString();

        $this->log->channel('audit')->info('Sber: Webhook received', [
            'correlation_id' => $correlationId,
            'order_id' => $payload['orderNumber'] ?? null,
            'order_status' => $payload['orderStatus'] ?? null,
        ]);

        $statusMap = [
            0 => 'pending',
            1 => 'pending',
            2 => 'captured',
            3 => 'failed',
            4 => 'refunded',
            6 => 'refunded',
        ];

        return [
            'provider' => 'sber',
            'provider_payment_id' => (string) ($payload['orderId'] ?? ''),
            'order_id' => (string) ($payload['orderNumber'] ?? ''),
            'status' => $statusMap[$payload['orderStatus'] ?? -1] ?? 'unknown',
            'amount' => (int) ($payload['amount'] ?? 0),
            'correlation_id' => $correlationId,
            'raw' => $payload,
        ];
    }

    /**
     * Fiscalize платёж через Sber ОФД (54-ФЗ)
     *
     * Сбер использует собственный ОФД модуль через параметры при initPayment
     * и sendReceipt для закрывающего чека.
     *
     * @param PaymentTransaction $transaction
     * @param string|null $correlationId
     * @return bool
     */
    public function fiscalize(PaymentTransaction $transaction, ?string $correlationId = null): bool
    {
        $correlationId ??= $transaction->correlation_id ?? Str::uuid()->toString();

        $this->log->channel('audit')->info('Sber: Fiscalization started', [
            'correlation_id' => $correlationId,
            'payment_id' => $transaction->id,
            'provider_payment_id' => $transaction->provider_payment_id,
        ]);

        try {
            $response = $this->http->asForm()->post('https://3dsec.sberbank.ru/payment/rest/sendReceipt.do', [
                'userName' => $this->username,
                'password' => $this->password,
                'orderId' => $transaction->provider_payment_id,
                'receipt' => json_encode([
                    'email' => $transaction->customer_email ?? '',
                    'items' => [[
                        'name' => $transaction->description ?? 'Услуга',
                        'price' => $transaction->amount,
                        'quantity' => 1,
                        'amount' => $transaction->amount,
                        'tax' => 'none',
                    ]],
                ]),
            ]);

            $success = ($response->json()['errorCode'] ?? '') === '0';

            if ($success) {
                $this->log->channel('audit')->info('Sber: Fiscalization succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $transaction->id,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Sber: Fiscalization failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
