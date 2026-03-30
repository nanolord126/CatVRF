<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowStockReached extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;

        public function __construct(
            public readonly int $itemId,
            public readonly int $currentStock,
            public readonly string $correlationId
        ) {}
}
