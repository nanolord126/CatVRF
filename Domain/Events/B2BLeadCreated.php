<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Events;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class B2BLeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly B2BLead $lead,
        public readonly string $correlationId,
    ) {}
}
