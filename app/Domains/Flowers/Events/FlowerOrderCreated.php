<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public string $correlation_id;

        public function __construct(
            public readonly FlowerOrder $order,
            ?string $correlationId = null
        ) {
            $this->correlation_id = $correlationId ?? (string) Str::uuid();
        }
}
