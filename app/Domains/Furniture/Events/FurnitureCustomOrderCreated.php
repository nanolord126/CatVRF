<?php declare(strict_types=1);

namespace App\Domains\Furniture\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureCustomOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        /**
         * @param FurnitureCustomOrder $order
         * @param string|null $correlationId
         */
        public function __construct(
            public readonly FurnitureCustomOrder $order,
            public readonly ?string $correlationId = null
        ) {}
}
