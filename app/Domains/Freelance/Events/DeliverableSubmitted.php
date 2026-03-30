<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliverableSubmitted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly FreelanceDeliverable $deliverable,
            public readonly string $correlationId,
        ) {}
}
