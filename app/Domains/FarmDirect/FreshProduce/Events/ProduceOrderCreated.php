<?php

declare(strict_types=1);


namespace App\Domains\FarmDirect\FreshProduce\Events;

use App\Domains\FarmDirect\FreshProduce\Models\ProduceOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ProduceOrderCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProduceOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProduceOrder $order,
        public readonly string $correlationId,
    ) {}
}
