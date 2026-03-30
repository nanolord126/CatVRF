<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIConstructorDesignSaved extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;

        public function __construct(
            public readonly int $userId,
            public readonly string $vertical,
            public readonly string $correlationId,
            public readonly array $designData = [],
        ) {}
}
