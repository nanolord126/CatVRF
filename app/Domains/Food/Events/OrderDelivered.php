<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderDelivered extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly int $orderId,
            public readonly int $restaurantId,
            public readonly int $clientId,
            public readonly int $deliveryAmount,
            public readonly string $correlationId,
        ) {}
}
