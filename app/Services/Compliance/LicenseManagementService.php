<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * License Management Service - ФЗ-323 & ФЗ-61 Compliance
 *
 * Implements licensing requirements for:
 * - Medicine storage (ФЗ-323, ФЗ-61)
 * - Pharmacy licenses
 * - Narcotic/psychotropic substance handling
 * - Storage temperature requirements (cold chain)
 * - Storage category requirements
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class LicenseManagementService
{
    use WithAuditLogging;

    private const LICENSE_TYPES = [
        'pharmacy' => 'Фармацевтическая деятельность',
        'medicine_storage' => 'Хранение лекарственных средств',
        'narcotic' => 'Оборот наркотических средств',
        'psychotropic' => 'Оборот психотропных веществ',
        'poisonous' => 'Оборот ядовитых веществ',
    ];

    private const STORAGE_CATEGORIES = [
        'general' => 'Общего хранения',
        'cold_chain' => 'Холодовая цепь (2-8°C)',
        'freezer' => 'Морозильная камера (-20°C)',
        'narcotic' => 'Для наркотических средств',
        'psychotropic' => 'Для психотропных веществ',
        'poisonous' => 'Для ядовитых веществ',
        'flammable' => 'Для легковоспламеняющихся веществ',
    ];

    private const TEMPERATURE_RANGES = [
        'cold_chain' => ['min' => 2, 'max' => 8],
        'freezer' => ['min' => -25, 'max' => -15],
        'general' => ['min' => 15, 'max' => 25],
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Register warehouse license
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $licenseType  License type
     * @param  string  $licenseNumber  License number
     * @param  string  $issuedDate  Issue date
     * @param  string  $expiryDate  Expiry date
     * @param  string  $issuedBy  Issuing authority
     * @param  int  $userId  User registering
     * @return string License ID
     */
    public function registerLicense(
        int $warehouseId,
        int $tenantId,
        string $licenseType,
        string $licenseNumber,
        string $issuedDate,
        string $expiryDate,
        string $issuedBy,
        int $userId,
        string $correlationId = ''
    ): string {
        if (! isset(self::LICENSE_TYPES[$licenseType])) {
            throw new \InvalidArgumentException("Invalid license type: {$licenseType}");
        }

        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $warehouseId,
            $tenantId,
            $licenseType,
            $licenseNumber,
            $issuedDate,
            $expiryDate,
            $issuedBy,
            $userId,
            $correlationId
        ) {
            $licenseId = Str::uuid()->toString();

            $this->db->table('warehouse_licenses')->insert([
                'id' => $licenseId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'license_type' => $licenseType,
                'license_number' => $licenseNumber,
                'issued_date' => $issuedDate,
                'expiry_date' => $expiryDate,
                'issued_by' => $issuedBy,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'license_registered',
                entityType: 'WarehouseLicense',
                entityId: $licenseId,
                context: [
                    'warehouse_id' => $warehouseId,
                    'license_type' => $licenseType,
                    'license_number' => $licenseNumber,
                    'expiry_date' => $expiryDate,
                    'correlation_id' => $correlationId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            // Invalidate cache
            Cache::tags(['warehouse_licenses', "warehouse:{$warehouseId}"])->flush();

            return $licenseId;
        });
    }

    /**
     * Check if warehouse has valid license for operation
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $licenseType  Required license type
     * @return bool
     */
    public function hasValidLicense(int $warehouseId, string $licenseType): bool
    {
        $cacheKey = "warehouse:{$warehouseId}:license:{$licenseType}";

        return Cache::remember($cacheKey, 3600, function () use ($warehouseId, $licenseType) {
            $license = $this->db->table('warehouse_licenses')
                ->where('warehouse_id', $warehouseId)
                ->where('license_type', $licenseType)
                ->where('is_active', true)
                ->where('expiry_date', '>', now())
                ->first();

            return $license !== null;
        });
    }

    /**
     * Check all warehouse licenses
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array License status
     */
    public function checkAllLicenses(int $warehouseId): array
    {
        $licenses = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get()
            ->keyBy('license_type')
            ->toArray();

        $status = [];
        foreach (self::LICENSE_TYPES as $type => $name) {
            $status[$type] = [
                'name' => $name,
                'has_license' => isset($licenses[$type]),
                'is_valid' => isset($licenses[$type]) && $licenses[$type]->expiry_date > now(),
                'expiry_date' => $licenses[$type]->expiry_date ?? null,
            ];
        }

        return $status;
    }

    /**
     * Register storage zone with category
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $category  Storage category
     * @param  float  $minTemp  Minimum temperature
     * @param  float  $maxTemp  Maximum temperature
     * @param  int  $userId  User registering
     * @return string Zone ID
     */
    public function registerStorageZone(
        int $warehouseId,
        int $tenantId,
        string $category,
        float $minTemp,
        float $maxTemp,
        int $userId
    ): string {
        if (! isset(self::STORAGE_CATEGORIES[$category])) {
            throw new \InvalidArgumentException("Invalid storage category: {$category}");
        }

        // Validate temperature range for category
        if (isset(self::TEMPERATURE_RANGES[$category])) {
            $range = self::TEMPERATURE_RANGES[$category];
            if ($minTemp < $range['min'] || $maxTemp > $range['max']) {
                throw new \InvalidArgumentException(
                    "Temperature range {$minTemp}-{$maxTemp}°C not valid for category {$category}"
                );
            }
        }

        return $this->db->transaction(function () use (
            $warehouseId,
            $tenantId,
            $category,
            $minTemp,
            $maxTemp,
            $userId
        ) {
            $zoneId = Str::uuid()->toString();

            $this->db->table('warehouse_storage_zones')->insert([
                'id' => $zoneId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'category' => $category,
                'min_temperature' => $minTemp,
                'max_temperature' => $maxTemp,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'storage_zone_registered',
                entityType: 'WarehouseStorageZone',
                entityId: $zoneId,
                context: [
                    'warehouse_id' => $warehouseId,
                    'category' => $category,
                    'temperature_range' => "{$minTemp}-{$maxTemp}°C",
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $zoneId;
        });
    }

    /**
     * Validate medicine can be stored in zone
     *
     * @param  string  $medicineCategory  Medicine storage category
     * @param  string  $zoneCategory  Zone category
     * @return bool
     */
    public function validateStorageCompatibility(string $medicineCategory, string $zoneCategory): bool
    {
        $compatibilityMatrix = [
            'general' => ['general'],
            'cold_chain' => ['cold_chain'],
            'freezer' => ['freezer'],
            'narcotic' => ['narcotic'],
            'psychotropic' => ['psychotropic'],
            'poisonous' => ['poisonous'],
            'flammable' => ['flammable'],
        ];

        return isset($compatibilityMatrix[$medicineCategory]) &&
               in_array($zoneCategory, $compatibilityMatrix[$medicineCategory], true);
    }

    /**
     * Check cold chain compliance
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Compliance status
     */
    public function checkColdChainCompliance(int $warehouseId): array
    {
        $zones = $this->db->table('warehouse_storage_zones')
            ->where('warehouse_id', $warehouseId)
            ->where('category', 'cold_chain')
            ->where('is_active', true)
            ->get();

        $violations = [];
        foreach ($zones as $zone) {
            // Get latest temperature readings
            $latestReading = $this->db->table('iot_telemetry')
                ->where('zone_id', $zone->id)
                ->where('metric_type', 'temperature')
                ->orderBy('timestamp', 'desc')
                ->first();

            if ($latestReading) {
                if ($latestReading->value < $zone->min_temperature || $latestReading->value > $zone->max_temperature) {
                    $violations[] = [
                        'zone_id' => $zone->id,
                        'current_temp' => $latestReading->value,
                        'allowed_range' => "{$zone->min_temperature}-{$zone->max_temperature}",
                        'timestamp' => $latestReading->timestamp,
                    ];
                }
            }
        }

        return [
            'compliant' => empty($violations),
            'violations' => $violations,
            'total_zones' => $zones->count(),
        ];
    }

    /**
     * Get expiring licenses (within 30 days)
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Expiring licenses
     */
    public function getExpiringLicenses(int $tenantId): array
    {
        return $this->db->table('warehouse_licenses')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->get()
            ->toArray();
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
        return $this->db->transaction(function () use ($licenseId, $reason, $userId) {
            $license = $this->db->table('warehouse_licenses')->where('id', $licenseId)->first();

            if (! $license) {
                throw new \RuntimeException("License not found: {$licenseId}");
            }

            $this->db->table('warehouse_licenses')
                ->where('id', $licenseId)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                    'revoked_by' => $userId,
                    'revocation_reason' => $reason,
                ]);

            $this->logAction(
                action: 'license_revoked',
                entityType: 'WarehouseLicense',
                entityId: $licenseId,
                context: [
                    'license_type' => $license->license_type,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $license->tenant_id
            );

            Cache::tags(['warehouse_licenses', "warehouse:{$license->warehouse_id}"])->flush();

            return true;
        });
    }

    /**
     * Get license types
     *
     * @return array
     */
    public function getLicenseTypes(): array
    {
        return self::LICENSE_TYPES;
    }

    /**
     * Get storage categories
     *
     * @return array
     */
    public function getStorageCategories(): array
    {
        return self::STORAGE_CATEGORIES;
    }

    /**
     * Get temperature ranges
     *
     * @return array
     */
    public function getTemperatureRanges(): array
    {
        return self::TEMPERATURE_RANGES;
    }
}
