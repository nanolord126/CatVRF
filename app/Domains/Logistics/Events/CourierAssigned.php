<?php declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use App\Domains\Logistics\Models\CourierService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CourierAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CourierService $courier,
        public string $correlationId,
    ) {}
}
