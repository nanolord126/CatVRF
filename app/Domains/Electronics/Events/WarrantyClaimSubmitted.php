<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarrantyClaimSubmitted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly int $warrantyClaimId,
            public readonly int $tenantId,
            public readonly int $userId,
            public readonly string $correlationId,
        ) {}
}
