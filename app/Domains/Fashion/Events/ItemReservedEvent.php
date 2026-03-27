<?php declare(strict_types=1);

namespace App\Domains\Fashion\Events;

use App\Domains\Fashion\Models\FashionProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ItemReservedEvent
 * 
 * Событие резервирования товара на 20 минут.
 * Канон 2026: correlation_id, типизация.
 */
final class ItemReservedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionProduct $product,
        public readonly int $quantity,
        public readonly string $correlationId,
        public readonly int $expiresInMinutes = 20
    ) {}
}
