<?php declare(strict_types=1);

namespace App\Domains\Archived\OfficeCatering\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CorporateOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        public function __construct(


            public readonly int $corporateOrderId,


            public readonly int $tenantId,


            public readonly int $userId,


            public readonly int $totalPrice,


            public readonly string $correlationId,


        ) {}
}
