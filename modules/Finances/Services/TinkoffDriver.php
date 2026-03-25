<?php

namespace App\Domains\Finances\Services;

use App\Domains\Finances\Interfaces\PaymentGatewayInterface;
use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Support\Facades\{Http, Log};
use Illuminate\Support\Str;
use Exception;

/**
 * Tinkoff API драйвер - основной платёжный шлюз.
 * 
 * Поддерживает:
 * - Платежи по картам (1-stage, 2-stage)
 * - SBP платежи
 * - QR-коды для SBP
 * - Токенизацию карт
 * - Рекуррентные платежи
 * - НДС по системам налогообложения (ОСН, УСН, ЕСХН, ЕНВД, ПСН)
 */
class TinkoffDriver implements PaymentGatewayInterface
{
    private array $config;
    private string $endpoint = 'https://securepay.tinkoff.ru/v2/';
    private string $password;

    public function __construct()
    {
        $this->config = config('payments.drivers.tinkoff');
        $this->password = $this->config['password'];
    }

    /**
     * Инициировать платёж.
     */
    public function initPayment(array $data, bool $hold = false): array
    {
        try {
            $amount = (int) ($data['amount'] * 100); // В копейках

            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'Amount' => $amount,
                'OrderId' => $data['order_id'],
                'PayType' => $hold ? '2-stage' : '1-stage',
                'Receipt' => $this->buildReceipt($data),
                'DATA' => ['sbp' => $this->config['sbp_enabled'] ?? true],
                'Description' => $data['description'] ?? null,
            ];

            if (isset($data['customer_key'])) {
                $payload['CustomerKey'] = $data['customer_key'];
            }

            // Добавить подпись
            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(15)
                ->post($this->endpoint . 'Init', $payload)
                ->throw()
                ->json();

            if (!$response['Success'] ?? false) {
                throw new Exception("Tinkoff Init failed: {$response['Message']}");
            }

            $this->log->info('Tinkoff payment initiated', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'payment_id' => $response['PaymentId'] ?? null,
            ]);

