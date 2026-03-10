<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Modules\Inventory\Models\Product;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(protected Product $product) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        FilamentNotification::make()
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
