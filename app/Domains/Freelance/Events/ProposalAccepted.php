<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceProposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProposalAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceProposal $proposal,
        public readonly string $correlationId,
    ) {}
}
