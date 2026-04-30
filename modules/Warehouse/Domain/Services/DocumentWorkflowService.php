<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Modules\Warehouse\Domain\Entities\WarehouseDocument;
use Modules\Warehouse\Domain\Enums\DocumentTypeEnum;
use Modules\Warehouse\Domain\Enums\DocumentStatusEnum;
use Modules\Warehouse\Domain\Repositories\WarehouseDocumentRepositoryInterface;
use Modules\Warehouse\Domain\Events\DocumentCreated;
use Modules\Warehouse\Domain\Events\DocumentSubmittedForApproval;
use Modules\Warehouse\Domain\Events\DocumentApproved;
use Modules\Warehouse\Domain\Events\DocumentRejected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Document Workflow Service
 * 
 * Управляет жизненным циклом документов в системе документооборота:
 * - Создание документов
 * - Отправка на согласование
 * - Согласование/отклонение
 * - Архивирование
 * - Отмена
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class DocumentWorkflowService
{
    public function __construct(
        private WarehouseDocumentRepositoryInterface $documentRepository,
        private LoggerInterface $logger,
        private AuditTrailService $auditService
    ) {}

    /**
     * Создание документа
     */
    public function createDocument(
        string $warehouseId,
        string $documentNumber,
        DocumentTypeEnum $documentType,
        \DateTimeImmutable $documentDate,
        int $createdBy,
        int $tenantId,
        ?string $relatedOrderId = null,
        ?string $relatedMovementId = null,
        ?string $supplierId = null,
        array $items = [],
        float $totalAmount = 0.0,
        ?string $currency = null,
        ?string $notes = null,
        ?string $branchId = null
    ): WarehouseDocument {
        
        $document = WarehouseDocument::create(
            warehouseId: $warehouseId,
            documentNumber: $documentNumber,
            documentType: $documentType,
            documentDate: $documentDate,
            createdBy: $createdBy,
            tenantId: $tenantId,
            relatedOrderId: $relatedOrderId,
            relatedMovementId: $relatedMovementId,
            supplierId: $supplierId,
            items: $items,
            totalAmount: $totalAmount,
            currency: $currency,
            notes: $notes,
            branchId: $branchId
        );

        $this->documentRepository->save($document);

        // Логирование
        $this->auditService->logCreated('warehouse_document', $document->getId()->toString(), [
            'document_type' => $documentType->value,
            'document_number' => $documentNumber,
            'warehouse_id' => $warehouseId,
        ], $createdBy);

        // Событие
        Event::dispatch(new DocumentCreated($document));

        $this->logger->info('Document created', [
            'document_id' => $document->getId()->toString(),
            'document_type' => $documentType->value,
            'created_by' => $createdBy,
        ]);

        return $document;
    }

    /**
     * Отправка документа на согласование
     */
    public function submitForApproval(string $documentId, int $submittedBy): WarehouseDocument
    {
        $document = $this->documentRepository->findById(
            \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
        );

        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document = $document->submitForApproval();
        $this->documentRepository->save($document);

        // Логирование
        $this->auditService->logAction('document_submitted_for_approval', $documentId, [
            'document_type' => $document->getDocumentType()->value,
            'document_number' => $document->getDocumentNumber(),
        ], $submittedBy);

        // Событие
        Event::dispatch(new DocumentSubmittedForApproval($document));

        $this->logger->info('Document submitted for approval', [
            'document_id' => $documentId,
            'submitted_by' => $submittedBy,
        ]);

        return $document;
    }

    /**
     * Согласование документа
     */
    public function approveDocument(
        string $documentId,
        int $approvedBy,
        string $comment = null
    ): WarehouseDocument {
        
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findById(
                \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
            );

            if (!$document) {
                throw new \InvalidArgumentException('Document not found');
            }

            $document = $document->approve($approvedBy, $comment);
            $this->documentRepository->save($document);

            // Логирование
            $this->auditService->logAction('document_approved', $documentId, [
                'document_type' => $document->getDocumentType()->value,
                'document_number' => $document->getDocumentNumber(),
                'comment' => $comment,
            ], $approvedBy);

            // Событие
            Event::dispatch(new DocumentApproved($document));

            $this->logger->info('Document approved', [
                'document_id' => $documentId,
                'approved_by' => $approvedBy,
            ]);

            DB::commit();
            
            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to approve document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отклонение документа
     */
    public function rejectDocument(
        string $documentId,
        int $rejectedBy,
        string $reason
    ): WarehouseDocument {
        
        DB::beginTransaction();
        
        try {
            $document = $this->documentRepository->findById(
                \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
            );

            if (!$document) {
                throw new \InvalidArgumentException('Document not found');
            }

            $document = $document->reject($rejectedBy, $reason);
            $this->documentRepository->save($document);

            // Логирование
            $this->auditService->logAction('document_rejected', $documentId, [
                'document_type' => $document->getDocumentType()->value,
                'document_number' => $document->getDocumentNumber(),
                'reason' => $reason,
            ], $rejectedBy);

            // Событие
            Event::dispatch(new DocumentRejected($document));

            $this->logger->info('Document rejected', [
                'document_id' => $documentId,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            DB::commit();
            
            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to reject document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Архивирование документа
     */
    public function archiveDocument(string $documentId, int $archivedBy): WarehouseDocument
    {
        $document = $this->documentRepository->findById(
            \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
        );

        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document = $document->archive();
        $this->documentRepository->save($document);

        // Логирование
        $this->auditService->logAction('document_archived', $documentId, [
            'document_type' => $document->getDocumentType()->value,
            'document_number' => $document->getDocumentNumber(),
        ], $archivedBy);

        $this->logger->info('Document archived', [
            'document_id' => $documentId,
            'archived_by' => $archivedBy,
        ]);

        return $document;
    }

    /**
     * Отмена документа
     */
    public function cancelDocument(string $documentId, int $cancelledBy, string $reason): WarehouseDocument
    {
        $document = $this->documentRepository->findById(
            \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
        );

        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        // Можно отменить только черновик или документ на согласовании
        if (!in_array($document->getStatus(), [DocumentStatusEnum::DRAFT, DocumentStatusEnum::PENDING_APPROVAL])) {
            throw new \InvalidArgumentException('Document cannot be cancelled in current status');
        }

        // Создаем новую версию со статусом CANCELLED
        $cancelledDocument = new WarehouseDocument(
            id: $document->getId(),
            warehouseId: $document->getWarehouseId(),
            documentNumber: $document->getDocumentNumber(),
            documentType: $document->getDocumentType(),
            status: DocumentStatusEnum::CANCELLED,
            documentDate: $document->getDocumentDate(),
            relatedOrderId: $document->toArray()['related_order_id'],
            relatedMovementId: $document->toArray()['related_movement_id'],
            supplierId: $document->toArray()['supplier_id'],
            items: $document->getItems(),
            totalAmount: $document->getTotalAmount(),
            currency: $document->toArray()['currency'],
            notes: $reason,
            createdBy: $document->getCreatedBy(),
            tenantId: $document->getTenantId(),
            branchId: $document->toArray()['branch_id'],
            createdAt: $document->toArray()['created_at'] instanceof \DateTimeImmutable 
                ? $document->toArray()['created_at'] 
                : new \DateTimeImmutable(),
            approvedAt: null,
            approvedBy: null,
            approvalComment: $reason,
            updatedAt: new \DateTimeImmutable()
        );

        $this->documentRepository->save($cancelledDocument);

        // Логирование
        $this->auditService->logAction('document_cancelled', $documentId, [
            'document_type' => $document->getDocumentType()->value,
            'document_number' => $document->getDocumentNumber(),
            'reason' => $reason,
        ], $cancelledBy);

        $this->logger->info('Document cancelled', [
            'document_id' => $documentId,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        return $cancelledDocument;
    }

    /**
     * Получение документов требующих согласования
     */
    public function getPendingApprovalDocuments(int $tenantId): array
    {
        return $this->documentRepository->findByStatusAndTenant(
            DocumentStatusEnum::PENDING_APPROVAL,
            $tenantId
        );
    }

    /**
     * Проверка прав на согласование
     */
    public function canApproveDocument(string $documentId, int $userId): bool
    {
        $document = $this->documentRepository->findById(
            \Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId::fromString($documentId)
        );

        if (!$document) {
            return false;
        }

        // Проверяем статус
        if (!$document->canBeApproved()) {
            return false;
        }

        // Проверяем права пользователя
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->can('approve documents');
    }
}
