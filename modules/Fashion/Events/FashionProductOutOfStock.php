<?php declare(strict_types=1);

namespace Modules\Fashion\Events;

use App\Domains\Fashion\Models\FashionProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FashionProductOutOfStock
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionProduct $product,
        public readonly int $previousStock,
        public readonly string $correlationId
    ) {}
}
