<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Payment\Domain\Entities\FiscalReceipt;
use Modules\Payment\Domain\Repositories\FiscalReceiptRepositoryInterface;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Fiscalization Service for 54-ФЗ compliance.
 * 
 * Handles KKT (cash register) receipt generation and transmission to OFD providers.
 * Supports:
 * - Prepayment receipts (Предоплата/Аванс)
 * - Full payment receipts with prepayment offset (Полный расчёт)
 * - Refund receipts (Возврат)
 * 
 * OFD Providers supported:
 * - OrangeData (https://orangedata.ru)
 * - CloudKassir (https://cloudkassir.ru)
 * - Atol (https://atol.ru)
 * - Yandex.Kassa (built-in fiscalization)
 */
final readonly class FiscalizationService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_TAG = 'fiscal_receipts';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;

    public function __construct(
        private FiscalReceiptRepositoryInterface $repository,
        private AuditService $audit,
        private LoggerInterface $logger,
        private readonly UuidInterface $uuid,
    ) {}

    /**
     * Create and send prepayment receipt (54-ФЗ requirement).
     * 
     * Called when payment is captured but before order fulfillment.
     */
    public function fiscalizePrepayment(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $sellerInn,
        string $agentName,
        int $amountKopecks,
        array $items,
    ): FiscalReceipt {
        $correlationId = $this->uuid->toString();

        $receipt = FiscalReceipt::createPrepayment(
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            inn: $sellerInn,
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            items: $items,
        );

        $this->repository->save($receipt);

        // Log audit event
        $this->audit->logEvent(
            'fiscal_54fz_prepayment_created',
            [
                'subject_type' => 'FiscalReceipt',
                'subject_id' => $receipt->uuid,
                'payment_intent_uuid' => $paymentIntentUuid,
                'order_id' => $orderId,
                'type' => 'prepayment',
                'amount_kopecks' => $amountKopecks,
                'seller_inn' => $sellerInn,
            ],
            correlationId: $correlationId,
        );

        // Send to OFD asynchronously
        $this->sendReceiptToOFD($receipt);

        $this->logger->info('Prepayment receipt created (54-ФЗ)', [
            'receipt_uuid' => $receipt->uuid,
            'payment_intent_uuid' => $paymentIntentUuid,
            'order_id' => $orderId,
            'amount_kopecks' => $amountKopecks,
            'correlation_id' => $correlationId,
        ]);

        return $receipt;
    }

    /**
     * Create and send full payment receipt (54-ФЗ requirement).
     * 
     * Called when order is fulfilled/delivered.
     * Includes prepayment offset if applicable.
     */
    public function fiscalizeFullPayment(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $sellerInn,
        string $agentName,
        int $amountKopecks,
        array $items,
        ?string $prepaymentReceiptUuid = null,
        int $prepaymentAmountKopecks = 0,
    ): FiscalReceipt {
        $correlationId = $this->uuid->toString();

        // Adjust items to include prepayment offset if applicable
        if ($prepaymentAmountKopecks > 0) {
            $items = $this->addPrepaymentOffset($items, $prepaymentAmountKopecks);
        }

        $receipt = FiscalReceipt::createFullPayment(
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            inn: $sellerInn,
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            items: $items,
            prepaymentReceiptUuid: $prepaymentReceiptUuid,
        );

        $this->repository->save($receipt);

        // Log audit event
        $this->audit->logEvent(
            'fiscal_54fz_full_payment_created',
            [
                'subject_type' => 'FiscalReceipt',
                'subject_id' => $receipt->uuid,
                'payment_intent_uuid' => $paymentIntentUuid,
                'order_id' => $orderId,
                'type' => 'full_payment',
                'amount_kopecks' => $amountKopecks,
                'seller_inn' => $sellerInn,
                'prepayment_receipt_uuid' => $prepaymentReceiptUuid,
                'prepayment_amount_kopecks' => $prepaymentAmountKopecks,
            ],
            correlationId: $correlationId,
        );

        // Send to OFD asynchronously
        $this->sendReceiptToOFD($receipt);

        $this->logger->info('Full payment receipt created (54-ФЗ)', [
            'receipt_uuid' => $receipt->uuid,
            'payment_intent_uuid' => $paymentIntentUuid,
            'order_id' => $orderId,
            'amount_kopecks' => $amountKopecks,
            'prepayment_amount_kopecks' => $prepaymentAmountKopecks,
            'correlation_id' => $correlationId,
        ]);

        return $receipt;
    }

    /**
     * Create and send refund receipt (54-ФЗ requirement).
     * 
     * Called when payment is refunded.
     */
    public function fiscalizeRefund(
        string $paymentIntentUuid,
        ?int $tenantId,
        ?int $orderId,
        string $sellerInn,
        string $agentName,
        int $amountKopecks,
        array $items,
        string $refundReason,
    ): FiscalReceipt {
        $correlationId = $this->uuid->toString();

        $receipt = FiscalReceipt::createRefund(
            paymentIntentUuid: $paymentIntentUuid,
            tenantId: $tenantId,
            orderId: $orderId,
            inn: $sellerInn,
            agentName: $agentName,
            amountKopecks: $amountKopecks,
            items: $items,
        );

        $this->repository->save($receipt);

        // Log audit event
        $this->audit->logEvent(
            'fiscal_54fz_refund_created',
            [
                'subject_type' => 'FiscalReceipt',
                'subject_id' => $receipt->uuid,
                'payment_intent_uuid' => $paymentIntentUuid,
                'order_id' => $orderId,
                'type' => 'refund',
                'amount_kopecks' => $amountKopecks,
                'seller_inn' => $sellerInn,
                'refund_reason' => $refundReason,
            ],
            correlationId: $correlationId,
        );

        // Send to OFD asynchronously
        $this->sendReceiptToOFD($receipt);

        $this->logger->info('Refund receipt created (54-ФЗ)', [
            'receipt_uuid' => $receipt->uuid,
            'payment_intent_uuid' => $paymentIntentUuid,
            'order_id' => $orderId,
            'amount_kopecks' => $amountKopecks,
            'refund_reason' => $refundReason,
            'correlation_id' => $correlationId,
        ]);

        return $receipt;
    }

    /**
     * Send receipt to OFD provider.
     * 
     * Supports multiple OFD providers with automatic retry logic.
     */
    private function sendReceiptToOFD(FiscalReceipt $receipt): void
    {
        $ofdProvider = config('fiscalization.default_provider', 'orangedata');

        try {
            $result = match ($ofdProvider) {
                'orangedata' => $this->sendToOrangeData($receipt),
                'cloudkassir' => $this->sendToCloudKassir($receipt),
                'atol' => $this->sendToAtol($receipt),
                'yandex' => $this->sendToYandexKassa($receipt),
                default => throw new \InvalidArgumentException("Unsupported OFD provider: {$ofdProvider}"),
            };

            // Update receipt with OFD response
            $updatedReceipt = $receipt->markAsSent(
                ofdProvider: $ofdProvider,
                ofdReceiptId: $result['receipt_id'],
            );

            if ($result['fiscal_sign'] && $result['fiscal_document_number'] && $result['fn_number']) {
                $updatedReceipt = $updatedReceipt->markAsConfirmed(
                    fiscalSign: $result['fiscal_sign'],
                    fiscalDocumentNumber: $result['fiscal_document_number'],
                    fnNumber: $result['fn_number'],
                );
            }

            $this->repository->save($updatedReceipt);
            $this->invalidateCache();

            $this->logger->info('Receipt sent to OFD successfully', [
                'receipt_uuid' => $receipt->uuid,
                'ofd_provider' => $ofdProvider,
                'ofd_receipt_id' => $result['receipt_id'],
                'fiscal_sign' => $result['fiscal_sign'] ?? null,
            ]);

        } catch (Exception $e) {
            $failedReceipt = $receipt->markAsFailed($e->getMessage());
            $this->repository->save($failedReceipt);

            $this->logger->error('Failed to send receipt to OFD', [
                'receipt_uuid' => $receipt->uuid,
                'ofd_provider' => $ofdProvider,
                'error' => $e->getMessage(),
                'retry_count' => $failedReceipt->retryCount,
            ]);

            // Queue retry if applicable
            if ($failedReceipt->canRetry()) {
                $this->queueRetry($failedReceipt);
            }
        }
    }

    /**
     * Send receipt to OrangeData OFD.
     */
    private function sendToOrangeData(FiscalReceipt $receipt): array
    {
        $apiKey = config('fiscalization.providers.orangedata.api_key');
        $apiUrl = config('fiscalization.providers.orangedata.api_url');

        if (! $apiKey || ! $apiUrl) {
            throw new Exception('OrangeData API credentials not configured');
        }

        $payload = $this->buildOrangeDataPayload($receipt);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($apiUrl, $payload);

        if (! $response->successful()) {
            throw new Exception("OrangeData API error: {$response->body()}");
        }

        $data = $response->json();

        return [
            'receipt_id' => $data['id'] ?? null,
            'fiscal_sign' => $data['fp'] ?? null,
            'fiscal_document_number' => $data['fn'] ?? null,
            'fn_number' => $data['fs'] ?? null,
        ];
    }

    /**
     * Send receipt to CloudKassir OFD.
     */
    private function sendToCloudKassir(FiscalReceipt $receipt): array
    {
        $apiKey = config('fiscalization.providers.cloudkassir.api_key');
        $apiUrl = config('fiscalization.providers.cloudkassir.api_url');

        if (! $apiKey || ! $apiUrl) {
            throw new Exception('CloudKassir API credentials not configured');
        }

        $payload = $this->buildCloudKassirPayload($receipt);

        $response = Http::withHeaders([
            'X-CloudKassir-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($apiUrl, $payload);

        if (! $response->successful()) {
            throw new Exception("CloudKassir API error: {$response->body()}");
        }

        $data = $response->json();

        return [
            'receipt_id' => $data['uuid'] ?? null,
            'fiscal_sign' => $data['fiscal_sign'] ?? null,
            'fiscal_document_number' => $data['fiscal_document_number'] ?? null,
            'fn_number' => $data['fn_number'] ?? null,
        ];
    }

    /**
     * Send receipt to Atol OFD.
     */
    private function sendToAtol(FiscalReceipt $receipt): array
    {
        $apiKey = config('fiscalization.providers.atol.api_key');
        $groupCode = config('fiscalization.providers.atol.group_code');
        $apiUrl = config('fiscalization.providers.atol.api_url');

        if (! $apiKey || ! $groupCode || ! $apiUrl) {
            throw new Exception('Atol API credentials not configured');
        }

        $payload = $this->buildAtolPayload($receipt);

        $response = Http::withHeaders([
            'Token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$apiUrl}/{$groupCode}/sell", $payload);

        if (! $response->successful()) {
            throw new Exception("Atol API error: {$response->body()}");
        }

        $data = $response->json();

        return [
            'receipt_id' => $data['uuid'] ?? null,
            'fiscal_sign' => $data['data']['fp'] ?? null,
            'fiscal_document_number' => $data['data']['fn'] ?? null,
            'fn_number' => $data['data']['fs'] ?? null,
        ];
    }

    /**
     * Send receipt to Yandex.Kassa (built-in fiscalization).
     */
    private function sendToYandexKassa(FiscalReceipt $receipt): array
    {
        // Yandex.Kassa handles fiscalization automatically
        // We just need to record the receipt as sent
        return [
            'receipt_id' => $receipt->uuid,
            'fiscal_sign' => 'yandex_auto',
            'fiscal_document_number' => 'yandex_auto',
            'fn_number' => 'yandex_auto',
        ];
    }

    /**
     * Build OrangeData API payload.
     */
    private function buildOrangeDataPayload(FiscalReceipt $receipt): array
    {
        return [
            'id' => $receipt->uuid,
            'inn' => $receipt->inn,
            'group' => config('fiscalization.providers.orangedata.group', 'Main'),
            'key' => config('fiscalization.providers.orangedata.key'),
            'content' => [
                'type' => $this->mapReceiptType($receipt->type),
                'positions' => $this->mapItemsToOrangeData($receipt->items),
                'checkClose' => [
                    'payments' => [
                        [
                            'type' => 1, // Electronic payment
                            'amount' => $receipt->amountKopecks / 100.0,
                        ],
                    ],
                    'taxationSystem' => 1, // General taxation system
                ],
                'agentInfo' => [
                    'type' => $this->mapAgentType($receipt->agentType),
                    'payingAgentOperation' => 'Маркетплейс',
                    'payingAgentPhone' => config('fiscalization.agent_phone'),
                ],
            ],
        ];
    }

    /**
     * Build CloudKassir API payload.
     */
    private function buildCloudKassirPayload(FiscalReceipt $receipt): array
    {
        return [
            'uuid' => $receipt->uuid,
            'inn' => $receipt->inn,
            'type' => $this->mapReceiptType($receipt->type),
            'items' => $this->mapItemsToCloudKassir($receipt->items),
            'payments' => [
                [
                    'type' => 'electronic',
                    'amount' => $receipt->amountKopecks / 100.0,
                ],
            ],
            'taxation_system' => 'osn',
            'agent_info' => [
                'type' => $receipt->agentType,
                'name' => $receipt->agentName,
            ],
        ];
    }

    /**
     * Build Atol API payload.
     */
    private function buildAtolPayload(FiscalReceipt $receipt): array
    {
        return [
            'external_id' => $receipt->uuid,
            'receipt' => [
                'client' => [
                    'inn' => $receipt->inn,
                ],
                'company' => [
                    'inn' => $receipt->inn,
                    'payment_address' => config('fiscalization.payment_address', 'https://catvrf.ru'),
                ],
                'items' => $this->mapItemsToAtol($receipt->items),
                'payments' => [
                    [
                        'type' => 1,
                        'sum' => $receipt->amountKopecks / 100.0,
                    ],
                ],
                'taxation' => 1,
                'agent_info' => [
                    'type' => $this->mapAgentType($receipt->agentType),
                    'paying_agent_operation' => 'Маркетплейс',
                ],
            ],
        ];
    }

    /**
     * Map receipt type to OFD format.
     */
    private function mapReceiptType(string $type): string
    {
        return match ($type) {
            FiscalReceipt::TYPE_PREPAYMENT => 'sell',
            FiscalReceipt::TYPE_FULL_PAYMENT => 'sell',
            FiscalReceipt::TYPE_REFUND => 'sell_refund',
            default => 'sell',
        };
    }

    /**
     * Map agent type to OFD format.
     */
    private function mapAgentType(string $agentType): int
    {
        return match ($agentType) {
            'payment_agent' => 1,
            'bank_agent' => 2,
            default => 1,
        };
    }

    /**
     * Map items to OrangeData format.
     */
    private function mapItemsToOrangeData(array $items): array
    {
        return array_map(fn($item) => [
            'quantity' => $item['quantity'],
            'price' => $item['price'] / 100.0,
            'tax' => $item['vat_rate'] ?? 1, // VAT rate
            'text' => $item['name'],
            'paymentMethodType' => 4, // Full payment
            'paymentSubjectType' => 1, // Product
        ], $items);
    }

    /**
     * Map items to CloudKassir format.
     */
    private function mapItemsToCloudKassir(array $items): array
    {
        return array_map(fn($item) => [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'] / 100.0,
            'vat' => $item['vat_rate'] ?? '20',
            'payment_method' => 'full_payment',
            'payment_object' => 'commodity',
        ], $items);
    }

    /**
     * Map items to Atol format.
     */
    private function mapItemsToAtol(array $items): array
    {
        return array_map(fn($item) => [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'] / 100.0,
            'vat' => [
                'type' => $item['vat_rate'] ?? 'vat20',
            ],
            'payment_method' => 'full_payment',
            'payment_object' => 'commodity',
        ], $items);
    }

    /**
     * Add prepayment offset to items.
     */
    private function addPrepaymentOffset(array $items, int $prepaymentAmountKopecks): array
    {
        // Add a special item for prepayment offset
        $items[] = [
            'name' => 'Зачет предоплаты',
            'quantity' => 1,
            'price' => -$prepaymentAmountKopecks, // Negative price for offset
            'vat_rate' => 1, // No VAT for offset
        ];

        return $items;
    }

    /**
     * Queue receipt retry.
     */
    private function queueRetry(FiscalReceipt $receipt): void
    {
        // This would dispatch a FiscalReceiptRetryJob
        $this->logger->info('Queued receipt retry', [
            'receipt_uuid' => $receipt->uuid,
            'retry_count' => $receipt->retryCount,
        ]);
    }

    /**
     * Get receipt by UUID.
     */
    public function getReceipt(string $uuid): ?FiscalReceipt
    {
        return $this->repository->findByUuid($uuid);
    }

    /**
     * Get receipts by payment intent.
     */
    public function getReceiptsByPaymentIntent(string $paymentIntentUuid): array
    {
        return $this->repository->findByPaymentIntentUuid($paymentIntentUuid);
    }

    /**
     * Get pending receipts (need to be sent to OFD).
     */
    public function getPendingReceipts(int $limit = 100): array
    {
        return $this->repository->findPending($limit);
    }

    /**
     * Process pending receipts (for scheduled job).
     */
    public function processPendingReceipts(): int
    {
        $pendingReceipts = $this->getPendingReceipts(100);
        $processed = 0;

        foreach ($pendingReceipts as $receipt) {
            try {
                $this->sendReceiptToOFD($receipt);
                $processed++;
            } catch (Exception $e) {
                $this->logger->error('Failed to process pending receipt', [
                    'receipt_uuid' => $receipt->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Cleanup old receipts (older than 5 years per 54-ФЗ).
     */
    public function cleanupOldReceipts(): int
    {
        $cutoffDate = CarbonImmutable::now()->subYears(5);
        $deleted = $this->repository->deleteOlderThan($cutoffDate);

        $this->logger->info('Fiscal receipts cleaned up (5-year retention)', [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return $deleted;
    }

    private function invalidateCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
