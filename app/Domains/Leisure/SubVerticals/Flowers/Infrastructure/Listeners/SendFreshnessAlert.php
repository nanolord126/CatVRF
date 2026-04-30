<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Listeners;

use Modules\Flowers\Domain\Events\FreshnessStatusChanged;
use Modules\Flowers\Domain\Events\LowStockAlert;
use Modules\Flowers\Domain\Events\ExpiryWarning;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendFreshnessAlert implements ShouldQueue
{
    public function handle(FreshnessStatusChanged|LowStockAlert|ExpiryWarning $event): void
    {
        $flower = match (true) {
            $event instanceof FreshnessStatusChanged => $event->flower,
            $event instanceof LowStockAlert => $event->flower,
            $event instanceof ExpiryWarning => $event->flower,
        };

        $alertType = match (true) {
            $event instanceof FreshnessStatusChanged => 'status_change',
            $event instanceof LowStockAlert => 'low_stock',
            $event instanceof ExpiryWarning => 'expiry_warning',
        };

        Log::warning('Freshness alert', [
            'flower_id' => $flower->id,
            'flower_name' => $flower->name,
            'alert_type' => $alertType,
            'freshness_status' => $flower->freshnessStatus->value,
            'stock_quantity' => $flower->stockQuantity,
            'expiry_date' => $flower->expiryDate->toDateString(),
        ]);

        // Send alert to venue manager
        // Could use email, SMS, or in-app notification
    }
}
