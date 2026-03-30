<?php declare(strict_types=1);

namespace App\Domains\Taxi\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeUpdated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly float $latitude,
            public readonly float $longitude,
            public readonly float $oldMultiplier,
            public readonly float $newMultiplier,
            public readonly string $correlationId,
        ) {}
}
