<?php declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierAssigned extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly DeliveryOrder $order,
            public readonly Courier $courier,
            public readonly string $correlationId
        ) {}
}
