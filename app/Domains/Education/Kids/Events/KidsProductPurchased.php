<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsProductPurchased extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly int $userId,
            public readonly int $productId,
            public readonly int $amountKopecks,
            public readonly string $correlationId,
            public array $metadata = []
        ) {}
}
