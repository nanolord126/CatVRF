<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PortfolioItemAdded extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public readonly PortfolioItem $item,
            public readonly string $correlationId,
        ) {}
}
