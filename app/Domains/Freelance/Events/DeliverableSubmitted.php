<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceDeliverable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DeliverableSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceDeliverable $deliverable,
        public readonly string $correlationId,
    ) {}
}
