<?php

declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Services;

use App\Domains\FarmDirect\Agro\Models\AgroFarm;
use App\Domains\FarmDirect\Agro\Models\AgroProduct;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис управления агро-промышленным комплексом
 */
final class AgroService
{
    private string $correlationId;

    public function __construct(?string $correlationId = null)
    {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Создание/регистрация агро-предприятия
     */
    public function registerFarm(array $data, int $tenantId): AgroFarm
    {
        // Fraud Check (защита от массовой регистрации фейковых ферм)
        FraudControlService::check($this->correlationId);

        return DB::transaction(function () use ($data, $tenantId) {
            $farm = AgroFarm::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'inn' => $data['inn'],
                'specialization' => $data['specialization'] ?? [],
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('Agro farm registered', [
                'farm_id' => $farm->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            return $farm;
        });
    }

    /**
     * Обновление складских остатков агро-продукции
     */
    public function updateStock(int $productId, float $quantity, string $reason = 'manual_update'): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $reason) {
            $product = AgroProduct::lockForUpdate()->findOrFail($productId);
            
            $oldStock = $product->current_stock;
            $product->current_stock = $quantity;
            $product->save();

            Log::channel('audit')->info('Agro stock updated', [
                'product_id' => $productId,
                'old_stock' => $oldStock,
                'new_stock' => $quantity,
                'reason' => $reason,
                'correlation_id' => $this->correlationId,
            ]);

            // Если остаток ниже порога — генерируем алерт (в будущем асинхронный Job)
            if ($product->current_stock <= $product->min_stock_alert) {
                // LowStockNotificationJob::dispatch(...)
            }

            return true;
        });
    }
}
