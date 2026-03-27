<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Consumable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ConsumablesDepleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ConsumablesDepleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Consumable $consumable,
        public readonly int $quantity,
        public readonly string $correlationId,
    ) {}
}
