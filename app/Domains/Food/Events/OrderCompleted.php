<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use SerializesModels;

        public function __construct(
            public RestaurantOrder $order,
            public string $correlationId = '',
        ) {}
}
