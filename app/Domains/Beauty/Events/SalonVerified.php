<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonVerified extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public readonly BeautySalon $salon,
            public readonly string $correlationId,
        ) {}
}
