<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\DocumentTemplate;
use Modules\Supermarket\Domain\Models\DocumentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Traits\WithAuditLogging;
use Modules\Supermarket\Application\Services\TemperatureCRMIntegrationService;

/**
 * DocumentManagementService — Сервис управления документами для поставщиков
 * 
 * Отвечает за:
 * - Создание и обновление документов
 * - Валидацию форматов (PDF, JPEG, TIFF)
 * - Проверку сроков действия сертификатов
 * - Управление партиями и количеством
 * - Автоматическое закрытие сертификатов
 */
final class DocumentManagementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly \App\Services\AuditService $auditService,
        private readonly TemperatureCRMIntegrationService $crmIntegration,
        private readonly PaymentDelayValidationService $paymentDelayValidation
    ) {}

    /**
     * Создать новый документ
     */
    public function createDocument(array $data, int $supplierId, ?int $userId = null): Document
    {
        return DB::transaction(function () use ($data, $supplierId, $userId) {
            // Валидация данных
            $this->validateDocumentData($data);

            // Загрузка файла
            if (isset($data['file'])) {
                $filePath = $this->uploadFile($data['file'], $supplierId);
                $data['file_path'] = $filePath;
                $data['file_size'] = $data['file']->getSize();
                $data['file_mime_type'] = $data['file']->getMimeType();
                $data['format'] = $this->detectFileFormat($data['file']);
            }

            // Создание документа
            $document = new Document();
            $document->uuid = (string) \Illuminate\Support\Str::uuid();
            $document->seller_id = $supplierId;
            $document->tenant_id = $data['tenant_id'] ?? null;
            $document->document_type = $data['document_type'];
            $document->title = $data['title'];
            $document->description = $data['description'] ?? null;
            $document->file_path = $data['file_path'] ?? null;
            $document->file_name = $data['file_name'] ?? null;
            $document->file_size = $data['file_size'] ?? null;
            $document->file_mime_type = $data['file_mime_type'] ?? null;
            $document->format = $data['format'] ?? null;
            $document->file_hash = $data['file_hash'] ?? null;
            $document->valid_from = $data['valid_from'] ?? null;
            $document->valid_until = $data['valid_until'] ?? null;
            $document->document_number = $data['document_number'] ?? null;
            $document->issuing_authority = $data['issuing_authority'] ?? null;
            
            // B2B Certificate fields
            $document->batch_number = $data['batch_number'] ?? null;
            $document->barcode = $data['barcode'] ?? null;
            $document->batch_weight = $data['batch_weight'] ?? null;
            $document->delivered_quantity = 0;
            $document->remaining_quantity = $data['batch_weight'] ?? null;
            $document->is_closed = false;
            $document->product_id = $data['product_id'] ?? null;
            // Temperature monitoring fields
            $document->temperature_requirement_id = $data['temperature_requirement_id'] ?? null;
            $document->requires_temperature_compliance = $data['requires_temperature_compliance'] ?? false;
            $document->temperature_violation_count = $data['temperature_violation_count'] ?? 0;
            $document->temperature_compliance_status = $data['temperature_compliance_status'] ?? 'unknown';
            
            $document->template_id = $data['template_id'] ?? null;
            
            $document->status = Document::STATUS_DRAFT;
            $document->save();

            // Валидация сертификата
            if ($document->format) {
                $document->validateCertificate($userId);
            }

            // Записать в историю
            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_CREATED,
                $userId,
                DocumentHistory::USER_TYPE_SUPPLIER,
                null,
                $document->toArray(),
                'Document created'
            );

            $this->logCreated(
                'document',
                $document->id,
                [
                    'requires_temperature_compliance' => $document->requires_temperature_compliance,
                    'document_type' => $document->document_type,
                    'batch_number' => $document->batch_number,
              

            // Sync temperature compliance to CRM if required
            if ($document->requires_temperature_compliance) {
                dispatch(function () use ($document) {
                    $this->crmIntegration->syncDocumentTemperatureComplianceToCRM(
                        $document->id,
                        $document->temperature_compliance_status,
                        $document->temperature_violation_count
                    );
                });
            }  ],
                $userId
            );

            return $document;
        });
    }

    /**
     * Обновить документ
     */
    public function updateDocument(int $documentId, array $data, ?int $userId = null): Document
    {
        return DB::transaction(function () use ($documentId, $data, $userId) {
            $document = Document::findOrFail($documentId);
            
            $oldValues = $document->toArray();

            // Валидация данных
            $this->validateDocumentData($data, $document->id);

            // Загрузка нового файла
            if (isset($data['file'])) {
                // Удалить старый файл
                if ($document->file_path) {
                    Storage::disk('public')->delete($document->file_path);
                }
                
                $filePath = $this->uploadFile($data['file'], $document->seller_id);
                $data['file_path'] = $filePath;
                $data['file_size'] = $data['file']->getSize();
                $data['file_mime_type'] = $data['file']->getMimeType();
                $data['format'] = $this->detectFileFormat($data['file']);
            }

            // Обновление полей
            $document->fill($data);
            
            // Пересчитать remaining_quantity если изменился batch_weight или delivered_quantity
            if (isset($data['batch_weight']) || isset($data['delivered_quantity'])) {
                $document->calculateRemainingQuantity();
            }

            $document->save();

            // Sync temperature compliance to CRM if updated
            if (isset($data['temperature_compliance_status']) || isset($data['temperature_violation_count'])) {
                dispatch(function () use ($document) {
                    $this->crmIntegration->syncDocumentTemperatureComplianceToCRM(
                        $document->id,
                        $document->temperature_compliance_status,
                        $document->temperature_violation_count
                    );
                });
            }

            // Валидация сертификата
            if ($document->format) {
                $document->validateCertificate($userId);
            }

            // Записать в историю
            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_UPDATED,
                $userId,
                DocumentHistory::USER_TYPE_SUPPLIER,
                $oldValues,
                $document->toArray(),
                'Document updated'
            );

            $this->logUpdated(
                'document',
                $document->id,
                array_diff_assoc($document->toArray(), $oldValues),
                $userId
            );

            return $document;
        });
    }

    /**
     * Удалить документ
     */
    public function deleteDocument(int $documentId, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($documentId, $userId) {
            $document = Document::findOrFail($documentId);
            
            $document->delete();

            // Записать в историю
            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_DELETED,
                $userId,
                DocumentHistory::USER_TYPE_SUPPLIER,
                $document->toArray(),
                null,
                'Document deleted'
            );

            $this->logDeleted(
                'document',
                $document->id,
                ['document_type' => $document->document_type],
                $userId
            );

            return true;
        });
    }

    /**
     * Проверить и установить отсрочку платежа для документа
     * 
     * @param int $documentId ID документа
     * @param int $delayDays Запрошенное количество дней отсрочки
     * @param string $inn ИНН поставщика
     * @param int $supplierId ID поставщика
     * @param int $tenantId ID тенанта
     * @param int|null $userId ID пользователя
     * @param float|null $priceMarkupPercent Повышение цены в % (max 10%, recommended 3%)
     * @return array ['allowed' => bool, 'reason' => string|null, 'request_id' => int|null]
     */
    public function setPaymentDelay(
        int $documentId,
        int $delayDays,
        string $inn,
        int $supplierId,
        int $tenantId,
        ?int $userId = null,
        ?float $priceMarkupPercent = null
    ): array {
        // Check if delay is allowed
        $validation = $this->paymentDelayValidation->canInitiateDelay(
            $supplierId,
            $tenantId,
            $inn,
            $delayDays
        );

        if (!$validation['allowed']) {
            return $validation;
        }

        // Validate price markup
        if ($priceMarkupPercent !== null) {
            $markupValidation = $this->validatePriceMarkup($priceMarkupPercent);
            if (!$markupValidation['allowed']) {
                return $markupValidation;
            }
        }

        // Update document with delay
        $document = Document::findOrFail($documentId);
        $document->payment_delay_days = $delayDays;
        $document->payment_delay_until = now()->addDays($delayDays);
        
        // Apply price markup if provided
        if ($priceMarkupPercent !== null && $priceMarkupPercent > 0) {
            $originalPrice = $document->batch_weight ?? 0;
            $document->price_with_delay = $originalPrice * (1 + $priceMarkupPercent / 100);
            $document->price_markup_percent = $priceMarkupPercent;
        }
        
        $document->save();

        $this->logAction(
            'document',
            $documentId,
            'payment_delay_set',
            [
                'delay_days' => $delayDays,
                'delay_until' => $document->payment_delay_until->toIso8601String(),
                'inn' => $inn,
                'price_markup_percent' => $priceMarkupPercent,
                'price_with_delay' => $document->price_with_delay,
            ],
            $userId
        );

        return [
            'allowed' => true,
            'reason' => null,
            'delay_days' => $delayDays,
            'delay_until' => $document->payment_delay_until->toIso8601String(),
            'price_markup_percent' => $priceMarkupPercent,
            'price_with_delay' => $document->price_with_delay,
            'markup_recommendation' => $priceMarkupPercent > 3 ? 'Price markup exceeds recommended 3%' : null,
        ];
    }

    /**
     * Валидировать повышение цены
     * 
     * @param float $markupPercent Повышение в процентах
     * @return array
     */
    private function validatePriceMarkup(float $markupPercent): array
    {
        $maxAllowedMarkup = 10.0; // Maximum 10%
        $recommendedMarkup = 3.0; // Recommended 3%

        if ($markupPercent < 0) {
            return [
                'allowed' => false,
                'reason' => 'Price markup cannot be negative',
            ];
        }

        if ($markupPercent > $maxAllowedMarkup) {
            return [
                'allowed' => false,
                'reason' => "Price markup cannot exceed {$maxAllowedMarkup}%. Recommended: {$recommendedMarkup}%.",
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'warning' => $markupPercent > $recommendedMarkup 
                ? "Price markup of {$markupPercent}% exceeds recommended {$recommendedMarkup}%" 
                : null,
        ];
    }

    /**
     * Создать запрос на расширенную отсрочку платежа
     * 
     * @param int $documentId ID документа
     * @param int $delayDays Запрошенное количество дней
     * @param string $inn ИНН
     * @param int $supplierId ID поставщика
     * @param int $tenantId ID тенанта
     * @param int $requestedByUserId ID пользователя, делающего запрос
     * @param string|null $reason Причина
     * @return array
     */
    public function requestExtendedPaymentDelay(
        int $documentId,
        int $delayDays,
        string $inn,
        int $supplierId,
        int $tenantId,
        int $requestedByUserId,
        ?string $reason = null
    ): array {
        $request = $this->paymentDelayValidation->createDelayRequest(
            $supplierId,
            $tenantId,
            $inn,
            $delayDays,
            $requestedByUserId,
            $reason
        );

        $this->logAction(
            'document',
            $documentId,
            'payment_delay_requested',
            [
                'request_id' => $request->id,
                'delay_days' => $delayDays,
                'inn' => $inn,
                'exceeds_limit' => $request->exceedsSelfInitiatedLimit(),
            ],
            $requestedByUserId
        );

        return [
            'request_id' => $request->id,
            'status' => $request->status,
            'exceeds_limit' => $request->exceedsSelfInitiatedLimit(),
            'requires_approval' => $request->exceedsSelfInitiatedLimit(),
        ];
    }

    /**
     * Добавить количество к доставленному
     */
    public function addDeliveredQuantity(int $documentId, float $quantity, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($documentId, $quantity, $userId) {
            $document = Document::findOrFail($documentId);

            if (!$document->canAddQuantity($quantity)) {
                throw new \InvalidArgumentException(
                    "Cannot add quantity. Batch weight: {$document->batch_weight}, " .
                    "Delivered: {$document->delivered_quantity}, Requested: {$quantity}"
                );
            }

            $oldQuantity = $document->delivered_quantity;
            $result = $document->addDeliveredQuantity($quantity);

            // Записать в историю
            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_UPDATED,
                $userId,
                DocumentHistory::USER_TYPE_SYSTEM,
                ['delivered_quantity' => $oldQuantity],
                ['delivered_quantity' => $document->delivered_quantity],
                "Added {$quantity} to delivered quantity"
            );

            return $result;
        });
    }

    /**
     * Закрыть сертификат
     */
    public function closeCertificate(int $documentId, ?int $userId = null): bool
    {
        $document = Document::findOrFail($documentId);
        
        $result = $document->closeCertificate();

        // Записать в историю
        DocumentHistory::createLog(
            $document->id,
            DocumentHistory::ACTION_CLOSED,
            $userId,
            DocumentHistory::USER_TYPE_ADMIN,
            null,
            null,
            'Certificate closed manually'
        );

        return $result;
    }

    /**
     * Открыть сертификат
     */
    public function reopenCertificate(int $documentId, ?int $userId = null): bool
    {
        $document = Document::findOrFail($documentId);
        
        $result = $document->reopenCertificate();

        // Записать в историю
        DocumentHistory::createLog(
            $document->id,
            DocumentHistory::ACTION_RESTORED,
            $userId,
            DocumentHistory::USER_TYPE_ADMIN,
            null,
            null,
            'Certificate reopened'
        );

        return $result;
    }

    /**
     * Проверить истекающие сертификаты
     */
    public function checkExpiringCertificates(int $daysThreshold = 30): array
    {
        $expiringSoon = Document::where('status', Document::STATUS_PUBLISHED)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays($daysThreshold))
            ->where('valid_until', '>', now())
            ->get();

        $expired = Document::where('status', Document::STATUS_PUBLISHED)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->get();

        foreach ($expired as $document) {
            $document->status = Document::STATUS_EXPIRED;
            $document->save();

            DocumentHistory::createLog(
                $document->id,
                DocumentHistory::ACTION_EXPIRED,
                null,
                DocumentHistory::USER_TYPE_SYSTEM,
                null,
                null,
                'Certificate expired automatically'
            );
        }

        return [
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
    }

    /**
     * Валидация данных документа
     */
    private function validateDocumentData(array $data, ?int $excludeId = null): void
    {
        $rules = [
            'document_type' => 'required|string|in:certificate,license,contract,invoice,price_list,catalog,quality_cert,honest_mark,other',
            'title' => 'required|string|max:255',
            'file' => 'sometimes|file|mimes:pdf,jpeg,jpg,tif,tiff|max:10240', // 10MB max
            'valid_from' => 'sometimes|date',
            'valid_until' => 'sometimes|date|after:valid_from',
            'batch_number' => 'sometimes|string|max:255',
            'barcode' => 'sometimes|string|max:255',
            'batch_weight' => 'sometimes|numeric|min:0',
            'product_id' => 'sometimes|exists:products,id',
            'template_id' => 'sometimes|exists:document_templates,id',
        ];

        // Уникальность номера документа
        if (isset($data['document_number'])) {
            $rules['document_number'] = 'sometimes|string|max:255|unique:supermarket_documents,document_number';
            if ($excludeId) {
                $rules['document_number'] .= ',' . $excludeId;
            }
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    /**
     * Загрузить файл
     */
    private function uploadFile($file, int $supplierId): string
    {
        $path = "documents/supplier_{$supplierId}/" . \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($path, $file->getClientOriginalName(), 'public');
    }

    /**
     * Определить формат файла
     */
    private function detectFileFormat($file): ?string
    {
        $mimeType = $file->getMimeType();
        
        return match($mimeType) {
            'application/pdf' => Document::FORMAT_PDF,
            'image/jpeg', 'image/jpg' => Document::FORMAT_JPEG,
            'image/tiff', 'image/tif' => Document::FORMAT_TIFF,
            default => null,
        };
    }

    /**
     * Получить статистику по документам поставщика
     */
    public function getSupplierStats(int $supplierId): array
    {
        $total = Document::where('seller_id', $supplierId)->count();
        $active = Document::where('seller_id', $supplierId)
            ->where('status', Document::STATUS_PUBLISHED)
            ->count();
        $expired = Document::where('seller_id', $supplierId)
            ->where('status', Document::STATUS_EXPIRED)
            ->count();
        $closed = Document::where('seller_id', $supplierId)
            ->where('is_closed', true)
            ->count();

        $totalBatchWeight = Document::where('seller_id', $supplierId)
            ->whereNotNull('batch_weight')
            ->sum('batch_weight');

        $totalDelivered = Document::where('seller_id', $supplierId)
            ->whereNotNull('delivered_quantity')
            ->sum('delivered_quantity');

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'closed' => $closed,
            'total_batch_weight' => $totalBatchWeight,
            'total_delivered' => $totalDelivered,
            'total_remaining' => $totalBatchWeight - $totalDelivered,
        ];
    }
}
}
