<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * License Management Service
 *
 * Manages licenses for regulated operations (ФЗ-323, ФЗ-61):
 * - Pharmaceutical license validation
 * - Storage category control
 * - License expiration monitoring
 * - Compliance enforcement
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class LicenseManagementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Register warehouse license
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $licenseType  License type (pharmaceutical, medical, controlled_substances)
     * @param  string  $licenseNumber  License number
     * @param  string  $issuedBy  Issuing authority
     * @param  string  $validUntil  Valid until date
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User registering
     * @return string License record ID
     */
    public function registerLicense(
        int $warehouseId,
        string $licenseType,
        string $licenseNumber,
        string $issuedBy,
        string $validUntil,
        int $tenantId,
        int $userId
    ): string {
        $licenseId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('warehouse_licenses')->insert([
            'id' => $licenseId,
            'warehouse_id' => $warehouseId,
            'license_type' => $licenseType,
            'license_number' => $licenseNumber,
            'issued_by' => $issuedBy,
            'valid_from' => now(),
            'valid_until' => $validUntil,
            'status' => 'active',
            'tenant_id' => $tenantId,
            'registered_by' => $userId,
            'created_at' => now(),
        ]);

        $this->logAction(
            action: 'warehouse_license_registered',
            entityType: 'WarehouseLicense',
            entityId: $licenseId,
            context: [
                'warehouse_id' => $warehouseId,
                'license_type' => $licenseType,
                'license_number' => $licenseNumber,
                'valid_until' => $validUntil,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->cache->tags(['licenses', "warehouse:{$warehouseId}"])->flush();

        return $licenseId;
    }

    /**
     * Validate license by type
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $licenseType  License type
     * @return array Validation result
     */
    public function validateLicenseByType(int $warehouseId, string $licenseType): array
    {
        $cacheKey = "license:{$warehouseId}:{$licenseType}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->where('license_type', $licenseType)
            ->where('status', 'active')
            ->orderBy('valid_until', 'desc')
            ->first();

        if (! $license) {
            $result = [
                'valid' => false,
                'warehouse_id' => $warehouseId,
                'license_type' => $licenseType,
                'reason' => 'No active license found',
            ];
        } else {
            $validUntil = \Carbon\Carbon::parse($license->valid_until);
            $daysUntilExpiry = now()->diffInDays($validUntil, false);

            if ($daysUntilExpiry < 0) {
                $result = [
                    'valid' => false,
                    'warehouse_id' => $warehouseId,
                    'license_type' => $licenseType,
                    'reason' => 'License expired',
                    'expired_days' => abs($daysUntilExpiry),
                ];

                $this->db->table('warehouse_licenses')
                    ->where('id', $license->id)
                    ->update(['status' => 'expired']);
            } elseif ($daysUntilExpiry <= 30) {
                $result = [
                    'valid' => true,
                    'warehouse_id' => $warehouseId,
                    'license_type' => $licenseType,
                    'license_number' => $license->license_number,
                    'valid_until' => $license->valid_until,
                    'days_until_expiry' => $daysUntilExpiry,
                    'warning' => 'License expiring soon',
                ];

                $this->logger->warning('License expiring soon', [
                    'warehouse_id' => $warehouseId,
                    'license_type' => $licenseType,
                    'days_until_expiry' => $daysUntilExpiry,
                ]);
            } else {
                $result = [
                    'valid' => true,
                    'warehouse_id' => $warehouseId,
                    'license_type' => $licenseType,
                    'license_number' => $license->license_number,
                    'valid_until' => $license->valid_until,
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            }
        }

        $this->cache->put($cacheKey, $result, now()->addHours(1));

        return $result;
    }

    /**
     * Validate storage category for product
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $productId  Product ID
     * @return array Validation result
     */
    public function validateStorageCategory(int $warehouseId, int $productId): array
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (! $product) {
            throw new \RuntimeException("Product not found: {$productId}");
        }

        $category = $product->category ?? '';
        $requiredLicense = $this->getRequiredLicenseForCategory($category);

        if (! $requiredLicense) {
            return [
                'valid' => true,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'category' => $category,
                'reason' => 'No special license required',
            ];
        }

        $licenseValidation = $this->validateLicenseByType($warehouseId, $requiredLicense);

        if (! $licenseValidation['valid']) {
            return [
                'valid' => false,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'category' => $category,
                'required_license' => $requiredLicense,
                'reason' => $licenseValidation['reason'],
            ];
        }

        return [
            'valid' => true,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'category' => $category,
            'required_license' => $requiredLicense,
            'license_number' => $licenseValidation['license_number'],
        ];
    }

    /**
     * Get required license for product category
     *
     * @param  string  $category  Product category
     * @return string|null Required license type
     */
    private function getRequiredLicenseForCategory(string $category): ?string
    {
        $category = strtolower($category);

        $pharmaceuticalCategories = [
            'pharmaceutical', 'medication', 'medicine', 'drug',
            'vaccine', 'antibiotic', 'insulin', 'prescription',
        ];

        $medicalCategories = [
            'medical', 'healthcare', 'medical_device', 'equipment',
        ];

        $controlledSubstanceCategories = [
            'controlled_substance', 'narcotic', 'psychotropic',
        ];

        foreach ($pharmaceuticalCategories as $pharmaCategory) {
            if (str_contains($category, $pharmaCategory)) {
                return 'pharmaceutical';
            }
        }

        foreach ($medicalCategories as $medicalCategory) {
            if (str_contains($category, $medicalCategory)) {
                return 'medical';
            }
        }

        foreach ($controlledSubstanceCategories as $controlledCategory) {
            if (str_contains($category, $controlledCategory)) {
                return 'controlled_substances';
            }
        }

        return null;
    }

    /**
     * Check if warehouse can store product
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $productId  Product ID
     * @return bool Can store
     */
    public function canStoreProduct(int $warehouseId, int $productId): bool
    {
        $validation = $this->validateStorageCategory($warehouseId, $productId);

        return $validation['valid'];
    }

    /**
     * Get expiring licenses
     *
     * @param  int  $days  Days threshold
     * @param  int  $tenantId  Tenant ID
     * @return array Expiring licenses
     */
    public function getExpiringLicenses(int $days = 30, int $tenantId = 0): array
    {
        $query = $this->db->table('warehouse_licenses as wl')
            ->join('warehouses as w', 'wl.warehouse_id', '=', 'w.id')
            ->where('wl.status', 'active')
            ->where('wl.valid_until', '<=', now()->addDays($days))
            ->where('wl.valid_until', '>', now())
            ->select('wl.*', 'w.name as warehouse_name');

        if ($tenantId > 0) {
            $query->where('wl.tenant_id', $tenantId);
        }

        $licenses = $query->get();

        return [
            'threshold_days' => $days,
            'total_licenses' => $licenses->count(),
            'licenses' => $licenses->map(function ($license) {
                $daysUntilExpiry = now()->diffInDays($license->valid_until, false);
                return [
                    'license_id' => $license->id,
                    'warehouse_id' => $license->warehouse_id,
                    'warehouse_name' => $license->warehouse_name,
                    'license_type' => $license->license_type,
                    'license_number' => $license->license_number,
                    'valid_until' => $license->valid_until,
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            })->toArray(),
        ];
    }

    /**
     * Revoke license
     *
     * @param  string  $licenseId  License ID
     * @param  string  $reason  Revocation reason
     * @param  int  $userId  User revoking
     * @return bool
     */
    public function revokeLicense(string $licenseId, string $reason, int $userId): bool
    {
        $license = $this->db->table('warehouse_licenses')
            ->where('id', $licenseId)
            ->first();

        if (! $license) {
            throw new \RuntimeException("License not found: {$licenseId}");
        }

        $this->db->table('warehouse_licenses')
            ->where('id', $licenseId)
            ->update([
                'status' => 'revoked',
                'revocation_reason' => $reason,
                'revoked_at' => now(),
                'revoked_by' => $userId,
            ]);

        $this->logAction(
            action: 'warehouse_license_revoked',
            entityType: 'WarehouseLicense',
            entityId: $licenseId,
            context: [
                'license_type' => $license->license_type,
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $license->tenant_id
        );

        $this->cache->tags(['licenses', "warehouse:{$license->warehouse_id}"])->flush();

        return true;
    }

    /**
     * Get warehouse licenses
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Licenses
     */
    public function getWarehouseLicenses(int $warehouseId): array
    {
        $licenses = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('valid_until', 'desc')
            ->get()
            ->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'total_licenses' => count($licenses),
            'active_licenses' => count(array_filter($licenses, fn ($l) => $l['status'] === 'active')),
            'licenses' => $licenses,
        ];
    }

    /**
     * Check compliance for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Compliance status
     */
    public function checkWarehouseCompliance(int $warehouseId): array
    {
        $warehouse = $this->db->table('warehouses')
            ->where('id', $warehouseId)
            ->first();

        if (! $warehouse) {
            throw new \RuntimeException("Warehouse not found: {$warehouseId}");
        }

        $licenseTypes = ['pharmaceutical', 'medical', 'controlled_substances'];
        $validLicenses = [];
        $missingLicenses = [];
        $expiredLicenses = [];
        $expiringLicenses = [];

        foreach ($licenseTypes as $licenseType) {
            $validation = $this->validateLicenseByType($warehouseId, $licenseType);

            if ($validation['valid']) {
                $validLicenses[] = $licenseType;

                if (isset($validation['warning'])) {
                    $expiringLicenses[] = [
                        'license_type' => $licenseType,
                        'days_until_expiry' => $validation['days_until_expiry'],
                    ];
                }
            } elseif (str_contains($validation['reason'], 'expired')) {
                $expiredLicenses[] = $licenseType;
            } else {
                $missingLicenses[] = $licenseType;
            }
        }

        $isCompliant = empty($missingLicenses) && empty($expiredLicenses);

        return [
            'warehouse_id' => $warehouseId,
            'warehouse_type' => $warehouse->type,
            'is_compliant' => $isCompliant,
            'valid_licenses' => $validLicenses,
            'missing_licenses' => $missingLicenses,
            'expired_licenses' => $expiredLicenses,
            'expiring_licenses' => $expiringLicenses,
            'compliance_percentage' => count($licenseTypes) > 0
                ? (count($validLicenses) / count($licenseTypes)) * 100
                : 0,
        ];
    }

    /**
     * Renew license
     *
     * @param  string  $licenseId  License ID
     * @param  string  $newValidUntil  New valid until date
     * @param  int  $userId  User renewing
     * @return bool
     */
    public function renewLicense(string $licenseId, string $newValidUntil, int $userId): bool
    {
        $license = $this->db->table('warehouse_licenses')
            ->where('id', $licenseId)
            ->first();

        if (! $license) {
            throw new \RuntimeException("License not found: {$licenseId}");
        }

        $this->db->table('warehouse_licenses')
            ->where('id', $licenseId)
            ->update([
                'valid_until' => $newValidUntil,
                'status' => 'active',
                'renewed_at' => now(),
                'renewed_by' => $userId,
            ]);

        $this->logAction(
            action: 'warehouse_license_renewed',
            entityType: 'WarehouseLicense',
            entityId: $licenseId,
            context: [
                'license_type' => $license->license_type,
                'new_valid_until' => $newValidUntil,
            ],
            userId: $userId,
            tenantId: $license->tenant_id
        );

        $this->cache->tags(['licenses', "warehouse:{$license->warehouse_id}"])->flush();

        return true;
    }
}
