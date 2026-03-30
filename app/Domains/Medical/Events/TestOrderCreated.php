<?php declare(strict_types=1);

namespace App\Domains\Medical\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TestOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public readonly MedicalTestOrder $testOrder,
            public readonly string $correlationId,
        ) {}
}
