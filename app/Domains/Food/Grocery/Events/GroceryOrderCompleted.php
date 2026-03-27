<?php

declare(strict_types=1);


namespace App\Domains\Food\Grocery\Events;

use App\Domains\Food\Grocery\Models\GroceryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * GroceryOrderCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GroceryOrderCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly GroceryOrder $order,
        public readonly string $correlationId
    ) {}
}
