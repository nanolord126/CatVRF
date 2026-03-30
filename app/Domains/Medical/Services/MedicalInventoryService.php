<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalInventoryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Резервация расходников для услуги (Hold).
         */
        public function reserveForService(int $serviceId, int $quantityMultiplier = 1, string $correlationId = null): void
        {
            $service = MedicalService::findOrFail($serviceId);
            $consumablesConfig = $service->consumables_config ?? [];

            if (empty($consumablesConfig)) {
                return;
            }

            DB::transaction(function () use ($consumablesConfig, $quantityMultiplier, $correlationId) {
                foreach ($consumablesConfig as $config) {
                    $sku = $config['sku'];
                    $amountPerService = $config['amount'];
                    $totalToReserve = $amountPerService * $quantityMultiplier;

                    $consumable = MedicalConsumable::where('sku', $sku)->first();

                    if (!$consumable) {
                        Log::channel('audit')->warning("Consumable with SKU $sku not found during reservation", [
                            'correlation_id' => $correlationId
                        ]);
                        continue;
                    }

                    // В MedicalConsumable добавим логику Hold в metadata или отдельное поле
                    $consumable->updateQuietly([
                        'metadata' => array_merge($consumable->metadata ?? [], [
                            'holds' => array_merge($consumable->metadata['holds'] ?? [], [
                                $correlationId => [
                                    'amount' => $totalToReserve,
                                    'reserved_at' => now()->toIso8601String()
                                ]
                            ])
                        ])
                    ]);
                }
            });
        }

        /**
         * Окончательное списание (Deduct) после завершения приема.
         */
        public function deductForService(
            int $serviceId,
            int $quantityMultiplier = 1,
            string $reason = 'usage',
            int $sourceId = null,
            string $correlationId = null
        ): void {
            $service = MedicalService::findOrFail($serviceId);
            $consumablesConfig = $service->consumables_config ?? [];

            DB::transaction(function () use ($consumablesConfig, $quantityMultiplier, $reason, $sourceId, $correlationId) {
                foreach ($consumablesConfig as $config) {
                    $sku = $config['sku'];
                    $amount = $config['amount'] * $quantityMultiplier;

                    $consumable = MedicalConsumable::where('sku', $sku)->lockForUpdate()->first();
                    if ($consumable) {
                        $consumable->decrementStock($amount, $reason);

                        // Очистка Hold
                        $holds = $consumable->metadata['holds'] ?? [];
                        unset($holds[$correlationId]);

                        $consumable->updateQuietly([
                            'metadata' => array_merge($consumable->metadata ?? [], ['holds' => $holds])
                        ]);

                        Log::channel('audit')->info("Medical consumable deducted", [
                            'sku' => $sku,
                            'amount' => $amount,
                            'source_id' => $sourceId,
                            'correlation_id' => $correlationId
                        ]);
                    }
                }
            });
        }

        /**
         * Снятие резерва при отмене.
         */
        public function releaseForService(int $serviceId, int $quantityMultiplier = 1, string $correlationId = null): void
        {
            $service = MedicalService::findOrFail($serviceId);
            $consumablesConfig = $service->consumables_config ?? [];

            DB::transaction(function () use ($consumablesConfig, $correlationId) {
                foreach ($consumablesConfig as $config) {
                    $sku = $config['sku'];
                    $consumable = MedicalConsumable::where('sku', $sku)->first();

                    if ($consumable) {
                        $holds = $consumable->metadata['holds'] ?? [];
                        unset($holds[$correlationId]);

                        $consumable->updateQuietly([
                            'metadata' => array_merge($consumable->metadata ?? [], ['holds' => $holds])
                        ]);
                    }
                }
            });
        }

        /**
         * Проверка критических остатков (для LowStockNotificationJob).
         */
        public function getCriticalItems(int $tenantId): \Illuminate\Support\Collection
        {
            return MedicalConsumable::where('tenant_id', $tenantId)
                ->whereRaw('stock_quantity <= min_threshold')
                ->get();
        }
}
