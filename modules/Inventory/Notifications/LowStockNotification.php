<?php declare(strict_types=1);

namespace Modules\Inventory\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;
    
        public function __construct(protected Product $product) {
        /**
         * Инициализировать класс
         */
        public function __construct()
        {
            // TODO: инициализация
        }
    }
    
        public function via($notifiable): array
        {
            return ['database'];
        }
    
        public function toArray($notifiable): array
        {
            FilamentNotification->make()
                ->title('Low Stock Alert: ' . $this->product->name)
                ->body("SKU {$this->product->sku} is below threshold: current {$this->product->stock}")
                ->danger()
                ->sendToDatabase($notifiable);
    
            return [
                'product_id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'current_stock' => $this->product->stock,
                'min_stock' => $this->product->min_stock,
            ];
        }
}
