<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryStarted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use SerializesModels;

        public function __construct(
            public DeliveryOrder $delivery,
            public string $correlationId = '',
        ) {}
}
