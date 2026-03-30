<?php declare(strict_types=1);

namespace App\Domains\Sports\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PurchaseRefunded extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public Purchase $purchase,
            public string $reason = '',
            public string $correlationId = '',
        ) {}
}
