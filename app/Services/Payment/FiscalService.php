<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Models\PaymentTransaction;
use App\Services\Fraud\FraudControlService;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Exceptions\FraudException;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Http\Request;

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
 */
final readonly class FiscalService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly PendingRequest $http,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly Request $request,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Fiscalize платёж в ОФД (54-ФЗ)
     *
     * ВАЖНО: Вызывается ТОЛЬКО после успешного capture!
     * Если fiscalization не удалась, она логируется, но не блокирует платёж
     * (платёж уже списан со счёта, поэтому должна быть фискальная справка)
     *
     *
     * @throws FraudException
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
            $this->logger->channel('audit')->$this->logger->info('Fiscal transmission started', [
                'correlation_id' => $correlationId,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ]);

            // 3. ПРОВЕРКА СТАТУСА: платёж должен быть CAPTURED
            if ($payment->status !== PaymentTransaction::STATUS_CAPTURED) {
                throw new \RuntimeException(
                    "Cannot fiscalize payment with status: {$payment->status}. Expected: captured"
                );
            }

            // 4. ПОЛУЧИТЬ ДАННЫЕ ЗАКАЗА
            $items = $payment->metadata['items'] ?? [];
            if (empty($items)) {
                $this->logger->channel('audit')->warning('Fiscal: no items to fiscalize', [
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
                $this->logger->channel('audit')->$this->logger->info('Fiscal transmission succeeded', [
                    'correlation_id' => $correlationId,
                    'payment_id' => $payment->id,
                    'fiscal_number' => $checkData['external_id'] ?? null,
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->error('Fiscal transmission failed', [
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
                    'email' => $this->config->get('fiscal.company_email', 'company@example.com'),
                    'inn' => $this->config->get('fiscal.company_inn', ''),
                    'payment_address' => $this->config->get('fiscal.payment_address', ''),
                ],
                'items' => $this->prepareItems($items),
                'payments' => [
                    [
                        'type' => 2,  // 2 = электронные деньги (card / e-wallet)
                    ],
                ],
                'total' => $payment->amount,
            ],
        ];
    }

    /**
     * Подготовить список товаров для ОФД
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
     *
     * @throws Exception
     */
    private function sendToOFD(array $checkData, PaymentTransaction $payment, string $correlationId): bool
    {
        $ofdProvider = $this->config->get('fiscal.provider', 'yandex');

        return match ($ofdProvider) {
            'tinkoff' => $this->sendToTinkoffOFD($checkData, $correlationId),
            'custom' => $this->sendToCustomOFD($checkData, $correlationId),
            default => throw new \InvalidArgumentException("Unknown OFD provider: {$ofdProvider}"),
        };
    }

    /**
     * Отправить в Yandex Kassa OFD API
     *
     *
     * @throws Exception
     */
    private function sendToYandexKassaOFD(array $checkData, string $correlationId): bool
    {
        $apiKey = $this->config->get('fiscal.yandex_api_key', '');
        if (! $apiKey) {
            throw new \RuntimeException('Yandex Kassa OFD API key not configured');
        }

        try {
            $response = $this->http
                ->withHeader('Authorization', "Bearer {$apiKey}")
                ->withHeader('X-Correlation-ID', $correlationId)
                ->post('https://api.yandex.com/receipts', $checkData);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Yandex OFD error: {$response->status()} - {$response->body()}"
                );
            }

            return true;
        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException("Failed to send to Yandex Kassa OFD: {$e->getMessage()}");
        }
    }

    /**
     * Отправить в Tinkoff OFD API
     *
     *
     * @throws Exception
     */
    private function sendToTinkoffOFD(array $checkData, string $correlationId): bool
    {
        $apiKey = $this->config->get('fiscal.tinkoff_api_key', '');
        $apiPassword = $this->config->get('fiscal.tinkoff_api_password', '');

        if (! $apiKey || ! $apiPassword) {
            throw new \RuntimeException('Tinkoff OFD credentials not configured');
        }

        try {
            $response = $this->http
                ->withBasicAuth($apiKey, $apiPassword)
                ->withHeader('X-Correlation-ID', $correlationId)
                ->post('https://api.tinkoff.ru/ofd/receipts', $checkData);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Tinkoff OFD error: {$response->status()} - {$response->body()}"
                );
            }

            return true;
        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            throw new \RuntimeException("Failed to send to Tinkoff OFD: {$e->getMessage()}");
        }
    }

    /**
     * Отправить в custom OFD провайдер
     */
    private function sendToCustomOFD(array $checkData, string $correlationId): bool
    {
        // Интеграция с кастомным провайдером фискальных данных
        // Имплементируется в подклассах или конкретных реализациях
        $this->logger->channel('audit')->$this->logger->info('Custom OFD provider not implemented', [
            'correlation_id' => $correlationId,
        ]);

        return false;
    }
}
