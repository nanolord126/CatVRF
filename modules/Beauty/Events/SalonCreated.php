declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * SalonCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SalonCreated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public $salon)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('beauty');
    }
}
