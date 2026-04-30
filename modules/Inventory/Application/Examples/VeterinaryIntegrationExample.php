<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с ветеринарным модулем
 * 
 * Сценарии:
 * - Выписка рецепта/назначения лекарства
 * - Вакцинация животного
 * - Использование медицинских расходных материалов
 */
final class VeterinaryIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Выписка рецепта с автоматическим FIFO-списанием
     */
    public function prescribeMedication(
        int $medicationItemId,
        int $quantity,
        int $vetId,
        int $patientId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($medicationItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Лекарство не найдено');
        }

        $domainItem = $item->toDomain();

        try {
            // Проверка перед назначением
            $this->fifoService->validateBeforeSale($domainItem, $quantity);

            // Автоматическое FIFO-списание
            $result = $this->fifoService->autoDeduct(
                $domainItem,
                $quantity,
                'prescription',
                [
                    'vet_id' => $vetId,
                    'patient_id' => $patientId,
                    'appointment_id' => $appointmentId,
                ]
            );

            // Логирование для медицинской отчётности
            $this->logPrescription($result, $vetId, $patientId, $appointmentId);

            // Возврат информации о партиях для медицинской карты
            $batchInfo = $result->deductedBatches->map(fn ($batch) => [
                'batch_number' => $batch['batch_number'],
                'quantity' => $batch['quantity'],
                'expiry_date' => $batch['expiry_date'],
                'days_left' => $batch['days_left'],
            ]);

            return $batchInfo->toArray();

        } catch (ShelfLifeException $e) {
            // Лекарство просрочено - блокировать назначение
            throw new \RuntimeException(
                "Невозможно назначить лекарство: {$e->getMessage()}"
            );
        } catch (InsufficientStockWithExpiryException $e) {
            // Недостаточно лекарства с действующим сроком годности
            throw new \RuntimeException(
                "Недостаточно лекарства с действующим сроком годности: {$e->getMessage()}"
            );
        }
    }

    /**
     * Пример 2: Вакцинация с трассировкой партии
     */
    public function administerVaccine(
        int $vaccineItemId,
        int $patientId,
        int $vetId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($vaccineItemId);
        if (!$item || $item->category !== 'medication') {
            throw new \InvalidArgumentException('Вакцина не найдена или неверная категория');
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности вакцины (строже - минимум 30 дней)
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 30) {
            throw new \RuntimeException(
                'Вакцина истекает менее чем через 30 дней. Использование не рекомендуется.'
            );
        }

        // Списание 1 дозы вакцины
        $result = $this->fifoService->autoDeduct(
            $domainItem,
            1,
            'prescription',
            [
                'vet_id' => $vetId,
                'patient_id' => $patientId,
                'appointment_id' => $appointmentId,
                'vaccination' => true,
            ]
        );

        // Запись в медицинскую карту с информацией о партии
        $this->recordVaccinationInMedicalRecord(
            $patientId,
            $result->deductedBatches[0]['batch_number'],
            $result->deductedBatches[0]['expiry_date'],
            $vetId
        );

        return [
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
            'administered_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Пример 3: Проверка доступности лекарств перед приёмом
     */
    public function checkMedicationAvailability(int $medicationItemId, int $requiredQuantity): array
    {
        $item = InventoryItemModel::find($medicationItemId);
        if (!$item) {
            return ['available' => false, 'reason' => 'Лекарство не найдено'];
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности
        if ($domainItem->isExpired()) {
            return [
                'available' => false,
                'reason' => 'Лекарство просрочено',
                'expiry_date' => $domainItem->expiryDate?->format('Y-m-d'),
            ];
        }

        // Проверка доступного количества с действующим сроком
        $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->sum('current_quantity');

        if ($availableStock < $requiredQuantity) {
            return [
                'available' => false,
                'reason' => 'Недостаточно количества',
                'required' => $requiredQuantity,
                'available' => $availableStock,
            ];
        }

        // Получение следующей истекающей партии для информации
        $nextBatch = $this->fifoService->getNextExpiringBatch($item->id);

        return [
            'available' => true,
            'total_stock' => $availableStock,
            'next_expiry_batch' => $nextBatch ? [
                'batch_number' => $nextBatch->batch_number,
                'expiry_date' => $nextBatch->expiry_date->format('Y-m-d'),
                'days_left' => $nextBatch->getDaysUntilExpiry(),
            ] : null,
        ];
    }

    /**
     * Пример 4: Создание партии лекарства при поступлении
     */
    public function receiveMedicationShipment(
        int $medicationItemId,
        string $batchNumber,
        string $manufactureDate,
        string $expiryDate,
        int $quantity,
        float $purchasePrice,
        string $storageLocation
    ): InventoryBatchModel {
        $item = InventoryItemModel::find($medicationItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Лекарство не найдено');
        }

        // Создание партии
        $batch = InventoryBatchModel::create([
            'inventory_item_id' => $item->id,
            'tenant_id' => $item->tenant_id,
            'batch_number' => $batchNumber,
            'manufacture_date' => $manufactureDate,
            'expiry_date' => $expiryDate,
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'storage_location' => $storageLocation,
            'status' => 'active',
        ]);

        // Обновление общего количества товара
        $item->increment('quantity', $quantity);

        // Проверка срока годности при поступлении
        if ($batch->isExpiringSoon(90)) {
            // Уведомление о партии с коротким сроком
            $this->notifyAboutShortShelfLife($batch, 90);
        }

        return $batch;
    }

    /**
     * Пример 5: Отчёт по использованию лекарств за период
     */
    public function getMedicationUsageReport(
        int $medicationItemId,
        string $startDate,
        string $endDate
    ): array {
        // Здесь можно использовать логи FIFO для построения отчёта
        // Пример реализации с использованием логов Laravel
        $logs = \Illuminate\Support\Facades\Log::getMonolog()->getHandlers();

        // В реальной реализации нужно хранить FIFO-движения в отдельной таблице
        // для возможности построения отчётов

        return [
            'medication_id' => $medicationItemId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_deducted' => 0,
            'batches_used' => [],
            'contexts' => [
                'prescription' => 0,
                'vaccination' => 0,
            ],
        ];
    }

    private function logPrescription($result, int $vetId, int $patientId, int $appointmentId): void
    {
        \Illuminate\Support\Facades\Log::info('Выписан рецепт с FIFO-списанием', [
            'vet_id' => $vetId,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'quantity' => $result->totalQuantity,
            'batches' => $result->deductedBatches,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function recordVaccinationInMedicalRecord(
        int $patientId,
        string $batchNumber,
        string $expiryDate,
        int $vetId
    ): void {
        // Интеграция с модулем ветеринарных карт
        // Здесь должна быть запись в медицинскую карту питомца
        // с информацией о партии вакцины
    }

    private function notifyAboutShortShelfLife(InventoryBatchModel $batch, int $days): void
    {
        \Illuminate\Support\Facades\Log::warning('Поступила партия с коротким сроком годности', [
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date->format('Y-m-d'),
            'days_left' => $batch->getDaysUntilExpiry(),
            'threshold_days' => $days,
        ]);
    }
}
