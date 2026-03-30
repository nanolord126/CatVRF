<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockNotificationListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(LowStockReached $event): void
        {
            try {
                $product = $event->product;
                $salon = $product->salon;

                // Отправить уведомление владельцу салона

                Log::channel('audit')->warning('Low stock alert', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $product->current_stock,
                    'min_threshold' => $product->min_stock_threshold,
                    'salon_id' => $salon->id,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('LowStockNotificationListener failed', [
                    'product_id' => $event->product->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
