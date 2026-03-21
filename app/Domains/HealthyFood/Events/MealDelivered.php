<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Events;

use App\Domains\HealthyFood\Models\MealSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MealDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MealSubscription $subscription,
        public readonly string           $correlationId,
    ) {}
}
