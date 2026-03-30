<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProduceOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            public readonly ProduceOrder $order,
            public readonly string $correlationId,
        ) {}
}
