<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public readonly Service $service,
            public readonly string $correlationId,
        ) {}
}
