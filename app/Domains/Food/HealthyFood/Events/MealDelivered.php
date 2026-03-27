<?php

declare(strict_types=1);


namespace App\Domains\Food\HealthyFood\Events;

use App\Domains\Food\HealthyFood\Models\MealSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * MealDelivered
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MealDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MealSubscription $subscription,
        public readonly string           $correlationId,
    ) {}
}
