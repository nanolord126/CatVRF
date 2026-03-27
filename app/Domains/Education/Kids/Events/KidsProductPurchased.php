<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Events;

use App\Domains\Education\Kids\Models\KidsProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * KidsProductPurchased - Fired when a child-related item is successfully bought.
 * Triggers: Loyalty points, Voucher auto-generation.
 * Layer: Events & Listeners (7/9)
 */
final class KidsProductPurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $productId,
        public readonly int $amountKopecks,
        public readonly string $correlationId,
        public array $metadata = []
    ) {}
}
