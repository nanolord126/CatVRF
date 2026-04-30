<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Pharmaceutical Document Flow Service for ФЗ-61 compliance
 * 
 * Сервис обеспечивает документооборот по движению лекарственных средств:
 * - Создание документов прихода/отгрузки
 * - Регистрация движения лекарств
 * - Валидация документов
 * - Архивирование документов
 */
final readonly class PharmaceuticalDocumentFlowService
{
    private const DOCUMENT_TYPES = [
        'receipt' => 'Приходная накладная',
        'shipment' => 'Расходная накладная',
        'return' => 'Накладная на возврат',
        'write_off' => 'Акт списания',
        'transfer' => 'Накладная на перемещение',
        'adjustment' => 'Акт корректировки',
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Создание документа движения лекарств
     */
    public function createMovementDocument(
        string $documentType,
        string $documentNumber,
        \DateTimeImmutable $documentDate,
        int $warehouseId,
        int $tenantId,
        int $createdBy,
        array $items,
        ?string $counterpartyId = null,
        ?string $notes = null
    ): string {
        if (!isset(self::DOCUMENT_TYPES[$documentType])) {
            throw new \InvalidArgumentException("Invalid document type: {$documentType}");
        }

        $documentId = (string) Str::uuid();

        return $this->db->transaction(function () use (
            $documentType,
            $documentNumber,
            $documentDate,
            $warehouseId,
            $tenantId,
            $createdBy,
            $items,
            $counterpartyId,
            $notes,
            $documentId
        ) {
            // Создание заголовка документа
            $this->db->table('pharmaceutical_documents')->insert([
                'id' => $documentId,
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'document_date' => $documentDate->format('Y-m-d H:i:s'),
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'counterparty_id' => $counterpartyId,
                'status' => 'draft',
                'total_quantity' => array_sum(array_column($items, 'quantity')),
                'total_amount' => array_sum(array_column($items, 'amount')),
                'notes' => $notes,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            // Создание позиций документа
            foreach ($items as $item) {
                $this->db->table('pharmaceutical_document_items')->insert([
                    'id' => (string) Str::uuid(),
                    'document_id' => $documentId,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'amount' => $item['amount'] ?? 0,
                    'serial_number' => $item['serial_number'] ?? null,
                    'marking_code' => $item['marking_code'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            $this->logger->info('Pharmaceutical document created', [
                'document_id' => $documentId,
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'warehouse_id' => $warehouseId,
            ]);

            return $documentId;
        });
    }

    /**
     * Подтверждение документа
     */
    public function confirmDocument(string $documentId, int $confirmedBy): void
    {
        $document = $this->db->table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->lockForUpdate()
            ->first();

        if (!$document) {
            throw new \RuntimeException("Document not found: {$documentId}");
        }

        if ($document->status !== 'draft') {
            throw new \RuntimeException("Document is not in draft status: {$document->status}");
        }

        $this->db->table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $confirmedBy,
            ]);

        $this->logger->info('Pharmaceutical document confirmed', [
            'document_id' => $documentId,
            'confirmed_by' => $confirmedBy,
        ]);
    }

    /**
     * Отмена документа
     */
    public function cancelDocument(string $documentId, string $reason, int $cancelledBy): void
    {
        $document = $this->db->table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->lockForUpdate()
            ->first();

        if (!$document) {
            throw new \RuntimeException("Document not found: {$documentId}");
        }

        if ($document->status === 'cancelled') {
            throw new \RuntimeException('Document is already cancelled');
        }

        $this->db->table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
            ]);

        $this->logger->warning('Pharmaceutical document cancelled', [
            'document_id' => $documentId,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }

    /**
     * Получение документа
     */
    public function getDocument(string $documentId): array
    {
        $document = $this->db->table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->first();

        if (!$document) {
            throw new \RuntimeException("Document not found: {$documentId}");
        }

        $items = $this->db->table('pharmaceutical_document_items')
            ->where('document_id', $documentId)
            ->get()
            ->toArray();

        return [
            'document' => (array) $document,
            'items' => $items,
            'type_name' => self::DOCUMENT_TYPES[$document->document_type] ?? $document->document_type,
        ];
    }

    /**
     * Получение списка документов
     */
    public function getDocuments(
        ?int $warehouseId = null,
        ?string $documentType = null,
        ?string $status = null,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null
    ): array {
        $query = $this->db->table('pharmaceutical_documents')
            ->orderBy('document_date', 'desc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('document_date', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('document_date', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        return $query->get()->map(function ($doc) {
            return array_merge((array) $doc, [
                'type_name' => self::DOCUMENT_TYPES[$doc->document_type] ?? $doc->document_type,
            ]);
        })->toArray();
    }

    /**
     * Генерация следующего номера документа
     */
    public function generateNextDocumentNumber(string $documentType, int $tenantId): string
    {
        $prefix = match ($documentType) {
            'receipt' => 'ПРХ',
            'shipment' => 'РСХ',
            'return' => 'ВЗВ',
            'write_off' => 'СПС',
            'transfer' => 'ПРМ',
            'adjustment' => 'КРР',
            default => 'DOC',
        };

        $date = now()->format('Ymd');

        $lastNumber = $this->db->table('pharmaceutical_documents')
            ->where('document_type', $documentType)
            ->where('tenant_id', $tenantId)
            ->where('document_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('document_number', 'desc')
            ->value('document_number');

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf("%s-%s-%06d", $prefix, $date, $sequence);
    }

    /**
     * Архивирование старых документов
     */
    public function archiveOldDocuments(int $days = 365): int
    {
        $cutoffDate = now()->subDays($days);

        $count = $this->db->table('pharmaceutical_documents')
            ->where('status', 'confirmed')
            ->where('document_date', '<', $cutoffDate)
            ->where('archived_at', null)
            ->update([
                'archived_at' => now(),
            ]);

        $this->logger->info('Pharmaceutical documents archived', [
            'count' => $count,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return $count;
    }
}
