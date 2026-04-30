<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Barcode Scanning Service
 *
 * Handles barcode scanning and validation for WMS:
 * - EAN-13, Code-128, QR code support
 * - Barcode validation and formatting
 * - Product/batch lookup by barcode
 * - Chestny ZNAK marking integration (ФЗ-61)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BarcodeScanningService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Lookup inventory item by barcode
     *
     * @param  string  $barcode  Barcode
     * @param  int  $warehouseId  Warehouse ID
     * @return array Item data
     */
    public function lookupByBarcode(string $barcode, int $warehouseId): array
    {
        $normalizedBarcode = $this->normalizeBarcode($barcode);

        $item = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->where(function ($query) use ($normalizedBarcode) {
                $query->where('sku', $normalizedBarcode)
                    ->orWhere('barcode', $normalizedBarcode)
                    ->orWhere('sku', 'LIKE', "%{$normalizedBarcode}%");
            })
            ->first();

        if (! $item) {
            return [
                'found' => false,
                'barcode' => $barcode,
                'message' => 'Item not found',
            ];
        }

        return [
            'found' => true,
            'barcode' => $barcode,
            'item_id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'current_stock' => $item->current_stock,
            'unit_cost' => $item->unit_cost,
        ];
    }

    /**
     * Lookup batch by serial number/barcode
     *
     * @param  string  $code  Serial number or barcode
     * @param  int  $warehouseId  Warehouse ID
     * @return array Batch data
     */
    public function lookupBatchByCode(string $code, int $warehouseId): array
    {
        $batch = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where(function ($query) use ($code) {
                $query->where('batch_number', $code)
                    ->orWhere('serial_number', $code);
            })
            ->first();

        if (! $batch) {
            return [
                'found' => false,
                'code' => $code,
                'message' => 'Batch not found',
            ];
        }

        return [
            'found' => true,
            'code' => $code,
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date,
            'current_quantity' => $batch->current_quantity,
            'status' => $batch->status,
        ];
    }

    /**
     * Validate barcode format
     *
     * @param  string  $barcode  Barcode
     * @return array Validation result
     */
    public function validateBarcode(string $barcode): array
    {
        $normalized = $this->normalizeBarcode($barcode);

        return [
            'original' => $barcode,
            'normalized' => $normalized,
            'valid' => $this->isValidBarcode($normalized),
            'type' => $this->detectBarcodeType($normalized),
        ];
    }

    /**
     * Validate Chestny ZNAK marking code (ФЗ-61)
     *
     * @param  string  $markingCode  Marking code
     * @return array Validation result
     */
    public function validateChestnyZnakCode(string $markingCode): array
    {
        $normalized = $this->normalizeBarcode($markingCode);

        $isValid = $this->isValidChestnyZnakFormat($normalized);

        if (! $isValid) {
            return [
                'valid' => false,
                'marking_code' => $markingCode,
                'message' => 'Invalid Chestny ZNAK format',
            ];
        }

        $parts = $this->parseChestnyZnakCode($normalized);

        return [
            'valid' => true,
            'marking_code' => $markingCode,
            'parsed' => $parts,
        ];
    }

    /**
     * Process scanned barcode for stock operation
     *
     * @param  string  $barcode  Barcode
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $operation  Operation type (in, out, transfer)
     * @param  int  $quantity  Quantity
     * @return array Operation result
     */
    public function processScannedBarcode(
        string $barcode,
        int $warehouseId,
        string $operation,
        int $quantity = 1
    ): array {
        $validation = $this->validateBarcode($barcode);

        if (! $validation['valid']) {
            return [
                'success' => false,
                'barcode' => $barcode,
                'message' => 'Invalid barcode format',
                'validation' => $validation,
            ];
        }

        $item = $this->lookupByBarcode($barcode, $warehouseId);

        if (! $item['found']) {
            return [
                'success' => false,
                'barcode' => $barcode,
                'message' => 'Item not found',
            ];
        }

        if ($operation === 'out' && $item['current_stock'] < $quantity) {
            return [
                'success' => false,
                'barcode' => $barcode,
                'item_id' => $item['item_id'],
                'message' => 'Insufficient stock',
                'available' => $item['current_stock'],
                'requested' => $quantity,
            ];
        }

        return [
            'success' => true,
            'barcode' => $barcode,
            'item' => $item,
            'operation' => $operation,
            'quantity' => $quantity,
        ];
    }

    /**
     * Generate barcode for item
     *
     * @param  int  $itemId  Item ID
     * @param  string  $type  Barcode type (EAN13, CODE128)
     * @return string Generated barcode
     */
    public function generateBarcode(int $itemId, string $type = 'CODE128'): string
    {
        return match ($type) {
            'EAN13' => $this->generateEAN13($itemId),
            'CODE128' => $this->generateCode128($itemId),
            default => "ITEM{$itemId}",
        };
    }

    /**
     * Normalize barcode (remove spaces, convert to uppercase)
     *
     * @param  string  $barcode  Barcode
     * @return string Normalized barcode
     */
    private function normalizeBarcode(string $barcode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $barcode));
    }

    /**
     * Check if barcode is valid format
     *
     * @param  string  $barcode  Barcode
     * @return bool Valid
     */
    private function isValidBarcode(string $barcode): bool
    {
        $length = strlen($barcode);

        if ($length === 13) {
            return $this->validateEAN13CheckDigit($barcode);
        }

        if ($length >= 8 && $length <= 20) {
            return true;
        }

        return false;
    }

    /**
     * Detect barcode type
     *
     * @param  string  $barcode  Barcode
     * @return string Barcode type
     */
    private function detectBarcodeType(string $barcode): string
    {
        $length = strlen($barcode);

        if ($length === 13) {
            return 'EAN13';
        }

        if ($length === 12) {
            return 'UPC-A';
        }

        if ($length === 8) {
            return 'EAN8';
        }

        if (ctype_alnum($barcode)) {
            return 'CODE128';
        }

        return 'UNKNOWN';
    }

    /**
     * Validate EAN-13 check digit
     *
     * @param  string  $barcode  Barcode
     * @return bool Valid
     */
    private function validateEAN13CheckDigit(string $barcode): bool
    {
        if (strlen($barcode) !== 13 || ! ctype_digit($barcode)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $barcode[12];
    }

    /**
     * Validate Chestny ZNAK format (ФЗ-61)
     *
     * @param  string  $code  Marking code
     * @return bool Valid
     */
    private function isValidChestnyZnakFormat(string $code): bool
    {
        $length = strlen($code);

        if ($length < 20 || $length > 50) {
            return false;
        }

        if (! ctype_alnum($code)) {
            return false;
        }

        return true;
    }

    /**
     * Parse Chestny ZNAK code
     *
     * @param  string  $code  Marking code
     * @return array Parsed components
     */
    private function parseChestnyZnakCode(string $code): array
    {
        return [
            'gtin' => substr($code, 0, 14),
            'serial' => substr($code, 14, 13),
            'verification_code' => substr($code, 27, 4),
        ];
    }

    /**
     * Generate EAN-13 barcode
     *
     * @param  int  $itemId  Item ID
     * @return string EAN-13 barcode
     */
    private function generateEAN13(int $itemId): string
    {
        $base = str_pad((string) $itemId, 12, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $base . $checkDigit;
    }

    /**
     * Generate Code-128 barcode
     *
     * @param  int  $itemId  Item ID
     * @return string Code-128 barcode
     */
    private function generateCode128(int $itemId): string
    {
        return 'ITEM' . str_pad((string) $itemId, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Bulk barcode lookup
     *
     * @param  array<string>  $barcodes  Barcodes
     * @param  int  $warehouseId  Warehouse ID
     * @return array Results
     */
    public function bulkLookup(array $barcodes, int $warehouseId): array
    {
        $results = [];
        $normalizedBarcodes = array_map([$this, 'normalizeBarcode'], $barcodes);

        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('sku', $normalizedBarcodes)
            ->orWhereIn('barcode', $normalizedBarcodes)
            ->get()
            ->keyBy('sku');

        foreach ($barcodes as $barcode) {
            $normalized = $this->normalizeBarcode($barcode);
            $item = $items->get($normalized) ?? $items->firstWhere('barcode', $normalized);

            $results[$barcode] = $item ? [
                'found' => true,
                'item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $item->current_stock,
            ] : [
                'found' => false,
                'message' => 'Item not found',
            ];
        }

        return $results;
    }
}
