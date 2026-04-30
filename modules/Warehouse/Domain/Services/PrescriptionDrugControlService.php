<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Modules\Warehouse\Domain\Exceptions\LicenseManagementException;
use Psr\Log\LoggerInterface;

/**
 * Prescription Drug Control Service for ФЗ-323 compliance
 * 
 * Сервис обеспечивает контроль отпуска рецептурных препаратов:
 * - Проверка рецепта перед отпуском
 * - Валидация рецепта
 * - Логирование отпуска рецептурных препаратов
 * - Контроль количества отпуска
 */
final readonly class PrescriptionDrugControlService
{
    private const PRESCRIPTION_REQUIRED_CATEGORIES = [
        'narcotic',
        'psychotropic',
        'antibiotic',
        'hormonal',
        'immunosuppressant',
        'cytotoxic',
        'controlled',
    ];

    private const MAX_DOSAGE_DAYS = 30;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Проверка, требуется ли рецепт для продукта
     */
    public function requiresPrescription(int $productId): bool
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (!$product) {
            return false;
        }

        // Проверка по категории
        $category = strtolower($product->category ?? '');
        foreach (self::PRESCRIPTION_REQUIRED_CATEGORIES as $requiredCategory) {
            if (str_contains($category, $requiredCategory)) {
                return true;
            }
        }

        // Проверка по флагу в продукте
        return (bool) ($product->requires_prescription ?? false);
    }

    /**
     * Валидация рецепта
     */
    public function validatePrescription(
        string $prescriptionNumber,
        int $doctorId,
        int $patientId,
        int $productId,
        int $requestedQuantity
    ): array {
        // Проверка существования рецепта
        $prescription = $this->db->table('prescriptions')
            ->where('prescription_number', $prescriptionNumber)
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->first();

        if (!$prescription) {
            throw new LicenseManagementException('Prescription not found');
        }

        // Проверка срока действия рецепта
        if ($prescription->expires_at && \Carbon\Carbon::parse($prescription->expires_at)->isPast()) {
            throw new LicenseManagementException('Prescription has expired');
        }

        // Проверка статуса рецепта
        if ($prescription->status !== 'active') {
            throw new LicenseManagementException('Prescription is not active');
        }

        // Проверка наличия продукта в рецепте
        $prescriptionItem = $this->db->table('prescription_items')
            ->where('prescription_id', $prescription->id)
            ->where('product_id', $productId)
            ->first();

        if (!$prescriptionItem) {
            throw new LicenseManagementException('Product not found in prescription');
        }

        // Проверка количества
        $remainingQuantity = $prescriptionItem->quantity - $prescriptionItem->dispensed_quantity;
        if ($requestedQuantity > $remainingQuantity) {
            throw new LicenseManagementException(
                "Requested quantity exceeds remaining. Available: {$remainingQuantity}, Requested: {$requestedQuantity}"
            );
        }

        return [
            'valid' => true,
            'prescription_id' => $prescription->id,
            'remaining_quantity' => $remainingQuantity,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
        ];
    }

    /**
     * Регистрация отпуска рецептурного препарата
     */
    public function recordDispensing(
        string $prescriptionId,
        int $productId,
        int $quantity,
        int $dispensedBy,
        int $warehouseId
    ): string {
        $dispensingId = (string) \Illuminate\Support\Str::uuid();

        return $this->db->transaction(function () use (
            $prescriptionId,
            $productId,
            $quantity,
            $dispensedBy,
            $warehouseId,
            $dispensingId
        ) {
            // Запись отпуска
            $this->db->table('prescription_dispensings')->insert([
                'id' => $dispensingId,
                'prescription_id' => $prescriptionId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'dispensed_by' => $dispensedBy,
                'warehouse_id' => $warehouseId,
                'dispensed_at' => now(),
                'created_at' => now(),
            ]);

            // Обновление количества в рецепте
            $this->db->table('prescription_items')
                ->where('prescription_id', $prescriptionId)
                ->where('product_id', $productId)
                ->increment('dispensed_quantity', $quantity);

            // Проверка, полностью ли отпущен рецепт
            $this->checkPrescriptionCompletion($prescriptionId);

            // Логирование
            $this->logger->info('Prescription drug dispensed', [
                'prescription_id' => $prescriptionId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'dispensed_by' => $dispensedBy,
                'warehouse_id' => $warehouseId,
            ]);

            return $dispensingId;
        });
    }

    /**
     * Проверка завершенности рецепта
     */
    private function checkPrescriptionCompletion(string $prescriptionId): void
    {
        $items = $this->db->table('prescription_items')
            ->where('prescription_id', $prescriptionId)
            ->get();

        $allDispensed = $items->every(function ($item) {
            return $item->dispensed_quantity >= $item->quantity;
        });

        if ($allDispensed) {
            $this->db->table('prescriptions')
                ->where('id', $prescriptionId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            $this->logger->info('Prescription fully dispensed', [
                'prescription_id' => $prescriptionId,
            ]);
        }
    }

    /**
     * Получение истории отпуска рецептурных препаратов
     */
    public function getDispensingHistory(
        ?int $patientId = null,
        ?int $productId = null,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null
    ): array {
        $query = $this->db->table('prescription_dispensings')
            ->join('prescriptions', 'prescription_dispensings.prescription_id', '=', 'prescriptions.id')
            ->join('products', 'prescription_dispensings.product_id', '=', 'products.id')
            ->select([
                'prescription_dispensings.*',
                'prescriptions.prescription_number',
                'prescriptions.doctor_id',
                'prescriptions.patient_id',
                'products.sku',
                'products.name as product_name',
            ]);

        if ($patientId) {
            $query->where('prescriptions.patient_id', $patientId);
        }

        if ($productId) {
            $query->where('prescription_dispensings.product_id', $productId);
        }

        if ($startDate) {
            $query->where('prescription_dispensings.dispensed_at', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('prescription_dispensings.dispensed_at', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        return $query->orderBy('prescription_dispensings.dispensed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Проверка на предмет злоупотребления (excessive dispensing)
     */
    public function checkExcessiveDispensing(int $patientId, int $productId, int $days = 30): bool
    {
        $threshold = $this->getDispensingThreshold($productId);

        $totalDispensed = $this->db->table('prescription_dispensings')
            ->join('prescriptions', 'prescription_dispensings.prescription_id', '=', 'prescriptions.id')
            ->where('prescriptions.patient_id', $patientId)
            ->where('prescription_dispensings.product_id', $productId)
            ->where('prescription_dispensings.dispensed_at', '>=', now()->subDays($days))
            ->sum('prescription_dispensings.quantity');

        if ($totalDispensed > $threshold) {
            $this->logger->warning('Excessive dispensing detected', [
                'patient_id' => $patientId,
                'product_id' => $productId,
                'total_dispensed' => $totalDispensed,
                'threshold' => $threshold,
                'period_days' => $days,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Получение порога отпуска для продукта
     */
    private function getDispensingThreshold(int $productId): int
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        // Базовый порог из конфигурации продукта
        $threshold = $product->dispensing_threshold ?? 100;

        // Кэширование
        return Cache::remember("dispensing_threshold_{$productId}", 3600, function () use ($threshold) {
            return $threshold;
        });
    }
}
