<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaymentMilestoneReleased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceContract $contract,
        public readonly float $amount,
        public readonly int $milestoneNumber,
        public readonly string $correlationId,
    ) {}
}
