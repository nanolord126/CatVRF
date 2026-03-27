<?php declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Exception;

/**
 * FiscalService
 *
 * Fiscalization по 54-ФЗ (передача чеков в налоговую систему через ОФД).
 * Вызывается ТОЛЬКО после успешного capture платежа, когда деньги точно списаны.
 *
 * Поддерживаемые ОФД:
 * - Yandex Kassa API
 * - Tinkoff API
 * - Собственный провайдер
 *
 * @final
 */
final readonly class FiscalService
{
    public function __construct(
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Fiscalize платёж в ОФД (54-ФЗ)
     *
     * ВАЖНО: Вызывается ТОЛЬКО после успешного capture!
     * Если fiscalization не удалась, она логируется, но не блокирует платёж
     * (платёж уже списан со счёта, поэтому должна быть фискальная справка)
     *
     * @param PaymentTransaction $payment
     * @param string|null $correlationId
     * @return bool
     *
     * @throws \App\Exceptions\FraudException
     */
    public function fiscalize(PaymentTransaction $payment, ?string $correlationId = null): bool
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'fiscal_transmission',
                'amount' => $payment->amount,
                'user_id' => $payment->user_id,
                'payment_id' => $payment->id,
                'correlation_id' => $correlationId,
            ]);

            // 2. AUDIT: Начало fiscalization
            Log::channel('audit')->info('Fiscal transmission started', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ]);

            // 3. ПРОВЕРКА СТАТУСА: платёж должен быть CAPTURED
            if ($payment->status !== PaymentTransaction::STATUS_CAPTURED) {
                throw new Exception(
                    "Cannot fiscalize payment with status: {$payment->status}. Expected: captured"
                );
            }

            // 4. ПОЛУЧИТЬ ДАННЫЕ ЗАКАЗА
            $items = $payment->metadata['items'] ?? [];
            if (empty($items)) {
                Log::channel('audit')->warning('Fiscal: no items to fiscalize', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                ]);
                return false;
            }

            // 5. ПОДГОТОВИТЬ ЧЕК
            $checkData = $this->prepareCheckData($payment, $items, $correlationId);

            // 6. ОТПРАВИТЬ В ОФД
            $result = $this->sendToOFD($checkData, $payment, $correlationId);

            if ($result) {
                Log::channel('audit')->info('Fiscal transmission succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                    'fiscal_number' => $checkData['external_id'] ?? null,
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::channel('audit')->error('Fiscal transmission failed', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Не выбрасываем исключение — платёж уже списан,
            // fiscalization будет повторена в фоновом Job (RetryFailedFiscalization)
            return false;
        }
    }

    /**
     * Подготовить данные чека для ОФД
     *
     * @param PaymentTransaction $payment
     * @param array $items
     * @param string $correlationId
     * @return array
     */
    private function prepareCheckData(PaymentTransaction $payment, array $items, string $correlationId): array
    {
        return [
            'external_id' => "payment_{$payment->id}_{$correlationId}",
            'correlation_id' => $correlationId,
            'receipt' => [
                'client' => [
                    'email' => $payment->metadata['customer_email'] ?? null,
                    'phone' => $payment->metadata['customer_phone'] ?? null,
                ],
                'company' => [
                    'email' => config('fiscal.company_email', 'company@example.com'),
                    'inn' => config('fiscal.company_inn', ''),
                    'payment_address' => config('fiscal.payment_address', ''),
                ],
                'items' => $this->prepareItems($items),
                'payments' => [
                    [
                        'type' => 2,  // 2 = электронные деньги (card / e-wallet)
                        'sum' => $payment->amount,
                    ],
                ],
                'total' => $payment->amount,
            ],
        ];
    }

    /**
     * Подготовить список товаров для ОФД
     *
     * @param array $items
     * @return array
     */
    private function prepareItems(array $items): array
    {
        return array_map(function (array $item) {
            return [
                'name' => $item['name'] ?? 'Unknown Item',
                'price' => intval($item['price'] ?? 0),
                'quantity' => floatval($item['quantity'] ?? 1),
                'amount' => intval($item['amount'] ?? 0),
                'vat_type' => $item['vat_type'] ?? 1,  // 1 = 20%, 2 = 10%, etc.
            ];
        }, $items);
    }

    /**
     * Отправить чек в ОФД
     *
     * @param array $checkData
     * @param PaymentTransaction $payment
     * @param string $correlationId
     * @return bool
     *
     * @throws Exception
     */
    private function sendToOFD(array $checkData, PaymentTransaction $payment, string $correlationId): bool
    {
        $ofdProvider = config('fiscal.provider', 'yandex');

        return match ($ofdProvider) {
            'yandex' => $this->sendToYandexKassaOFD($checkData, $correlationId),
            'tinkoff' => $this->sendToTinkoffOFD($checkData, $correlationId),
            'custom' => $this->sendToCustomOFD($checkData, $correlationId),
            default => throw new Exception("Unknown OFD provider: {$ofdProvider}"),
        };
    }

    /**
     * Отправить в Yandex Kassa OFD API
     *
     * @param array $checkData
     * @param string $correlationId
     * @return bool
     *
     * @throws Exception
     */
    private function sendToYandexKassaOFD(array $checkData, string $correlationId): bool
    {
        $apiKey = config('fiscal.yandex_api_key', '');
        if (!$apiKey) {
            throw new Exception('Yandex Kassa OFD API key not configured');
        }

        try {
            $response = $this->http
                ->withHeader('Authorization', "Bearer {$apiKey}")
                ->withHeader('X-Correlation-ID', $correlationId)
                ->post('https://api.yandex.com/receipts', $checkData);

            if (!$response->successful()) {
                throw new Exception(
                    "Yandex OFD error: {$response->status()} - {$response->body()}"
                );
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to send to Yandex Kassa OFD: {$e->getMessage()}");
        }
    }

    /**
     * Отправить в Tinkoff OFD API
     *
     * @param array $checkData
     * @param string $correlationId
     * @return bool
     *
     * @throws Exception
     */
    private function sendToTinkoffOFD(array $checkData, string $correlationId): bool
    {
        $apiKey = config('fiscal.tinkoff_api_key', '');
        $apiPassword = config('fiscal.tinkoff_api_password', '');

        if (!$apiKey || !$apiPassword) {
            throw new Exception('Tinkoff OFD credentials not configured');
        }

        try {
            $response = $this->http
                ->withBasicAuth($apiKey, $apiPassword)
                ->withHeader('X-Correlation-ID', $correlationId)
                ->post('https://api.tinkoff.ru/ofd/receipts', $checkData);

            if (!$response->successful()) {
                throw new Exception(
                    "Tinkoff OFD error: {$response->status()} - {$response->body()}"
                );
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to send to Tinkoff OFD: {$e->getMessage()}");
        }
    }

    /**
     * Отправить в custom OFD провайдер
     *
     * @param array $checkData
     * @param string $correlationId
     * @return bool
     */
    private function sendToCustomOFD(array $checkData, string $correlationId): bool
    {
        // Заглушка для кастомного провайдера
        // Имплементируется в подклассах или конкретных реализациях
        Log::channel('audit')->info('Custom OFD provider not implemented', [
            'correlation_id' => $correlationId,
        ]);

        return false;
    }
}
