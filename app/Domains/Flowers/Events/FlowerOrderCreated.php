<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use App\Domains\Flowers\Models\FlowerOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: FlowerOrderCreated (Flowers).
 * Событие создания заказа цветов.
 */
final class FlowerOrderCreated
{
    use Dispatchable, SerializesModels;

    public string $correlation_id;

    public function __construct(
        public readonly FlowerOrder $order,
        ?string $correlationId = null
    ) {
        $this->correlation_id = $correlationId ?? (string) Str::uuid();
    }
}
