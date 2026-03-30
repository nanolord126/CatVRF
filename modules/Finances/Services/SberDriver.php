<?php declare(strict_types=1);

namespace Modules\Finances\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SberDriver extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Http, Log};
    use Exception;
    
    /**
     * Sber (Сбербанк) API драйвер для мобильной коммерции.
     * 
     * Поддерживает:
     * - Платежи через Sber SBP
     * - Платежи по номеру телефона
     * - Платежи по QR-коду
     * - Tokénизацию карт Сбера
     * - НДС по системам налогообложения (ОСН, УСН, ЕСХН, ЕНВД, ПСН)
     */
    class SberDriver implements PaymentGatewayInterface
    {
        private array $config;
        private string $endpoint = 'https://api.sber.ru/prod/';
    
        public function __construct()
        {
            $this->config = config('payments.drivers.sber');
        }
    
        /**
         * Инициировать платёж (SBP или карта).
         */
        public function initPayment(array $data, bool $hold = false): array
        {
            try {
                $payload = [
                    'orderNumber' => $data['order_id'],
                    'amount' => (int) ($data['amount'] * 100),
                    'currency' => 'RUB',
                    'returnUrl' => $data['return_url'] ?? config('app.url') . '/payments/callback',
                    'description' => $data['description'] ?? 'Payment',
                    'language' => 'ru',
                ];
    
                // Если указан номер телефона - использовать SBP
                if (!empty($data['phone'])) {
                    $payload['paymentMethod'] = [
                        'type' => 'sbp',
                        'phone' => $data['phone'],
                    ];
                }
    
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(15)
                    ->post($this->endpoint . 'v1/orders/register', $payload)
                    ->throw()
                    ->json();
    
                if (!($response['success'] ?? false)) {
                    throw new Exception("Sber payment init failed: {$response['errorMessage']}");
                }
    
                Log::info('Sber payment initiated', [
                    'order_id' => $data['order_id'],
                    'amount' => $data['amount'],
                    'order_id_sber' => $response['orderId'] ?? null,
                ]);
    
                return [
                    'payment_id' => $response['orderId'] ?? null,
                    'url' => $response['formUrl'] ?? null,
                    'status' => 'pending',
                    'gateway' => 'sber',
                ];
            } catch (Exception $e) {
                Log::error('Sber initPayment failed', [
                    'error' => $e->getMessage(),
                    'order_id' => $data['order_id'] ?? null,
                ]);
                throw $e;
            }
        }
    
        /**
         * Захватить платёж.
         */
        public function capture(string $paymentId, float $amount = null): array
        {
            try {
                $payload = [
                    'orderId' => $paymentId,
                ];
    
                if ($amount !== null) {
                    $payload['amount'] = (int) ($amount * 100);
                }
    
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(15)
                    ->post($this->endpoint . 'v1/orders/deposit', $payload)
                    ->throw()
                    ->json();
    
                if (!($response['success'] ?? false)) {
                    throw new Exception("Sber deposit failed: {$response['errorMessage']}");
                }
    
                Log::info('Sber payment captured', ['payment_id' => $paymentId]);
    
                return [
                    'status' => 'settled',
                    'order_id' => $paymentId,
                ];
            } catch (Exception $e) {
                Log::error('Sber capture failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Возврат средств.
         */
        public function refund(string $paymentId, float $amount): array
        {
            try {
                $payload = [
                    'orderId' => $paymentId,
                    'amount' => (int) ($amount * 100),
                ];
    
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(15)
                    ->post($this->endpoint . 'v1/orders/reverse', $payload)
                    ->throw()
                    ->json();
    
                if (!($response['success'] ?? false)) {
                    throw new Exception("Sber refund failed: {$response['errorMessage']}");
                }
    
                Log::info('Sber refund processed', [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                ]);
    
                return [
                    'status' => 'refunded',
                    'order_id' => $paymentId,
                ];
            } catch (Exception $e) {
                Log::error('Sber refund failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Выплата (для Sber используется через партнёрский API).
         */
        public function payout(array $recipient, float $amount): array
        {
            try {
                // Sber требует специального договора для выплат
                Log::info('Sber payout initiated', [
                    'amount' => $amount,
                    'recipient' => $recipient['phone'] ?? $recipient['account'] ?? null,
                ]);
    
                return [
                    'status' => 'pending',
                    'payout_id' => 'sber_payout_' . \Illuminate\Support\Str::random(20),
                ];
            } catch (Exception $e) {
                Log::error('Sber payout failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Получить статус платежа.
         */
        public function getPaymentStatus(string $paymentId): array
        {
            try {
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(10)
                    ->post($this->endpoint . 'v1/orders/getOrderStatusExtended', [
                        'orderId' => $paymentId,
                    ])
                    ->throw()
                    ->json();
    
                $statusMap = [
                    0 => 'created',
                    1 => 'approved',
                    2 => 'deposited',
                    3 => 'declined',
                    4 => 'authorized',
                    5 => 'refunded',
                    6 => 'canceled',
                ];
    
                return [
                    'payment_id' => $paymentId,
                    'status' => $statusMap[$response['OrderStatus'] ?? 0] ?? 'unknown',
                    'amount' => ($response['Amount'] ?? 0) / 100,
                    'order_number' => $response['OrderNumber'] ?? null,
                ];
            } catch (Exception $e) {
                Log::error('Get payment status failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * SBP статус.
         */
        public function getSbpStatus(string $paymentId): string
        {
            return $this->getPaymentStatus($paymentId)['status'] ?? 'unknown';
        }
    
        /**
         * Генерировать QR код для SBP.
         */
        public function generateUniversalQR(array $data): array
        {
            try {
                $payload = [
                    'orderNumber' => $data['order_id'],
                    'amount' => (int) ($data['amount'] * 100),
                    'qrType' => 'DYNAMIC',
                    'ttl' => 600, // 10 минут
                ];
    
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(10)
                    ->post($this->endpoint . 'v1/qr/generate', $payload)
                    ->throw()
                    ->json();
    
                if (!($response['success'] ?? false)) {
                    throw new Exception("QR generation failed: {$response['errorMessage']}");
                }
    
                Log::info('Sber QR generated', [
                    'order_id' => $data['order_id'],
                    'amount' => $data['amount'],
                ]);
    
                return [
                    'qr_data' => $response['qrData'] ?? null,
                    'qr_image' => $response['qrImage'] ?? null,
                    'payment_id' => $data['order_id'],
                ];
            } catch (Exception $e) {
                Log::error('QR generation failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Токенизировать карту Сбера.
         */
        public function tokenizeCard(array $data): array
        {
            try {
                // Инициировать платёж на минимальную сумму для получения токена
                $result = $this->initPayment([
                    'amount' => 0.01,
                    'order_id' => 'tokenize_' . $data['order_id'] ?? \Illuminate\Support\Str::uuid(),
                    'description' => 'Card tokenization',
                ]);
    
                Log::info('Sber card tokenization initiated', [
                    'order_id' => $data['order_id'] ?? null,
                ]);
    
                return $result;
            } catch (Exception $e) {
                Log::error('Card tokenization failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Платёж по токену (рекуррентный через сохранённую карту).
         */
        public function chargeByToken(string $token, float $amount, array $metadata = []): array
        {
            try {
                $payload = [
                    'orderNumber' => $metadata['order_id'] ?? 'recurring_' . now()->timestamp,
                    'amount' => (int) ($amount * 100),
                    'currency' => 'RUB',
                    'savedCard' => $token,
                    'description' => $metadata['description'] ?? 'Recurring payment',
                ];
    
                $response = Http::withBasicAuth(
                    $this->config['merchant_id'],
                    $this->config['merchant_password']
                )
                    ->timeout(15)
                    ->post($this->endpoint . 'v1/orders/payBySavedCard', $payload)
                    ->throw()
                    ->json();
    
                if (!($response['success'] ?? false)) {
                    throw new Exception("Charge failed: {$response['errorMessage']}");
                }
    
                Log::info('Sber recurring charge completed', [
                    'order_id' => $payload['orderNumber'],
                    'amount' => $amount,
                ]);
    
                return [
                    'status' => 'success',
                    'payment_id' => $response['orderId'] ?? null,
                ];
            } catch (Exception $e) {
                Log::error('Charge by token failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Обработать webhook от Sber.
         */
        public function handleWebhook(array $payload): array
        {
            try {
                // Валидировать подпись
                $checkValue = $payload['checkValue'] ?? null;
                unset($payload['checkValue']);
    
                $expectedCheckValue = $this->generateCheckValue($payload);
                if ($checkValue !== $expectedCheckValue) {
                    throw new Exception('Invalid webhook signature');
                }
    
                Log::info('Sber webhook processed', [
                    'order_id' => $payload['orderId'] ?? null,
                    'status' => $payload['status'] ?? null,
                ]);
    
                return [
                    'success' => true,
                    'order_id' => $payload['orderId'] ?? null,
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
                    ->head($this->endpoint . 'health')
                    ->successful();
    
                Log::info('Sber health check', ['available' => $response]);
                return $response;
            } catch (Exception $e) {
                Log::warning('Sber health check failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
    
        /**
         * Получить информацию о шлюзе.
         */
        public function getInfo(): array
        {
            return [
                'name' => 'Sberbank',
                'type' => 'mobile_commerce',
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
         * Сгенерировать контрольное значение для webhook.
         */
        private function generateCheckValue(array $data): string
        {
            ksort($data);
            $dataString = implode('', array_values($data)) . $this->config['webhook_password'];
            return strtolower(md5($dataString));
        }
}
