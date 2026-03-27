<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\BeautyProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: товар/расходник кончился (остаток < минимума).
 * Production 2026.
 */
final class LowStockReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        readonly public BeautyProduct $product,
        readonly public string $correlationId = '',
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.inventory'),
        ];
    }
}
