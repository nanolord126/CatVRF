<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PrescriptionVerified extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        public function __construct(


            public readonly int $prescriptionId,


            public readonly int $tenantId,


            public readonly int $verifiedBy,


            public readonly string $correlationId,


        ) {}
}
