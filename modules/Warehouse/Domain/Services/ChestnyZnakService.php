<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use Modules\Warehouse\Domain\Exceptions\ChestnyZnakException;
use Modules\Warehouse\Domain\Entities\Batch;

/**
 * Chestny ZNAK Integration Service for ФЗ-61 compliance
 * 
 * Сервис обеспечивает интеграцию с системой Честный ЗНАК:
 * - Проверка кодов маркировки
 * - Регистрация товаров в системе
 * - Отправка данных о движении
 * - Получение статусов маркировки
 */
final readonly class ChestnyZnakService
{
    private const API_VERSION = 'v3';
    private const PRODUCT_GROUP_PHARMA = 'pharma'; // Лекарственные препараты
    private const PRODUCT_GROUP_TOBACCO = 'tobacco'; // Табачные изделия
    private const PRODUCT_GROUP_WATER = 'water'; // Питьевая вода

    public function __construct(
        private readonly HttpFactory $http,
        private readonly LoggerInterface $logger,
        private string $apiUrl,
        private string $apiKey,
        private ?string $certificatePath = null
    ) {}

    /**
     * Проверка кода маркировки
     */
    public function checkMarkingCode(string $code, string $productGroup = self::PRODUCT_GROUP_PHARMA): array
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents/check", [
                'codes' => [$code],
            ]);

            if (!$response->successful()) {
                throw ChestnyZnakException::checkFailed($code, $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK check failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Регистрация прихода товара с маркировкой
     */
    public function registerReceipt(
        string $documentNumber,
        \DateTimeImmutable $documentDate,
        array $markedProducts,
        string $productGroup = self::PRODUCT_GROUP_PHARMA
    ): string {
        try {
            $document = [
                'document_number' => $documentNumber,
                'document_date' => $documentDate->format('Y-m-d'),
                'type' => 'LP_INTRODUCE_GOODS', // Ввод в оборот
                'product_group' => $productGroup,
                'products' => array_map(fn ($product) => [
                    'cis' => $product['cis'], // Код маркировки
                    'quantity' => $product['quantity'],
                    'price' => $product['price'] ?? 0,
                ], $markedProducts),
            ];

            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents", $document);

            if (!$response->successful()) {
                throw ChestnyZnakException::registrationFailed($documentNumber, $response->body());
            }

            $result = $response->json();
            return $result['document_id'] ?? throw ChestnyZnakException::invalidResponse();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK receipt registration failed', [
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Регистрация отгрузки товара с маркировкой
     */
    public function registerShipment(
        string $documentNumber,
        \DateTimeImmutable $documentDate,
        string $receiverInn,
        array $markedProducts,
        string $productGroup = self::PRODUCT_GROUP_PHARMA
    ): string {
        try {
            $document = [
                'document_number' => $documentNumber,
                'document_date' => $documentDate->format('Y-m-d'),
                'type' => 'LP_SHIP_GOODS', // Отгрузка
                'product_group' => $productGroup,
                'receiver_inn' => $receiverInn,
                'products' => array_map(fn ($product) => [
                    'cis' => $product['cis'],
                    'quantity' => $product['quantity'],
                ], $markedProducts),
            ];

            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents", $document);

            if (!$response->successful()) {
                throw ChestnyZnakException::registrationFailed($documentNumber, $response->body());
            }

            $result = $response->json();
            return $result['document_id'] ?? throw ChestnyZnakException::invalidResponse();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK shipment registration failed', [
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Получение статуса документа
     */
    public function getDocumentStatus(string $documentId, string $productGroup = self::PRODUCT_GROUP_PHARMA): array
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents/{$documentId}/status");

            if (!$response->successful()) {
                throw ChestnyZnakException::statusCheckFailed($documentId, $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK status check failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Регистрация списания товара (damage, loss, expiration)
     */
    public function registerWriteOff(
        string $documentNumber,
        \DateTimeImmutable $documentDate,
        string $reasonType, // 'damage', 'loss', 'expiration', 'other'
        array $markedProducts,
        string $productGroup = self::PRODUCT_GROUP_PHARMA
    ): string {
        try {
            $document = [
                'document_number' => $documentNumber,
                'document_date' => $documentDate->format('Y-m-d'),
                'type' => 'LP_WRITE_OFF_GOODS',
                'product_group' => $productGroup,
                'reason_type' => $reasonType,
                'products' => array_map(fn ($product) => [
                    'cis' => $product['cis'],
                    'quantity' => $product['quantity'],
                    'reason' => $product['reason'] ?? null,
                ], $markedProducts),
            ];

            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents", $document);

            if (!$response->successful()) {
                throw ChestnyZnakException::registrationFailed($documentNumber, $response->body());
            }

            $result = $response->json();
            return $result['document_id'] ?? throw ChestnyZnakException::invalidResponse();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK write-off registration failed', [
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Проверка обязательности маркировки для товара
     */
    public function isMarkingRequired(string $productCategory): bool
    {
        $markedCategories = [
            'pharmaceutical',
            'medication',
            'medicine',
            'drug',
            'vaccine',
            'antibiotic',
            'tobacco',
            'water',
        ];

        $category = strtolower($productCategory);

        foreach ($markedCategories as $markedCategory) {
            if (str_contains($category, $markedCategory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Определение группы товаров для Честный ЗНАК
     */
    public function determineProductGroup(string $productCategory): string
    {
        $category = strtolower($productCategory);

        if (str_contains($category, 'tobacco')) {
            return self::PRODUCT_GROUP_TOBACCO;
        }

        if (str_contains($category, 'water')) {
            return self::PRODUCT_GROUP_WATER;
        }

        // По умолчанию - лекарства
        return self::PRODUCT_GROUP_PHARMA;
    }

    /**
     * Валидация формата кода маркировки DataMatrix
     */
    public function validateMarkingCodeFormat(string $code): bool
    {
        // DataMatrix код должен начинаться с (01) для GTIN
        // Формат: (01)GTIN(21)SERIAL(17)EXPIRY(10)BATCH
        $pattern = '/^\(01\d{14}\)\(21\d{7}\)\(17\d{6}\)\(10.{6}\)$/';
        
        return (bool) preg_match($pattern, $code);
    }

    /**
     * Извлечение GTIN из кода маркировки
     */
    public function extractGtin(string $code): ?string
    {
        if (preg_match('/\(01(\d{14})\)/', $code, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Извлечение серийного номера из кода маркировки
     */
    public function extractSerial(string $code): ?string
    {
        if (preg_match('/\(21(\d{7})\)/', $code, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Извлечение срока годности из кода маркировки
     */
    public function extractExpiry(string $code): ?string
    {
        if (preg_match('/\(17(\d{6})\)/', $code, $matches)) {
            return $matches[1]; // YYMMDD
        }

        return null;
    }

    /**
     * Извлечение номера партии из кода маркировки
     */
    public function extractBatch(string $code): ?string
    {
        if (preg_match('/\(10(.{6})\)/', $code, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Регистрация возврата товара
     */
    public function registerReturn(
        string $documentNumber,
        \DateTimeImmutable $documentDate,
        string $senderInn,
        array $markedProducts,
        string $productGroup = self::PRODUCT_GROUP_PHARMA
    ): string {
        try {
            $document = [
                'document_number' => $documentNumber,
                'document_date' => $documentDate->format('Y-m-d'),
                'type' => 'LP_RETURN_GOODS',
                'product_group' => $productGroup,
                'sender_inn' => $senderInn,
                'products' => array_map(fn ($product) => [
                    'cis' => $product['cis'],
                    'quantity' => $product['quantity'],
                ], $markedProducts),
            ];

            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents", $document);

            if (!$response->successful()) {
                throw ChestnyZnakException::registrationFailed($documentNumber, $response->body());
            }

            $result = $response->json();
            return $result['document_id'] ?? throw ChestnyZnakException::invalidResponse();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK return registration failed', [
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Получение информации о коде маркировки
     */
    public function getCodeInfo(string $code, string $productGroup = self::PRODUCT_GROUP_PHARMA): array
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/codes/{$code}/info");

            if (!$response->successful()) {
                throw ChestnyZnakException::infoCheckFailed($code, $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK code info failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Пакетная проверка кодов маркировки
     */
    public function checkMarkingCodesBatch(array $codes, string $productGroup = self::PRODUCT_GROUP_PHARMA): array
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents/check", [
                'codes' => $codes,
            ]);

            if (!$response->successful()) {
                throw ChestnyZnakException::checkBatchFailed($response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK batch check failed', [
                'codes_count' => count($codes),
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Подписание документа ЭЦП
     */
    public function signDocument(string $documentId, string $signature, string $productGroup = self::PRODUCT_GROUP_PHARMA): bool
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents/{$documentId}/sign", [
                'signature' => $signature,
                'signature_type' => 'CMS', // CryptoPro signature
            ]);

            if (!$response->successful()) {
                throw ChestnyZnakException::signingFailed($documentId, $response->body());
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK document signing failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Отправка документа в систему
     */
    public function sendDocument(string $documentId, string $productGroup = self::PRODUCT_GROUP_PHARMA): bool
    {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents/{$documentId}/send");

            if (!$response->successful()) {
                throw ChestnyZnakException::sendFailed($documentId, $response->body());
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK document send failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Получение списка документов за период
     */
    public function getDocumentsByPeriod(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $productGroup = self::PRODUCT_GROUP_PHARMA
    ): array {
        try {
            $response = $this->http->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->apiUrl}/" . self::API_VERSION . "/{$productGroup}/documents", [
                'date_from' => $startDate->format('Y-m-d'),
                'date_to' => $endDate->format('Y-m-d'),
            ]);

            if (!$response->successful()) {
                throw ChestnyZnakException::documentsListFailed($response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Chestny ZNAK documents list failed', [
                'period' => "{$startDate->format('Y-m-d')} - {$endDate->format('Y-m-d')}",
                'error' => $e->getMessage(),
            ]);
            throw ChestnyZnakException::connectionError($e->getMessage());
        }
    }

    /**
     * Обработка webhook уведомления от Честный ЗНАК
     */
    public function handleWebhook(array $payload): array
    {
        $documentId = $payload['document_id'] ?? null;
        $eventType = $payload['event_type'] ?? null;

        if (!$documentId || !$eventType) {
            throw ChestnyZnakException::invalidWebhookPayload();
        }

        $this->logger->info('Chestny ZNAK webhook received', [
            'document_id' => $documentId,
            'event_type' => $eventType,
        ]);

        // Логирование события webhook
        $this->logWebhookEvent($documentId, $eventType, $payload);

        return match ($eventType) {
            'document_processed' => $this->handleDocumentProcessed($documentId, $payload),
            'document_rejected' => $this->handleDocumentRejected($documentId, $payload),
            'document_signed' => $this->handleDocumentSigned($documentId, $payload),
            default => ['status' => 'ignored', 'reason' => 'Unknown event type'],
        };
    }

    /**
     * Логирование события webhook
     */
    private function logWebhookEvent(string $documentId, string $eventType, array $payload): void
    {
        $this->db->table('chestny_znak_webhooks')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'document_id' => $documentId,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'processed_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Обработка события: документ обработан
     */
    private function handleDocumentProcessed(string $documentId, array $payload): array
    {
        // Обновить статус документа в локальной БД
        $this->db->table('chestny_znak_documents')
            ->where('document_id', $documentId)
            ->update([
                'status' => 'processed',
                'status_message' => $payload['message'] ?? null,
                'processed_at' => now(),
            ]);

        return ['status' => 'processed', 'document_id' => $documentId];
    }

    /**
     * Обработка события: документ отклонен
     */
    private function handleDocumentRejected(string $documentId, array $payload): array
    {
        $this->db->table('chestny_znak_documents')
            ->where('document_id', $documentId)
            ->update([
                'status' => 'rejected',
                'status_message' => $payload['message'] ?? null,
                'rejection_reason' => $payload['rejection_reason'] ?? null,
                'processed_at' => now(),
            ]);

        return ['status' => 'rejected', 'document_id' => $documentId];
    }

    /**
     * Обработка события: документ подписан
     */
    private function handleDocumentSigned(string $documentId, array $payload): array
    {
        $this->db->table('chestny_znak_documents')
            ->where('document_id', $documentId)
            ->update([
                'status' => 'signed',
                'signature' => $payload['signature'] ?? null,
                'signed_at' => now(),
            ]);

        return ['status' => 'signed', 'document_id' => $documentId];
    }

    /**
     * Retry-logic для API вызовов с экспоненциальным backoff
     */
    private function retryApiCall(callable $apiCall, int $maxRetries = 3, int $initialDelayMs = 1000): mixed
    {
        $delay = $initialDelayMs;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $apiCall();
            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }

                $this->logger->warning('API call failed, retrying', [
                    'attempt' => $attempt,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage(),
                ]);

                usleep($delay * 1000);
                $delay *= 2; // Exponential backoff
            }
        }

        throw new \RuntimeException('Retry failed');
    }

    /**
     * Синхронизация статусов документов с Честный ЗНАК
     */
    public function syncDocumentStatuses(string $productGroup = self::PRODUCT_GROUP_PHARMA): array
    {
        $pendingDocuments = $this->db->table('chestny_znak_documents')
            ->whereIn('status', ['pending', 'processing'])
            ->get();

        $results = [];

        foreach ($pendingDocuments as $document) {
            try {
                $status = $this->getDocumentStatus($document->document_id, $productGroup);
                
                $this->db->table('chestny_znak_documents')
                    ->where('id', $document->id)
                    ->update([
                        'status' => $status['status'] ?? $document->status,
                        'status_message' => $status['message'] ?? null,
                        'synced_at' => now(),
                    ]);

                $results[] = [
                    'document_id' => $document->document_id,
                    'status' => 'synced',
                    'new_status' => $status['status'] ?? null,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'document_id' => $document->document_id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
