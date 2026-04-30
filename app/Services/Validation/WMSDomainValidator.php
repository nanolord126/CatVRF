<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * WMS Domain Validator - Input validation at Domain level
 *
 * Provides strict validation for WMS operations to ensure data integrity
 * and compliance with Russian federal laws (152-ФЗ, ФЗ-323, ФЗ-61).
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WMSDomainValidator
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Validate stock movement data
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateStockMovement(array $data): void
    {
        $validator = Validator::make($data, [
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'type' => 'required|string|in:in,out,transfer,adjustment,return,damage,expiration',
            'quantity' => 'required|integer|min:-999999|max:999999',
            'reason' => 'nullable|string|max:1000',
            'source_type' => 'nullable|string|max:100',
            'source_id' => 'nullable|integer',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'created_by' => 'required|integer|exists:users,id',
            'correlation_id' => 'nullable|string|uuid',
        ], [
            'type.in' => 'Invalid movement type. Must be one of: in, out, transfer, adjustment, return, damage, expiration',
            'quantity.min' => 'Quantity cannot be less than -999999',
            'quantity.max' => 'Quantity cannot exceed 999999',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('Stock movement validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }

        // Business rule validation
        $this->validateMovementBusinessRules($data);
    }

    /**
     * Validate inventory item data
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateInventoryItem(array $data): void
    {
        $validator = Validator::make($data, [
            'product_id' => 'required|integer|exists:products,id',
            'sku' => 'required|string|max:100|unique:inventory_items,sku,NULL,id,tenant_id,' . ($data['tenant_id'] ?? 0),
            'name' => 'required|string|max:500',
            'current_stock' => 'required|integer|min:0|max:999999999',
            'hold_stock' => 'nullable|integer|min:0|max:999999999',
            'min_stock_threshold' => 'nullable|integer|min:0|max:999999999',
            'max_stock_threshold' => 'nullable|integer|min:0|max:999999999',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
            'unit_cost' => 'nullable|numeric|min:0|max:999999999.99',
            'abc_class' => 'nullable|string|in:A,B,C',
            'requires_marking' => 'nullable|boolean',
            'supplier_name' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
        ], [
            'sku.unique' => 'SKU must be unique within tenant',
            'current_stock.max' => 'Stock quantity exceeds maximum allowed',
            'abc_class.in' => 'ABC class must be one of: A, B, C',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('Inventory item validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }

        // Business rule validation
        $this->validateInventoryBusinessRules($data);
    }

    /**
     * Validate inventory batch data (ФЗ-61 compliance)
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateInventoryBatch(array $data): void
    {
        $validator = Validator::make($data, [
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date|after:today',
            'manufacture_date' => 'nullable|date|before:expiry_date',
            'initial_quantity' => 'required|integer|min:1|max:999999999',
            'current_quantity' => 'nullable|integer|min:0|max:999999999',
            'serial_number' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:quarantine,available,on_hold,recalled,expired,depleted',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'created_by' => 'required|integer|exists:users,id',
        ], [
            'expiry_date.after' => 'Expiry date must be in the future (ФЗ-61 compliance)',
            'manufacture_date.before' => 'Manufacture date must be before expiry date',
            'initial_quantity.min' => 'Initial quantity must be at least 1',
            'status.in' => 'Invalid batch status',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('Inventory batch validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }

        // ФЗ-61 compliance: expiry date validation for medicines
        $this->validateBatchExpiryCompliance($data);
    }

    /**
     * Validate cycle count plan data
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateCycleCountPlan(array $data): void
    {
        $validator = Validator::make($data, [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'count_type' => 'required|string|in:full,partial,abc,spot_check',
            'status' => 'nullable|string|in:planned,in_progress,submitted,approved,rejected',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'created_by' => 'required|integer|exists:users,id',
        ], [
            'count_type.in' => 'Invalid count type. Must be one of: full, partial, abc, spot_check',
            'scheduled_date.after_or_equal' => 'Scheduled date cannot be in the past',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('Cycle count plan validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }
    }

    /**
     * Validate batch recall data
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateBatchRecall(array $data): void
    {
        $validator = Validator::make($data, [
            'batch_id' => 'required|string|exists:inventory_batches,id',
            'recall_type' => 'required|string|in:voluntary,mandatory',
            'reason' => 'required|string|min:10|max:5000',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'created_by' => 'required|integer|exists:users,id',
        ], [
            'recall_type.in' => 'Recall type must be either voluntary or mandatory',
            'reason.min' => 'Recall reason must be at least 10 characters',
            'reason.max' => 'Recall reason cannot exceed 5000 characters',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('Batch recall validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }
    }

    /**
     * Validate license data (ФЗ-323, ФЗ-61 compliance)
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function validateLicense(array $data): void
    {
        $validator = Validator::make($data, [
            'license_number' => 'required|string|max:100',
            'license_type' => 'required|string|in:pharmaceutical,controlled_substances,medical_devices',
            'issued_date' => 'required|date|before_or_equal:today',
            'expiry_date' => 'required|date|after:issued_date',
            'issued_by' => 'required|string|max:500',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
        ], [
            'license_type.in' => 'Invalid license type',
            'issued_date.before_or_equal' => 'Issue date cannot be in the future',
            'expiry_date.after' => 'Expiry date must be after issue date',
        ]);

        if ($validator->fails()) {
            $this->logger->warning('License validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $this->sanitizeForLog($data),
            ]);

            throw new ValidationException($validator);
        }
    }

    /**
     * Validate movement business rules
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    private function validateMovementBusinessRules(array $data): void
    {
        // Check if movement would result in negative stock
        if (in_array($data['type'], ['out', 'transfer', 'damage', 'expiration'], true)) {
            $absoluteQuantity = abs($data['quantity']);
            if ($absoluteQuantity > 999999) {
                throw ValidationException::withMessages([
                    'quantity' => ['Movement quantity exceeds maximum allowed limit'],
                ]);
            }
        }

        // Validate source information for certain movement types
        if (in_array($data['type'], ['transfer', 'return'], true)) {
            if (empty($data['source_type']) || empty($data['source_id'])) {
                throw ValidationException::withMessages([
                    'source_type' => ['Source type and ID are required for this movement type'],
                    'source_id' => ['Source type and ID are required for this movement type'],
                ]);
            }
        }
    }

    /**
     * Validate inventory business rules
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    private function validateInventoryBusinessRules(array $data): void
    {
        // Validate stock thresholds
        if (isset($data['min_stock_threshold']) && isset($data['max_stock_threshold'])) {
            if ($data['min_stock_threshold'] > $data['max_stock_threshold']) {
                throw ValidationException::withMessages([
                    'min_stock_threshold' => ['Minimum threshold cannot exceed maximum threshold'],
                    'max_stock_threshold' => ['Maximum threshold cannot be less than minimum threshold'],
                ]);
            }
        }

        // Validate hold stock against current stock
        if (isset($data['hold_stock']) && isset($data['current_stock'])) {
            if ($data['hold_stock'] > $data['current_stock']) {
                throw ValidationException::withMessages([
                    'hold_stock' => ['Hold stock cannot exceed current stock'],
                ]);
            }
        }
    }

    /**
     * Validate batch expiry compliance (ФЗ-61)
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    private function validateBatchExpiryCompliance(array $data): void
    {
        $expiryDate = \Illuminate\Support\Carbon::parse($data['expiry_date']);
        $today = \Illuminate\Support\Carbon::today();

        // For pharmaceutical products, block if expiry is less than 30 days
        if (isset($data['product_id'])) {
            $daysUntilExpiry = $today->diffInDays($expiryDate, false);
            
            if ($daysUntilExpiry < 30) {
                $this->logger->warning('Batch expiry violates ФЗ-61 requirements', [
                    'expiry_date' => $data['expiry_date'],
                    'days_until_expiry' => $daysUntilExpiry,
                ]);

                throw ValidationException::withMessages([
                    'expiry_date' => ['Batch expires in less than 30 days, violates ФЗ-61 requirements for pharmaceutical products'],
                ]);
            }
        }
    }

    /**
     * Sanitize data for logging (remove PII)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeForLog(array $data): array
    {
        $piiFields = ['supplier_name', 'performed_by', 'approved_by', 'contact_person'];
        
        foreach ($piiFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = str_repeat('*', min(strlen($data[$field]), 8));
            }
        }

        return $data;
    }
}
