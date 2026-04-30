<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\OFD;

use App\Domains\Payment\Contracts\OFDInterface;
use App\Domains\Payment\DTOs\FiscalReceiptDto;
use App\Domains\Payment\Models\FiscalDocument;
use App\Domains\Payment\Models\PaymentRecord;
use App\Services\AuditService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * OFD Service - оркестратор для фискализации чеков (54-ФЗ).
 *
 * Управляет отправкой чеков в ОФД через разных провайдеров.
 * Автоматически создаёт FiscalDocument записи.
 *
 * Используется в PaymentCoordinatorService после успешного платежа.
 */
final readonly class OFDService
{
    private const OFD_PROVIDERS = [
        'tensor' => TensorOFDService::class,
        'kontur' => KonturOFDService::class,
    ];

    public function __construct(
        private readonly Container $container,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Фискализировать чек для платежа.
     *
     * @return FiscalDocument
     */
    public function fiscalizePayment(
        PaymentRecord $paymentRecord,
        FiscalReceiptDto $receiptDto,
        string $ofdProvider = 'tensor',
        string $correlationId = null,
    ): FiscalDocument {
        $correlationId ??= (string) Str::uuid();

        $this->logger->info('OFD: fiscalizing payment', [
            'payment_record_id' => $paymentRecord->id,
            'ofd_provider' => $ofdProvider,
            'correlation_id' => $correlationId,
        ]);

        $ofd = $this->getOFDProvider($ofdProvider);

        $receiptData = $receiptDto->toArray();

        return $this->db->transaction(function () use ($paymentRecord, $ofd, $receiptData, $ofdProvider, $correlationId) {
            // 1. Отправить чек в ОФД
            $ofdResponse = $ofd->sendReceipt($receiptData, $correlationId);

            // 2. Создать запись фискального документа
            $fiscalDocument = FiscalDocument::create([
                'tenant_id' => $paymentRecord->tenant_id,
                'business_group_id' => $paymentRecord->business_group_id,
                'payment_record_id' => $paymentRecord->id,
                'uuid' => (string) Str::uuid(),
                'ofd_provider' => $ofdProvider,
                'receipt_type' => $receiptData['type'],
                'fiscal_sign' => $ofdResponse['fiscal_sign'],
                'fiscal_document_number' => $ofdResponse['fiscal_document_number'],
                'fiscal_document_attribute' => $ofdResponse['fiscal_document_attribute'],
                'status' => 'sent',
                'amount_kopecks' => $receiptData['total_amount'],
                'taxation_type' => $receiptData['taxation_type'],
                'receipt_data' => $receiptData,
                'ofd_response' => $ofdResponse['ofd_response'],
                'sent_at' => now(),
                'correlation_id' => $correlationId,
                'metadata' => $receiptData['metadata'] ?? null,
            ]);

            $this->logger->info('OFD: fiscal document created', [
                'fiscal_document_id' => $fiscalDocument->id,
                'fiscal_sign' => $fiscalDocument->fiscal_sign,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'fiscal_document_created',
                subjectType: FiscalDocument::class,
                subjectId: $fiscalDocument->id,
                newValues: [
                    'payment_record_id' => $paymentRecord->id,
                    'ofd_provider' => $ofdProvider,
                    'fiscal_sign' => $fiscalDocument->fiscal_sign,
                ],
                correlationId: $correlationId,
            );

            return $fiscalDocument;
        });
    }

    /**
     * Проверить статус чека в ОФД.
     *
     * @return FiscalDocument
     */
    public function checkReceiptStatus(FiscalDocument $fiscalDocument, string $correlationId): FiscalDocument
    {
        $ofd = $this->getOFDProvider($fiscalDocument->ofd_provider);

        $statusResponse = $ofd->getReceiptStatus($fiscalDocument->fiscal_sign, $correlationId);

        $fiscalDocument->update([
            'status' => $statusResponse['status'] === 'received' ? 'received' : $fiscalDocument->status,
            'received_at' => $statusResponse['received_at'] ?? $fiscalDocument->received_at,
        ]);

        $this->logger->info('OFD: receipt status checked', [
            'fiscal_document_id' => $fiscalDocument->id,
            'status' => $statusResponse['status'],
            'correlation_id' => $correlationId,
        ]);

        return $fiscalDocument->fresh();
    }

    /**
     * Получить провайдер ОФД.
     */
    private function getOFDProvider(string $provider): OFDInterface
    {
        if (! isset(self::OFD_PROVIDERS[$provider])) {
            throw new \InvalidArgumentException("OFD provider {$provider} not supported");
        }

        return $this->container->make(self::OFD_PROVIDERS[$provider]);
    }

    /**
     * Поддерживаемые провайдеры.
     *
     * @return array<int, string>
     */
    public function getSupportedProviders(): array
    {
        return array_keys(self::OFD_PROVIDERS);
    }
}
