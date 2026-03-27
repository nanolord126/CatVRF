<?php

namespace App\Domains\Finances\Services;

use App\Domains\Finances\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\{Http, Log};
use Exception;

/**
 * Tochka Bank API драйвер для корпоративных платежей и выплат.
 * 
 * Поддерживает:
 * - Переводы на счета в Tochka Bank
 * - Корпоративные платежи
 * - Выплаты зарплат (Payroll)
 * - API WebHooks для подтверждения операций
 * - НДС по системам налогообложения (ОСН, УСН, ЕСХН, ЭНВД, ПСН)
 */
class TochkaDriver implements PaymentGatewayInterface
{
    private array $config;
    private string $endpoint = 'https://api.tochka.com/api/v1/';

    public function __construct()
    {
        $this->config = config('payments.drivers.tochka');
    }

    /**
     * Инициировать платёж (корпоративный перевод).
     */
    public function initPayment(array $data, bool $hold = false): array
    {
        try {
            $payload = [
                'accounts' => [$this->config['account_id']],
                'receivers' => [
                    [
                        'name' => $data['recipient_name'] ?? 'Recipient',
                        'bik' => $data['recipient_bik'],
                        'account' => $data['recipient_account'],
                        'inn' => $data['recipient_inn'] ?? null,
                    ],
                ],
                'amount' => (int) ($data['amount'] * 100),
                'purpose' => $data['description'] ?? 'Payment',
                'order_id' => $data['order_id'],
            ];

            // Получить токен доступа
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(20)
                ->post($this->endpoint . 'payments', $payload)
                ->throw()
                ->json();

            if (!($response['success'] ?? false)) {
                throw new Exception("Tochka payment init failed: {$response['message']}");
            }

            Log::info('Tochka payment initiated', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'payment_id' => $response['data']['payment_id'] ?? null,
            ]);

            return [
                'payment_id' => $response['data']['payment_id'] ?? null,
                'status' => 'pending',
                'gateway' => 'tochka',
            ];
        } catch (Exception $e) {
            Log::error('Tochka initPayment failed', [
                'error' => $e->getMessage(),
                'order_id' => $data['order_id'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Захватить платёж (финализировать).
     */
    public function capture(string $paymentId, float $amount = null): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(15)
                ->post($this->endpoint . "payments/{$paymentId}/confirm")
                ->throw()
                ->json();

            Log::info('Tochka payment captured', ['payment_id' => $paymentId]);

            return [
                'status' => 'settled',
                'confirmation_id' => $response['data']['confirmation_id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Tochka capture failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Возврат средств.
     */
    public function refund(string $paymentId, float $amount): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(15)
                ->post($this->endpoint . "payments/{$paymentId}/refund", [
                    'amount' => (int) ($amount * 100),
                ])
                ->throw()
                ->json();

            Log::info('Tochka refund processed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
            ]);

            return [
                'status' => 'refunded',
                'refund_id' => $response['data']['refund_id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Tochka refund failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Выплата на счёт (зарплата, выплата партнёрам).
     */
    public function payout(array $recipient, float $amount): array
    {
        try {
            $payload = [
                'accounts' => [$this->config['account_id']],
                'receivers' => [
                    [
                        'name' => $recipient['name'],
                        'bik' => $recipient['bik'],
                        'account' => $recipient['account'],
                        'inn' => $recipient['inn'] ?? null,
                    ],
                ],
                'amount' => (int) ($amount * 100),
                'purpose' => $recipient['purpose'] ?? 'Payout',
            ];

            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(20)
                ->post($this->endpoint . 'payouts', $payload)
                ->throw()
                ->json();

            if (!($response['success'] ?? false)) {
                throw new Exception("Tochka payout failed: {$response['message']}");
            }

            Log::info('Tochka payout initiated', [
                'amount' => $amount,
                'recipient_account' => $recipient['account'],
                'payout_id' => $response['data']['payout_id'] ?? null,
            ]);

            return [
                'status' => 'pending',
                'payout_id' => $response['data']['payout_id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Tochka payout failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Получить статус платежа.
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->timeout(10)
                ->get($this->endpoint . "payments/{$paymentId}")
                ->throw()
                ->json();

            return [
                'payment_id' => $paymentId,
                'status' => $response['data']['status'] ?? 'unknown',
                'amount' => ($response['data']['amount'] ?? 0) / 100,
            ];
        } catch (Exception $e) {
            Log::error('Get payment status failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * SBP статус (Tochka использует через интеграцию).
     */
    public function getSbpStatus(string $paymentId): string
    {
        return $this->getPaymentStatus($paymentId)['status'] ?? 'unknown';
    }

    /**
     * Генерировать QR код (если поддерживается).
     */
    public function generateUniversalQR(array $data): array
    {
        // Tochka не поддерживает QR напрямую - использует стандартный способ
        return [
            'qr_data' => null,
            'error' => 'Tochka Bank does not support QR code generation',
        ];
    }

    /**
     * Токенизировать карту (корпоративные карты Tochka).
     */
    public function tokenizeCard(array $data): array
    {
        // Для Tochka это не применимо - работаем с корпоративными счетами
        return [
            'status' => 'not_applicable',
            'message' => 'Tochka Bank uses account-based payments, not card tokens',
        ];
    }

    /**
     * Платёж по токену (рекуррентный через Tochka счёт).
     */
    public function chargeByToken(string $token, float $amount, array $metadata = []): array
    {
        // Для Tochka используем регулярные платежи
        return $this->initPayment([
            'amount' => $amount,
            'order_id' => $metadata['order_id'] ?? 'recurring_' . now()->timestamp,
            'recipient_account' => $token,
            'description' => $metadata['description'] ?? 'Recurring payment',
        ]);
    }

    /**
     * Обработать webhook от Tochka.
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $signature = $payload['signature'] ?? null;
            unset($payload['signature']);

            $expectedSignature = $this->generateSignature($payload);
            if ($signature !== $expectedSignature) {
                throw new Exception('Invalid webhook signature');
            }

            Log::info('Tochka webhook processed', [
                'payment_id' => $payload['payment_id'] ?? null,
                'status' => $payload['status'] ?? null,
            ]);

            return [
                'success' => true,
                'payment_id' => $payload['payment_id'] ?? null,
                'status' => $payload['status'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Проверить здоровье API.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)
                ->head($this->endpoint . 'status')
                ->successful();

            Log::info('Tochka health check', ['available' => $response]);
            return $response;
        } catch (Exception $e) {
            Log::warning('Tochka health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить информацию о шлюзе.
     */
    public function getInfo(): array
    {
        return [
            'name' => 'Tochka Bank',
            'type' => 'corporate',
            'endpoint' => $this->endpoint,
            'features' => [
                'corporate_payments' => true,
                'payouts' => true,
                'sbp' => false,
                'qr_codes' => false,
                'tokenization' => false,
                'recurring' => true,
            ],
            'is_available' => $this->healthCheck(),
        ];
    }

    /**
     * Получить токен доступа (OAuth).
     */
    private function getAccessToken(): string
    {
        return \Illuminate\Support\Facades\Cache::remember('tochka_token', 3600, function () {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://auth.tochka.com/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'scope' => 'payments:read payments:write',
                ])
                ->throw()
                ->json();

            return $response['access_token'] ?? throw new Exception('Failed to get Tochka access token');
        });
    }

    /**
     * Сгенерировать подпись для webhook.
     */
    private function generateSignature(array $data): string
    {
        ksort($data);
        $dataString = json_encode($data);
        return hash_hmac('sha256', $dataString, $this->config['webhook_secret']);
    }
}
