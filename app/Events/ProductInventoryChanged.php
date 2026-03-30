<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductInventoryChanged extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;

        public function __construct(
            public readonly int $productId,
            public readonly string $vertical,
            public readonly int $oldQuantity,
            public readonly int $newQuantity,
            public readonly string $correlationId,
        ) {}
}
