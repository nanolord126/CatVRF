<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Illuminate\Support\Facades\Validator;

/**
 * WMS Input Validator
 *
 * Validates input data for WMS operations at Domain level:
 * - Inventory operations
 * - Warehouse operations
 * - Batch operations
 * - License operations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WMSInputValidator
{
    public function validateInventoryMovement(array $data): array
    {
        $validator = Validator::make($data, [
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'source_type' => 'required|string|max:100',
            'source_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid inventory movement data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }

    public function validateWarehouseCreation(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'lat' => 'nullable|numeric|between:-90,90',
            'lon' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'warehouse_type' => 'required|in:general,cold_chain,freezer,narcotic,psychotropic',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid warehouse data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }

    public function validateLicenseRegistration(array $data): array
    {
        $validator = Validator::make($data, [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'license_type' => 'required|in:pharmacy,medicine_storage,narcotic,psychotropic,poisonous',
            'license_number' => 'required|string|max:100',
            'issued_date' => 'required|date|before:today',
            'expiry_date' => 'required|date|after:today',
            'issued_by' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid license data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }

    public function validateBatchCreation(array $data): array
    {
        $validator = Validator::make($data, [
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date|after:today',
            'quantity' => 'required|integer|min:1',
            'serial_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid batch data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }

    public function validateCycleCount(array $data): array
    {
        $validator = Validator::make($data, [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'count_type' => 'required|in:A_items,B_items,C_items,high_value,random',
            'scheduled_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid cycle count data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }

    public function validateStorageZone(array $data): array
    {
        $validator = Validator::make($data, [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'category' => 'required|in:general,cold_chain,freezer,narcotic,psychotropic,poisonous,flammable',
            'min_temperature' => 'required|numeric',
            'max_temperature' => 'required|numeric|gt:min_temperature',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid storage zone data: '.json_encode($validator->errors()));
        }

        return $validator->validated();
    }
}
