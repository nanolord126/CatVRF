<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewSubmitted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here


        use Dispatchable, InteractsWithSockets, SerializesModels;


        public function __construct(public ServiceReview $review, public string $correlationId) {}
}
