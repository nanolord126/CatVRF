<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class UserTasteProfileChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly array $previousData = [],
        public readonly array $newData = [],
    ) {}
}
