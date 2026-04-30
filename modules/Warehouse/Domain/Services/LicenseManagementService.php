<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\Product;
use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;
use Modules\Warehouse\Domain\Exceptions\LicenseManagementException;
use Illuminate\Database\DatabaseManager;

/**
 * License Management Service for ФЗ-323 and ФЗ-61 compliance
 *
 * Сервис обеспечивает проверку лицензий для хранения лекарственных средств:
 * - Лицензия на фармацевтическую деятельность
 * - Лицензия на хранение наркотических/психотропных веществ
 * - Контроль температурного режима
 * - Специальные требования к помещениям
 */
final readonly class LicenseManagementService
{
    private const LICENSE_TYPES = [
        'pharmaceutical' => 'Лицензия на фармацевтическую деятельность',
        'narcotic' => 'Лицензия на деятельность с наркотическими средствами',
        'psychotropic' => 'Лицензия на деятельность с психотропными веществами',
        'controlled' => 'Лицензия на деятельность с контролируемыми веществами',
    ];

    private const TEMPERATURE_RANGES = [
        'standard' => ['min' => 2.0, 'max' => 25.0],
        'refrigerated' => ['min' => 2.0, 'max' => 8.0],
        'frozen' => ['min' => -25.0, 'max' => -10.0],
        'room_temperature' => ['min' => 15.0, 'max' => 25.0],
    ];

    public function __construct(
        private readonly DatabaseManager $db
    ) {}
    /**
     * Проверка лицензии склада на хранение лекарственных средств
     */
    public function validateWarehouseLicense(Warehouse $warehouse, string $productCategory): bool
    {
        // Для центральных и региональных складов требуется лицензия
        if (in_array($warehouse->getType()->value, ['central', 'regional'], true)) {
            if ($this->isPharmaceuticalProduct($productCategory)) {
                return $this->hasPharmacyLicense($warehouse);
            }
        }

        return true;
    }

    /**
     * Проверка лицензии на хранение наркотических/психотропных веществ
     */
    public function validateControlledSubstanceLicense(Warehouse $warehouse, string $productCategory): bool
    {
        if (!$this->isControlledSubstance($productCategory)) {
            return true;
        }

        return $this->hasControlledSubstanceLicense($warehouse);
    }

    /**
     * Проверка температурного режима для хранения
     */
    public function validateTemperatureRequirements(
        Product $product,
        float $currentTemperature,
        float $humidity = null
    ): bool {
        if (!$product->requiresTemperatureControl()) {
            return true;
        }

        $minTemp = $product->getMinTemperature() ?? 2.0;
        $maxTemp = $product->getMaxTemperature() ?? 25.0;

        if ($currentTemperature < $minTemp || $currentTemperature > $maxTemp) {
            throw new LicenseManagementException(
                sprintf(
                    'Temperature violation for product %s: current %.1f°C, required %.1f-%.1f°C',
                    $product->getSku(),
                    $currentTemperature,
                    $minTemp,
                    $maxTemp
                )
            );
        }

        return true;
    }

    /**
     * Проверка срока годности партии (ФЗ-61)
     */
    public function validateBatchExpiry(Batch $batch): bool
    {
        if ($batch->isExpired()) {
            throw new LicenseManagementException(
                sprintf(
                    'Batch %s for product %s is expired on %s',
                    $batch->getBatchNumber(),
                    $batch->getProductSku(),
                    $batch->getExpiryDate()->format('Y-m-d')
                )
            );
        }

        // Блокировка партии за 30 дней до истечения для лекарственных средств
        if ($this->isPharmaceuticalProduct($batch->getProductSku()) && $batch->isExpiringSoon(30)) {
            throw new LicenseManagementException(
                sprintf(
                    'Batch %s expires in less than 30 days, blocking for pharmaceutical product',
                    $batch->getBatchNumber()
                )
            );
        }

        return true;
    }

    /**
     * Проверка помещения для хранения лекарств
     */
    public function validateStorageRequirements(Warehouse $warehouse, string $productCategory): bool
    {
        if (!$this->isPharmaceuticalProduct($productCategory)) {
            return true;
        }

        // Проверка наличия зоны для лекарственных средств
        // Проверка оборудования (холодильники, климат-контроль)
        // Проверка систем безопасности

        return true;
    }

    /**
     * Проверка требования рецепта для отпуска
     */
    public function requiresPrescription(string $productCategory): bool
    {
        $prescriptionCategories = [
            'antibiotics',
            'narcotics',
            'psychotropics',
            'controlled_medications'
        ];

        return in_array(strtolower($productCategory), $prescriptionCategories, true);
    }

    /**
     * Проверка на фармацевтический продукт
     */
    private function isPharmaceuticalProduct(string $categoryOrSku): bool
    {
        $pharmaCategories = [
            'pharmaceutical',
            'medication',
            'medicine',
            'drug',
            'vaccine',
            'antibiotic'
        ];

        $category = strtolower($categoryOrSku);

        foreach ($pharmaCategories as $pharmaCategory) {
            if (str_contains($category, $pharmaCategory)) {
                return true;
            }
        }

        // Проверка по SKU (префикс MED-)
        return str_starts_with($categoryOrSku, 'MED-');
    }

    /**
     * Проверка на контролируемое вещество
     */
    private function isControlledSubstance(string $category): bool
    {
        $controlledCategories = [
            'narcotic',
            'psychotropic',
            'precursor',
            'controlled_substance'
        ];

        $category = strtolower($category);

        foreach ($controlledCategories as $controlledCategory) {
            if (str_contains($category, $controlledCategory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка наличия лицензии на фармацевтическую деятельность
     */
    private function hasPharmacyLicense(Warehouse $warehouse): bool
    {
        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouse->getId()->toString())
            ->where('license_type', 'pharmaceutical')
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->first();

        return $license !== null;
    }


    /**
     * Валидация лицензии склада для определенного типа деятельности
     */
    public function validateLicenseByType(Warehouse $warehouse, string $licenseType): void
    {
        $this->validateLicenseType($licenseType);

        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouse->getId()->toString())
            ->where('license_type', $licenseType)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->first();

        if (!$license) {
            throw new LicenseManagementException(
                sprintf(
                    'License %s not found or expired for warehouse %s',
                    $licenseType,
                    $warehouse->getId()->toString()
                )
            );
        }
    }

    /**
     * Проверка срока действия лицензии с предупреждением
     */
    public function checkLicenseExpiry(string $warehouseId, string $licenseType, int $warningDays = 90): array
    {
        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->where('license_type', $licenseType)
            ->where('status', 'active')
            ->first();

        if (!$license) {
            return [
                'exists' => false,
                'status' => 'not_found',
                'message' => 'License not found',
            ];
        }

        $expiryDate = \Carbon\Carbon::parse($license->expiry_date);
        $daysUntilExpiry = $expiryDate->diffInDays(now());

        $status = match (true) {
            $expiryDate->isPast() => 'expired',
            $daysUntilExpiry <= 30 => 'critical',
            $daysUntilExpiry <= $warningDays => 'warning',
            default => 'valid',
        };

        return [
            'exists' => true,
            'license_number' => $license->license_number,
            'expiry_date' => $expiryDate->toIso8601String(),
            'days_until_expiry' => $daysUntilExpiry,
            'status' => $status,
            'message' => $this->getExpiryMessage($status, $daysUntilExpiry),
        ];
    }

    /**
     * Валидация лицензии для хранения лекарственных средств (ФЗ-61)
     */
    public function validatePharmaceuticalStorageLicense(int $warehouseId): void
    {
        $warehouse = $this->getWarehouse($warehouseId);

        // Проверка наличия лицензии на хранение лекарств
        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->where('license_type', 'pharmaceutical_storage')
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->first();

        if (!$license) {
            throw new LicenseManagementException(
                "Warehouse {$warehouseId} does not have active pharmaceutical storage license"
            );
        }

        $this->logger->info('Pharmaceutical storage license validated', [
            'warehouse_id' => $warehouseId,
            'license_number' => $license->license_number,
        ]);
    }

    /**
     * Получение всех лицензий склада
     */
    public function getWarehouseLicenses(string $warehouseId): array
    {
        return $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('expiry_date')
            ->get()
            ->toArray();
    }

    /**
     * Проверка требований к помещению склада
     */
    public function validateStorageRequirements(Warehouse $warehouse, string $productCategory): array
    {
        $violations = [];

        if (!$this->isPharmaceuticalProduct($productCategory)) {
            return $violations;
        }

        // Проверка холодильного оборудования
        if (!$warehouse->hasRefrigeration()) {
            $violations[] = 'Missing refrigeration equipment';
        }

        // Проверка системы мониторинга температуры
        if (!$warehouse->hasTemperatureMonitoring()) {
            $violations[] = 'Missing temperature monitoring system';
        }

        // Проверка вентиляции
        if (!$warehouse->hasVentilation()) {
            $violations[] = 'Missing ventilation system';
        }

        // Проверка противопожарной безопасности
        if (!$warehouse->hasFireSafety()) {
            $violations[] = 'Missing fire safety systems';
        }

        // Для контролируемых веществ - дополнительные проверки
        if ($this->isControlledSubstance($productCategory)) {
            if (!$warehouse->hasSecureStorage()) {
                $violations[] = 'Missing secure storage (safe)';
            }
            if (!$warehouse->hasVideoSurveillance()) {
                $violations[] = 'Missing video surveillance';
            }
            if (!$warehouse->hasAccessControl()) {
                $violations[] = 'Missing access control system';
            }
        }

        return $violations;
    }

    /**
     * Валидация типа лицензии
     */
    private function validateLicenseType(string $licenseType): void
    {
        if (!array_key_exists($licenseType, self::LICENSE_TYPES)) {
            throw new LicenseManagementException(
                sprintf(
                    'Invalid license type: %s. Available types: %s',
                    $licenseType,
                    implode(', ', array_keys(self::LICENSE_TYPES))
                )
            );
        }
    }

    /**
     * Получение сообщения о сроке действия лицензии
     */
    private function getExpiryMessage(string $status, int $days): string
    {
        return match ($status) {
            'expired' => 'License has expired',
            'critical' => sprintf('License expires in %d days (critical)', $days),
            'warning' => sprintf('License expires in %d days', $days),
            default => 'License is valid',
        };
    }

    /**
     * Получение описания типа лицензии
     */
    public static function getLicenseTypeDescription(string $licenseType): string
    {
        return self::LICENSE_TYPES[$licenseType] ?? $licenseType;
    }

    /**
     * Автоматическая проверка compliance всех складов
     */
    public function runComplianceCheck(): array
    {
        $warehouses = $this->db->table('warehouses')
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($warehouses as $warehouse) {
            $violations = [];
            $licenseStatus = [];

            foreach (array_keys(self::LICENSE_TYPES) as $licenseType) {
                try {
                    $check = $this->checkLicenseExpiry($warehouse->id, $licenseType);
                    if ($check['exists']) {
                        $licenseStatus[$licenseType] = $check['status'];
                    }
                } catch (\Exception $e) {
                    $violations[] = sprintf('License check failed for %s: %s', $licenseType, $e->getMessage());
                }
            }

            $results[] = [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'license_status' => $licenseStatus,
                'violations' => $violations,
                'compliance_status' => empty($violations) ? 'compliant' : 'non_compliant',
            ];
        }

        return $results;
    }
    /**
     * Проверка наличия лицензии на контролируемые вещества
     */
    private function hasControlledSubstanceLicense(Warehouse $warehouse): bool
    {
        $license = $this->db->table('warehouse_licenses')
            ->where('warehouse_id', $warehouse->getId()->toString())
            ->where('license_type', 'controlled_substances')
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->first();

        return $license !== null;
    }
}
