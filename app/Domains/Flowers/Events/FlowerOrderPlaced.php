<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerOrderPlaced extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly FlowerOrder $order,
            public readonly string $correlationId,
        ) {}
}
