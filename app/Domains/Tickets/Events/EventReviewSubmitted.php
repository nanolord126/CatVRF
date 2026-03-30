<?php declare(strict_types=1);

namespace App\Domains\Tickets\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventReviewSubmitted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public EventReview $review,
            public string $correlationId = '',
        ) {}
}
