<?php

declare(strict_types=1);


namespace App\Domains\Fashion\Events;

use App\Domains\Fashion\Models\FashionOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * OrderShipped
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class OrderShipped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FashionOrder $order,
        public string $correlationId,
    ) {}
}
