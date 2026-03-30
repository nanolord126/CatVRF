<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowPartsStockAlertListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(LowPartsStock $event): void
        {
            try {
                $part = $event->part;

                Log::channel('audit')->warning('Low auto parts stock alert', [
                    'part_id' => $part->id,
                    'part_name' => $part->name,
                    'current_stock' => $part->current_stock,
                    'min_threshold' => $part->min_stock_threshold,
                    'sku' => $part->sku,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('LowPartsStockAlertListener failed', [
                    'part_id' => $event->part->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