            return [
                'payment_id' => $response['PaymentId'] ?? null,
                'url' => $response['PaymentURL'] ?? null,
                'gateway' => 'tinkoff',
                'status' => 'pending',
            ];
        } catch (Exception $e) {
            $this->log->error('Tinkoff initPayment failed', [
                'order_id' => $data['order_id'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Захватить средства (финализировать платёж при 2-stage).
     */
    public function capture(string $paymentId, float $amount = null): bool
    {
        try {
            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'PaymentId' => $paymentId,
            ];

            if ($amount !== null) {
                $payload['Amount'] = (int) ($amount * 100);
            }

            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(15)
                ->post($this->endpoint . 'Confirm', $payload)
                ->throw()
                ->json();

            if (!($response['Success'] ?? false)) {
                throw new Exception("Tinkoff Confirm failed: {$response['Message']}");
            }

            $this->log->info('Tinkoff payment captured', [
                'payment_id' => $paymentId,
                'amount' => $amount,
            ]);

            return true;
        } catch (Exception $e) {
            $this->log->error('Tinkoff capture failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Возврат средств.
     */
    public function refund(string $paymentId, float $amount, array $data = []): array
    {
        try {
            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'PaymentId' => $paymentId,
                'Amount' => (int) ($amount * 100),
            ];

            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(15)
                ->post($this->endpoint . 'Refund', $payload)
                ->throw()
                ->json();

            if (!($response['Success'] ?? false)) {
                throw new Exception("Tinkoff Refund failed: {$response['Message']}");
            }

            $this->log->info('Tinkoff refund processed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'refund_id' => $response['RefundId'] ?? null,
            ]);

            return [
                'status' => 'refunded',
                'refund_id' => $response['RefundId'] ?? null,
            ];
        } catch (Exception $e) {
            $this->log->error('Tinkoff refund failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Выплата на счёт (банковский перевод).
     */
    public function payout(array $recipient, float $amount, array $data = []): array
    {
        try {
            // Tinkoff поддерживает выплаты через выделенный API
            // Реальная реализация использует отдельный эндпоинт для переводов
            
            $payoutData = [
                'TerminalKey' => $this->config['terminal_id'],
                'Phone' => $recipient['phone'] ?? null,
                'Account' => $recipient['account'] ?? null,
                'Amount' => (int)($amount * 100), // В копейках
                'OrderId' => \Illuminate\Support\Str::uuid()->toString(),
                'Description' => 'Пользовательская выплата',
            ];

            $payoutData['Token'] = $this->generateSignature(array_filter($payoutData, fn($k) => !in_array($k, ['Token']), ARRAY_FILTER_USE_KEY));

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . 'SendPayment/', $payoutData);

            if ($response->failed()) {
                throw new Exception('Payout request failed: ' . $response->status());
            }

            $result = $response->json();

            if (($result['Success'] ?? false) || ($result['Status'] === 'SENT')) {
                $this->log->channel('tinkoff')->info('Tinkoff payout successful', [
                    'recipient' => $recipient['account'] ?? null,
                    'amount' => $amount,
                    'order_id' => $payoutData['OrderId'],
                ]);

                return [
                    'status' => 'success',
                    'payout_id' => $result['OrderId'] ?? $payoutData['OrderId'],
                    'transaction_id' => $result['TransactionId'] ?? null,
                ];
            } else {
                throw new Exception($result['Message'] ?? 'Payout failed');
            }
        } catch (Exception $e) {
            $this->log->error('Tinkoff payout failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Получить статус платежа.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'PaymentId' => $paymentId,
            ];

            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(10)
                ->post($this->endpoint . 'GetState', $payload)
                ->throw()
                ->json();

            return [
                'payment_id' => $paymentId,
                'status' => $this->mapStatus($response['Status'] ?? 'UNKNOWN'),
                'amount' => ($response['Amount'] ?? 0) / 100,
                'response_code' => $response['ErrorCode'] ?? null,
            ];
        } catch (Exception $e) {
            $this->log->error('Get payment status failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Получить статус SBP платежа.
     */
    public function getSbpStatus(string $paymentId): string
    {
        return $this->getPaymentStatus($paymentId)['status'] ?? 'unknown';
    }

    /**
     * Сгенерировать универсальный QR-код для SBP.
     */
    public function generateUniversalQR(array $data): array
    {
        try {
            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'Amount' => (int) ($data['amount'] * 100),
                'OrderId' => $data['order_id'],
            ];

            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(10)
                ->post($this->endpoint . 'GetQr', $payload)
                ->throw()
                ->json();

            if (!($response['Success'] ?? false)) {
                throw new Exception("GetQr failed: {$response['Message']}");
            }

            $this->log->info('Universal QR generated', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
            ]);

            return [
                'qr_data' => $response['Data'] ?? null,
                'qr_image' => 'https://securepay.tinkoff.ru/image/' . ($response['PaymentId'] ?? null),
                'payment_id' => $response['PaymentId'] ?? null,
            ];
        } catch (Exception $e) {
            $this->log->error('QR generation failed', ['order_id' => $data['order_id'] ?? null, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Токенизировать карту.
     */
    public function tokenizeCard(array $data): array
    {
        // В Tinkoff 3DS платёж на 1 копейку для получения токена
        return $this->initPayment([
            'amount' => 0.01,
            'order_id' => 'tokenize_' . $data['order_id'] ?? \Illuminate\Support\Str::uuid(),
            'customer_key' => $data['customer_key'],
            'description' => 'Card tokenization',
        ]);
    }

    /**
     * Платёж по сохранённому токену (рекуррентный платёж).
     */
    public function chargeByToken(string $token, float $amount, array $metadata = []): array
    {
        try {
            $payload = [
                'TerminalKey' => $this->config['terminal_id'],
                'PaymentId' => $metadata['payment_id'] ?? null,
                'RebillId' => $token,
                'Amount' => (int) ($amount * 100),
            ];

            // Если нет payment_id, сначала инициировать платёж
            if (!$payload['PaymentId']) {
                $init = $this->initPayment([
                    'amount' => $amount,
                    'order_id' => $metadata['order_id'] ?? 'recurring_' . now()->timestamp,
                    'customer_key' => $metadata['customer_key'] ?? null,
                ]);
                $payload['PaymentId'] = $init['payment_id'];
            }

            $payload['Token'] = $this->generateSignature($payload);

            $response = Http::timeout(15)
                ->post($this->endpoint . 'Charge', $payload)
                ->throw()
                ->json();

            if (!($response['Success'] ?? false)) {
                throw new Exception("Charge failed: {$response['Message']}");
            }

            $this->log->info('Recurring charge completed', [
                'payment_id' => $payload['PaymentId'],
                'amount' => $amount,
            ]);

            return [
                'status' => 'success',
                'payment_id' => $response['PaymentId'] ?? null,
                'confirmation_id' => $response['ConfirmationId'] ?? null,
            ];
        } catch (Exception $e) {
            $this->log->error('Charge by token failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Обработать webhook от Tinkoff.
     */
    public function handleWebhook(array $payload): array
    {
        try {
            // Валидировать подпись
            $token = $payload['Token'] ?? null;
            unset($payload['Token']);

            $expectedToken = $this->generateSignature($payload);
            if (!hash_equals($expectedToken, $token ?? '')) {
                throw new Exception('Invalid webhook signature');
            }

            $this->log->info('Tinkoff webhook processed', [
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
            ]);

            return [
                'success' => true,
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $this->mapStatus($payload['Status'] ?? 'UNKNOWN'),
            ];
        } catch (Exception $e) {
            $this->log->error('Webhook processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Проверить здоровье API.
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)
                ->head($this->endpoint)
                ->successful();

            $this->log->info('Tinkoff health check', ['available' => $response]);
            return [
                'status' => $response ? 'healthy' : 'unhealthy',
                'provider' => 'tinkoff',
                'timestamp' => now(),
            ];
        } catch (Exception $e) {
            $this->log->warning('Tinkoff health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'provider' => 'tinkoff',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о шлюзе.
     */
    public function getInfo(): array
    {
        return [
            'name' => 'Tinkoff',
            'type' => 'primary',
            'endpoint' => $this->endpoint,
            'features' => [
                'payments' => true,
                'refunds' => true,
                'sbp' => true,
                'qr_codes' => true,
                'tokenization' => true,
                'recurring' => true,
            ],
            'is_available' => $this->healthCheck(),
        ];
    }

    /**
     * Построить объект чека для ОФД.
     */
    /**
     * Получить налоговый код для системы налогообложения (Tinkoff).
     * 
     * @param string $taxSystem Система налогообложения
     * @param string|null $taxCode Код налога из товара
     * @return string Налоговый код для Tinkoff API (vat0, vat10, vat18, none)
     */
    private function getTaxCode(string $taxSystem, ?string $taxCode = null): string
    {
        // УСН - без НДС
        if (str_contains($taxSystem, 'usn')) {
            return 'none';
        }

        // ЕСХН - без НДС
        if ($taxSystem === 'esn') {
            return 'none';
        }

        // ОСН - с НДС (0%, 10%, 20%)
        if ($taxSystem === 'osn') {
            $taxRates = [
                'vat_0' => 'vat0',
                'vat_10' => 'vat10',
                'vat_20' => 'vat20',
            ];
            
            if ($taxCode && isset($taxRates[strtolower($taxCode)])) {
                return $taxRates[strtolower($taxCode)];
            }
            
            return 'vat20';
        }

        // ЕНВД, ПСН - без НДС
        if (in_array($taxSystem, ['envd', 'psn'])) {
            return 'none';
        }

        return 'none';
    }

    private function buildReceipt(array $data): array
    {
        $taxSystem = strtolower($data['tax_system'] ?? 'osn');
        $items = $data['items'] ?? [];
        
        // Если передали товары, обработать их с налогами
        $receiptItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $taxCode = $this->getTaxCode($taxSystem, $item['tax'] ?? null);
                $receiptItems[] = [
                    'Name' => $item['name'] ?? $data['description'] ?? 'Payment',
                    'Price' => (int) (($item['price'] ?? 0) * 100),
                    'Quantity' => (int) ($item['qty'] ?? 1),
                    'Amount' => (int) (($item['price'] ?? 0) * ($item['qty'] ?? 1) * 100),
                    'Tax' => $taxCode,
                ];
            }
        } else {
            // Если товаров нет, создать один общий элемент
            $taxCode = $this->getTaxCode($taxSystem, $data['tax'] ?? null);
            $receiptItems[] = [
                'Name' => $data['description'] ?? 'Payment',
                'Price' => (int) ($data['amount'] * 100),
                'Quantity' => 1,
                'Amount' => (int) ($data['amount'] * 100),
                'Tax' => $taxCode,
            ];
        }

        return [
            'Email' => $data['email'] ?? 'noreply@catvrf.ru',
            'Phone' => $data['phone'] ?? null,
            'Taxation' => $taxSystem,
            'Items' => $receiptItems,
        ];
    }

    /**
     * Сгенерировать подпись для запроса.
     */
    private function generateSignature(array $data): string
    {
        // Tinkoff использует простое хеширование параметров + пароль
        ksort($data);
        $dataString = implode('', array_values($data)) . $this->password;
        return hash('sha256', $dataString);
    }

    /**
     * Маппировать статусы Tinkoff -> наши.
     */
    private function mapStatus(string $tinkoffStatus): string
    {
        return match ($tinkoffStatus) {
            'NEW' => 'pending',
            'FORM_SHOWED' => 'awaiting_payment',
            'AUTHORIZED' => 'authorized',
            'CONFIRMED' => 'settled',
            'REFUNDED' => 'refunded',
            'PARTIAL_REFUNDED' => 'partial_refund',
            'CANCELED' => 'cancelled',
            'REJECTED' => 'failed',
            default => 'unknown',
        };
    }
}
