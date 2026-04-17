<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\Models\BonusTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BonusAwarded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BonusTransaction $bonus,
        public readonly string $correlationId,
    ) {}
}
