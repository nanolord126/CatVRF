<?php declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

final readonly class FiscalService
{
    /**
     * Передать чек в ОФД (54-ФЗ fiscalization)
     *
     * Вызывается ТОЛЬКО после успешного capture платежа,
     * когда деньги точно списаны со счёта.
     */
    public function fiscalize(PaymentTransaction $payment): void
    {
        try {
            // Проверка: платёж должен быть CAPTURED
            if ($payment->status !== PaymentTransaction::STATUS_CAPTURED) {
                throw new Exception(
                    "Cannot fiscalize payment with status: {$payment->status}"
                );
            }

            // Получить данные заказа (assumption: metadata содержит items)
            $items = $payment->metadata['items'] ?? [];
            if (empty($items)) {
                Log::channel('audit')->warning('Fiscal: no items to fiscalize', [
                    'payment_id' => $payment->id,
                    'correlation_id' => $payment->correlation_id,
                ]);
                return;
            }

            // Подготовить чек для ОФД
            $checkData = [
                'external_id' => "payment_{$payment->id}_{$payment->correlation_id}",
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
                            'type' => 1, // 1 = наличные, 2 = электронные
                            'sum' => $payment->amount,
                        ],
                    ],
                    'total' => $payment->amount,
                ],
            ];

            // Отправить в ОФД API (Yandex Kassa, Tinkoff, или собственный)
            $this->sendToOFD($checkData, $payment);

            Log::channel('audit')->info('Payment fiscalized', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'correlation_id' => $payment->correlation_id,
            ]);

        } catch (Exception $e) {
            Log::channel('audit')->error('Fiscal error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $payment->correlation_id,
            ]);

            // Не выбрасываем исключение — fiscalization не должна блокировать платёж
            // Но логируем для последующей обработки
        }
    }

    /**
     * Подготовить список товаров для ОФД
     */
    private function prepareItems(array $items): array
    {
        return array_map(function (array $item) {
            return [
                'name' => $item['name'] ?? 'Unknown',
                'price' => intval($item['price'] ?? 0),
                'quantity' => floatval($item['quantity'] ?? 1),
                'amount' => intval($item['amount'] ?? 0),
                'vat_type' => $item['vat_type'] ?? 1, // 1 = 20%, 2 = 10%, etc.
            ];
        }, $items);
    }

    /**
     * Отправить чек в ОФД (заглушка)
     *
     * В production нужна интеграция с:
     * - Yandex Kassa OFD API
     * - Tinkoff OFD API
     * - Или собственный ОФД провайдер
     */
    private function sendToOFD(array $checkData, PaymentTransaction $payment): void
    {
        $ofdProvider = config('fiscal.provider', 'yandex'); // 'yandex', 'tinkoff', 'custom'

        match ($ofdProvider) {
            'yandex' => $this->sendToYandexKassaOFD($checkData),
            'tinkoff' => $this->sendToTinkoffOFD($checkData),
            'custom' => $this->sendToCustomOFD($checkData),
            default => throw new Exception("Unknown OFD provider: {$ofdProvider}"),
        };
    }

    /**
     * Отправить в Yandex Kassa OFD API
     */
    private function sendToYandexKassaOFD(array $checkData): void
    {
        $apiKey = config('fiscal.yandex_api_key', '');
        if (!$apiKey) {
            throw new Exception('Yandex Kassa OFD API key not configured');
        }

        // Пример: POST https://api.yandex.com/receipts
        $response = Http::withHeader('Authorization', "Bearer {$apiKey}")
            ->post('https://api.yandex.com/receipts', $checkData);

        if (!$response->successful()) {
            throw new Exception(
                "Yandex OFD error: {$response->status()} - {$response->body()}"
            );
        }
    }

    /**
     * Отправить в Tinkoff OFD API
     */
    private function sendToTinkoffOFD(array $checkData): void
    {
        // Аналогично Yandex, но с Tinkoff API
    }

    /**
     * Отправить в custom OFD провайдер
     */
    private function sendToCustomOFD(array $checkData): void
    {
    }
}
