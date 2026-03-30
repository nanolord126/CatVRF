<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentMilestoneReleased extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly FreelanceContract $contract,
            public readonly float $amount,
            public readonly int $milestoneNumber,
            public readonly string $correlationId,
        ) {}
}
