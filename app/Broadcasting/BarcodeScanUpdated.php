<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Barcode Scan Updated Event
 *
 * Broadcasts real-time updates when barcode scans result in stock adjustments.
 * Enables live inventory tracking across multiple scanner devices.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class BarcodeScanUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $warehouseId,
        private readonly string $barcode,
        private readonly int $itemId,
        private readonly string $itemName,
        private readonly string $sku,
        private readonly int $previousStock,
        private readonly int $newStock,
        private readonly string $adjustmentType,
        private readonly int $quantity,
        private readonly int $movementId,
        private readonly int $userId,
        private readonly string $userName,
        private readonly string $correlationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("scanner.{$this->tenantId}.{$this->warehouseId}");
    }

    public function broadcastAs(): string
    {
        return 'barcode.scan.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => 'barcode_scan_updated',
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'barcode' => $this->barcode,
            'item_id' => $this->itemId,
            'item_name' => $this->itemName,
            'sku' => $this->sku,
            'previous_stock' => $this->previousStock,
            'new_stock' => $this->newStock,
            'adjustment_type' => $this->adjustmentType,
            'quantity' => $this->quantity,
            'movement_id' => $this->movementId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'correlation_id' => $this->correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
