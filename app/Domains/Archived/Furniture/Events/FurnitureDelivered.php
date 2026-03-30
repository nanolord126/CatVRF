<?php declare(strict_types=1);

namespace App\Domains\Archived\Furniture\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureDelivered extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        public function __construct(


            public readonly int $furnitureOrderId,


            public readonly int $tenantId,


            public readonly string $correlationId,


        ) {}
}
