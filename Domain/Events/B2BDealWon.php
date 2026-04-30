<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Events;

use Modules\CatCRM\Domain\Entities\B2BDeal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class B2BDealWon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly B2BDeal $deal,
        public readonly string $correlationId,
    ) {}
}
