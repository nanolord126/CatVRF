<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\AutoPart;
use App\Domains\Auto\Models\AutoVehicle;
use App\Domains\Auto\Models\AutoRepairOrder;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AutoPartService — Канон 2026.
 * 
 * Логика подбора запчастей по VIN, управление каталогом и складскими остатками.
 */
final readonly class AutoPartService
{
    public function __construct(
        private FraudControlService $fraudControl
    ) {}

    /**
     * Поиск совместимых запчастей по полному VIN или маске модели.
     * 
     * @param string $vin 17-значный идентификатор
     * @param string|null $correlationId
     */
    public function findPartsByVin(string $vin, ?string $correlationId = null): Collection
    {
        Log::channel('audit')->info('VIN Search initiated', [
            'vin' => $vin,
            'correlation_id' => $correlationId,
        ]);

        // 1. Предварительная валидация VIN (проверка структуры через модель)
        if (!AutoVehicle::isValidVin($vin)) {
            Log::channel('audit')->warning('Invalid VIN search attempt', ['vin' => $vin]);
            return collect();
        }

        // 2. Поиск в БД по JSON полю compatibility_vin
        // В реальном проекте здесь может быть интеграция с внешним API (TecDoc, Laximo)
        return AutoPart::whereJsonContains('compatibility_vin', $vin)
            ->where('stock_quantity', '>', 0)
            ->get();
    }

    /**
     * Создание новой запчасти в каталоге с проверкой фрода.
     */
    public function createPart(array $data, string $correlationId): AutoPart
    {
        return DB::transaction(function () use ($data, $correlationId) {
            // 1. Fraud Check перед мутацией
            $this->fraudControl->check([
                'type' => 'auto_part_creation',
                'sku' => $data['sku'] ?? null,
                'tenant_id' => tenant('id'),
            ]);

            // 2. Создание записи
            $part = AutoPart::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Auto Part created', [
                'uuid' => $part->uuid,
                'sku' => $part->sku,
                'correlation_id' => $correlationId,
            ]);

            return $part;
        });
    }

    /**
     * Резервирование запчасти для заказ-наряда (Inventory Integration).
     */
    public function reserveForOrder(AutoPart $part, int $quantity, string $orderUuid): bool
    {
        return DB::transaction(function () use ($part, $quantity, $orderUuid) {
            if ($part->stock_quantity < $quantity) {
                throw new \RuntimeException("Insufficient stock for part: {$part->sku}");
            }

            $part->decrement('stock_quantity', $quantity);
            
            Log::channel('inventory')->info('Part reserved for order', [
                'part_uuid' => $part->uuid,
                'quantity' => $quantity,
                'order_uuid' => $orderUuid,
            ]);

            return true;
        });
    }
}
